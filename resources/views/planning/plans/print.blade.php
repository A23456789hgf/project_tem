<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الخطة التشغيلية | {{ $plan->plan_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <x-letterhead-styles :letterhead="$letterhead ?? null" />

    <style>
        :root {
            --primary: {{ $letterhead?->primary_color ?? '#0C5B47' }};
            --primary-light: #e6eeec;
            --border-dark: #222;
            --text-dark: #1a1a1a;
            --text-muted: #555;
        }

        /* --- إعدادات الطباعة والهوامش --- */
        @page {
            size: A4 landscape;
            margin: 20mm 15mm 20mm 15mm;
            /* علوي | يمين | سفلي | يسار */
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
                /* تكرار الرأس في كل صفحة */
            }
        }

        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            direction: rtl;
            background: #f5f5f5;
            color: var(--text-dark);
            line-height: 1.5;
        }

        .print-container {
            background: white;
            width: 277mm;
            /* عرض الـ A4 عرضياً ناقص الهوامش تقريباً */
            margin: 0 auto;
            padding: 5px;
        }

        /* --- الترويسة (Header) --- */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 20px;
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
            font-size: 11pt;
            line-height: 1.6;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .qr-code-img {
            width: 90px;
            height: 90px;
            border: 1px solid #ddd;
            padding: 2px;
            background: #fff;
        }

        .logo {
            max-height: 80px;
            margin-bottom: 5px;
        }

        h1 {
            font-size: 18pt;
            color: var(--primary);
            margin: 5px 0;
            font-weight: 800;
        }

        .plan-number-badge {
            background: var(--primary-light);
            color: var(--primary);
            padding: 4px 15px;
            border-radius: 5px;
            font-weight: 700;
            display: inline-block;
            margin-top: 5px;
            border: 1px solid var(--primary);
        }

        /* --- شبكة المعلومات السريعة --- */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 1px solid var(--border-dark);
            margin-bottom: 15px;
        }

        .info-item {
            padding: 8px;
            border-left: 1px solid var(--border-dark);
            text-align: center;
        }

        .info-item:last-child {
            border-left: none;
        }

        .info-label {
            display: block;
            font-size: 9pt;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 3px;
        }

        .info-value {
            display: block;
            font-size: 10.5pt;
            font-weight: 700;
        }

        /* --- الجدول الاحترافي --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
            /* ضروري لثبات الهوامش */
        }

        th {
            background-color: var(--primary) !important;
            color: white !important;
            border: 1px solid var(--border-dark);
            padding: 10px 4px;
            font-size: 9.5pt;
            text-align: center;
        }

        td {
            border: 1px solid var(--border-dark);
            padding: 7px 4px;
            font-size: 9pt;
            text-align: center;
            word-wrap: break-word;
            vertical-align: middle;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .project-name {
            text-align: right;
            font-weight: 700;
            padding-right: 8px;
        }

        .importance-badge {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: 700;
            display: inline-block;
        }

        .imp-normal {
            background: #e2e8f0 !important;
            color: #475569;
        }

        .imp-important {
            background: #fef3c7 !important;
            color: #92400e;
        }

        .imp-very_important {
            background: #fee2e2 !important;
            color: #991b1b;
        }

        /* --- التوقيعات --- */
        .footer-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding: 0 20px;
        }

        .sig-box {
            width: 250px;
            text-align: center;
        }

        .sig-space {
            margin-top: 40px;
            border-top: 1.5px solid var(--border-dark);
            padding-top: 5px;
            font-weight: 700;
        }

        /* --- أزرار التحكم --- */
        .floating-actions {
            position: fixed;
            bottom: 25px;
            left: 25px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Cairo';
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: 0.3s;
        }

        .btn-print {
            background: var(--primary);
            color: white;
        }

        .btn-close {
            background: #555;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
    </style>
</head>

<body>

    <div class="print-container">
        <x-letterhead-selector :currentLetterhead="$letterhead ?? null" />

        <x-letterhead-header 
            :letterhead="$letterhead ?? null"
            :documentNumber="$plan->plan_number"
            :documentDate="now()->format('Y-m-d')"
            :qrCodeData="$qrCodeData ?? null"
            customTitle="مصفوفة الخطة العامة لمشاريع العام 1447"
        />
        
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">الجهة المقدمة</span>
                <span class="info-value">{{ $plan->submitting_organization_name }}</span>
            </div>
            {{-- <div class="info-item">
                <span class="info-label">الأولوية الوطنية</span>
                <span class="info-value">{{ $plan->priority->priority }}</span>
            </div> --}}
            <div class="info-item">
                <span class="info-label">تاريخ الإصدار</span>
                <span class="info-value">{{ now()->format('Y-m-d') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">إجمالي الميزانية</span>
                <span class="info-value">{{ number_format($plan->projects->sum('cost'), 2) }}</span>
            </div>
        </div>

        <table style="width:100%; border-collapse:collapse; text-align:center; vertical-align:middle;">
            <thead>
                <tr>
                    <th width="3%" style="text-align:center; vertical-align:middle;">م</th>
                    <th width="18%" style="text-align:center; vertical-align:middle;">اسم المشروع</th>
                    <th width="8%" style="text-align:center; vertical-align:middle;">الأهمية</th>
                    <th width="7%" style="text-align:center; vertical-align:middle;">الحالة</th>
                    <th width="15%" style="text-align:center; vertical-align:middle;">المؤشرات</th>
                    <th width="15%" style="text-align:center; vertical-align:middle;">المخرجات</th>
                    <th width="8%" style="text-align:center; vertical-align:middle;">المستهدف</th>
                    <th width="10%" style="text-align:center; vertical-align:middle;">التكلفة التقديرية</th>
                    <th width="12%" style="text-align:center; vertical-align:middle;">الجهة المشاركة</th>
                    <th width="6%" style="text-align:center; vertical-align:middle;">التمويل</th>
                </tr>
            </thead>

            <tbody>
                @foreach($plan->projects as $index => $project)
                    <tr>
                        <td style="text-align:center; vertical-align:middle;">
                            {{ $index + 1 }}
                        </td>

                        <td class="project-name">
                            <div style="font-weight:bold; margin-bottom: 5px;">{{ $project->name }}</div>
                            @if($project->goals && $project->goals->count() > 0)
                                <div
                                    style="font-weight: normal; font-size: 8pt; margin-top: 8px; margin-bottom: 8px; border-top: 1px dashed #ccc; padding-top: 5px; text-align: right;">
                                    <div style="font-weight: bold; color: var(--secondary); margin-bottom: 3px;">الأهداف
                                        والنتائج والمخرجات:</div>
                                    @foreach($project->goals as $goal)
                                        <div
                                            style="margin-bottom: 6px; background-color: #fafafa; border: 1px solid #e2e8f0; border-radius: 3px; padding: 4px;">
                                            <div
                                                style="font-weight: bold; color: #1e293b; border-bottom: 0.5px solid #eee; padding-bottom: 2px;">
                                                [هدف] {{ $goal->specific_goal }} <span
                                                    style="color: var(--primary);">({{ $goal->weight }}%)</span>
                                            </div>
                                            @if(!empty($goal->results_json) && is_array($goal->results_json))
                                                <div style="padding-top: 3px; padding-right: 10px;">
                                                    @foreach($goal->results_json as $result)
                                                        <div style="font-size: 7.5pt; color: #334155; margin-bottom: 2px;">
                                                            ↳ <strong style="color: #059669;">نتيجة:</strong>
                                                            {{ $result['result_name'] ?? '' }}
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
                                <div style="
                                            font-weight:normal;
                                            font-size:8pt;
                                            margin-top:8px;
                                            border-top:1px solid #eee;
                                            padding-top:5px;
                                            text-align:center;">

                                    @foreach($project->activities as $act)
                                        <div style="
                                                            margin-bottom:8px;
                                                            background-color:#fcfcfc;
                                                            border:1px solid #f0f0f0;
                                                            border-radius:3px;
                                                            padding:4px;
                                                            text-align:center;">

                                            <div style="
                                                                font-weight:bold;
                                                                border-bottom:0.5px solid #eee;
                                                                padding-bottom:2px;
                                                                text-align:center;">

                                                <div style="color:#333;">
                                                    [نشاط] {{ $act->name }}
                                                </div>

                                                <div style="color:var(--primary); margin-top:2px;">
                                                    {{ $act->weight }}%
                                                </div>
                                            </div>

                                            @if($act->actions->count() > 0)
                                                <div style="padding-top:3px;">
                                                    @foreach($act->actions as $action)
                                                        <div style="
                                                                                            font-size:7pt;
                                                                                            color:#555;
                                                                                            padding:3px;
                                                                                            border-bottom:0.2px solid #f9f9f9;
                                                                                            text-align:center;">

                                                            <div>
                                                                • {{ $action->name }}
                                                                ({{ $action->weight }}%)
                                                            </div>

                                                            <div style="
                                                                                                font-size:6.5pt;
                                                                                                color:#888;
                                                                                                margin-top:2px;">
                                                                {{ $action->start_date_g ? $action->start_date_g->format('Y/m/d') : '-' }}
                                                                -
                                                                {{ $action->end_date_g ? $action->end_date_g->format('Y/m/d') : '-' }}
                                                            </div>

                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                        </div>
                                    @endforeach

                                </div>
                            @endif
                        </td>

                        <td style="text-align:center; vertical-align:middle;">
                            @php
                                $labels = [
                                    'normal' => 'عادي',
                                    'important' => 'هام',
                                    'very_important' => 'هام جداً'
                                ];
                            @endphp

                            <span class="importance-badge imp-{{ $project->importance }}">
                                {{ $labels[$project->importance] ?? $project->importance }}
                            </span>
                        </td>

                        <td style="text-align:center; vertical-align:middle;">
                            {{ $project->status == 'new' ? 'جديد' : 'مستمر' }}
                        </td>

                        <td style="text-align:center; vertical-align:middle;">
                            {{ $project->indicators }}
                        </td>

                        <td style="text-align:center; vertical-align:middle;">
                            {{ $project->outputs }}
                        </td>

                        <td style="text-align:center; vertical-align:middle;">
                            {{ number_format($project->target_value) }}
                            <br>
                            <small>{{ $project->baseline }}</small>
                        </td>

                        <td style="
                            text-align:center;
                            vertical-align:middle;
                            font-weight:800;">
                            {{ number_format($project->cost) }}
                            <br>
                            <small>{{ $project->cost_type }}</small>
                        </td>

                        <td style="text-align:center; vertical-align:middle;">
                            {{ $project->participatingEntity->name }}
                        </td>

                        <td style="text-align:center; vertical-align:middle;">
                            {{ $project->funding_availability ? 'متوفر' : 'مطلوب' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr style="background-color:#eee !important;">
                    <td colspan="7" style="
                    text-align:center;
                    vertical-align:middle;
                    font-weight:800;
                    padding:10px;">
                        الإجمالي الكلي للتكاليف التقديرية
                    </td>

                    <td colspan="3" style="
                    text-align:center;
                    vertical-align:middle;
                    font-size:11pt;
                    font-weight:800;
                    color:var(--primary);">
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

        <x-letterhead-footer :letterhead="$letterhead ?? null" documentName="مصفوفة الخطة العامة لمشاريع العام 1447" />
    </div>

    <div class="floating-actions no-print">
        <button onclick="window.print()" class="btn btn-print">طباعة الوثيقة الرسمية</button>
        <button onclick="window.history.back()" class="btn btn-close">إغلاق</button>
    </div>

</body>

</html>

