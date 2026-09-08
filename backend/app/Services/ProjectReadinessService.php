<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Setting;

/**
 * يفحص المشروع تلقائياً لاكتشاف المتطلبات الناقصة من العميل (دومين،
 * استضافة، شعار، محتوى، بوابة دفع...) بدل فتح كل مشروع يدوياً، ويحسب
 * نسبة "جاهزية المشروع". قائمة المتطلبات المطلوبة لكل نوع مشروع قابلة
 * للتعديل من صفحة الإعدادات (Setting::KEY_PROJECT_TYPE_REQUIREMENTS).
 */
class ProjectReadinessService
{
    /** @var array<string, string> مفتاح كل متطلّب واسمه بالعربي */
    private const REQUIREMENT_LABELS = [
        'logo' => 'الشعار (Logo)',
        'domain' => 'الدومين',
        'hosting' => 'الاستضافة',
        'content' => 'المحتوى النصي',
        'product_images' => 'صور المنتجات',
        'payment_gateway' => 'بوابة الدفع',
        'shipping_company' => 'شركة الشحن',
        'seo' => 'إعداد SEO',
        'google_analytics' => 'ربط Google Analytics',
        'search_console' => 'ربط Search Console',
        'social_media' => 'حسابات التواصل الاجتماعي',
    ];

    /** @var array<string, array<int, string>> المتطلبات الافتراضية حسب نوع المشروع */
    public const DEFAULT_REQUIREMENTS = [
        'ecommerce' => ['logo', 'domain', 'hosting', 'content', 'product_images', 'payment_gateway', 'shipping_company', 'seo', 'google_analytics', 'search_console'],
        'corporate' => ['logo', 'domain', 'hosting', 'content', 'seo', 'google_analytics', 'search_console'],
        'landing_page' => ['logo', 'domain', 'hosting', 'content'],
        'portfolio' => ['logo', 'domain', 'hosting', 'content'],
        'blog' => ['logo', 'domain', 'hosting', 'content', 'seo', 'google_analytics', 'search_console'],
        'booking' => ['logo', 'domain', 'hosting', 'content', 'payment_gateway', 'seo'],
        'marketplace' => ['logo', 'domain', 'hosting', 'content', 'product_images', 'payment_gateway', 'shipping_company', 'seo', 'google_analytics', 'search_console'],
        'custom' => ['logo', 'domain', 'hosting', 'content'],
        'mobile_app' => ['logo', 'content'],
        'other' => ['logo', 'domain', 'hosting', 'content'],
    ];

    public static function requirementsFor(?string $projectType): array
    {
        $stored = Setting::get(Setting::KEY_PROJECT_TYPE_REQUIREMENTS);
        $map = self::DEFAULT_REQUIREMENTS;

        if ($stored) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                $map = array_merge($map, $decoded);
            }
        }

        return $map[$projectType] ?? $map['other'];
    }

    /**
     * Calc - Missing Items / Calc - Missing Count (SRS §12).
     *
     * @return array{percent: int, items: array<int, array{key: string, label: string, status: string, message: ?string}>, missing: array<int, string>, missing_count: int, missing_items: string}
     */
    public function evaluate(Project $project): array
    {
        $keys = self::requirementsFor($project->project_type);
        $items = [];

        foreach ($keys as $key) {
            [$status, $message] = $this->checkRequirement($project, $key);
            $items[] = [
                'key' => $key,
                'label' => self::REQUIREMENT_LABELS[$key] ?? $key,
                'status' => $status, // ready | missing | in_progress
                'message' => $message,
            ];
        }

        $readyCount = count(array_filter($items, fn ($i) => $i['status'] === 'ready'));
        $missing = array_map(fn ($i) => $i['label'], array_filter($items, fn ($i) => $i['status'] !== 'ready'));

        $missing = array_values($missing);

        return [
            'percent' => count($items) > 0 ? (int) round(($readyCount / count($items)) * 100) : 100,
            'items' => $items,
            'missing' => $missing,
            'missing_count' => count($missing),
            'missing_items' => implode('، ', $missing),
        ];
    }

    /** @return array{0: string, 1: ?string} */
    private function checkRequirement(Project $project, string $key): array
    {
        return match ($key) {
            'logo' => $project->has_logo
                ? ['ready', null]
                : ($project->needs_logo_design ? ['in_progress', 'تصميم الشعار مطلوب من الفريق'] : ['missing', 'لم يتم استلام الشعار من العميل']),
            'domain' => $project->has_domain
                ? ['ready', null]
                : ['missing', $project->domain_purchaser === 'client' ? 'بانتظار قيام العميل بشراء الدومين' : 'الدومين غير متوفر بعد'],
            'hosting' => $project->has_hosting
                ? ['ready', null]
                : ['missing', $project->hosting_purchaser === 'client' ? 'بانتظار قيام العميل بشراء الاستضافة' : 'الاستضافة غير متوفرة بعد'],
            'content' => $project->content_ready ? ['ready', null] : ['missing', 'المحتوى النصي غير جاهز بعد'],
            'product_images' => $project->product_images_ready ? ['ready', null] : ['missing', 'صور المنتجات غير جاهزة بعد'],
            'payment_gateway' => ! $project->needs_payment_gateway || in_array($project->payment_gateway_status, ['done', 'na'], true)
                ? ['ready', null]
                : [$project->payment_gateway_status === 'in_progress' ? 'in_progress' : 'missing', 'إعداد بوابة الدفع لم يكتمل'],
            'shipping_company' => $project->has_shipping_company ? ['ready', null] : ['missing', 'لم يتم تحديد شركة شحن بعد'],
            'seo' => ! $project->seo_required || in_array($project->seo_status, ['done', 'na'], true)
                ? ['ready', null]
                : [$project->seo_status === 'in_progress' ? 'in_progress' : 'missing', 'إعداد SEO لم يكتمل'],
            'google_analytics' => $project->google_analytics_connected ? ['ready', null] : ['missing', 'Google Analytics غير مربوط'],
            'search_console' => $project->search_console_connected ? ['ready', null] : ['missing', 'Search Console غير مربوط'],
            'social_media' => $project->social_media_available ? ['ready', null] : ['missing', 'حسابات التواصل الاجتماعي غير متوفرة'],
            default => ['ready', null],
        };
    }
}
