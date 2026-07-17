<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * يحاكي عمل أداتي Screaming Frog وWappalyzer: يجلب رابط الموقع ويفحص
 * الـ HTML والـ Headers لاستنتاج إطار العمل، طبيعة النشاط التجاري،
 * والبنية التحتية، ثم يقترح المنصة الأنسب لإعادة البناء.
 */
class SiteAnalyzerService
{
    /** @var array<string, array<int, string>> إشارات كشف كل منصة (نص يُبحث عنه داخل HTML) */
    private const PLATFORM_SIGNATURES = [
        'Salla' => ['cdn.salla.network', 'salla.sa', 's-salla', 'salla-app'],
        'Shopify' => ['cdn.shopify.com', 'shopify.theme', 'x-shopid', 'myshopify.com'],
        'Zid' => ['zid.store', 'assets.zid.store', 'zid-cdn'],
        'Wix' => ['wix.com', '_wixcidx', 'static.wixstatic.com'],
        'Squarespace' => ['squarespace.com', 'static1.squarespace.com'],
        'Magento' => ['mage.cookies', 'skin/frontend', 'static/version'],
        'WooCommerce' => ['woocommerce', 'wc-ajax'],
        'WordPress' => ['wp-content', 'wp-includes', '/wp-json/', 'wp-emoji-release'],
        'Drupal' => ['drupal.settings', 'sites/default/files', 'x-generator" content="drupal'],
        'Joomla' => ['joomla!', '/media/jui/'],
        'Laravel' => ['laravel_session', 'csrf-token', 'livewire'],
        'Next.js' => ['__next_data__', '_next/static'],
        'Nuxt/Vue' => ['__nuxt', 'data-server-rendered'],
    ];

    /** @var array<string, array<int, string>> كلمات مفتاحية لاستنتاج طبيعة النشاط التجاري */
    private const BUSINESS_KEYWORDS = [
        'ecommerce' => ['أضف إلى السلة', 'عربة التسوق', 'سلة المشتريات', 'add to cart', 'checkout', 'shopping cart', 'متجر', 'تسوق الآن', 'product-price'],
        'restaurant' => ['مطعم', 'قائمة الطعام', 'menu', 'delivery', 'توصيل الطلبات', 'احجز طاولة'],
        'medical' => ['عيادة', 'طبيب', 'حجز موعد', 'د. ', 'تحاليل طبية', 'مركز طبي', 'clinic', 'doctor'],
        'real_estate' => ['عقار', 'شقق للبيع', 'فلل', 'تمليك', 'إيجار', 'real estate', 'property'],
        'education' => ['أكاديمية', 'دورة تدريبية', 'كورس', 'تعليم عن بعد', 'e-learning', 'course'],
        'blog_news' => ['مدونة', 'آخر الأخبار', 'تدوينة', 'blog', 'اقرأ المزيد'],
        'legal' => ['محاماة', 'استشارات قانونية', 'مكتب محاماة', 'law firm'],
        'corporate' => ['من نحن', 'خدماتنا', 'شركة رائدة', 'about us', 'our services'],
    ];

    private const BUSINESS_LABELS = [
        'ecommerce' => 'متجر إلكتروني (بيع منتجات أونلاين)',
        'restaurant' => 'مطعم / خدمات طلب وتوصيل طعام',
        'medical' => 'قطاع طبي / عيادات وحجز مواعيد',
        'real_estate' => 'قطاع عقاري',
        'education' => 'منصة تعليمية / تدريبية',
        'blog_news' => 'مدونة / موقع إخباري',
        'legal' => 'مكتب محاماة / استشارات قانونية',
        'corporate' => 'موقع تعريفي لشركة أو مؤسسة',
        'unknown' => 'غير محدد بدقة — يتطلب مراجعة يدوية من الفريق التجاري',
    ];

    public static function businessLabel(?string $type): string
    {
        return self::BUSINESS_LABELS[$type] ?? self::BUSINESS_LABELS['unknown'];
    }

