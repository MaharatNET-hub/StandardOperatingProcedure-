<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Setting;
use Carbon\Carbon;

/**
 * يحسب "درجة الأولوية اليومية" لمشروع بناءً على قرب موعد التسليم، حالة
 * التحديث مع العميل، ملاحظات العميل، قرب الاجتماع، والمتطلبات الناقصة —
 * بنفس منطق النقاط الموصوف في وثيقة "Project Priority & Management System".
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

    /** المراحل التي لا ترفع الأولوية إطلاقاً (متوقفة عن العميل أو مغلقة) */
    private const NON_PRIORITIZED_STAGES = ['paused', 'cancelled', 'completed', 'live'];

    private const BLOCKING_BLOCKERS = [
        'waiting_client', 'waiting_domain', 'waiting_hosting', 'waiting_content',
        'waiting_logo', 'waiting_product_images', 'waiting_payment_gateway', 'other',
    ];

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
     * @return array{score: int, level: string, blocked: bool, reasons: array<int, array{label: string, points: int}>}
     */
    public function evaluate(Project $project, ?array $readiness = null): array
    {
        $weights = self::weights();
        $reasons = [];
        $now = Carbon::now();

        if (in_array($project->pipeline_stage, self::NON_PRIORITIZED_STAGES, true)) {
            return ['score' => 0, 'level' => 'low', 'blocked' => false, 'reasons' => []];
        }

        if ($project->content_deadline) {
            $days = $now->startOfDay()->diffInDays(Carbon::parse($project->content_deadline)->startOfDay(), false);
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

        if ($project->next_client_update_at) {
            $updateDate = Carbon::parse($project->next_client_update_at);
            if ($updateDate->isPast() && ! $updateDate->isToday()) {
                $reasons[] = ['label' => 'موعد التحديث مع العميل متأخر', 'points' => $weights['update_overdue']];
            } elseif ($updateDate->isToday()) {
                $reasons[] = ['label' => 'موعد التحديث مع العميل اليوم', 'points' => $weights['update_due_today']];
            }
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
            $reasons[] = ['label' => 'توجد بيانات أساسية ناقصة من العميل', 'points' => $weights['missing_basic_data']];
        }

        $score = array_sum(array_column($reasons, 'points'));
        $blocked = in_array($project->blocker, self::BLOCKING_BLOCKERS, true);

        return [
            'score' => $score,
            'level' => $this->levelFor($score),
            'blocked' => $blocked,
            'reasons' => $reasons,
        ];
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
