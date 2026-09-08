<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Setting;
use Carbon\Carbon;

/**
 * يحسب "درجة الأولوية اليومية" لمشروع بناءً على قرب موعد التسليم، حالة
 * التحديث مع العميل، ملاحظات العميل، قرب الاجتماع، والمتطلبات الناقصة —
 * بنفس منطق النقاط الموصوف في وثيقة SRS (القسم 13–15).
 * الأوزان قابلة للتعديل من صفحة الإعدادات (Setting::KEY_PRIORITY_WEIGHTS).
 */
class ProjectPriorityService
{
    /** @var array<string, int> */
    public const DEFAULT_WEIGHTS = [
        'delivery_overdue' => 50,
        'delivery_1_day' => 40,
        'delivery_3_days' => 30,
        'delivery_1_week' => 20,
        'update_overdue' => 35,
        'update_due_today' => 25,
        'feedback_new' => 30,
        'feedback_in_progress' => 25,
        'meeting_today' => 25,
        'meeting_tomorrow' => 15,
        'missing_basic_data' => 10,
    ];

    /** حالات Calc - Update Status (SRS §9) */
    public const UPDATE_STATUSES = ['no_schedule', 'ok', 'due_tomorrow', 'due_today', 'overdue'];

    public static function weights(): array
    {
        $stored = Setting::get(Setting::KEY_PRIORITY_WEIGHTS);
        if (! $stored) {
            return self::DEFAULT_WEIGHTS;
        }

        $decoded = json_decode($stored, true);

        return is_array($decoded) ? array_merge(self::DEFAULT_WEIGHTS, $decoded) : self::DEFAULT_WEIGHTS;
    }

    /**
     * Calc - Update Status (SRS §9): حالة التحديث القادم مع العميل.
     */
    public function updateStatus(Project $project): string
    {
        if (! $project->next_client_update_at) {
            return 'no_schedule';
        }

        $due = Carbon::parse($project->next_client_update_at)->startOfDay();
        $today = Carbon::now()->startOfDay();

        return match (true) {
            $due->lt($today) => 'overdue',
            $due->isSameDay($today) => 'due_today',
            $due->isSameDay($today->copy()->addDay()) => 'due_tomorrow',
            default => 'ok',
        };
    }

    /**
     * BR-02 / BR-15: المشروع "قابل للتنفيذ" فقط إذا لم يكن مغلقاً ولم يكن
     * متوقفاً بانتظار العميل أو جهة خارجية (دومين، استضافة، محتوى...).
     */
    public function isWorkable(Project $project): bool
    {
        if (in_array($project->pipeline_stage, Project::CLOSED_STAGES, true)) {
            return false;
        }

        if (in_array($project->blocker, Project::NON_WORKABLE_BLOCKERS, true)) {
            return false;
        }

        return ! in_array($project->pipeline_stage, ['waiting_client', 'waiting_payment', 'waiting_content'], true);
    }

    /**
     * @return array{score: int, level: string, blocked: bool, workable: bool, update_status: string, reasons: array<int, array{label: string, points: int}>, summary: string}
     */
    public function evaluate(Project $project, ?array $readiness = null): array
    {
        $weights = self::weights();
        $reasons = [];
        $now = Carbon::now();
        $updateStatus = $this->updateStatus($project);
        $workable = $this->isWorkable($project);
        $blocked = in_array($project->blocker, Project::NON_WORKABLE_BLOCKERS, true)
            || $project->blocker === 'other';

        // BR-01: المشاريع المكتملة أو الملغاة أو المتوقفة تحصل على درجة صفر.
        if (in_array($project->pipeline_stage, Project::CLOSED_STAGES, true)) {
            return [
                'score' => 0,
                'level' => 'low',
                'blocked' => $blocked,
                'workable' => false,
                'update_status' => $updateStatus,
                'reasons' => [],
                'summary' => '',
            ];
        }

        if ($project->content_deadline) {
            $days = $now->copy()->startOfDay()->diffInDays(Carbon::parse($project->content_deadline)->startOfDay(), false);
            if ($days < 0) {
                $reasons[] = ['label' => 'المشروع متأخر عن موعد التسليم', 'points' => $weights['delivery_overdue']];
            } elseif ($days <= 1) {
                $reasons[] = ['label' => 'موعد التسليم خلال يوم', 'points' => $weights['delivery_1_day']];
            } elseif ($days <= 3) {
                $reasons[] = ['label' => 'موعد التسليم خلال 3 أيام', 'points' => $weights['delivery_3_days']];
            } elseif ($days <= 7) {
                $reasons[] = ['label' => 'موعد التسليم خلال أسبوع', 'points' => $weights['delivery_1_week']];
            }
        }

        if ($updateStatus === 'overdue') {
            $reasons[] = ['label' => 'موعد التحديث مع العميل متأخر', 'points' => $weights['update_overdue']];
        } elseif ($updateStatus === 'due_today') {
            $reasons[] = ['label' => 'موعد التحديث مع العميل اليوم', 'points' => $weights['update_due_today']];
        }

        if ($project->client_feedback_status === 'new') {
            $reasons[] = ['label' => 'يوجد ملاحظات جديدة من العميل', 'points' => $weights['feedback_new']];
        } elseif ($project->client_feedback_status === 'in_progress') {
            $reasons[] = ['label' => 'يوجد تعديلات قيد التنفيذ من ملاحظات العميل', 'points' => $weights['feedback_in_progress']];
        }

        if ($project->next_meeting_at) {
            $meetingDate = Carbon::parse($project->next_meeting_at);
            if ($meetingDate->isToday()) {
                $reasons[] = ['label' => 'اجتماع العميل اليوم', 'points' => $weights['meeting_today']];
            } elseif ($meetingDate->isTomorrow()) {
                $reasons[] = ['label' => 'اجتماع العميل غداً', 'points' => $weights['meeting_tomorrow']];
            }
        }

        if ($readiness && $readiness['percent'] < 100 && ! empty($readiness['missing'])) {
            $count = $readiness['missing_count'] ?? count($readiness['missing']);
            $reasons[] = ['label' => "توجد {$count} متطلبات أساسية ناقصة", 'points' => $weights['missing_basic_data']];
        }

        $score = array_sum(array_column($reasons, 'points'));

        return [
            'score' => $score,
            'level' => $this->levelFor($score),
            'blocked' => $blocked,
            'workable' => $workable,
            'update_status' => $updateStatus,
            'reasons' => $reasons,
            'summary' => $this->summarize($reasons),
        ];
    }

    /**
     * Calc - Priority Reasons (SRS §15): سطر واحد يشرح مصدر كل نقطة، مثل:
     * "‎+40 موعد التسليم خلال يوم • ‎+25 موعد التحديث مع العميل اليوم".
     *
     * @param  array<int, array{label: string, points: int}>  $reasons
     */
    private function summarize(array $reasons): string
    {
        return implode(' • ', array_map(fn ($r) => "+{$r['points']} {$r['label']}", $reasons));
    }

    private function levelFor(int $score): string
    {
        return match (true) {
            $score >= 80 => 'critical',
            $score >= 50 => 'high',
            $score >= 25 => 'medium',
            default => 'low',
        };
    }
}
