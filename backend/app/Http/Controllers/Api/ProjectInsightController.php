<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectPriorityService;
use App\Services\ProjectReadinessService;
use Illuminate\Http\Request;

/**
 * نقاط نهاية "Decision Support" الخاصة بنظام إدارة أولويات المشاريع:
 * قائمة أولويات المبرمج اليومية، ولوحة متابعة العملاء الخاصة بالإدارة.
 */
class ProjectInsightController extends Controller
{
    /** المراحل المستبعدة من قوائم الأولوية/المتابعة لأنها لم تعد بحاجة تدخل يومي */
    private const INACTIVE_STAGES = ['paused', 'cancelled', 'completed', 'live'];

    public function __construct(
        private readonly ProjectPriorityService $priorityService,
        private readonly ProjectReadinessService $readinessService,
    ) {}

    /**
     * قائمة عمل المبرمج اليومية: مشاريعه (الرئيسية والمساعدة) مرتبة من
     * الأعلى أولوية للأقل، مع سبب كل ترتيب وما الناقص لتنفيذ المشروع.
     */
    public function myPriorities(Request $request)
    {
        $user = $request->user();

        $projects = Project::query()
            ->with(['primaryDeveloper:id,name', 'developers:id,name'])
            ->whereNotIn('pipeline_stage', Project::CLOSED_STAGES)
            ->where(function ($q) use ($user) {
                $q->where('primary_developer_id', $user->id)
                    ->orWhereHas('developers', fn ($qq) => $qq->where('users.id', $user->id));
            })
            ->get();

        $items = $projects->map(function (Project $project) use ($user) {
            $readiness = $this->readinessService->evaluate($project);
            $priority = $this->priorityService->evaluate($project, $readiness);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client_name,
                'current_task' => $project->current_task,
                'content_deadline' => $project->content_deadline,
                'is_primary' => $project->primary_developer_id === $user->id,
                'pipeline_stage' => $project->pipeline_stage,
                'blocker' => $project->blocker,
                'priority' => $priority,
                'readiness' => $readiness,
            ];
        })->sortByDesc(fn ($p) => $p['priority']['score'])->values();

        return response()->json(['data' => $items]);
    }

    /**
     * لوحة متابعة العملاء: أي المشاريع بحاجة تواصل مع العميل اليوم ولماذا
     * (تحديث متأخر/مستحق، ملاحظات جديدة، أو المشروع متوقف بانتظار العميل).
     */
    public function clientFollowUps(Request $request)
    {
        $user = $request->user();
        if (! $user->hasPermission('view_all_projects')) {
            abort(403, 'متابعة العملاء متاحة لمن يملك صلاحية الاطلاع على كل المشاريع.');
        }

        $projects = Project::query()
            ->with(['primaryDeveloper:id,name'])
            ->whereNotIn('pipeline_stage', self::INACTIVE_STAGES)
            ->where(function ($q) {
                $q->where('next_client_update_at', '<=', now())
                    ->orWhereIn('client_feedback_status', ['new', 'in_progress'])
                    ->orWhere('blocker', 'waiting_client');
            })
            ->get();

        $items = $projects->map(function (Project $project) {
            $readiness = $this->readinessService->evaluate($project);

            $reasons = [];
            if ($project->next_client_update_at && $project->next_client_update_at->lte(now())) {
                $reasons[] = $project->next_client_update_at->isToday() ? 'موعد التحديث اليوم' : 'موعد التحديث متأخر';
            }
            if (in_array($project->client_feedback_status, ['new', 'in_progress'], true)) {
                $reasons[] = 'يوجد ملاحظات من العميل بانتظار المتابعة';
            }
            if ($project->blocker === 'waiting_client') {
                $reasons[] = 'المشروع متوقف بانتظار العميل';
            }
            if ($readiness['missing_count'] > 0) {
                $reasons[] = "ناقص {$readiness['missing_count']} من متطلبات المشروع";
            }

            return [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client_name,
                'primary_developer' => $project->primaryDeveloper?->name,
                'last_client_update_at' => $project->last_client_update_at,
                'next_client_update_at' => $project->next_client_update_at,
                'days_since_update' => $project->last_client_update_at ? (int) $project->last_client_update_at->diffInDays(now()) : null,
                'client_feedback_status' => $project->client_feedback_status,
                'blocker' => $project->blocker,
                'update_status' => $this->priorityService->updateStatus($project),
                'missing_from_client' => $readiness['missing'],
                'missing_count' => $readiness['missing_count'],
                'reasons' => $reasons,
            ];
        })->values();

        return response()->json(['data' => $items]);
    }
}
