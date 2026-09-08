<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectPriorityService;
use App\Services\ProjectReadinessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * لوحات النظام (Views) الواردة في وثيقة SRS القسم 18:
 * PM Dashboard، Today — By Developer (Workable)، Updates Due — All (incl. Paused)،
 * Paused — Monitor، Critical Watchlist، و Pipeline.
 *
 * كل لوحة تُبنى من نفس بيانات المشروع بعد تمريرها على خدمتي الأولوية
 * والجاهزية، ولا تُخزَّن نتائجها — تُحسب لحظياً عند الفتح.
 */
class ProjectViewController extends Controller
{
    public function __construct(
        private readonly ProjectPriorityService $priorityService,
        private readonly ProjectReadinessService $readinessService,
    ) {}

    /**
     * 18.1 PM Dashboard — كل المشاريع مجمّعة حسب المبرمج المسؤول
     * (Dev - Owner) ومرتّبة داخل كل مجموعة حسب Priority Score.
     */
    public function pmDashboard(Request $request)
    {
        $rows = $this->rows($request, fn (Builder $q) => $q->whereNotIn('pipeline_stage', ['cancelled', 'completed']));

        return response()->json([
            'data' => $this->groupByOwner($rows),
            'totals' => [
                'projects' => $rows->count(),
                'critical' => $rows->where('priority.level', 'critical')->count(),
                'workable' => $rows->where('priority.workable', true)->count(),
                'blocked' => $rows->where('priority.workable', false)->count(),
            ],
        ]);
    }

    /**
     * 18.2 Today — By Developer (Workable) — أهم لوحة يومية: الأعمال القابلة
     * للتنفيذ فعلياً لكل مبرمج مرتّبة حسب الأولوية (BR-15). المشاريع
     * المتوقفة بانتظار العميل أو جهة خارجية لا تظهر هنا (BR-02).
     */
    public function todayByDeveloper(Request $request)
    {
        $rows = $this->rows($request, fn (Builder $q) => $q->whereNotIn('pipeline_stage', Project::CLOSED_STAGES))
            ->filter(fn ($row) => $row['priority']['workable'])
            ->values();

        return response()->json([
            'data' => $this->groupByOwner($rows),
            'top_per_developer' => 3,
        ]);
    }

    /**
     * 18.4 Updates Due — All (incl. Paused) — كل المشاريع التي حان أو تأخر
     * موعد تحديث العميل فيها، بما فيها المتوقفة (BR-04) حتى لا يُنسى التواصل.
     */
    public function updatesDue(Request $request)
    {
        $rows = $this->rows($request, fn (Builder $q) => $q
            ->whereNotIn('pipeline_stage', ['cancelled', 'completed'])
            ->whereNotNull('next_client_update_at'))
            ->filter(fn ($row) => in_array($row['priority']['update_status'], ['due_today', 'overdue'], true))
            ->sortBy(fn ($row) => $row['next_client_update_at'])
            ->values();

        return response()->json(['data' => $rows]);
    }

    /**
     * 18.5 Paused — Monitor — المشاريع المتوقفة: سبب الإيقاف، تاريخه،
     * ومدة بقاء المشروع متوقفاً.
     */
    public function pausedMonitor(Request $request)
    {
        $rows = $this->rows($request, fn (Builder $q) => $q->where('pipeline_stage', 'paused'))
            ->sortByDesc(fn ($row) => $row['paused_days'] ?? 0)
            ->values();

        return response()->json(['data' => $rows]);
    }

    /**
     * 18.6 Critical Watchlist — المشاريع التي بلغت مستوى الأولوية Critical.
     */
    public function criticalWatchlist(Request $request)
    {
        $rows = $this->rows($request, fn (Builder $q) => $q->whereNotIn('pipeline_stage', Project::CLOSED_STAGES))
            ->filter(fn ($row) => $row['priority']['level'] === 'critical')
            ->sortByDesc(fn ($row) => $row['priority']['score'])
            ->values();

        return response()->json(['data' => $rows]);
    }

