<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChecklistItem;
use App\Models\Phase;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()
            ->with(['creator:id,name', 'currentPhase', 'developers:id,name,role'])
            ->withCount([
                'checklistItems as checklist_total',
                'checklistItems as checklist_done' => fn ($q) => $q->where('status', 'done'),
            ]);

        $user = $request->user();
        if ($user->role === \App\Models\User::ROLE_DEVELOPER) {
            $query->whereHas('developers', fn ($q) => $q->where('users.id', $user->id));
        }

        return $query->latest()->paginate(15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'envato_preview_url' => ['nullable', 'url'],
            'content_deadline' => ['nullable', 'date'],
            'revision_rounds_allowed' => ['nullable', 'integer', 'min:0'],
            'developer_ids' => ['array'],
            'developer_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $project = Project::create([
            ...$data,
            'created_by' => $request->user()->id,
            'status' => Project::STATUS_IN_PROGRESS,
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

        return response()->json($project->load(['projectPhases.phase', 'developers']), 201);
    }

    public function show(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($request, $project);

        return $project->load([
            'creator:id,name',
            'developers:id,name,email,role',
            'currentPhase',
            'projectPhases.phase',
            'licenses.renewalResponsible:id,name',
            'performanceReports.measurer:id,name',
            'qaReviews.reviewer:id,name',
            'signoffs.user:id,name',
        ])->append([]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($request, $project);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'client_name' => ['sometimes', 'string', 'max:255'],
            'envato_preview_url' => ['nullable', 'url'],
            'status' => ['sometimes', 'string'],
            'content_deadline' => ['nullable', 'date'],
            'revision_rounds_allowed' => ['sometimes', 'integer', 'min:0'],
            'developer_ids' => ['array'],
            'developer_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $developerIds = $data['developer_ids'] ?? null;
        unset($data['developer_ids']);

        $project->update($data);

        if ($developerIds !== null) {
            $project->developers()->sync($developerIds);
        }

        ActivityLog::log($project->id, $request->user()->id, 'project_updated');

        return $project->fresh(['developers', 'currentPhase']);
    }

    private function authorizeProjectAccess(Request $request, Project $project): void
    {
        $user = $request->user();
        if ($user->role === \App\Models\User::ROLE_DEVELOPER
            && ! $project->developers()->where('users.id', $user->id)->exists()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا المشروع.');
        }
    }
}
