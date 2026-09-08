<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Services\ProjectReadinessService;
use Illuminate\Http\Request;

/**
 * تعديل حالة متطلبات المشروع مباشرة من تبويب الجاهزية: تعليم المتطلّب
 * كجاهز (أو ناقص/قيد التنفيذ) مع تاريخ سريان الحالة وملاحظة اختيارية.
 *
 * التاريخ منفصل عن وقت الإدخال عمداً — قد يُسجَّل اليوم أن الشعار وصل
 * الأسبوع الماضي، فتبقى مدة التعطّل الحقيقية موثّقة.
 */
class ProjectRequirementController extends Controller
{
    /**
     * الحقول التي تُحدَّث لكل متطلّب حسب الحالة المطلوبة.
     * المتطلبات ثنائية الحالة تتجاهل "قيد التنفيذ" وتُعامله كغير جاهز.
     */
    private const FIELD_MAP = [
        'logo' => ['field' => 'has_logo', 'type' => 'boolean'],
        'domain' => ['field' => 'has_domain', 'type' => 'boolean'],
        'hosting' => ['field' => 'has_hosting', 'type' => 'boolean'],
        'content' => ['field' => 'content_ready', 'type' => 'boolean'],
        'product_images' => ['field' => 'product_images_ready', 'type' => 'boolean'],
        'shipping_company' => ['field' => 'has_shipping_company', 'type' => 'boolean'],
        'google_analytics' => ['field' => 'google_analytics_connected', 'type' => 'boolean'],
        'search_console' => ['field' => 'search_console_connected', 'type' => 'boolean'],
        'social_media' => ['field' => 'social_media_available', 'type' => 'boolean'],
        'payment_gateway' => ['field' => 'payment_gateway_status', 'type' => 'status'],
        'seo' => ['field' => 'seo_status', 'type' => 'status'],
    ];

    /** العائق المقابل لكل متطلّب — يُرفع تلقائياً عند اكتمال المتطلّب. */
    private const BLOCKER_MAP = [
        'logo' => 'waiting_logo',
        'domain' => 'waiting_domain',
        'hosting' => 'waiting_hosting',
        'content' => 'waiting_content',
        'product_images' => 'waiting_product_images',
        'payment_gateway' => 'waiting_payment_gateway',
        'shipping_company' => 'waiting_shipping',
    ];

    public function __construct(private readonly ProjectReadinessService $readinessService) {}

    /** سجل تغيّر حالة كل المتطلبات لهذا المشروع (الأحدث أولاً). */
    public function index(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($request, $project);

        return response()->json([
            'data' => $project->requirementUpdates()
                ->with('author:id,name')
                ->orderByDesc('effective_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function update(Request $request, Project $project, string $requirement)
    {
        $this->authorizeProjectAccess($request, $project);

        if (! array_key_exists($requirement, self::FIELD_MAP)) {
            abort(404, 'متطلّب غير معروف.');
        }

        $data = $request->validate([
            'status' => ['required', 'in:ready,in_progress,missing'],
            'effective_date' => ['nullable', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $effectiveDate = $data['effective_date'] ?? now()->toDateString();

        $project->update($this->fieldsFor($requirement, $data['status']));

        // إن كان المشروع متوقفاً بانتظار هذا المتطلّب تحديداً، يُرفع التوقف.
        if ($data['status'] === 'ready'
            && isset(self::BLOCKER_MAP[$requirement])
            && $project->blocker === self::BLOCKER_MAP[$requirement]) {
            $project->update(['blocker' => 'none']);
        }

        $update = $project->requirementUpdates()->create([
            'requirement_key' => $requirement,
            'status' => $data['status'],
            'effective_date' => $effectiveDate,
            'note' => $data['note'] ?? null,
            'changed_by' => $request->user()->id,
        ]);

        $label = ProjectReadinessService::labelFor($requirement);
        $statusLabel = match ($data['status']) {
            'ready' => 'جاهز',
            'in_progress' => 'قيد التنفيذ',
            default => 'ناقص',
        };
        ActivityLog::log(
            $project->id,
            $request->user()->id,
            'requirement_updated',
            "تغيير حالة \"{$label}\" إلى {$statusLabel} بتاريخ {$effectiveDate}"
        );

        $project->refresh();

        return response()->json([
            'update' => $update->load('author:id,name'),
            'readiness' => $this->readinessService->evaluate($project),
            'blocker' => $project->blocker,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFor(string $requirement, string $status): array
    {
        $map = self::FIELD_MAP[$requirement];

        if ($map['type'] === 'boolean') {
            return [$map['field'] => $status === 'ready'];
        }

        return [$map['field'] => match ($status) {
            'ready' => 'done',
            'in_progress' => 'in_progress',
            default => 'not_started',
        }];
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
}
