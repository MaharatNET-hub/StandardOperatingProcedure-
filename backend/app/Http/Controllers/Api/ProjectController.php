<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChecklistItem;
use App\Models\Phase;
use App\Models\Project;
use App\Services\ProjectPriorityService;
use App\Services\ProjectReadinessService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectPriorityService $priorityService,
        private readonly ProjectReadinessService $readinessService,
    ) {}

    public function index(Request $request)
    {
        $query = Project::query()
            ->with(['creator:id,name', 'currentPhase', 'developers:id,name,role', 'primaryDeveloper:id,name', 'requirementUpdates.author:id,name'])
            ->withCount([
                'checklistItems as checklist_total',
                'checklistItems as checklist_done' => fn ($q) => $q->where('status', 'done'),
                'notes as notes_count',
            ]);

        $user = $request->user();
        if (! $user->hasPermission('view_all_projects')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('developers', fn ($qq) => $qq->where('users.id', $user->id))
                    ->orWhere('primary_developer_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('pipeline_stage')) {
            $query->where('pipeline_stage', $request->string('pipeline_stage'));
        }

        if ($request->filled('project_type')) {
            $query->where('project_type', $request->string('project_type'));
        }

        if ($request->filled('developer_id')) {
            $developerId = $request->integer('developer_id');
            $query->where(function ($q) use ($developerId) {
                $q->whereHas('developers', fn ($qq) => $qq->where('users.id', $developerId))
                    ->orWhere('primary_developer_id', $developerId);
            });
        }

        if ($request->filled('blocker') && $request->string('blocker') !== 'none') {
            $query->where('blocker', $request->string('blocker'));
        }

        if ($request->boolean('waiting_for_client')) {
            $query->where('blocker', 'waiting_client');
        }

        if ($request->boolean('has_client_feedback')) {
            $query->whereIn('client_feedback_status', ['new', 'in_progress']);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)
                ->orWhere('client_name', 'like', $search)
                ->orWhere('domain_name', 'like', $search)
                ->orWhere('phone', 'like', $search)
                ->orWhere('whatsapp', 'like', $search));
        }

        $perPage = min($request->integer('per_page', 15), 100);
        $paginated = $query->latest()->paginate($perPage)->withQueryString();

        $paginated->getCollection()->transform(function (Project $project) {
            $readiness = $this->readinessService->evaluate($project);
            $project->setAttribute('readiness', $readiness);
            $project->setAttribute('priority', $this->priorityService->evaluate($project, $readiness));

            return $project;
        });

        if ($request->boolean('missing_requirements')) {
            $paginated->setCollection(
                $paginated->getCollection()->filter(fn ($p) => $p->readiness['percent'] < 100)->values()
            );
        }

        return $paginated;
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $project = Project::create([
            ...$data,
            'created_by' => $request->user()->id,
            'status' => Project::STATUS_IN_PROGRESS,
            'pipeline_stage' => $data['pipeline_stage'] ?? 'new_project',
        ]);

        $project->developers()->sync($data['developer_ids'] ?? []);

        // Instantiate the fixed 5-phase workflow for this project.
        foreach (Phase::orderBy('order')->get() as $phase) {
            $project->projectPhases()->create([
                'phase_id' => $phase->id,
                'status' => $phase->order === 0 ? 'in_progress' : 'pending',
                'started_at' => $phase->order === 0 ? now() : null,
            ]);
        }
        $project->update(['current_phase_id' => Phase::orderBy('order')->first()?->id]);

        // Instantiate the full mandatory QA checklist for this project.
        foreach (ChecklistItem::pluck('id') as $itemId) {
            $project->checklistItems()->create(['checklist_item_id' => $itemId]);
        }

        // Prepared signoff — matches the "إعداد" row on the SOP signature table.
        $project->signoffs()->create([
            'role' => \App\Models\Signoff::ROLE_PREPARED,
            'user_id' => $request->user()->id,
            'signature_name' => $request->user()->name,
            'signed_at' => now(),
        ]);

        ActivityLog::log($project->id, $request->user()->id, 'project_created', "تم إنشاء المشروع: {$project->name}");

        return response()->json($project->load(['projectPhases.phase', 'developers', 'primaryDeveloper:id,name']), 201);
    }

    public function show(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($request, $project);

        $project->load([
            'creator:id,name',
            'developers:id,name,email,role',
            'primaryDeveloper:id,name,email',
            'currentPhase',
            'projectPhases.phase',
            'licenses.renewalResponsible:id,name',
            'performanceReports.measurer:id,name',
            'qaReviews.reviewer:id,name',
            'signoffs.user:id,name',
            'requirementUpdates.author:id,name',
        ]);

        $project->loadCount('notes');
        $project->setAttribute('paused_days', $project->pausedDays());

        $readiness = $this->readinessService->evaluate($project);
        $project->setAttribute('readiness', $readiness);
        $project->setAttribute('priority', $this->priorityService->evaluate($project, $readiness));

        return $project;
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($request, $project);

        $data = $request->validate($this->rules(forUpdate: true));

        $developerIds = $data['developer_ids'] ?? null;
        unset($data['developer_ids']);

        $this->logFieldTransitions($project, $data, $request->user()->id);

        $data = $this->applyPausedSince($project, $data);

        $project->update($data);

        if ($developerIds !== null) {
            $project->developers()->sync($developerIds);
        }

        ActivityLog::log($project->id, $request->user()->id, 'project_updated');

        return $project->fresh(['developers', 'primaryDeveloper:id,name', 'currentPhase']);
    }

    /**
     * زر "إرسال تحديث للعميل" — يسجّل تاريخ التحديث الحالي ويحسب موعد
     * التحديث القادم تلقائياً (Last Update + عدد الأيام المحدد للمشروع).
     */
    public function sendClientUpdate(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($request, $project);

        $data = $request->validate([
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $intervalDays = $data['interval_days'] ?? $project->client_update_interval_days ?? 3;
        $now = now();

        $project->update([
            'last_client_update_at' => $now,
            'next_client_update_at' => $now->copy()->addDays($intervalDays),
            'client_update_interval_days' => $intervalDays,
        ]);

        ActivityLog::log($project->id, $request->user()->id, 'client_update_sent', "تم إرسال تحديث للعميل — التحديث القادم بعد {$intervalDays} يوم/أيام");

        return $project->fresh();
    }

    public function destroy(Request $request, Project $project)
    {
        if (! $request->user()->hasPermission('manage_projects')) {
            abort(403, 'حذف المشاريع متاح لمن يملك صلاحية إدارة المشاريع فقط.');
        }

        $name = $project->name;
        $project->delete();

        ActivityLog::log(null, $request->user()->id, 'project_deleted', "تم حذف المشروع: {$name}");

        return response()->json(['message' => 'تم حذف المشروع.']);
    }

    private function authorizeProjectAccess(Request $request, Project $project): void
    {
        $user = $request->user();
        if (! $user->hasPermission('view_all_projects')
            && ! $project->developers()->where('users.id', $user->id)->exists()
            && $project->primary_developer_id !== $user->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا المشروع.');
        }
    }

    /**
     * Date - Paused Since (SRS §8): يضبط تاريخ الإيقاف تلقائياً عند دخول
     * المشروع مرحلة "متوقف"، ويمسحه عند استئنافه — حتى تُحسب مدة التوقف
     * في لوحة "Paused — Monitor" دون إدخال يدوي.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyPausedSince(Project $project, array $data): array
    {
        if (! array_key_exists('pipeline_stage', $data) || array_key_exists('paused_since', $data)) {
            return $data;
        }

        $newStage = $data['pipeline_stage'];

        if ($newStage === 'paused' && $project->pipeline_stage !== 'paused') {
            $data['paused_since'] = now()->toDateString();
        } elseif ($newStage !== 'paused' && $project->pipeline_stage === 'paused') {
            $data['paused_since'] = null;
        }

        return $data;
    }

    private function logFieldTransitions(Project $project, array $data, int $userId): void
    {
        if (array_key_exists('pipeline_stage', $data) && $data['pipeline_stage'] !== $project->pipeline_stage) {
            ActivityLog::log($project->id, $userId, 'pipeline_stage_changed', "تغيير مرحلة المشروع من {$project->pipeline_stage} إلى {$data['pipeline_stage']}");
        }

        if (array_key_exists('client_feedback_status', $data)
            && $data['client_feedback_status'] === 'new'
            && $project->client_feedback_status !== 'new') {
            ActivityLog::log($project->id, $userId, 'client_feedback_recorded', 'وردت ملاحظات جديدة من العميل');
        }

        if (array_key_exists('blocker', $data) && $data['blocker'] !== $project->blocker && $data['blocker'] !== 'none') {
            ActivityLog::log($project->id, $userId, 'project_blocked', "المشروع متوقف: {$data['blocker']}");
        }
    }

    private function rules(bool $forUpdate = false): array
    {
        $sometimes = $forUpdate ? 'sometimes' : 'nullable';
        $required = $forUpdate ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'client_name' => [$required, 'string', 'max:255'],
            'project_type' => ['nullable', 'in:'.implode(',', Project::PROJECT_TYPES)],
            'project_description' => ['nullable', 'string'],
            'current_task' => ['nullable', 'string', 'max:255'],
            'envato_preview_url' => ['nullable', 'url'],
            'site_url' => ['nullable', 'url'],
            'pipeline_stage' => ['nullable', 'in:'.implode(',', Project::PIPELINE_STAGES)],
            'content_deadline' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'next_meeting_at' => ['nullable', 'date'],
            'paused_since' => ['nullable', 'date'],
            'revision_rounds_allowed' => [$sometimes, 'integer', 'min:0'],
            'primary_developer_id' => ['nullable', 'integer', 'exists:users,id'],
            'developer_ids' => ['array'],
            'developer_ids.*' => ['integer', 'exists:users,id'],

            'has_domain' => ['nullable', 'boolean'],
            'domain_name' => ['nullable', 'string', 'max:255'],
            'domain_login_info' => ['nullable', 'string'],
            'domain_purchaser' => ['nullable', 'in:client,maharat'],
            'has_hosting' => ['nullable', 'boolean'],
            'hosting_provider' => ['nullable', 'string', 'max:255'],
            'hosting_login_info' => ['nullable', 'string'],
            'hosting_purchaser' => ['nullable', 'in:client,maharat'],

            'has_logo' => ['nullable', 'boolean'],
            'needs_logo_design' => ['nullable', 'boolean'],
            'design_style' => ['nullable', 'string', 'max:255'],
            'website_languages_count' => ['nullable', 'integer', 'min:1', 'max:10'],
            'primary_language' => ['nullable', 'string', 'max:100'],
            'secondary_language' => ['nullable', 'string', 'max:100'],

            'needs_payment_gateway' => ['nullable', 'boolean'],
            'payment_gateway_type' => ['nullable', 'string', 'max:255'],
            'payment_gateway_status' => ['nullable', 'in:not_started,in_progress,done,na'],
            'has_shipping_company' => ['nullable', 'boolean'],
            'shipping_company_name' => ['nullable', 'string', 'max:255'],

            'content_ready' => ['nullable', 'boolean'],
            'product_images_ready' => ['nullable', 'boolean'],
            'seo_required' => ['nullable', 'boolean'],
            'seo_status' => ['nullable', 'in:not_started,in_progress,done,na'],
            'google_analytics_connected' => ['nullable', 'boolean'],
            'search_console_connected' => ['nullable', 'boolean'],

            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'social_media_available' => ['nullable', 'boolean'],
            'social_media_login_info' => ['nullable', 'string'],

            'last_client_update_at' => ['nullable', 'date'],
            'next_client_update_at' => ['nullable', 'date'],
            'client_update_interval_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'client_feedback_status' => ['nullable', 'in:'.implode(',', Project::FEEDBACK_STATUSES)],
            'client_feedback_notes' => ['nullable', 'string'],
            'client_feedback_at' => ['nullable', 'date'],

            'client_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'blocker' => ['nullable', 'in:'.implode(',', Project::BLOCKERS)],
            'website_uploaded' => ['nullable', 'boolean'],
        ];
    }
}
