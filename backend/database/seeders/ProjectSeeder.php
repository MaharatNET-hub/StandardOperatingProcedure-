<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Signoff;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * مشروع "Zevora" النموذجي — مثال جاهز يوضّح مزايا النظام دون إدخال يدوي:
 * درجة أولوية محسوبة بأسبابها، متطلبات ناقصة، موعد تحديث مستحق للعميل،
 * وسجل ملاحظات فيه أنواع مختلفة.
 *
 * السيدر متوافق مع إعادة التشغيل: يطابق المشروع باسمه فلا يتكرر، ولا
 * يُنشئ المراحل أو بنود قائمة التحقق أو الملاحظات إن كانت موجودة أصلاً.
 */
class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $owner = $this->pickDeveloper('wordpress');
        $assistant = $this->pickDeveloper('custom_dev', excludeId: $owner?->id);
        $manager = User::where('role', User::ROLE_ADMIN)->first();

        $project = Project::updateOrCreate(
            ['name' => 'Zevora'],
            [
                'client_name' => 'Zevora Store',
                'project_type' => 'ecommerce',
                'project_description' => 'متجر إلكتروني لبيع منتجات العناية بالبشرة، بواجهة عربية/إنجليزية وبوابة دفع وشحن محلي.',
                'current_task' => 'بناء صفحة المنتج وربط سلة الشراء',
                'site_url' => 'https://zevora.example.com',
                'status' => Project::STATUS_IN_PROGRESS,
                'pipeline_stage' => 'in_progress',
                'created_by' => $manager?->id,
                'primary_developer_id' => $owner?->id,

                'start_date' => now()->subDays(18)->toDateString(),
                'content_deadline' => now()->addDays(6)->toDateString(),
                'next_meeting_at' => now()->addDay()->setTime(11, 0),

                // متابعة العميل: التحديث القادم مستحق اليوم
                'last_client_update_at' => now()->subDays(3),
                'next_client_update_at' => now(),
                'client_update_interval_days' => 3,
                'client_feedback_status' => 'new',
                'client_feedback_at' => now()->subDay(),
                'client_feedback_notes' => 'العميل يطلب تكبير صور المنتجات وإضافة قسم "الأكثر مبيعاً" في الصفحة الرئيسية.',

                // المتطلبات: الدومين والاستضافة جاهزان، والباقي ناقص
                'has_domain' => true,
                'domain_name' => 'zevora.com',
                'domain_purchaser' => 'client',
                'has_hosting' => true,
                'hosting_provider' => 'Hostinger',
                'hosting_purchaser' => 'maharat',
                'has_logo' => false,
                'needs_logo_design' => true,
                'design_style' => 'عصري وبسيط بألوان فاتحة',
                'website_languages_count' => 2,
                'primary_language' => 'العربية',
                'secondary_language' => 'English',

                'needs_payment_gateway' => true,
                'payment_gateway_type' => 'HyperPay',
                'payment_gateway_status' => 'in_progress',
                'has_shipping_company' => false,
                'content_ready' => false,
                'product_images_ready' => false,
                'seo_required' => true,
                'seo_status' => 'not_started',
                'google_analytics_connected' => false,
                'search_console_connected' => false,

                'phone' => '0500000000',
                'whatsapp' => '0500000000',
                'social_media_available' => true,

                'blocker' => 'none',
                'internal_notes' => 'مشروع نموذجي للتجربة — يمكن حذفه من صفحة المشاريع.',
            ]
        );

        $project->developers()->syncWithoutDetaching(array_filter([$owner?->id, $assistant?->id]));

        $this->attachPhases($project);
        $this->attachChecklist($project);
        $this->attachSignoff($project, $manager);
        $this->attachNotes($project, $manager);
    }

    /** يختار مبرمجاً بالتخصّص المطلوب، وإلا أي مبرمج متاح. */
    private function pickDeveloper(string $specialization, ?int $excludeId = null): ?User
    {
        $query = User::where('role', User::ROLE_DEVELOPER)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));

        return (clone $query)->where('specialization', $specialization)->first()
            ?? $query->first();
    }

    /** المراحل الخمس — أول مرحلة قيد التنفيذ، كما يفعل إنشاء المشروع من الواجهة. */
    private function attachPhases(Project $project): void
    {
        if ($project->projectPhases()->exists()) {
            return;
        }

        foreach (Phase::orderBy('order')->get() as $phase) {
            $project->projectPhases()->create([
                'phase_id' => $phase->id,
                'status' => $phase->order === 0 ? 'in_progress' : 'pending',
                'started_at' => $phase->order === 0 ? now()->subDays(18) : null,
            ]);
        }

        $project->update(['current_phase_id' => Phase::orderBy('order')->first()?->id]);
    }

    /** قائمة التحقق كاملة مع تعليم أول ربع البنود كمنجزة ليظهر تقدّم واقعي. */
    private function attachChecklist(Project $project): void
    {
        if ($project->checklistItems()->exists()) {
            return;
        }

        $itemIds = ChecklistItem::orderBy('id')->pluck('id');
        $doneUntil = (int) floor($itemIds->count() / 4);

        foreach ($itemIds->values() as $index => $itemId) {
            $done = $index < $doneUntil;
            $project->checklistItems()->create([
                'checklist_item_id' => $itemId,
                'status' => $done ? 'done' : 'pending',
                'checked_at' => $done ? now()->subDays(5) : null,
            ]);
        }
    }

    private function attachSignoff(Project $project, ?User $manager): void
    {
        if (! $manager || $project->signoffs()->where('role', Signoff::ROLE_PREPARED)->exists()) {
            return;
        }

        $project->signoffs()->create([
            'role' => Signoff::ROLE_PREPARED,
            'user_id' => $manager->id,
            'signature_name' => $manager->name,
            'signed_at' => now()->subDays(18),
        ]);
    }

    /** سجل ملاحظات يوضّح الأنواع المختلفة بترتيب زمني. */
    private function attachNotes(Project $project, ?User $manager): void
    {
        if ($project->notes()->exists()) {
            return;
        }

        $notes = [
            ['اجتماع انطلاق المشروع', 'meeting', 'تم الاتفاق على الهوية البصرية ونطاق المتجر وعدد الأقسام.', 'done', 18],
            ['اعتماد قالب Astra بدل القالب المقترح', 'decision', 'القالب الأصلي ثقيل ويؤثر على سرعة الصفحة، والبديل يغطي نفس المتطلبات.', 'done', 14],
            ['تم إرسال تحديث بنسبة الإنجاز', 'client_update', 'أُرسل للعميل تحديث يشمل الصفحة الرئيسية وصفحة "من نحن".', 'done', 3],
            ['ملاحظات العميل على الصفحة الرئيسية', 'client_feedback', 'تكبير صور المنتجات وإضافة قسم "الأكثر مبيعاً".', 'new', 1],
            ['بانتظار استلام الشعار وصور المنتجات', 'internal_note', 'لا يمكن إنهاء صفحة المنتج قبل استلام الصور بجودة عالية.', 'in_progress', 0],
        ];

        foreach ($notes as [$title, $type, $body, $status, $daysAgo]) {
            $note = $project->notes()->create([
                'title' => $title,
                'type' => $type,
                'body' => $body,
                'status' => $status,
                'created_by' => $manager?->id,
            ]);

            // الملاحظات سجل تاريخي — نضبط تاريخ الإضافة ليعكس تسلسل الأحداث.
            ProjectNote::withoutTimestamps(fn () => $note->update([
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ]));
        }
    }
}