    /**
     * 18.7 Pipeline — لوحة Board تعرض المشاريع حسب مرحلة سير العمل
     * (Proj - Status) من الإنشاء وحتى الإنجاز.
     */
    public function pipeline(Request $request)
    {
        $rows = $this->rows($request);

        $columns = [];
        foreach (Project::PIPELINE_STAGES as $stage) {
            $columns[] = [
                'stage' => $stage,
                'projects' => $rows->where('pipeline_stage', $stage)
                    ->sortByDesc(fn ($row) => $row['priority']['score'])
                    ->values(),
            ];
        }

        return response()->json(['data' => $columns]);
    }

    /**
     * يبني صفوف اللوحات: يطبّق نطاق صلاحية المستخدم، ثم يمرّر كل مشروع على
     * خدمتي الجاهزية والأولوية.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(Request $request, ?callable $scope = null): Collection
    {
        $user = $request->user();

        $query = Project::query()->with(['primaryDeveloper:id,name,specialization', 'developers:id,name']);

        if (! $user->hasPermission('view_all_projects')) {
            $query->where(function ($q) use ($user) {
                $q->where('primary_developer_id', $user->id)
                    ->orWhereHas('developers', fn ($qq) => $qq->where('users.id', $user->id));
            });
        }

        if ($scope) {
            $scope($query);
        }

        // فلترة اللوحات حسب تخصّص المبرمج المسؤول (ووردبريس / برمجة خاصة / فلاتر)
        if ($request->filled('specialization')) {
            $specialization = $request->string('specialization')->toString();
            $query->whereHas('primaryDeveloper', fn ($q) => $q->where('specialization', $specialization));
        }

        if ($request->filled('developer_id')) {
            $developerId = $request->integer('developer_id');
            $query->where(function ($q) use ($developerId) {
                $q->where('primary_developer_id', $developerId)
                    ->orWhereHas('developers', fn ($qq) => $qq->where('users.id', $developerId));
            });
        }

        return $query->get()->map(function (Project $project) {
            $readiness = $this->readinessService->evaluate($project);
            $priority = $this->priorityService->evaluate($project, $readiness);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client_name,
                'current_task' => $project->current_task,
                'pipeline_stage' => $project->pipeline_stage,
                'blocker' => $project->blocker,
                'project_type' => $project->project_type,
                'content_deadline' => $project->content_deadline?->toDateString(),
                'next_meeting_at' => $project->next_meeting_at?->toDateTimeString(),
                'paused_since' => $project->paused_since?->toDateString(),
                'paused_days' => $project->pausedDays(),
                'last_client_update_at' => $project->last_client_update_at?->toDateTimeString(),
                'next_client_update_at' => $project->next_client_update_at?->toDateTimeString(),
                'client_update_interval_days' => $project->client_update_interval_days,
                'client_feedback_status' => $project->client_feedback_status,
                'owner' => [
                    'id' => $project->primary_developer_id,
                    'name' => $project->primaryDeveloper?->name,
                    'specialization' => $project->primaryDeveloper?->specialization,
                ],
                'assistants' => $project->developers
                    ->where('id', '!=', $project->primary_developer_id)
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
                    ->values(),
                'priority' => $priority,
                'readiness' => $readiness,
            ];
        });
    }

    /**
     * تجميع الصفوف حسب المبرمج المسؤول مع ترتيب كل مجموعة حسب درجة الأولوية.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function groupByOwner(Collection $rows): array
    {
        $groups = $rows->groupBy(fn ($row) => $row['owner']['id'] ?? 0)
            ->map(fn (Collection $group) => [
                'developer' => [
                    'id' => $group->first()['owner']['id'],
                    'name' => $group->first()['owner']['name'] ?? 'غير مُسند',
                    'specialization' => $group->first()['owner']['specialization'],
                ],
                'projects' => $group->sortByDesc(fn ($row) => $row['priority']['score'])->values(),
                'total_score' => $group->sum(fn ($row) => $row['priority']['score']),
            ])
            ->values()
            ->sortByDesc('total_score')
            ->values();

        return $groups->all();
    }
}
