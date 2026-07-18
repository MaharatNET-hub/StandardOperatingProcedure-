<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; direction: rtl; text-align: right; color: #262220; font-size: 11px; background: #ffffff; }
        h1, h2, h3, p { margin: 0; }

        /* Signifier */
        .kicker { font-size: 9px; letter-spacing: 3px; color: #a9812f; font-weight: bold; }
        .kicker-line { height: 1px; background: #a9812f; margin: 6px 0 18px 0; width: 64px; }
        .doc-title { font-size: 24px; color: #1c1a17; font-weight: bold; }
        .doc-subtitle { font-size: 10.5px; color: #8a8378; margin-top: 3px; }
        .ref-box { text-align: left; font-size: 9.5px; color: #8a8378; line-height: 1.7; }
        .ref-box b { color: #262220; }

        .rule { height: 1px; background: #e7e2d9; margin: 22px 0; }
        .rule-strong { height: 1px; background: #1c1a17; margin: 26px 0 20px 0; }

        .section-label { font-size: 9.5px; letter-spacing: 2px; color: #a9812f; font-weight: bold; margin-bottom: 10px; }
        .section-body { font-size: 11px; color: #3c372f; line-height: 1.8; }

        table { width: 100%; border-collapse: collapse; }
        .kv td { padding: 5px 0; vertical-align: top; border-bottom: 1px solid #f1eee7; font-size: 10.5px; }
        .kv td.k { color: #8a8378; width: 150px; }
        .kv td.v { color: #262220; font-weight: bold; }

        .reco-box { border-right: 2px solid #a9812f; padding: 4px 0 4px 14px; margin-top: 4px; }
        .reco-title { font-size: 12.5px; font-weight: bold; color: #1c1a17; margin-bottom: 5px; }
        .reco-reason { font-size: 10.5px; color: #5c564c; line-height: 1.8; }

        .cost-table th, .cost-table td { padding: 9px 8px; text-align: right; font-size: 10.5px; }
        .cost-table thead th { border-bottom: 1px solid #1c1a17; color: #1c1a17; font-weight: bold; font-size: 9.5px; letter-spacing: 0.5px; }
        .cost-table tbody td { border-bottom: 1px solid #f1eee7; color: #3c372f; }
        .cost-table tfoot td { border-top: 1px solid #1c1a17; padding-top: 10px; font-weight: bold; color: #1c1a17; }
        .cycle-tag { font-size: 9px; color: #a9812f; }

        .summary-grid td { width: 33.33%; vertical-align: top; padding-left: 18px; }
        .summary-card { border: 1px solid #efeadf; padding: 14px 16px; }
        .summary-card .label { font-size: 9px; color: #8a8378; letter-spacing: 1px; margin-bottom: 6px; }
        .summary-card .value { font-size: 13px; color: #1c1a17; font-weight: bold; }

        .footer { margin-top: 26px; font-size: 8.5px; color: #b3ab9c; text-align: center; }
        .footer .kicker-line { margin: 0 auto 6px auto; }

        .screenshot-frame { border: 1px solid #efeadf; padding: 6px; }
        .screenshot-frame img { width: 100%; }
        .issue-row td { padding: 6px 0; border-bottom: 1px solid #f1eee7; font-size: 10.5px; vertical-align: top; }
        .issue-row .issue-label { color: #3c372f; }
        .issue-row .issue-count { color: #b3452f; font-weight: bold; text-align: left; width: 60px; }
        .example-list { font-size: 9.5px; color: #8a8378; margin-top: 4px; }
        .score-value { font-size: 22px; font-weight: bold; }
        .score-good { color: #3f7d4a; }
        .score-mid { color: #a9812f; }
        .score-bad { color: #b3452f; }
        .audit-reco { background: #faf8f4; border: 1px solid #efeadf; padding: 14px 16px; font-size: 10.5px; color: #3c372f; line-height: 1.8; }
    </style>
</head>
<body>

    <table>
        <tr>
            <td>
                <div class="kicker">MAHARAT NET — مهارات نت</div>
                <div class="kicker-line"></div>
                <div class="doc-title">عرض سعر فني</div>
                <div class="doc-subtitle">Technical Proposal &amp; Quotation</div>
            </td>
            <td style="width: 200px;">
                <div class="ref-box">
                    رقم المرجع&nbsp;<b>#{{ str_pad($quotation->id, 5, '0', STR_PAD_LEFT) }}</b><br>
                    تاريخ الإصدار&nbsp;<b>{{ $generatedAt->format('Y-m-d') }}</b><br>
                    العميل&nbsp;<b>{{ $quotation->client_name ?? '—' }}</b>
                </div>
            </td>
        </tr>
    </table>

    <div class="rule-strong"></div>

    {{-- ملخص المشروع --}}
    <div class="section-label">ملخص المشروع</div>
    <table class="kv">
        <tr><td class="k">الموقع المفحوص</td><td class="v">{{ $quotation->url }}</td></tr>
        <tr><td class="k">طبيعة النشاط التجاري</td><td class="v">{{ \App\Services\SiteAnalyzerService::businessLabel($quotation->business_type) }}</td></tr>
    </table>
    @if ($quotation->project_summary)
        <p class="section-body" style="margin-top: 10px;">{{ $quotation->project_summary }}</p>
    @elseif ($quotation->business_summary)
        <p class="section-body" style="margin-top: 10px;">{{ $quotation->business_summary }}</p>
    @endif

    <div class="rule"></div>

    {{-- التحليل الفني --}}
    <div class="section-label">التحليل الفني — محاكاة Screaming Frog / Wappalyzer</div>
    <table class="summary-grid">
        <tr>
            <td>
                <div class="summary-card">
                    <div class="label">إطار العمل / المنصة الحالية</div>
                    <div class="value">{{ $quotation->detected_framework ?? '—' }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="label">بروتوكول الأمان</div>
                    <div class="value">{{ ($quotation->infrastructure['https'] ?? false) ? 'HTTPS مفعّل' : 'HTTPS غير مفعّل' }}</div>
                </div>
            </td>
            <td style="padding-left: 0;">
                <div class="summary-card">
                    <div class="label">الشبكة / الحماية</div>
                    <div class="value">{{ $quotation->infrastructure['cdn_or_firewall'] ?? '—' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="kv" style="margin-top: 12px;">
        <tr><td class="k">الخادم (Server)</td><td class="v">{{ $quotation->infrastructure['server'] ?? '—' }}</td></tr>
        <tr><td class="k">عنوان الصفحة (Title)</td><td class="v">{{ $quotation->meta_title ?? '—' }}</td></tr>
    </table>

    @if ($quotation->crawl_summary)
        <div class="rule"></div>
        <div class="section-label">تحليل الزحف (محاكاة Screaming Frog)</div>
        @php $crawl = $quotation->crawl_summary; @endphp
        <table class="summary-grid">
            <tr>
                <td>
                    <div class="summary-card">
                        <div class="label">صفحات تم فحصها</div>
                        <div class="value">{{ $crawl['pages_crawled'] ?? 0 }}</div>
                    </div>
                </td>
                <td>
                    <div class="summary-card">
                        <div class="label">روابط مكسورة</div>
                        <div class="value">{{ count($crawl['broken_links'] ?? []) }}</div>
                    </div>
                </td>
                <td style="padding-left: 0;">
                    <div class="summary-card">
                        <div class="label">صفحات بدون H1</div>
                        <div class="value">{{ count($crawl['pages_without_h1'] ?? []) }}</div>
                    </div>
                </td>
            </tr>
        </table>
        <table class="kv" style="margin-top: 12px;">
            <tr><td class="k">صفحات بدون Title</td><td class="v">{{ count($crawl['missing_title'] ?? []) }}</td></tr>
            <tr><td class="k">صفحات بدون Meta Description</td><td class="v">{{ count($crawl['missing_meta_description'] ?? []) }}</td></tr>
            <tr><td class="k">عناوين مكررة</td><td class="v">{{ count($crawl['duplicate_titles'] ?? []) }}</td></tr>
        </table>

        @php
            $issueRows = array_filter([
                ['label' => 'روابط مكسورة (404 أو خطأ خادم)', 'count' => count($crawl['broken_links'] ?? [])],
                ['label' => 'وسم <head> مكرر', 'count' => count($crawl['duplicate_head_tags'] ?? [])],
                ['label' => 'وسم <body> مكرر', 'count' => count($crawl['duplicate_body_tags'] ?? [])],
                ['label' => 'عنوان H1 مفقود', 'count' => count($crawl['pages_without_h1'] ?? [])],
                ['label' => 'صفحات بحاجة لعنوان H2', 'count' => count($crawl['pages_missing_h2'] ?? [])],
                ['label' => 'ترتيب عناوين خاطئ (تخطي مستوى)', 'count' => count($crawl['heading_order_issues'] ?? [])],
                ['label' => 'عنوان الصفحة الرئيسية مطابق لـ H1', 'count' => ($crawl['homepage_title_matches_h1'] ?? false) ? 1 : 0],
                ['label' => 'صور بدون نص بديل (Alt Text)', 'count' => $crawl['missing_alt_images'] ?? 0],
                ['label' => 'صور يتجاوز حجمها 100 كيلوبايت', 'count' => count($crawl['large_images'] ?? [])],
            ], fn ($row) => $row['count'] > 0);
        @endphp

        @if (! empty($issueRows) || ($crawl['total_images'] ?? 0) > 0)
            <div class="rule"></div>
            <div class="section-label">المشاكل الفنية المكتشفة آلياً</div>
            @if (($crawl['pages_crawled'] ?? 0) > 0 && ($crawl['total_images'] ?? 0) / max($crawl['pages_crawled'], 1) > 10)
                <p class="section-body" style="margin-bottom: 8px;">
                    الموقع يحتوي على {{ $crawl['pages_crawled'] }} صفحة مفحوصة و{{ $crawl['total_images'] }} صورة — عدد كبير جداً من الصور مقارنة بعدد الصفحات، ما يسبب بطئاً ملحوظاً بسرعة التحميل.
                </p>
            @endif
            <table>
                @foreach ($issueRows as $row)
                    <tr class="issue-row">
                        <td class="issue-label">{{ $row['label'] }}</td>
                        <td class="issue-count">{{ $row['count'] }}</td>
                    </tr>
                @endforeach
            </table>
            @if (! empty($crawl['broken_links']))
                <div class="example-list">
                    أمثلة: {{ collect($crawl['broken_links'])->take(5)->map(fn ($b) => $b['url'].' ('.($b['status'] ?: 'تعذر الوصول').')')->implode('، ') }}
                </div>
            @endif
        @endif
    @endif

    @if ($quotation->homepage_screenshot)
        <div class="rule"></div>
        <div class="section-label">لقطة من الصفحة الرئيسية</div>
        <div class="screenshot-frame">
            <img src="data:image/png;base64,{{ $quotation->homepage_screenshot }}">
        </div>
    @endif

    @php
        $scoreClass = fn (?int $s) => $s === null ? '' : ($s >= 75 ? 'score-good' : ($s >= 50 ? 'score-mid' : 'score-bad'));
    @endphp
    @if ($quotation->ux_score !== null || $quotation->seo_score !== null || $quotation->speed_score !== null)
        <div class="rule"></div>
        <div class="section-label">نتيجة التقييم الفني</div>
        <table class="summary-grid">
            <tr>
                <td>
                    <div class="summary-card">
                        <div class="label">تجربة المستخدم (UX)</div>
                        <div class="score-value {{ $scoreClass($quotation->ux_score) }}">{{ $quotation->ux_score !== null ? $quotation->ux_score.'%' : '—' }}</div>
                    </div>
                </td>
                <td>
                    <div class="summary-card">
                        <div class="label">تحسين محركات البحث (SEO)</div>
                        <div class="score-value {{ $scoreClass($quotation->seo_score) }}">{{ $quotation->seo_score !== null ? $quotation->seo_score.'%' : '—' }}</div>
                    </div>
                </td>
                <td style="padding-left: 0;">
                    <div class="summary-card">
                        <div class="label">سرعة الموقع</div>
                        <div class="score-value {{ $scoreClass($quotation->speed_score) }}">{{ $quotation->speed_score !== null ? $quotation->speed_score.'%' : '—' }}</div>
                    </div>
                </td>
            </tr>
        </table>
        @if ($quotation->audit_recommendation)
            <div class="audit-reco" style="margin-top: 12px;">
                <b>نصائح مهارات نت:</b> {{ $quotation->audit_recommendation }}
            </div>
        @endif
    @endif

    <div class="rule"></div>

    {{-- التوصية الفنية --}}
    <div class="section-label">التوصية الفنية</div>
    <div class="reco-box">
        <div class="reco-title">{{ $quotation->recommended_platform ?? '—' }}</div>
        <div class="reco-reason">{{ $quotation->recommendation_reason }}</div>
    </div>

    @if ($quotation->technical_scope)
        <div class="rule"></div>
        <div class="section-label">التوصيف التقني للعمل المقترح</div>
        <p class="section-body">{{ $quotation->technical_scope }}</p>
    @endif

    @if (! empty($quotation->proposed_pages))
        <div class="rule"></div>
        <div class="section-label">الصفحات المقترحة للموقع الجديد</div>
        <table class="kv">
            @foreach ($quotation->proposed_pages as $i => $page)
                <tr><td class="k">{{ $i + 1 }}.</td><td class="v">{{ $page }}</td></tr>
            @endforeach
        </table>
    @endif

    <div class="rule"></div>

    {{-- جدول التكاليف التشغيلية --}}
    <div class="section-label">جدول التكاليف التشغيلية (خطة الاشتراك)</div>
    @php
        $cycleLabels = ['monthly' => 'شهري', 'yearly' => 'سنوي', 'lifetime' => 'مدى الحياة'];
        $items = collect($quotation->cost_items ?? []);
    @endphp
    <table class="cost-table">
        <thead>
            <tr>
                <th>الإضافة / الخدمة</th>
                <th style="width: 110px;">النوع</th>
                <th style="width: 90px;">الدورة</th>
                <th style="width: 90px;">السعر</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item['name'] ?? '—' }}</td>
                    <td>{{ $item['type'] ?? '—' }}</td>
                    <td><span class="cycle-tag">{{ $cycleLabels[$item['cycle'] ?? 'yearly'] ?? '—' }}</span></td>
                    <td>{{ number_format((float) ($item['price'] ?? 0), 2) }} {{ $quotation->currency }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="color:#8a8378;">لا توجد إضافات مدفوعة مطلوبة لهذا المشروع.</td></tr>
            @endforelse
        </tbody>
        @if ($items->count())
            <tfoot>
                <tr>
                    <td colspan="3">إجمالي الإضافات (تقريبي)</td>
                    <td>{{ number_format($items->sum(fn ($i) => (float) ($i['price'] ?? 0)), 2) }} {{ $quotation->currency }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="rule"></div>

    <table class="summary-grid">
        <tr>
            <td>
                <div class="summary-card">
                    <div class="label">الدومين (تقديري)</div>
                    <div class="value">{{ $quotation->domain_cost !== null ? number_format((float) $quotation->domain_cost, 2).' '.$quotation->currency.' / سنوياً' : '—' }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="label">الاستضافة (تقديري)</div>
                    <div class="value">{{ $quotation->hosting_cost !== null ? number_format((float) $quotation->hosting_cost, 2).' '.$quotation->currency.' / '.$cycleLabels[$quotation->hosting_cycle] : '—' }}</div>
                </div>
            </td>
            <td style="padding-left: 0;">
                <div class="summary-card">
                    <div class="label">الدعم الفني المجاني</div>
                    <div class="value">{{ $quotation->support_months ? $quotation->support_months.' شهر' : '—' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <div class="kicker-line"></div>
        Maharat Net — مهارات نت &nbsp;|&nbsp; هذا العرض تقديري وصالح لمدة 15 يوماً من تاريخ الإصدار وقابل للتعديل بعد المراجعة الفنية النهائية.
    </div>

</body>
</html>
