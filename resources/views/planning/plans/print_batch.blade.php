<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مصفوفة الخطط المجمعة | {{ $printDate }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0C5B47;
            --primary-light: #e6eeec;
            --border-dark: #222;
            --text-dark: #1a1a1a;
            --text-muted: #555;
        }

        @page {
            size: A4 landscape;
            margin: 15mm 10mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            tr {
                page-break-inside: avoid;
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
        }

        .print-container {
            padding: 5px;
        }

        /* تنسيق الترويسة بالكامل */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-left,
        .header-center,
        .header-right {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            /* يجعل النصوص موسطة داخل كل div */
            text-align: center;
        }

        .header-left {
            width: 25%;
        }

        .header-center {
            width: 50%;
        }

        .header-right {
            width: 25%;
        }

        .logo {
            max-height: 70px;
        }

        h1 {
            font-size: 16pt;
            color: var(--primary);
            margin: 5px 0;
            font-weight: 800;
        }

        .batch-info {
            font-size: 10pt;
            font-weight: 700;
            color: var(--text-muted);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th {
            background-color: var(--primary);
            color: white;
            border: 1px solid var(--border-dark);
            padding: 8px 2px;
            font-size: 8.5pt;
            text-align: center;
        }

        td {
            border: 1px solid var(--border-dark);
            padding: 5px;
            font-size: 8pt;
            text-align: center;
            vertical-align: middle;
        }

        .merged-cell {
            background: #f8fafc;
            font-weight: 800;
            vertical-align: middle !important;
            text-align: center !important;
        }

        .footer-info {
            margin-top: 20px;
            text-align: center;
            font-size: 8pt;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .floating-actions {
            position: fixed;
            bottom: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
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
            background: var(--primary);
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

        <!-- الترويسة مع النصوص الموسطة -->
        <div class="header-section">
            <div class="header-right">
                الجمهورية اليمنية<br>
          وزارة الزراعة والثروة السمكية والموارد المائية<br>
               {{ $plan->submitting_organization_name }}
            </div>

            <div class="header-center">
                <img src="{{ asset('images/logo.png') }}" class="logo"><br>
                <h1>مصفوفة المتابعة المجمعة للخطط التشغيلية</h1>
                <div class="batch-info">
                    تقرير مجمع بتاريخ: {{ $printDate }} | عدد الخطط: {{ $plans->count() }}
                </div>
            </div>

            <div class="header-left">
                تاريخ الاستخراج:<br>
                <strong>{{ now()->format('Y/m/d H:i') }}</strong>
            </div>
        </div>

        <table style="width:100%; border-collapse:collapse; text-align:center; vertical-align:middle;">
            <thead>
                <tr>
                    <th width="3%" style="text-align:center; vertical-align:middle;">م</th>
                    <th width="8%" style="text-align:center; vertical-align:middle;">رقم الخطة</th>
                    <th width="12%" style="text-align:center; vertical-align:middle;">الجهة المقدمة</th>
                    <th width="15%" style="text-align:center; vertical-align:middle;">اسم المشروع</th>
                    <th width="12%" style="text-align:center; vertical-align:middle;">النشاط</th>
                    <th width="12%" style="text-align:center; vertical-align:middle;">الإجراء</th>
                    <th width="6%" style="text-align:center; vertical-align:middle;">وزن النشاط</th>
                    <th width="6%" style="text-align:center; vertical-align:middle;">وزن الإجراء</th>
                    <th width="10%" style="text-align:center; vertical-align:middle;">تكلفة الإجراء</th>
                    <th width="8%" style="text-align:center; vertical-align:middle;">التمويل</th>
                </tr>
            </thead>

            <tbody>
                @php 
                                        $count = 1;
                    $grandTotal = 0;
                @endphp

                @foreach($plans as $plan)
                    @foreach($plan->projects as $project)

                        @php
                            $actionCount = $project->activities->sum(function ($activity) {
                                return $activity->actions->count();
                            });
                        @endphp

                        @foreach($project->activities as $activity)
                            @foreach($activity->actions as $action)

                                                @php 
                                                                        $actionCost = 0;
                                                    if ($project->cost > 0 && $activity->weight > 0 && $action->weight > 0) {
                                                        $actionCost = $project->cost * ($activity->weight / 100) * ($action->weight / 100);
                                                    }
                                                    $grandTotal += $actionCost; 
                                                @endphp
                                <tr>
                                    @if ($loop->parent->first && $loop->first)
                                        <td rowspan="{{ $actionCount > 0 ? $actionCount : 1 }}" class="merged-cell" style="text-align:center; vertical-align:middle;">{{ $count++ }}</td>
                                        <td rowspan="{{ $actionCount > 0 ? $actionCount : 1 }}" class="merged-cell" style="text-align:center; vertical-align:middle;">{{ $plan->plan_number }}</td>
                                        <td rowspan="{{ $actionCount > 0 ? $actionCount : 1 }}" class="merged-cell" style="text-align:center; vertical-align:middle;">{{ $plan->submitting_organization_name }}</td>
                                        <td rowspan="{{ $actionCount > 0 ? $actionCount : 1 }}" class="merged-cell" style="text-align:center; vertical-align:middle;">
                                            <div style="font-weight:bold;">{{ $project->name }}</div>
                                            @if($project->goals && $project->goals->count() > 0)
                                                <div style="font-weight: normal; font-size: 8pt; margin-top: 8px; border-top: 1px dashed #ccc; padding-top: 5px; text-align: right;">
                                                    <div style="font-weight: bold; color: var(--primary); margin-bottom: 3px;">الأهداف والنتائج والمخرجات:</div>
                                                    @foreach($project->goals as $goal)
                                                        <div style="margin-bottom: 6px; background-color: #fafafa; border: 1px solid #e2e8f0; border-radius: 3px; padding: 4px; text-align: right;">
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
                                        </td>
                                    @endif

                                    <td style="text-align:center; vertical-align:middle;">{{ $activity->name }}</td>
                                    <td style="text-align:center; vertical-align:middle;">{{ $action->name }}</td>
                                    <td style="text-align:center; vertical-align:middle;">{{ $activity->weight }} %</td>
                                    <td style="text-align:center; vertical-align:middle;">{{ $action->weight }} %</td>
                                    <td style="font-weight:800; text-align:center; vertical-align:middle;">{{ number_format($actionCost, 2) }}</td>
                                    <td style="text-align:center; vertical-align:middle;">{{ $project->funding_availability ? 'متوفر' : 'مطلوب' }}</td>
                                </tr>

                            @endforeach
                        @endforeach
                    @endforeach
                @endforeach

            </tbody>

            <tfoot>
                <tr style="background:#f1f5f9;font-weight:800;">
                    <td colspan="8" style="text-align:center; vertical-align:middle; padding:10px;">
                        الإجمالي الكلي لجميع الإجراءات
                    </td>
                    <td colspan="2" style="text-align:center; vertical-align:middle; font-size:11pt; color:var(--primary);">
                        {{ number_format($grandTotal, 2) }}
                    </td>
                </tr>
            </tfoot>

        </table>

        <div class="footer-info">
            هذه الوثيقة صادرة عن نظام إدارة الخطط الإلكتروني - إصدار مجمع للمصفوفة العامة.
        </div>

    </div>

    <div class="floating-actions no-print">
        <button onclick="window.print()" class="btn btn-print">طباعة</button>
        <button onclick="window.history.back()" class="btn btn-close">إغلاق</button>
    </div>

</body>

</html>
