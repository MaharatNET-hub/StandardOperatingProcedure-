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

    /** @var array<string, array<int, string>> الصفحات المقترحة للموقع الجديد حسب طبيعة النشاط */
    private const BUSINESS_PAGE_TEMPLATES = [
        'ecommerce' => ['الرئيسية', 'كل المنتجات (تصنيفات وفلترة)', 'صفحة المنتج', 'سلة المشتريات', 'إتمام الشراء والدفع', 'حسابي / تسجيل الدخول', 'من نحن', 'اتصل بنا', 'سياسة الشحن والاستبدال', 'الأسئلة الشائعة'],
        'restaurant' => ['الرئيسية', 'قائمة الطعام (المنيو)', 'الفروع (خريطة)', 'الطلب أونلاين / التوصيل', 'حجز طاولة', 'من نحن', 'اتصل بنا'],
        'medical' => ['الرئيسية', 'الأقسام والتخصصات', 'الأطباء والكادر الطبي', 'حجز موعد أونلاين', 'من نحن', 'اتصل بنا وموقع العيادة'],
        'real_estate' => ['الرئيسية', 'العقارات المعروضة (بحث وفلترة)', 'صفحة تفاصيل العقار', 'أضف عقارك', 'من نحن', 'اتصل بنا'],
        'education' => ['الرئيسية', 'الدورات / البرامج التدريبية', 'صفحة الدورة والتسجيل', 'المدربون', 'من نحن', 'اتصل بنا'],
        'blog_news' => ['الرئيسية', 'التصنيفات', 'صفحة المقال', 'عن الموقع', 'اتصل بنا'],
        'legal' => ['الرئيسية', 'مجالات الممارسة', 'فريق المحامين', 'استشارة أونلاين', 'من نحن', 'اتصل بنا'],
        'corporate' => ['الرئيسية', 'من نحن', 'خدماتنا', 'أعمالنا / معرض الأعمال', 'المدونة (اختياري)', 'اتصل بنا'],
        'unknown' => ['الرئيسية', 'من نحن', 'خدماتنا / منتجاتنا', 'اتصل بنا'],
    ];

    public static function businessLabel(?string $type): string
    {
        return self::BUSINESS_LABELS[$type] ?? self::BUSINESS_LABELS['unknown'];
    }

    /** @return array<int, string> */
    public static function proposedPages(?string $type): array
    {
        return self::BUSINESS_PAGE_TEMPLATES[$type] ?? self::BUSINESS_PAGE_TEMPLATES['unknown'];
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
        $crawlSummary = $this->crawlSite($url, $html, $response->status());

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
            'crawl_summary' => $crawlSummary,
            'proposed_pages' => self::proposedPages($businessType),
        ];
    }

    /**
     * محاكاة مصغّرة لزحف Screaming Frog: يستخرج الروابط الداخلية من الصفحة
     * الرئيسية، يجلب حتى 12 صفحة إضافية من نفس الدومين بشكل متزامن، ويفحص
     * كل صفحة (الحالة، H1، Title، Meta Description) لتجميع تقرير سريع.
     */
    private function crawlSite(string $baseUrl, string $homepageHtml, int $homepageStatus): array
    {
        $links = $this->extractInternalLinks($homepageHtml, $baseUrl, 12);

        $pages = [$baseUrl => ['status' => $homepageStatus, 'html' => $homepageHtml]];

        if (! empty($links)) {
            try {
                $responses = Http::pool(fn ($pool) => collect($links)->each(
                    fn ($link) => $pool->as($link)->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; MaharatNetAnalyzer/1.0; +https://maharatnet.com)',
                    ])->timeout(10)->connectTimeout(6)->get($link)
                ));

                foreach ($links as $link) {
                    $resp = $responses[$link] ?? null;
                    if ($resp instanceof \Illuminate\Http\Client\Response) {
                        $pages[$link] = ['status' => $resp->status(), 'html' => (string) $resp->body()];
                    } else {
                        $pages[$link] = ['status' => 0, 'html' => ''];
                    }
                }
            } catch (Throwable $e) {
                // نكتفي بتحليل الصفحة الرئيسية لو فشل الزحف الموسّع
            }
        }

        $brokenLinks = [];
        $missingTitle = [];
        $missingMeta = [];
        $withoutH1 = [];
        $titles = [];

        foreach ($pages as $pageUrl => $page) {
            $status = $page['status'];
            if ($status === 0 || $status >= 400) {
                $brokenLinks[] = ['url' => $pageUrl, 'status' => $status];

                continue;
            }

            $pageHtml = $page['html'];
            $title = $this->extractTag($pageHtml, 'title');
            $desc = $this->extractMeta($pageHtml, 'description');
            $h1Count = preg_match_all('#<h1[ >]#i', $pageHtml);

            if (! $title) {
                $missingTitle[] = $pageUrl;
            } else {
                $titles[mb_strtolower($title)][] = $pageUrl;
            }

            if (! $desc) {
                $missingMeta[] = $pageUrl;
            }

            if ($h1Count === 0) {
                $withoutH1[] = $pageUrl;
            }
        }

        $duplicateTitles = [];
        foreach ($titles as $urls) {
            if (count($urls) > 1) {
                $duplicateTitles[] = $urls;
            }
        }

        return [
            'pages_crawled' => count($pages),
            'broken_links' => $brokenLinks,
            'missing_title' => $missingTitle,
            'missing_meta_description' => $missingMeta,
            'pages_without_h1' => $withoutH1,
            'duplicate_titles' => $duplicateTitles,
        ];
    }

    /** @return array<int, string> */
    private function extractInternalLinks(string $html, string $baseUrl, int $limit): array
    {
        preg_match_all('#href=["\']([^"\']+)["\']#i', $html, $matches);
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        $skipExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'zip', 'css', 'js', 'ico', 'woff', 'woff2', 'ttf', 'mp4', 'xml', 'json'];

        $links = [];
        foreach ($matches[1] as $href) {
            $absolute = $this->resolveUrl($baseUrl, $href);
            if (! $absolute) {
                continue;
            }

            if (parse_url($absolute, PHP_URL_HOST) !== $baseHost) {
                continue;
            }

            $path = parse_url($absolute, PHP_URL_PATH) ?? '';
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($extension, $skipExtensions, true)) {
                continue;
            }

            $clean = rtrim(strtok($absolute, '#'), '/');
            if ($clean === '' || $clean === rtrim($baseUrl, '/')) {
                continue;
            }

            $links[$clean] = true;
            if (count($links) >= $limit) {
                break;
            }
        }

        return array_keys($links);
    }

    private function resolveUrl(string $base, string $href): ?string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#')
            || preg_match('#^(mailto|tel|javascript):#i', $href)) {
            return null;
        }

        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $baseParts = parse_url($base);
        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';

        if (str_starts_with($href, '//')) {
            return "{$scheme}:{$href}";
        }

        if (str_starts_with($href, '/')) {
            return "{$scheme}://{$host}{$href}";
        }

        $basePath = $baseParts['path'] ?? '/';
        $dir = str_ends_with($basePath, '/') ? $basePath : (dirname($basePath).'/');

        return "{$scheme}://{$host}{$dir}{$href}";
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