    public function analyze(string $url): array
    {
        $url = $this->normalizeUrl($url);

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; MaharatNetAnalyzer/1.0; +https://maharatnet.com)',
            ])->timeout(15)->connectTimeout(8)->get($url);
        } catch (Throwable $e) {
            throw new RuntimeException('تعذر الوصول إلى الرابط. تأكد أن الموقع يعمل وأن الرابط صحيح.');
        }

        if ($response->failed() && $response->status() >= 500) {
            throw new RuntimeException("الموقع أعاد خطأ خادم ({$response->status()}) — تعذر إتمام التحليل.");
        }

        $html = (string) $response->body();
        $haystack = mb_strtolower($html);
        $headers = collect($response->headers())->map(fn ($v) => is_array($v) ? implode(', ', $v) : $v);
        $headerHaystack = mb_strtolower($headers->map(fn ($v, $k) => "$k: $v")->implode(' | '));

        [$framework, $signals] = $this->detectFramework($haystack, $headerHaystack);
        $metaTitle = $this->extractTag($html, 'title');
        $metaDescription = $this->extractMeta($html, 'description');
        [$businessType, $businessSummary] = $this->detectBusiness($haystack, $metaTitle, $metaDescription);
        $infrastructure = $this->detectInfrastructure($url, $headers);
        [$recommendedPlatform, $reason] = $this->recommendPlatform($framework, $businessType);

        return [
            'url' => $url,
            'detected_framework' => $framework,
            'detected_signals' => $signals,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'business_type' => $businessType,
            'business_summary' => $businessSummary,
            'infrastructure' => $infrastructure,
            'recommended_platform' => $recommendedPlatform,
            'recommendation_reason' => $reason,
        ];
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }

    /** @return array{0: string, 1: array<int, string>} */
    private function detectFramework(string $haystack, string $headerHaystack): array
    {
        $matched = [];

        foreach (self::PLATFORM_SIGNATURES as $platform => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle) || str_contains($headerHaystack, $needle)) {
                    $matched[$platform][] = $needle;
                    break;
                }
            }
        }

        if (empty($matched)) {
            return ['HTML مخصص / لا يعتمد على منصة جاهزة', []];
        }

        // أفضلية للمنصات المتخصصة (سلة/Shopify) على WordPress العام لو كلاهما ظهر
        $priority = ['Salla', 'Shopify', 'Zid', 'Wix', 'Squarespace', 'Magento', 'WooCommerce', 'WordPress', 'Drupal', 'Joomla', 'Next.js', 'Nuxt/Vue', 'Laravel'];
        foreach ($priority as $platform) {
            if (isset($matched[$platform])) {
                $signals = $matched[$platform];
                // WooCommerce يعني ووردبريس + ووكومرس
                if ($platform === 'WooCommerce' && isset($matched['WordPress'])) {
                    return ['WordPress + WooCommerce', array_merge($signals, $matched['WordPress'])];
                }

                return [$platform, $signals];
            }
        }

        $first = array_key_first($matched);

        return [$first, $matched[$first]];
    }

    /** @return array{0: string, 1: string} */
    private function detectBusiness(string $haystack, ?string $title, ?string $description): array
    {
        $scores = [];
        foreach (self::BUSINESS_KEYWORDS as $type => $keywords) {
            $count = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, mb_strtolower($keyword))) {
                    $count++;
                }
            }
            if ($count > 0) {
                $scores[$type] = $count;
            }
        }

        if (empty($scores)) {
            $type = 'unknown';
        } else {
            arsort($scores);
            $type = array_key_first($scores);
        }

        $summary = self::BUSINESS_LABELS[$type];
        if ($title) {
            $summary = "{$summary} — العنوان الظاهر للموقع: \"{$title}\".";
        }
        if ($description) {
            $summary .= " الوصف التعريفي: \"{$description}\".";
        }

        return [$type, $summary];
    }

    /** @param \Illuminate\Support\Collection<string, string> $headers */
    private function detectInfrastructure(string $url, $headers): array
    {
        $lower = $headers->mapWithKeys(fn ($v, $k) => [mb_strtolower($k) => $v]);
        $headerHaystack = mb_strtolower($lower->map(fn ($v, $k) => "$k: $v")->implode(' | '));

        $cdn = 'غير محدد';
        if (str_contains($headerHaystack, 'cf-ray') || str_contains($headerHaystack, 'cloudflare')) {
            $cdn = 'Cloudflare';
        } elseif (str_contains($headerHaystack, 'x-amz-cf-id')) {
            $cdn = 'Amazon CloudFront';
        } elseif (str_contains($headerHaystack, 'x-served-by') && str_contains($headerHaystack, 'cache')) {
            $cdn = 'Fastly';
        } elseif (str_contains($headerHaystack, 'x-sucuri-id')) {
            $cdn = 'Sucuri (جدار حماية)';
        }

        return [
            'https' => str_starts_with($url, 'https://'),
            'server' => $lower->get('server', 'غير معلن'),
            'powered_by' => $lower->get('x-powered-by', 'غير معلن'),
            'cdn_or_firewall' => $cdn,
        ];
    }

    /** @return array{0: string, 1: string} */
    private function recommendPlatform(string $framework, string $businessType): array
    {
        $alreadyOnStrongEcommercePlatform = in_array($framework, ['Salla', 'Shopify', 'Zid'], true);

        if ($businessType === 'ecommerce') {
            if ($alreadyOnStrongEcommercePlatform) {
                return [
                    "الإبقاء على منصة {$framework} مع تحسينات تقنية",
                    'الموقع مبني بالفعل على منصة تجارة إلكترونية متخصصة ومناسبة لطبيعة النشاط؛ التوصية هي تحسين الأداء والـ SEO بدل إعادة البناء الكاملة.',
                ];
            }

            return [
                'منصة سلة (Salla) — أو WooCommerce على WordPress + Astra كبديل اقتصادي',
                'النشاط تجارة إلكترونية والموقع الحالي غير مبني على منصة تجارية متخصصة؛ سلة توفر بوابات دفع وشحن جاهزة للسوق العربي، وWooCommerce خيار أوفر إن كانت المنتجات محدودة العدد.',
            ];
        }

        if (str_contains($framework, 'WordPress')) {
            return [
                'WordPress + إطار Astra (وفق SOP الحالي)',
                'الموقع مبني أصلاً على WordPress، وإعادة البناء على Astra Pro يحافظ على سهولة الإدارة مستقبلاً مع تحسين الأداء والتوافق مع معايير الشركة.',
            ];
        }

        if (in_array($framework, ['Next.js', 'Nuxt/Vue', 'Laravel'], true)) {
            return [
                'نظام مخصص (Laravel / Vue) — لا يُنصح بالتحويل إلى WordPress',
                'الموقع الحالي مبني على تقنية برمجية مخصصة، ما يدل على متطلبات وظيفية متقدمة يصعب تغطيتها عبر WordPress؛ يُفضّل تطوير النظام المخصص أو ترحيله بحذر مع تحليل تقني أعمق.',
            ];
        }

        return [
            'WordPress + إطار Astra (التوصية القياسية لشركة مهارات نت)',
            'طبيعة الموقع تعريفية/مؤسسية بلا متطلبات وظيفية معقدة، وWordPress + Astra يوفر أفضل توازن بين السرعة، سهولة التحديث، والتكلفة.',
        ];
    }

    private function extractTag(string $html, string $tag): ?string
    {
        if (preg_match("#<{$tag}[^>]*>(.*?)</{$tag}>#is", $html, $m)) {
            return trim(html_entity_decode(strip_tags($m[1])));
        }

        return null;
    }

    private function extractMeta(string $html, string $name): ?string
    {
        if (preg_match('#<meta[^>]+name=["\']'.preg_quote($name, '#').'["\'][^>]+content=["\']([^"\']*)["\']#i', $html, $m)) {
            return trim(html_entity_decode($m[1]));
        }

        return null;
    }
}
