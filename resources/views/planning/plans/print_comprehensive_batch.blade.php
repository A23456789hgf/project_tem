<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المصفوفة الشاملة للخطط (تشغيلي + تنفيذي) | {{ $printDate }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0C5B47;
            --secondary: #f97316;
            --primary-light: #e6eeec;
            --border-dark: #222;
            --text-dark: #1a1a1a;
            --text-muted: #555;
        }

        @page {
            size: A4 landscape;
            margin: 10mm 5mm 10mm 5mm;
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
            line-height: 1.3;
        }

        .print-container {
            width: 100%;
            margin: 0 auto;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--secondary);
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .header-center {
            text-align: center;
        }

        .logo {
            max-height: 60px;
            margin-bottom: 2px;
        }

        h1 {
            font-size: 14pt;
            color: var(--primary);
            margin: 2px 0;
            font-weight: 800;
        }

        .batch-info {
            font-size: 9pt;
            font-weight: 700;
            color: var(--text-muted);
        }

        /* --- Table Styling --- */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th {
            background-color: #1e293b !important;
            color: white !important;
            border: 1px solid #000;
            padding: 5px 2px;
            font-size: 7.5pt;
            text-align: center;
        }

        td {
            border: 1px solid #444;
            padding: 3px 2px;
            font-size: 7pt;
            text-align: center;
            vertical-align: top;
            word-wrap: break-word;
        }

        .project-row {
            background-color: #f8fafc !important;
            font-weight: 700;
        }

        .activity-row {
            background-color: #f1f5f9 !important;
        }

        .action-row {
            background-color: #fff !important;
        }

        .text-end {
            text-align: right;
            padding-right: 5px;
        }

        .fw-bold {
            font-weight: 700;
        }

        .text-primary {
            color: var(--primary);
        }

        .text-secondary {
            color: var(--secondary);
        }

        .weight-badge {
            display: inline-block;
            background: #e2e8f0;
            padding: 1px 3px;
            border-radius: 3px;
            font-weight: 700;
            font-size: 6.5pt;
        }

        .status-badge {
            font-size: 6.5pt;
            padding: 1px 3px;
            border-radius: 3px;
            border: 0.5px solid #ccc;
        }

        .footer-info {
            margin-top: 15px;
            text-align: center;
            font-size: 7pt;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }

        .floating-actions {
            position: fixed;
            bottom: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
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

        .btn-close {
            background: #555;
            color: white;
        }
    </style>
</head>

<body>
    <div class="print-container">
        <div class="header-section">
            <div class="header-right" style="
    display: flex;
    flex-direction: column;
    justify-content: center; /* محاذاة عمودية */
    align-items: center;    /* محاذاة أفقية */
    text-align: center;     /* لتوسيط النصوص داخل الأعمدة */
    height: 150px;          /* مثال: يمكن تغييره حسب الحاجة */
">
                الجمهورية اليمنية<br>
                وزارة الزراعة والثروة السمكية والموارد المائية<br>
                {{ $plan->submitting_organization_name ?? '-' }}
            </div>
            <div class="header-center">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo"><br>
                <h3>مصفوفة الخطة العامة لمشاريع العام 1447</h 3>

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

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">الجهة المقدمة</span>
                <span class="info-value">{{ $plan->submitting_organization_name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">فترة الخطة</span>
                <span class="info-value">
                    @if($plan->start_date_g && $plan->end_date_g)
                        من {{ \Carbon\Carbon::parse($plan->start_date_g)->format('Y-m-d') }} إلى {{ \Carbon\Carbon::parse($plan->end_date_g)->format('Y-m-d') }}
                    @elseif(is_object($plan->priority))
                        {{ $plan->priority->priority ?? $plan->priority->name ?? '-' }}
                    @else
                        {{ $plan->priority ?? '-' }}
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">تاريخ الإصدار</span>
                <span class="info-value">{{ now()->format('Y-m-d') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">إجمالي الميزانية</span>
                <span class="info-value">{{ number_format($plan->projects->sum('cost'), 2) }}</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="3%">م</th>
                    <th width="18%">اسم المشروع</th>
                    <th width="8%">الأهمية</th>
                    <th width="7%">الحالة</th>
                    <th width="15%">المؤشرات</th>
                    <th width="15%">المخرجات</th>
                    <th width="8%">المستهدف</th>
                    <th width="10%">التكلفة التقديرية</th>
                    <th width="12%">الجهة المشاركة</th>
                    <th width="6%">التمويل</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan->projects as $index => $project)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="project-name">
                            <div style="font-weight:bold; margin-bottom: 5px;">{{ $project->name }}</div>
                            @if($project->goals && $project->goals->count() > 0)
                                <div style="font-weight: normal; font-size: 8pt; margin-top: 8px; margin-bottom: 8px; border-top: 1px dashed #ccc; padding-top: 5px; text-align: right;">
                                    <div style="font-weight: bold; color: var(--secondary); margin-bottom: 3px;">الأهداف والنتائج والمخرجات:</div>
                                    @foreach($project->goals as $goal)
                                        <div style="margin-bottom: 6px; background-color: #fafafa; border: 1px solid #e2e8f0; border-radius: 3px; padding: 4px;">
                                            <div style="font-weight: bold; color: #1e293b; border-bottom: 0.5px solid #eee; padding-bottom: 2px;">
                                                [هدف] {{ $goal->specific_goal }} <span style="color: var(--primary);">({{ $goal->weight }}%)</span>
                                            </div>
                                            @if(!empty($goal->results_json) && is_array($goal->results_json))
                                                <div style="padding-top: 3px; padding-right: 10px;">
                                                    @foreach($goal->results_json as $result)
                                                        <div style="font-size: 7.5pt; color: #334155; margin-bottom: 2px;">
                                                            ↳ <strong style="color: #059669;">نتيجة:</strong> {{ $result['result_name'] ?? '' }}
                                                        </div>
                                                        @if(!empty($result['outputs']) && is_array($result['outputs']))
                                                            <div style="padding-right: 15px; font-size: 7pt; color: #64748b;">
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
                            @endif
                            @if($project->activities->count() > 0)
                                <div
                                    style="font-weight: normal; font-size: 8pt; margin-top: 8px; border-top: 1px solid #eee; padding-top: 5px;">
                                    @foreach($project->activities as $act)
                                        <div
                                            style="margin-bottom: 8px; background-color: #fcfcfc; border: 1px solid #f0f0f0; border-radius: 3px; padding: 4px;">
                                            <div
                                                style="display: flex; justify-content: space-between; font-weight: bold; border-bottom: 0.5px solid #eee; padding-bottom: 2px;">
                                                <span style="color: #333;">[نشاط] {{ $act->name }}</span>
                                                <span style="color: var(--primary);">{{ $act->weight }}%</span>
                                            </div>
                                            @if($act->actions->count() > 0)
                                                <div style="padding-top: 3px;">
                                                    @foreach($act->actions as $action)
                                                        <div
                                                            style="display: flex; justify-content: space-between; font-size: 7pt; color: #555; padding: 1px 15px; border-bottom: 0.2px solid #f9f9f9;">
                                                            <span style="flex: 1;">• {{$action->name}} ({{$action->weight}}%)</span>
                                                            <span style="font-size: 6.5pt; color: #888;">
                                                                {{ $action->start_date_g ? $action->start_date_g->format('Y/m/d') : '-' }} -
                                                                {{ $action->end_date_g ? $action->end_date_g->format('Y/m/d') : '-' }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            @php
                                $labels = ['normal' => 'عادي', 'important' => 'هام', 'very_important' => 'هام جداً'];
                            @endphp
                            <span class="importance-badge imp-{{ $project->importance }}">
                                {{ $labels[$project->importance] ?? $project->importance }}
                            </span>
                        </td>
                        <td>{{ $project->status == 'new' ? 'جديد' : 'مستمر' }}</td>
                        <td style="text-align: right;">{{ $project->indicators }}</td>
                        <td style="text-align: right;">{{ $project->outputs }}</td>
                        <td>{{ number_format($project->target_value) }}<br><small>{{ $project->baseline }}</small></td>
                        <td style="font-weight: 800;">
                            {{ number_format($project->cost) }}<br>
                            <small>{{ $project->cost_type }}</small>
                        </td>
                        <td>{{ $project->participatingEntity->name }}</td>
                        <td>{{ $project->funding_availability ? 'متوفر' : 'مطلوب' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #eee !important;">
                    <td colspan="7" style="text-align: left; font-weight: 800; padding: 10px;">الإجمالي الكلي للتكاليف
                        التقديرية:</td>
                    <td colspan="3" style="font-size: 11pt; font-weight: 800; color: var(--primary);">
                        {{ number_format($plan->projects->sum('cost'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="footer-signatures">
            <div class="sig-box">
                <span>إعداد / المسؤول المختص</span>
                <div class="sig-space">{{ $plan->creator->username ?? '......................' }}</div>
            </div>
            <div class="sig-box">
                <span>مراجعة / مدير التخطيط</span>
                <div class="sig-space">......................</div>
            </div>
            <div class="sig-box">
                <span>اعتماد / رئيس الجهة</span>
                <div class="sig-space">ختم وتوقيع رسمي</div>
            </div>
        </div>

        <div
            style="margin-top: 40px; text-align: center; font-size: 8pt; color: #777; border-top: 1px solid #eee; padding-top: 10px;">
            هذه الوثيقة صادرة عن النظام الإلكتروني لإدارة الخطط - تاريخ الاستخراج: {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    <div class="floating-actions no-print">
        <button onclick="window.print()" class="btn btn-print">طباعة المصفوفة الشاملة</button>
        <button onclick="window.history.back()" class="btn btn-close">إغلاق</button>
    </div>

</body>

</html>
