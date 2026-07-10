<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; text-align: right; color: #1e293b; font-size: 11px; }
        h1, h2, h3 { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo { width: 40px; height: 40px; }
        .title { font-size: 20px; font-weight: bold; color: #1e1b4b; }
        .subtitle { font-size: 11px; color: #64748b; margin-top: 2px; }
        .meta { font-size: 10px; color: #94a3b8; }
        .section { margin-top: 18px; }
        .section-title { font-size: 14px; font-weight: bold; color: #1e1b4b; border-bottom: 2px solid #4f46e5; padding-bottom: 4px; margin-bottom: 8px; }
        .info-table td { padding: 4px 0; }
        .info-label { color: #64748b; width: 130px; }
        .info-value { font-weight: bold; }
        .cat-table th, .cat-table td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: right; }
        .cat-table th { background: #f1f5f9; font-size: 10px; color: #475569; }
        .bar-bg { background: #e2e8f0; height: 8px; border-radius: 4px; width: 100%; }
        .bar-fill { background: #4f46e5; height: 8px; border-radius: 4px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .badge-good { background: #d1fae5; color: #047857; }
        .badge-warn { background: #fef3c7; color: #b45309; }
        .badge-bad { background: #fee2e2; color: #b91c1c; }
        .perf-table th, .perf-table td { border: 1px solid #e2e8f0; padding: 8px; text-align: center; }
        .perf-table th { background: #f1f5f9; font-size: 10px; color: #475569; }
        .perf-score { font-size: 18px; font-weight: bold; }
        .sign-table th, .sign-table td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: right; font-size: 10px; }
        .sign-table th { background: #1e1b4b; color: #fff; }
        .footer { position: fixed; bottom: -18px; left: 0; right: 0; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 46px;">
                <img class="logo" src="data:image/png;base64,{{ $logoBase64 }}">
            </td>
            <td>
                <div class="title">تقرير المشروع</div>
                <div class="subtitle">Maharat Net — نظام إدارة الجودة والمشاريع</div>
            </td>
            <td style="text-align: left;" class="meta">
                تاريخ الإصدار<br>{{ $generatedAt->format('Y-m-d H:i') }}
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">معلومات المشروع</div>
        <table class="info-table">
            <tr><td class="info-label">اسم المشروع</td><td class="info-value">{{ $project->name }}</td></tr>
            <tr><td class="info-label">العميل</td><td class="info-value">{{ $project->client_name }}</td></tr>
            <tr><td class="info-label">الحالة</td><td class="info-value">
                @php
                    $statusLabels = [
                        'in_progress' => 'قيد التنفيذ', 'in_review' => 'قيد مراجعة الجودة',
                        'changes_requested' => 'طلب تعديلات', 'approved' => 'معتمد', 'delivered' => 'تم التسليم',
                    ];
                @endphp
                {{ $statusLabels[$project->status] ?? $project->status }}
            </td></tr>
            <tr><td class="info-label">المرحلة الحالية</td><td class="info-value">{{ $project->currentPhase->name_ar ?? '—' }}</td></tr>
            <tr><td class="info-label">رابط الموقع</td><td class="info-value">{{ $project->site_url ?? '—' }}</td></tr>
            <tr><td class="info-label">المبرمجون</td><td class="info-value">{{ $project->developers->pluck('name')->join('، ') ?: '—' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">ملخص قائمة التحقق — {{ $doneItems }} / {{ $totalItems }} ({{ $overallPercent }}%)</div>
        <table class="cat-table">
            <thead>
                <tr>
                    <th style="width: 40px;">القسم</th>
                    <th>الاسم</th>
                    <th style="width: 70px;">المنجز</th>
                    <th style="width: 140px;">النسبة</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $cat)
                    @php $pct = $cat['total'] > 0 ? round((($cat['done'] + $cat['na']) / $cat['total']) * 100) : 0; @endphp
                    <tr>
                        <td>{{ $cat['code'] }}</td>
                        <td>{{ $cat['name_ar'] }}</td>
                        <td>{{ $cat['done'] }} / {{ $cat['total'] }}</td>
                        <td>
                            <table><tr><td class="bar-bg"><div class="bar-fill" style="width: {{ $pct }}%;"></div></td></tr></table>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">مقارنة الأداء</div>
        @if ($originalReport || $finalReport)
            <table class="perf-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>القالب الأصلي (Envato Demo)</th>
                        <th>الموقع النهائي</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Lighthouse — موبايل</td>
                        <td><span class="perf-score">{{ $originalReport->lighthouse_mobile ?? '—' }}</span></td>
                        <td><span class="perf-score">{{ $finalReport->lighthouse_mobile ?? '—' }}</span></td>
                    </tr>
                    <tr>
                        <td>Lighthouse — ديسكتوب</td>
                        <td><span class="perf-score">{{ $originalReport->lighthouse_desktop ?? '—' }}</span></td>
                        <td><span class="perf-score">{{ $finalReport->lighthouse_desktop ?? '—' }}</span></td>
                    </tr>
                    <tr>
                        <td>عدد الإضافات</td>
                        <td>{{ $originalReport->plugin_count ?? '—' }}</td>
                        <td>{{ $finalReport->plugin_count ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p class="meta">لا توجد تقارير أداء مسجّلة بعد.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">جدول الاعتماد</div>
        @php
            $roleLabels = ['prepared' => 'إعداد', 'reviewed' => 'مراجعة', 'final_approval' => 'اعتماد نهائي'];
            $signoffsByRole = $project->signoffs->keyBy('role');
        @endphp
        <table class="sign-table">
            <thead>
                <tr><th>الدور</th><th>الاسم</th><th>التاريخ</th></tr>
            </thead>
            <tbody>
                @foreach ($roleLabels as $role => $label)
                    @php $signoff = $signoffsByRole->get($role); @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $signoff->signature_name ?? '—' }}</td>
                        <td>{{ $signoff?->signed_at?->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Maharat Net — تقرير مُولَّد تلقائياً من نظام إدارة الجودة والمشاريع
    </div>

</body>
</html>
