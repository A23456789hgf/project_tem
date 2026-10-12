<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الخطة التنفيذية | {{ $plan->plan_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <x-letterhead-styles :letterhead="$letterhead ?? null" />

    <style>
        :root {
            --primary: {{ $letterhead?->primary_color ?? '#1e293b' }};
            --secondary: #f97316;
            --primary-light: #f1f5f9;
            --border-dark: #222;
            --text-dark: #1a1a1a;
            --text-muted: #555;
        }

        @page {
            size: A4 landscape;
            margin: 15mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background-color: white;
            }

            .no-print {
                display: none !important;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }
        }

        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            direction: rtl;
            background: #fff;
            color: var(--text-dark);
            line-height: 1.4;
        }

        .print-container {
            width: 100%;
            margin: 0 auto;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid var(--secondary);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-right,
        .header-left {
            width: 25%;
        }

        .header-center {
            width: 50%;
            text-align: center;
        }

        .header-right {
            font-weight: 700;
            font-size: 10pt;
            line-height: 1.5;
        }

        .qr-code-img {
            width: 80px;
            height: 80px;
            border: 1px solid #ddd;
            padding: 2px;
        }

        .logo {
            max-height: 70px;
            margin-bottom: 5px;
        }

        h1 {
            font-size: 16pt;
            color: var(--primary);
            margin: 5px 0;
            font-weight: 800;
        }

        .plan-number {
            font-size: 11pt;
            font-weight: 700;
            color: var(--secondary);
            margin-top: 5px;
        }

        .info-bar {
            display: flex;
            background: var(--primary-light);
            border: 1px solid #ddd;
            margin-bottom: 15px;
            border-radius: 5px;
            overflow: hidden;
        }

        .info-item {
            flex: 1;
            padding: 6px 10px;
            border-left: 1px solid #ddd;
            text-align: center;
        }

        .info-item:last-child {
            border-left: none;
        }

        .info-label {
            display: block;
            font-size: 8pt;
            color: var(--text-muted);
            font-weight: 600;
        }

        .info-value {
            display: block;
            font-size: 9.5pt;
            font-weight: 700;
        }

        /* --- Table Styling --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }

        th {
            background-color: var(--primary) !important;
            color: white !important;
            border: 1px solid #444;
            padding: 8px 4px;
            font-size: 9pt;
            text-align: center;
        }

        td {
            border: 1px solid #ccc;
            padding: 5px 4px;
            font-size: 8.5pt;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .project-row {
            background-color: #f1f5f9 !important;
            font-weight: 800;
            text-align: right !important;
        }

        .activity-cell {
            text-align: right;
            padding-right: 15px;
            font-weight: 700;
            background-color: #fafafa;
        }

        .action-cell {
            text-align: right;
            padding-right: 30px;
            font-size: 8pt;
            color: #334155;
        }

        .weight-badge {
            font-weight: 700;
            color: var(--primary);
        }

        /* --- Footer --- */
        .footer-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }

        .sig-box {
            width: 30%;
            text-align: center;
        }

        .sig-line {
            margin-top: 35px;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: 700;
            font-size: 9pt;
        }

        .floating-actions {
            position: fixed;
            bottom: 20px;
            left: 20px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 700;
            font-family: 'Cairo';
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .btn-print {
            background: var(--secondary);
            color: white;
        }

        .btn-back {
            background: #64748b;
            color: white;
            margin-right: 5px;
        }
    </style>
</head>

<body>

    <div class="print-container">
        <x-letterhead-selector :currentLetterhead="$letterhead ?? null" />
        <div class="header-section">
            <div class="header-right" style="
    display: flex;
    flex-direction: column;
    justify-content: center; /* محاذاة عمودية */
    align-items: center;    /* محاذاة أفقية */
    text-align: center;     /* لتوسيط النصوص داخل الأعمدة */
    height: 150px;          /* مثال: يمكن تغييره حسب الحاجة */
">
                @php
                    $headerRightLines = !empty($letterhead?->header_right_text) ? explode("\n", $letterhead->header_right_text) : [
                        'الجمهورية اليمنية',
                        $letterhead?->organization_name ?? 'وزارة الزراعة والثروة السمكية والموارد المائية',
                        $plan->submitting_organization_name ?? '',
                    ];
                @endphp
                @foreach($headerRightLines as $line)
                    @if(trim($line))
                        {{ trim($line) }}<br>
                    @endif
                @endforeach
                
                
            </div>
            <div class="header-center">
                @php
                    $planLogoUrl = !empty($letterhead?->logo_path) ? asset('storage/' . $letterhead->logo_path) : asset('images/logo.png');
                @endphp
                <img src="{{ $planLogoUrl }}" alt="Logo" class="logo"><br>
                <h3>مصفوفة الخطة العامة لمشاريع العام 1447</h3>

            </div>

            <div class="header-left" style="
    display: flex;
    flex-direction: column;   /* ترتيب العناصر عمودياً */
    justify-content: center;  /* محاذاة عمودية */
    align-items: center;      /* محاذاة أفقية */
    text-align: center;       /* توسيط النصوص داخل الأعمدة */
    height: 140px;            /* ارتفاع مناسب */
">
                <img src="{{ $qrCodeData }}" alt="Verification QR" class="qr-code-img"
                    style="max-width: 60px; height: auto;">

                <span style="font-size: 8pt; margin-top: 5px; font-weight: 600;"> {{ $plan->plan_number }} </span>


            </div>
        </div>

        <div class="info-bar">
            <div class="info-item">
                <span class="info-label">الجهة</span>
                <span class="info-value">{{ $plan->submitting_organization_name }}</span>
            </div>

            <div class="info-item">
                <span class="info-label">عدد المشاريع</span>
                <span class="info-value">{{ $plan->projects->count() }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">تاريخ الطباعة</span>
                <span class="info-value">{{ date('Y/m/d') }}</span>
            </div>
        </div>

        <table style="width:100%; border-collapse:collapse; text-align:center; vertical-align:middle;">
            <thead>
                <tr>
                    <th style="width: 25%; text-align:center; vertical-align:middle;">المشروع / النشاط / الإجراء</th>
                    <th style="width: 6%; text-align:center; vertical-align:middle;">الوزن %</th>
                    <th style="width: 12%; text-align:center; vertical-align:middle;">تاريخ البدء المتوقع</th>
                    <th style="width: 12%; text-align:center; vertical-align:middle;">تاريخ الانتهاء المتوقع</th>
                    <th style="width: 6%; text-align:center; vertical-align:middle;">المدة (يوم)</th>
                    <th style="width: 15%; text-align:center; vertical-align:middle;">المؤشرات / المخرجات</th>
                    <th style="width: 12%; text-align:center; vertical-align:middle;">الجهات المشاركة</th>
                    <th style="width: 12%; text-align:center; vertical-align:middle;">مصدر التمويل</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan->projects as $project)
                    <tr class="project-row">
                        <td colspan="8" style="padding: 8px 15px; text-align:center; vertical-align:middle;">
                            <div><span style="color: var(--secondary);">[مشروع]</span> <span style="font-weight:bold;">{{ $project->name }}</span></div>
                            @if($project->goals && $project->goals->count() > 0)
                                <div style="font-weight: normal; font-size: 8.5pt; margin-top: 8px; border-top: 1px dashed #ccc; padding-top: 5px; text-align: right; background: #fff; padding: 10px; border-radius: 5px;">
                                    <div style="font-weight: bold; color: var(--secondary); margin-bottom: 5px;">الأهداف والنتائج والمخرجات:</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                    @foreach($project->goals as $goal)
                                        <div style="flex: 1; min-width: 250px; margin-bottom: 6px; background-color: #fafafa; border: 1px solid #e2e8f0; border-radius: 3px; padding: 6px;">
                                            <div style="font-weight: bold; color: #1e293b; border-bottom: 0.5px solid #eee; padding-bottom: 2px;">
                                                [هدف] {{ $goal->specific_goal }} <span style="color: var(--primary);">({{ $goal->weight }}%)</span>
                                            </div>
                                            @if(!empty($goal->results_json) && is_array($goal->results_json))
                                                <div style="padding-top: 3px; padding-right: 10px;">
                                                    @foreach($goal->results_json as $result)
                                                        <div style="font-size: 8pt; color: #334155; margin-bottom: 2px;">
                                                            ↳ <strong style="color: #059669;">نتيجة:</strong> {{ $result['result_name'] ?? '' }}
                                                        </div>
                                                        @if(!empty($result['outputs']) && is_array($result['outputs']))
                                                            <div style="padding-right: 15px; font-size: 7.5pt; color: #64748b;">
                                                                @foreach($result['outputs'] as $output)
                                                                    <div>- مخرج: {{ $output['output_name'] ?? '' }}</div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @foreach($project->activities as $activity)
                        <tr>
                            <td class="activity-cell" style="text-align:center; vertical-align:middle;">
                                <span style="color: #64748b;">(نشاط)</span> {{ $activity->name }}
                            </td>
                            <td class="weight-badge" style="text-align:center; vertical-align:middle;">{{ $activity->weight }}%</td>
                            <td style="text-align:center; vertical-align:middle;">-</td>
                            <td style="text-align:center; vertical-align:middle;">-</td>
                            <td style="text-align:center; vertical-align:middle;">-</td>
                            <td rowspan="{{ $activity->actions->count() ?: 1 }}" style="font-size: 7.5pt; text-align:center; vertical-align:middle;">
                                <strong>المؤشرات:</strong> {{ $project->indicators ?: '-' }}<br>
                                <strong>المخرجات:</strong> {{ $project->outputs ?: '-' }}
                            </td>
                            <td rowspan="{{ $activity->actions->count() ?: 1 }}" style="text-align:center; vertical-align:middle;">{{ $project->participatingEntity->name }}</td>
                            <td rowspan="{{ $activity->actions->count() ?: 1 }}" style="text-align:center; vertical-align:middle;">
                                {{ $project->fundingSource->name ?? 'غير محدد' }}
                            </td>
                        </tr>
                        @foreach($activity->actions as $action)
                            <tr>
                                <td class="action-cell" style="text-align:center; vertical-align:middle;">
                                    <span style="color: #94a3b8;">•</span> {{ $action->name }}
                                </td>
                                <td style="text-align:center; vertical-align:middle;">{{ $action->weight }}%</td>
                                <td style="text-align:center; vertical-align:middle;">{{ $action->start_date_g ? $action->start_date_g->format('Y/m/d') : '-' }}</td>
                                <td style="text-align:center; vertical-align:middle;">{{ $action->end_date_g ? $action->end_date_g->format('Y/m/d') : '-' }}</td>
                                <td style="text-align:center; vertical-align:middle;">{{ $action->duration }}</td>
                                {{-- Rowspan columns handled above --}}
                                @if($loop->first)
                                    {{-- Empty placeholders if needed or just skip since they are rowspan --}}
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div class="footer-signatures">
            <div class="sig-box">
                <span>إعداد / المسؤول المختص</span>
                <div class="sig-line">{{ Auth::user()->name }}</div>
            </div>
            <div class="sig-box">
                <span>مراجعة / مدير التخطيط</span>
                <div class="sig-line">....................................</div>
            </div>
            <div class="sig-box">
                <span>اعتماد / رئيس اللجنة</span>
                <div class="sig-line">ختم التخطيط والسياسات</div>
            </div>
        </div>
    </div>

    <div class="floating-actions no-print">
        <button onclick="window.print()" class="btn btn-print">طباعة الخطة</button>
        <button onclick="window.history.back()" class="btn btn-back">رجوع</button>
    </div>

</body>

</html>
