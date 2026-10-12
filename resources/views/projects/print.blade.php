<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة المشروع | اعتماد رسمي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    @php
        $letterhead = $letterhead ?? null;
        $primaryColor = $letterhead ? ($letterhead->getTypographySettings()['primary_color'] ?? '#2c5f2d') : '#2c5f2d';
        $secondaryColor = $letterhead ? ($letterhead->getTypographySettings()['secondary_color'] ?? '#97bc62') : '#97bc62';
        $resolvedLogoUrl = !empty($logoBase64) ? $logoBase64 : ($letterhead ? $letterhead->getLogoUrl() : asset('images/logo.png'));
        $rightSettings = $letterhead ? $letterhead->getHeaderRightSettings() : [];
        $headerRightText = !empty($rightSettings['text']) ? $rightSettings['text'] : "الجمهورية اليمنية\nوزارة الزراعة والثروة السمكية والموارد المائية";
    @endphp
    @if($letterhead)
        <x-letterhead-styles :letterhead="$letterhead" />
    @endif
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --main-color: {{ $primaryColor }};
            --sec-color: {{ $secondaryColor }};
            --dark-text: #1f2937;
            --light-text: #6b7280;
        }

        @page {
            size: A4;
            margin: 0;
        }

        html {
            direction: rtl;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: #e5e7eb;
            margin: 0;
            padding: 20px 0;
            direction: rtl;
            text-align: right;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 10mm auto;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            page-break-after: always;
            page-break-inside: avoid;
            direction: rtl;
            overflow: hidden;
        }

        .internal-padding {
            padding: 40px 40px 60px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            direction: rtl;
        }

        /* ========== صفحة الغلاف ========== */
        .cover-header-bg {
            background-color: var(--main-color);
            height: 55%;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
            z-index: 0;
        }

        .cover-content {
            position: relative;
            z-index: 1;
            padding: 60px 40px;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
            direction: rtl;
        }

        .gov-title {
            color: white;
            font-size: 20px;
            opacity: 0.95;
            margin-bottom: 8px;
            font-weight: 500;
            direction: rtl;
        }

        .logo-container {
            margin-top: 50px;
            margin-bottom: 35px;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .logo-wrapper {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 350px;
            height: 350px;
            background-color: white;
            border-radius: 50%;
            padding: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            border: 2px solid rgba(255, 255, 255, 0.4);
            position: relative;
            overflow: hidden;
        }

        .logo-wrapper::before {
            content: '';
            position: absolute;
            top: -10%;
            left: -10%;
            right: -10%;
            bottom: -10%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.5) 80%);
            border-radius: 50%;
            z-index: 1;
        }

        .logo-wrapper img {
            width: 280px;
            height: auto;
            display: block;
            position: relative;
            z-index: 2;
        }

        .doc-badge {
            background-color: var(--sec-color);
            color: var(--main-color);
            padding: 8px 35px;
            border-radius: 40px;
            font-weight: 800;
            font-size: 16px;
            margin-bottom: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .project-details-bottom {
            margin-top: auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 20px;
        }

        .project-name-main {
            font-size: 28px;
            color: var(--dark-text);
            font-weight: 800;
            margin: 0 0 25px 0;
            line-height: 1.4;
            max-width: 90%;
            text-align: center;
            direction: rtl;
        }

        .applicant-info-card {
            background: #fff;
            border-right: 12px solid var(--main-color);
            border-left: none;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.05);
            padding: 20px 35px;
            border-radius: 12px;
            width: 85%;
            text-align: center;
            border: 1px solid #eef2e6;
        }

        .app-label {
            font-size: 14px;
            color: var(--light-text);
            display: block;
            margin-bottom: 5px;
        }

        .app-value {
            display: block;
            text-align: center;
            font-size: 18px;
            color: var(--main-color);
            font-weight: bold;
        }

        .cover-footer {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 20px 0 10px;
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
        }

        /* ===== الأنماط الداخلية ===== */
        .internal-title {
            border-bottom: 3px solid var(--main-color);
            padding-bottom: 12px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            direction: rtl;
        }

        .internal-title h3 {
            font-size: 20px;
            color: var(--main-color);
            font-weight: 800;
            margin: 0;
            text-align: right;
        }

        .internal-title .project-code-light {
            font-size: 13px;
            color: #8ba888;
            text-align: left;
        }

        /* ===== توحيد تنسيق جميع الجداول ===== */
        .unified-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12.5px;
            background: #fff;
            border: 1px solid #b8c5b8;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            direction: rtl;
        }

        .unified-table th {
            background: linear-gradient(180deg, #2c5f2d 0%, #3a7a3b 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 12.5px;
            padding: 9px 10px;
            text-align: center;
            border: 1px solid #245025;
            letter-spacing: 0.2px;
        }

        .unified-table td {
            border: 1px solid #d1d9d1;
            padding: 7px 10px;
            text-align: right;
            vertical-align: middle;
            font-size: 12.5px;
            color: #2d3748;
            line-height: 1.5;
        }

        .unified-table tbody tr:nth-child(even) {
            background-color: #f7faf5;
        }

        .section-header {
            margin-top: 18px;
            margin-bottom: 14px;
            border-right: 5px solid var(--main-color);
            border-left: none;
            padding-right: 15px;
            padding-left: 0;
            direction: rtl;
        }

        .section-header h2 {
            font-size: 20px;
            color: #1f2937;
            text-align: right;
        }

        /* ===== البيانات الأساسية في صف واحد ===== */
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 12px;
            margin-bottom: 15px;
            background-color: #f9fbf8;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            direction: rtl;
            text-align: right;
        }

        .data-item {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #edf2f7;
            padding: 10px 5px;
            min-height: 38px;
            direction: rtl;
        }

        .data-item:last-child {
            border-bottom: none;
        }

        .data-label {
            font-size: 13px;
            color: #5b6e8c;
            font-weight: 700;
            min-width: 160px;
            flex-shrink: 0;
            text-align: right;
        }

        .data-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e2a3e;
            text-align: left;
            flex: 1;
        }

        .summary-card {
            background-color: #f9fbf8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
            direction: rtl;
        }

        .summary-card h4,
        .summary-card h6,
        .summary-card p {
            margin: 0;
            padding: 0;
            line-height: 1.7;
            text-align: right;
            direction: rtl;
        }

        .summary-card h4 {
            color: var(--main-color);
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 8px;
        }

        .summary-card p {
            color: #2d3748;
            font-size: 13.5px;
            text-align: justify;
            text-align-last: right;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            display: inline-block;
        }

        .status-final {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .status-draft {
            background-color: #feebc8;
            color: #744210;
        }

        .hierarchy-label {
            font-weight: 800;
            color: var(--main-color);
            margin-bottom: 8px;
            font-size: 16px;
            text-align: right;
        }

        .page-number-footer {
            position: absolute;
            bottom: 15px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 12px;
            color: #5b6e8c;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-weight: 500;
            z-index: 10;
        }

        .compact-table {
            font-size: 11.5px;
        }

        .compact-table th,
        .compact-table td {
            padding: 6px 7px;
            font-size: 11.5px;
        }

        .detail-block {
            background-color: #f9fbf8;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
            border-right: 4px solid var(--main-color);
            border-left: none;
            direction: rtl;
        }

        .detail-block h6 {
            font-size: 14px;
            color: var(--main-color);
            font-weight: 700;
            margin-bottom: 6px;
            text-align: right;
        }

        .detail-block p {
            font-size: 13.5px;
            color: #2d3748;
            line-height: 1.8;
            text-align: justify;
            text-align-last: right;
            margin: 0;
            direction: rtl;
        }

        .basic-data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 10px;
            direction: rtl;
        }

        .basic-data-item {
            padding: 4px 6px;
            display: flex;
            flex-direction: column;
            text-align: right;
            line-height: 1.4;
        }

        .basic-data-label {
            font-size: 14px;
            color: #666;
        }

        .basic-data-value {
            font-size: 14px;
            font-weight: 500;
            color: #222;
        }

        /* ===== Print Controls ===== */
        .print-controls {
            position: fixed;
            top: 20px;
            left: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 220px;
            direction: rtl;
        }

        .print-controls button {
            background: var(--main-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .print-controls button:hover {
            background: #1e401f;
        }

        .print-controls .secondary-btn {
            background: #e2e8f0;
            color: #1f2937;
        }

        .print-controls .secondary-btn:hover {
            background: #cbd5e1;
        }

        .print-controls .btn-group {
            display: flex;
            gap: 5px;
        }

        .print-controls .btn-group button {
            flex: 1;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .page {
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }

            .cover-header-bg {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .unified-table {
                page-break-inside: avoid;
            }

            .print-controls {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- لوحة التحكم في الطباعة -->
    @if($letterhead)
        <div class="no-print" style="max-width: 210mm; margin: 15px auto 5px; padding: 0 10px;">
            <x-letterhead-selector :currentLetterhead="$letterhead" />
        </div>
    @endif

    <!-- ========== الصفحة 1: الغلاف ========== -->
    <div class="page">
        <div class="cover-header-bg"></div>
        <div class="cover-content">
            @foreach(explode("\n", trim($headerRightText)) as $titleLine)
                @if(trim($titleLine))
                    <div class="gov-title">{{ trim($titleLine) }}</div>
                @endif
            @endforeach
            <div class="logo-container">
                <div class="logo-wrapper">
                    <img src="{{ $resolvedLogoUrl }}" alt="الشعار الرسمي">
                </div>
            </div>
            <span class="doc-badge">وثيقة اعتماد مشروع</span>
            <div class="project-details-bottom">
                <div class="project-name-main">{{ $project->project_name ?? '' }}</div>
                <div class="applicant-info-card">
                    <span class="app-label">الجهة المقدمة للمشروع</span>
                    <span class="app-value">{{ $project->creator_entity_name ?? '' }}</span>
                </div>
                <div style="font-size: 14px; color: #5b6e8c; margin-top: 15px; font-weight: bold; text-align: center;">
                    بواسطة: {{ optional($project->createdBy)->name ?? 'غير محدد' }} | بتاريخ: {{ $project->created_at ? $project->created_at->format('Y-m-d') : '-' }}
                </div>
                <div class="cover-footer">
                    <div style="text-align: right;">
                        <div style="font-size: 14px; color: #4a5b6e; font-weight: bold;">الرقم المرجعي</div>
                        <div style="font-weight: 800; font-size: 28px; color: var(--main-color);">
                            {{ $project->form_number ?? '' }}
                        </div>
                    </div>
                    <div class="qr-container"
                        style="padding: 8px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <img src="{{ $qrCodeBase64 }}" alt="QR Code" style="width:95px; height:95px; display:block;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== الصفحة 2: البيانات الأساسية + النطاق الجغرافي ========== -->
    <div class="page internal-padding">
        <div class="internal-title">
            <h3>بيانات المشروع الأساسية والنطاق الجغرافي</h3>
            <span class="project-code-light">{{ $project->form_number ?? '' }}</span>
        </div>

        <div class="section-header">
            <h2>أولاً: البيانات الأساسية</h2>
        </div>

        <div
            style="background:#f9fbf8;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;margin-bottom:20px;">
            <div class="basic-data-grid">
                <div class="basic-data-item">
                    <span class="basic-data-label">البرنامج</span>
                    <div class="basic-data-value">{{ $project->program->name ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">المجال</span>
                    <div class="basic-data-value">{{ $project->domain->name ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">المجال الفرعي</span>
                    <div class="basic-data-value">{{ $project->subdomain->name ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">نوع التدخل</span>
                    <div class="basic-data-value">{{ $project->intervention->name ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">الأولوية</span>
                    <div class="basic-data-value">{{ $project->priority->name ?? $project->priority ?? 'غير محدد' }}
                    </div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">عدد المستفيدين</span>
                    <div class="basic-data-value">
                        {{ $project->number_of_beneficiaries ? number_format($project->number_of_beneficiaries) : 'غير محدد' }}
                    </div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">تاريخ البداية (ميلادي)</span>
                    <div class="basic-data-value">
                        {{ $project->start_date_gregorian ? \Carbon\Carbon::parse($project->start_date_gregorian)->format('Y-m-d') : 'غير محدد' }}
                    </div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">تاريخ النهاية (ميلادي)</span>
                    <div class="basic-data-value">
                        {{ $project->end_date_gregorian ? \Carbon\Carbon::parse($project->end_date_gregorian)->format('Y-m-d') : 'غير محدد' }}
                    </div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">تاريخ البداية (هجري)</span>
                    <div class="basic-data-value">{{ $project->start_date_hijri ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">تاريخ النهاية (هجري)</span>
                    <div class="basic-data-value">{{ $project->end_date_hijri ?? 'غير محدد' }}</div>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2>ثانياً: النطاق الجغرافي للمشروع</h2>
        </div>
        <table class="unified-table">
            <thead>
                <tr>
                    <th style="width:40px;">م</th>
                    <th>المحافظة</th>
                    <th>المديرية</th>
                    <th>العزلة</th>
                    <th>القرية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->locations ?? [] as $index => $loc)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>{{ $loc->governorate->name ?? 'جميع المحافظات' }}</td>
                        <td>{{ $loc->directorate->name ?? 'جميع المديريات' }}</td>
                        <td>{{ $loc->subArea->name ?? $loc->area ?? 'جميع العزل' }}</td>
                        <td>{{ $loc->village->name ?? 'جميع القرى' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;">لا توجد مواقع مسجلة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ========== الصفحة 3: تفاصيل المشروع ========== -->
    <div class="page internal-padding">
        <div class="internal-title">
            <h3>تفاصيل المشروع</h3>
            <span class="project-code-light">{{ $project->form_number ?? '' }}</span>
        </div>
        <div class="section-header">
            <h2>ثالثا: تفاصيل المشروع </h2>
        </div>
        <div class="section-header">
            <h6>معلومات الخطة</h6>
        </div>
        <div class="data-grid" style="grid-template-columns: 1fr;">
            <div class="data-item">
                <span class="data-label">هل المشروع ضمن الخطة؟ </span>
                <span class="data-value">{{ optional($project->detail)->is_part_of_plan ? 'نعم' : 'لا' }}</span>
            </div>
        </div>

        <div class="section-header">
            <h4>ملخص المشروع</h4>
        </div>
        <div class="detail-block">
            <p>{{ optional($project->detail)->project_summary ?? '---' }}</p>
        </div>

        <div class="section-header">
            <h4>مقدمة المشروع</h4>
        </div>
        <div class="detail-block">
            <p>{{ optional($project->detail)->project_introduction ?? '---' }}</p>
        </div>

        <div class="section-header">
            <h4>المشكلة والمبررات</h4>
        </div>
        <div class="detail-block">
            <p>{{ optional($project->detail)->problem_and_justification ?? '---' }}</p>
        </div>

        <div class="section-header">
            <h4>مكونات المشروع</h4>
        </div>
        <div class="detail-block">
            <p>{{ optional($project->detail)->project_components ?? '---' }}</p>
        </div>

        <div class="section-header">
            <h4> الأثر المتوقع</h4>
        </div>
        <div class="detail-block">
            <p>{{ optional($project->detail)->expected_impact ?? '---' }}</p>
        </div>
    </div>

    <!-- ========== الصفحة 4: الأهداف ========== -->
    <div class="page internal-padding">
        <div class="internal-title">
            <h3>الأهداف والمؤشرات</h3>
        </div>
        <div class="section-header">
            <h2>ثامناً: الأهداف</h2>
        </div>
        @if(($project->mainObjectives ?? [])->count())
            <div
                style="background:#fef9e6; padding:16px; border-right:5px solid #2c5f2d; margin-bottom:24px; border-radius:6px; direction:rtl;">
                <span class="hierarchy-label">الهدف العام:</span>
                @foreach($project->mainObjectives as $obj)
                    <div style="font-weight:700; margin-top:6px; text-align:right;">- {{ $obj->objective }}</div>
                @endforeach
            </div>
        @endif

        @forelse($project->specialObjectives ?? [] as $i => $goal)
            @php
                $allResults = $goal->results ?? collect();
                $goalRowSpan = 0;

                foreach ($allResults as $r) {
                    $goalRowSpan += max(($r->outputs ?? collect())->count(), 1);
                }

                if ($goalRowSpan === 0)
                    $goalRowSpan = 1;
            @endphp

            <div style="margin-bottom:18px;
                                                                                    border:1px solid #b8c5b8;
                                                                                    border-radius:6px;
                                                                                    page-break-inside:avoid;
                                                                                    font-family:Tahoma, Arial;">

                {{-- الهدف --}}
                <div style="background:linear-gradient(180deg,#2c5f2d,#3a7a3b);
                                                                                        color:#fff;
                                                                                        padding:8px 12px;
                                                                                        font-size:12px;
                                                                                        line-height:1.5;
                                                                                        text-align:center;">
                    <strong>الهدف الخاص {{ $i + 1 }}:</strong>
                    {{ $goal->objective }}

                    <div style="font-size:11px;
                                                                                            margin-top:4px;
                                                                                            opacity:0.95;
                                                                                            text-align:center;">
                        الوزن: {{ $goal->objective_weight ?? '-' }}%
                        | قيمة المؤشر: {{ $goal->indicator_type ?? $goal->indicator ?? '-' }}
                        | القيمة المستهدفة: {{ $goal->target_value ?? '-' }}
                        | وحدة القياس: {{ $goal->measurement_unit ?? '-' }}
                    </div>
                </div>

                {{-- الجدول --}}
                <table style="width:100%;
                                                                                          border-collapse:collapse;
                                                                                          font-size:11px;
                                                                                          table-layout:fixed;
                                                                                          text-align:center;">

                    <thead>
                        <tr style="background:#f2f2f2;">
                            <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">
                                النتيجة </th>
                            <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">القيمة
                                المستهدفة</th>
                            <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">نوع
                                المؤشر</th>
                            <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">وحدة
                                المؤشر</th>
                            <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">المخرج
                            </th>
                            <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">القيمة
                                المستهدفة</th>
                            <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">نوع
                                المؤشر</th>
                            <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">وحدة
                                المؤشر</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($allResults as $result)
                            @php
                                $outputs = $result->outputs ?? collect();
                                $resultRowSpan = max($outputs->count(), 1);
                            @endphp

                            @forelse($outputs as $oIndex => $output)
                                <tr>

                                    {{-- النتائج (دمج + توسيط مثل Excel) --}}
                                    @if($oIndex === 0)
                                        <td rowspan="{{ $resultRowSpan }}"
                                            style="border:1px solid #ccc;
                                                                                                                                                                                                                                                                                               padding:5px;
                                                                                                                                                                                                                                                                                               text-align:center;
                                                                                                                                                                                                                                                                                               vertical-align:middle;">
                                            {{ $result->result_name }}
                                        </td>

                                        <td rowspan="{{ $resultRowSpan }}"
                                            style="border:1px solid #ccc;
                                                                                                                                                                                                                                                                                               padding:5px;
                                                                                                                                                                                                                                                                                               text-align:center;
                                                                                                                                                                                                                                                                                               vertical-align:middle;">
                                            {{ $result->target_value ?? '-' }}
                                        </td>

                                        <td rowspan="{{ $resultRowSpan }}"
                                            style="border:1px solid #ccc;
                                                                                                                                                                                                                                                                                               padding:5px;
                                                                                                                                                                                                                                                                                               text-align:center;
                                                                                                                                                                                                                                                                                               vertical-align:middle;">
                                            {{ $result->indicator_type ?? '-' }}
                                        </td>

                                        <td rowspan="{{ $resultRowSpan }}"
                                            style="border:1px solid #ccc;
                                                                                                                                                                                                                                                                                               padding:5px;
                                                                                                                                                                                                                                                                                               text-align:center;
                                                                                                                                                                                                                                                                                               vertical-align:middle;">
                                            {{ $result->indicator_unit ?? '-' }}
                                        </td>
                                    @endif

                                    {{-- المخرجات --}}
                                    <td
                                        style="border:1px solid #ccc;
                                                                                                                                                                                                                               padding:5px;
                                                                                                                                                                                                                               text-align:center;
                                                                                                                                                                                                                               vertical-align:middle;">
                                        {{ $output->output }}
                                    </td>

                                    <td
                                        style="border:1px solid #ccc;
                                                                                                                                                                                                                               padding:5px;
                                                                                                                                                                                                                               text-align:center;
                                                                                                                                                                                                                               vertical-align:middle;">
                                        {{ $output->target_value ?? '-' }}
                                    </td>

                                    <td
                                        style="border:1px solid #ccc;
                                                                                                                                                                                                                               padding:5px;
                                                                                                                                                                                                                               text-align:center;
                                                                                                                                                                                                                               vertical-align:middle;">
                                        {{ $output->indicator_type ?? '-' }}
                                    </td>

                                    <td
                                        style="border:1px solid #ccc;
                                                                                                                                                                                                                               padding:5px;
                                                                                                                                                                                                                               text-align:center;
                                                                                                                                                                                                                               vertical-align:middle;">
                                        {{ $output->indicator_unit ?? '-' }}
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">
                                        {{ $result->result_name }}
                                    </td>
                                    <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">
                                        {{ $result->target_value ?? '-' }}
                                    </td>
                                    <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">
                                        {{ $result->indicator_type ?? '-' }}
                                    </td>
                                    <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">
                                        {{ $result->indicator_unit ?? '-' }}
                                    </td>

                                    <td
                                        style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle; color:#999;">
                                        -</td>
                                    <td
                                        style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle; color:#999;">
                                        -</td>
                                    <td
                                        style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle; color:#999;">
                                        -</td>
                                    <td
                                        style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle; color:#999;">
                                        -</td>
                                </tr>
                            @endforelse

                        @empty
                            <tr>
                                <td colspan="8"
                                    style="text-align:center;
                                                                                                                                                               padding:10px;
                                                                                                                                                               color:#777;
                                                                                                                                                               border:1px solid #ccc;">
                                    لا توجد نتائج مسجلة
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>

            </div>

        @empty
            <div style="text-align:center;
                                                                            padding:15px;
                                                                            color:#999;
                                                                            font-size:12px;">
                لا توجد أهداف مسجلة
            </div>
        @endforelse
    </div>

    <!-- ========== الصفحة 5: الجهات (المخاطر + المشرفة + المنفذة) ========== -->
    <div class="page internal-padding">
        <div class="internal-title">
            <h3>المخاطر والجهات المعنية</h3>
        </div>

        <div class="section-header">
            <h2>تاسعاً: المخاطر والافتراضات</h2>
        </div>
        <table class="unified-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>الخطر</th>
                    <th style="width:90px;">المستوى</th>
                    <th>إجراءات التخفيف</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->risks ?? [] as $i => $risk)
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        <td>{{ $risk->risk }}</td>
                        <td>{{ $risk->risk_rate ?? '-' }}</td>
                        <td>{{ $risk->proposed_solution }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;">لا توجد مخاطر مسجلة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-header">
            <h2>عاشراً: الجهات المشرفة</h2>
        </div>
        <table class="unified-table">
            <thead>
                <tr>
                    <th style="width:90px;">النوع</th>
                    <th>الجهة</th>
                    <th>الجهة الأب</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->supervisingAuthorities ?? [] as $entity)
                    <tr>
                        <td style="text-align:center;">{{ $entity->authority_type == 'internal' ? 'داخلية' : 'خارجية' }}
                        </td>
                        <td>{{ $entity->authority?->agency_name ?? ($entity->relationLoaded('internalEntity') ? ($entity->internalEntity?->name ?? '-') : '-') }}</td>
                        <td style="text-align:center;">-</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;">لا توجد جهات مشرفة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-header">
            <h2>الحادي عشر: الجهات المنفذة</h2>
        </div>
        <table class="unified-table">
            <thead>
                <tr>
                    <th style="width:90px;">النوع</th>
                    <th>الجهة</th>
                    <th>الجهة الأم</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->implementingEntities ?? [] as $entity)
                    <tr>
                        <td style="text-align:center;">{{ $entity->authority_type == 'internal' ? 'داخلية' : 'خارجية' }}
                        </td>
                        <td>{{ $entity->authority?->agency_name ?? ($entity->relationLoaded('internalEntity') ? ($entity->internalEntity?->name ?? '-') : '-') }}</td>
                        <td style="text-align:center;">-</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;">لا توجد جهات منفذة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-header">
            <h2>الجهات المشاركة والمستفيدة</h2>
        </div>
        <table class="unified-table">
            <thead>
                <tr>
                    <th style="width:90px;">الدور</th>
                    <th>الجهة</th>
                    <th>الجهة الأم</th>
                </tr>
            </thead>
            <tbody>
                @foreach($project->participatingEntities ?? [] as $entity)
                    <tr>
                        <td style="text-align:center; font-weight:bold; color:#b45309;">مشاركة</td>
                        <td>{{ optional($entity->authority)->agency_name ?? '-' }}</td>
                        <td style="text-align:center;">{{ optional($entity->parent)->agency_name ?? '-' }}</td>
                    </tr>
                @endforeach
                @foreach($project->beneficiaryEntities ?? [] as $entity)
                    <tr>
                        <td style="text-align:center; font-weight:bold; color:#be123c;">مستفيدة</td>
                        <td>{{ optional($entity->authority)->agency_name ?? '-' }}</td>
                        <td style="text-align:center;">{{ optional($entity->parent)->agency_name ?? '-' }}</td>
                    </tr>
                @endforeach
                @if(count($project->participatingEntities ?? []) == 0 && count($project->beneficiaryEntities ?? []) == 0)
                    <tr>
                        <td colspan="3" style="text-align:center;">لا توجد جهات مشاركة أو مستفيدة</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- ========== الصفحة 6: الأنشطة ========== -->
    <div class="page internal-padding">
        <div class="internal-title">
            <h3>الأنشطة</h3>
        </div>

        <div class="section-header">
            <h2>الثاني عشر: الأنشطة المبدئية</h2>
        </div>
        @php
            $preliminaryTotal = collect($project->preliminaryActivities ?? [])->flatMap(fn($a) => $a->procedures ?? [])->flatMap(fn($p) => $p->costs ?? [])->sum('total');
        @endphp
        <table class="unified-table compact-table">
            <thead>
                <tr>
                    <th style="width:35px;">#</th>
                    <th>النشاط</th>
                    <th>الإجراء</th>
                    <th>البند المالي</th>
                    <th>الوحدة</th>
                    <th style="width:55px;">الكمية</th>
                    <th style="width:75px;">السعر</th>
                    <th style="width:85px;">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->preliminaryActivities ?? [] as $act)
                    @foreach($act->procedures ?? [] as $proc)
                        @foreach($proc->costs ?? [] as $cost)
                            <tr>
                                <td style="text-align:center; vertical-align:top;">{{ $loop->parent->parent->iteration ?? '' }}</td>
                                <td style="vertical-align:top;">{{ $act->name }}</td>
                                <td>
                                    <div style="font-weight:bold;">{{ $proc->procedure_name }}</div>
                                    <div style="font-size:10px; color:#5b6e8c; margin-top:4px;">
                                        الأهمية: {{ $proc->weight ?? '-' }}% | المدة: {{ $proc->duration_days ?? '-' }} يوم | وسائل التحقق: {{ $proc->verification_means ?? '-' }}
                                    </div>
                                </td>
                                <td style="vertical-align:top;">{{ $cost->financialItem->name ?? '-' }}</td>
                                <td>{{ $cost->unit->unit_name ?? '-' }}</td>
                                <td style="text-align:center;">{{ $cost->quantity }}</td>
                                <td style="text-align:center;">{{ number_format($cost->amount) }}</td>
                                <td style="text-align:center;">{{ number_format($cost->total) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;">لا توجد أنشطة مبدئية</td>
                    </tr>
                @endforelse
            </tbody>
            @if($preliminaryTotal > 0)
                <tfoot>
                    <tr style="background-color: #f2f7f2; font-weight: bold;">
                        <td colspan="7" style="text-align: left; font-weight: bold; padding: 7px 10px;">الإجمالي:</td>
                        <td style="text-align: center; font-weight: bold; color: #b91c1c; padding: 7px 10px;">
                            {{ number_format($preliminaryTotal) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <div class="section-header">
            <h2>الثالث عشر: أنشطة التنفيذ</h2>
        </div>
        @forelse($project->executiveActivities ?? [] as $act)
            @php
                $activityTotal = 0;
                foreach ($act->actions ?? [] as $action) {
                    foreach ($action->costs ?? [] as $cost) {
                        $activityTotal += $cost->total;
                    }
                }
            @endphp
            <div style="border:1px solid #b8c5b8; border-radius:8px; margin-bottom:16px; overflow:hidden; direction:rtl;">
                <div
                    style="background: linear-gradient(180deg, #2c5f2d 0%, #3a7a3b 100%); color:white; padding:10px 14px; font-weight:700; font-size:13px; text-align:right;">
                    {{ $act->name }}
                </div>
                <table class="unified-table compact-table" style="margin:0; border:none; box-shadow:none; border-radius:0;">
                    <thead>
                        <tr>
                            <th>الإجراء</th>
                            <th>المنفذ</th>
                            <th>البند</th>
                            <th>الوحدة</th>
                            <th style="width:55px;">الكمية</th>
                            <th style="width:75px;">السعر</th>
                            <th style="width:85px;">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($act->actions ?? [] as $action)
                            @foreach($action->costs ?? [] as $cost)
                                <tr>
                                    <td>
                                        <div style="font-weight:bold;">{{ $action->action }}</div>
                                        <div style="font-size:10px; color:#5b6e8c; margin-top:4px;">
                                            الأهمية: {{ $action->weight ?? '-' }}% | المدة: {{ $action->duration_days ?? '-' }} يوم | وسائل التحقق: {{ $action->verification_means ?? '-' }}
                                        </div>
                                    </td>
                                    <td style="vertical-align:top;">{{ optional($action->assignedEntities->first())->name ?? '-' }}</td>
                                    <td>{{ $cost->financialItem->name ?? '-' }}</td>
                                    <td>{{ $cost->unit->unit_name ?? '-' }}</td>
                                    <td style="text-align:center;">{{ $cost->quantity }}</td>
                                    <td style="text-align:center;">{{ number_format($cost->amount) }}</td>
                                    <td style="text-align:center;">{{ number_format($cost->total) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    @if($activityTotal > 0)
                        <tfoot>
                            <tr style="background-color: #f2f7f2; font-weight: bold;">
                                <td colspan="6" style="text-align: left; font-weight: bold; padding: 6px 7px;">إجمالي النشاط:
                                </td>
                                <td style="text-align: center; font-weight: bold; color: #b91c1c; padding: 6px 7px;">
                                    {{ number_format($activityTotal) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @empty
            <div style="color:#aaa; text-align:center;">لا توجد أنشطة تنفيذية</div>
        @endforelse

        @if(isset($executiveTotal) && $executiveTotal > 0)
            <div class="summary-card"
                style="text-align:center; margin-top: 15px; padding: 12px; background-color: #f2f7f2; border: 1px solid #b8c5b8;">
                <span class="data-label" style="font-size: 14px; font-weight: bold; color: #2c5f2d;">إجمالي أنشطة
                    التنفيذ:</span>
                <span class="data-value" style="color:#b91c1c; font-size:18px; font-weight: bold; margin-right: 10px;">
                    {{ number_format($executiveTotal) }} ريال
                </span>
            </div>
        @endif
    </div>

    <!-- ========== الصفحة 7: التمويلات والاعتماد ========== -->
    <div class="page internal-padding">
        <div class="internal-title">
            <h3>التمويل والاعتماد النهائي</h3>
        </div>

        <div class="section-header">
            <h2>الرابع عشر: مصادر التمويل</h2>
        </div>
        <table class="unified-table">
            <thead>
                <tr>
                    <th style="width:35px;">#</th>
                    <th>مصدر التمويل</th>
                    <th>الجهة الممولة</th>
                    <th>النوع</th>
                    <th style="width:100px;">المبلغ</th>
                    <th style="width:70px;">النسبة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->financings ?? [] as $i => $f)
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        <td>{{ $f->fundingSource->name ?? '-' }}</td>
                        <td>{{ $f->authority->agency_name ?? '-' }}</td>
                        <td>{{ $f->financingType->name ?? '-' }}</td>
                        <td style="text-align:center;">{{ number_format($f->financing_amount) }}</td>
                        <td style="text-align:center;">{{ $f->financing_percentage }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">لا توجد بيانات تمويل</td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($project->financings ?? []) > 0)
                @php
                    $financingTotalAmount = 0;
                    $financingTotalPercentage = 0;
                    foreach ($project->financings ?? [] as $f) {
                        $financingTotalAmount += $f->financing_amount;
                        $financingTotalPercentage += $f->financing_percentage;
                    }
                @endphp
                <tfoot>
                    <tr style="background-color: #f2f7f2; font-weight: bold;">
                        <td colspan="4" style="text-align: left; font-weight: bold; padding: 8px 10px;">الإجمالي:</td>
                        <td style="text-align: center; font-weight: bold; color: #b91c1c; padding: 8px 10px;">
                            {{ number_format($financingTotalAmount) }}</td>
                        <td style="text-align: center; font-weight: bold; color: #2c5f2d; padding: 8px 10px;">
                            {{ $financingTotalPercentage }}%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
        @if($project->cost)
            <div class="summary-card" style="text-align:center;">
                <span class="data-label">إجمالي التكلفة المعتمدة</span>
                <div class="data-value" style="color:#b91c1c; font-size:22px; margin-top:8px;">
                    {{ number_format($project->cost->total_cost) }} ريال
                </div>
                <div style="color:#5b6e8c; font-size:15px; margin-top:5px; font-weight:600;">
                    (فقط {{ numberToArabicText($project->cost->total_cost) }} ريال لا غير)
                </div>
            </div>
        @endif

        <div class="section-header">
            <h2>الخامس عشر: الوثائق والمرفقات</h2>
        </div>
        <table class="unified-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>اسم الوثيقة</th>
                    <th style="width:140px;">تاريخ الرفع</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->documents ?? [] as $i => $doc)
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        <td>{{ $doc->document_name }}</td>
                        <td style="text-align:center;">{{ $doc->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;">لا توجد وثائق مرفقة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:50px; display:grid; grid-template-columns:1fr 1fr; gap:40px; text-align:center;">
            <div>
                <div style="font-weight:800;">ختم وتوقيع الجهة المقدمة</div>
                <div style="border-bottom:2px dotted #aaa; width:180px; margin:10px auto;"></div>
            </div>
            <div>
                <div style="font-weight:800;">اعتماد اللجنة الزراعية والسمكية العليا</div>
                <div style="border-bottom:2px dotted #aaa; width:180px; margin:10px auto;"></div>
            </div>
        </div>
        <div
            style="margin-top:auto; text-align:center; font-size:11px; border-top:1px solid #e2e8f0; padding-top:18px;">
            صادر عن النظام الإلكتروني للجنة الزراعية والسمكية العليا - {{ date('Y') }} | تاريخ الطباعة:
            {{ now()->format('Y-m-d') }}
        </div>
    </div>

    <script>
        (function () {
            let pages = document.querySelectorAll('.page');
            let total = pages.length;
            pages.forEach((page, idx) => {
                if (page.querySelector('.page-number-footer')) return;
                let footer = document.createElement('div');
                footer.className = 'page-number-footer';
                footer.innerText = `صفحة ${idx + 1} من ${total}`;
                page.appendChild(footer);
            });
        })();

        let currentZoom = 100;
        function changeFontSize(step) {
            currentZoom += step * 5;
            document.querySelectorAll('.page').forEach(page => {
                page.style.zoom = currentZoom + '%';
            });
        }

        let isEditMode = false;
        function toggleEditMode() {
            isEditMode = !isEditMode;
            document.querySelectorAll('.page').forEach(page => {
                page.contentEditable = isEditMode;
                if (isEditMode) {
                    page.style.border = "2px dashed #97bc62";
                    page.style.outline = "none";
                } else {
                    page.style.border = "none";
                }
            });

            const btn = document.getElementById('editBtn');
            if (isEditMode) {
                btn.innerHTML = "✅ حفظ التعديلات";
                btn.style.background = "#97bc62";
                btn.style.color = "white";
            } else {
                btn.innerHTML = "✏️ تفعيل وضع التعديل";
                btn.style.background = "#e2e8f0";
                btn.style.color = "#1f2937";
            }
        }
    </script>
</body>

</html>