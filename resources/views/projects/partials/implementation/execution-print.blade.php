<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير التنفيذ - {{ $project->project_name }}</title>
    <x-letterhead-styles :letterhead="$letterhead ?? null" />
    <style>
        :root {
            --primary-color: {{ $letterhead?->primary_color ?? '#1a4d2e' }};
            --secondary-color: #4f6f52;
            --accent-color: #f8f9fa;
            --text-dark: #2d2e2e;
            --border-color: #e0e0e0;
        }

        /* إعدادات الطباعة الأفقية والهوامش */
        @media print {
            @page {
                size: A4 landscape;
                /* هوامش طباعة معيارية للمستندات العربية */
                margin: 20mm 15mm 20mm 15mm; 
            }
            
            body {
                background-color: white !important;
                -webkit-print-color-adjust: exact;
                counter-increment: page;
            }

            .no-print { display: none !important; }
            
            .report-container {
                box-shadow: none !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* ترقيم الصفحات في التذييل */
            .footer {
                position: fixed;
                bottom: -15mm;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 10px;
                border-top: 1px solid #eee;
                padding-top: 5px;
            }

            .page-number::after {
                content: "صفحة " counter(page);
            }

            /* منع انقسام العناصر بشكل سيء */
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            .activity-block { page-break-inside: avoid; margin-bottom: 20px; }
        }

        /* التنسيق العام للعرض على المتصفح */
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f0f2f5;
            color: var(--text-dark);
            margin: 0;
            padding: 20px;
        }

        .report-container {
            background-color: white;
            max-width: 277mm; /* تقريباً عرض A4 الأفقي مطروحاً منه الهوامش */
            margin: 0 auto;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-radius: 8px;
            position: relative;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .report-header h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 24px;
        }

        .project-details-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            background: var(--accent-color);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .detail-item { font-size: 13px; line-height: 1.6; }
        .detail-item strong { color: var(--primary-color); display: block; margin-bottom: 5px; font-weight: 700; }

        .section-header {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            margin: 25px 0 15px 0;
            display: flex;
            align-items: center;
        }

        .activity-block {
            margin-bottom: 20px;
            border: 1px solid #eee;
            border-right: 5px solid var(--secondary-color);
            padding: 15px 20px;
            border-radius: 4px;
        }

        .activity-title {
            font-size: 15px;
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #eee;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 10px;
        }

        th {
            background-color: #f8f9fa !important;
            color: #333;
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: right;
        }

        td {
            padding: 10px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            text-align: center;
            display: inline-block;
        }
        .bg-completed { background-color: #28a745; }
        .bg-in_progress { background-color: #007bff; }
        .bg-delayed { background-color: #dc3545; }

        .justification-row {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .justification-box {
            flex: 1;
            font-size: 11px;
            padding: 10px;
            border-radius: 4px;
            border-right: 4px solid;
        }
        
        .box-financial { background-color: #fffcf0; border-color: #ffc107; color: #856404; }
        .box-technical { background-color: #f0f7ff; border-color: #2196f3; color: #0c5460; }

        .btn-print {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            font-family: 'Cairo';
            font-weight: bold;
            z-index: 1000;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 11px;
            color: #888;
        }
    </style>
</head>
<body>

    <button class="no-print btn-print" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 8px;">
        <x-icon name="print" size="18" /> طباعة التقرير (A4 أفقي)
    </button>

    <div class="report-container">
        <x-letterhead-selector :currentLetterhead="$letterhead ?? null" />
        @php
            $executionLogoUrl = !empty($letterhead?->logo_path) ? asset('storage/' . $letterhead->logo_path) : asset('images/logo.png');
        @endphp
        <div class="report-header">
            <div>
                <h1 style="display: inline-flex; align-items: center; gap: 8px;"><x-icon name="file-invoice" size="24" /> تقرير تنفيذ المشروع</h1>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">{{ $letterhead?->organization_name ?? 'سجل المتابعة الدوري للأنشطة والإجراءات' }}</div>
            </div>
            <div style="text-align: left;">
                <img src="{{ $executionLogoUrl }}" alt="Logo" style="height: 50px; max-width: 150px; object-fit: contain;">
            </div>
        </div>

        <div class="project-details-grid">
            <div class="detail-item">
                <strong>اسم المشروع</strong> {{ $project->project_name }}
            </div>
            <div class="detail-item">
                <strong>رقم النموذج</strong> {{ $project->form_number }}
            </div>
            <div class="detail-item">
                <strong>حالة المشروع</strong> {{ $project->status }}
            </div>
            <div class="detail-item">
                <strong>تاريخ الاستخراج</strong> {{ date('Y-m-d') }}
            </div>
        </div>

        <div class="section-header" style="display: flex; align-items: center;">
            <x-icon name="tasks" style="margin-left: 10px;" size="18" /> أولاً: المرحلة التحضيرية
        </div>

        @foreach($project->preliminaryActivities as $activity)
            <div class="activity-block">
                <div class="activity-title">{{ $activity->name }}</div>
                
                @foreach($activity->procedures as $procedure)
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #444; display: flex; align-items: center;">
                            <x-icon name="chevron-left" style="color: var(--primary-color); margin-left: 5px;" size="12" /> 
                            {{ $procedure->procedure_name }}
                        </div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th width="15%">التاريخ الفعلي</th>
                                    <th width="15%">الحالة</th>
                                    <th width="10%">نسبة الإنجاز</th>
                                    <th width="15%">المبلغ المنصرف</th>
                                    <th>ملاحظات الفريق التنفيذي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($procedure->executions as $execution)
                                    <tr>
                                        <td>{{ $execution->actual_start_date_gregorian ? $execution->actual_start_date_gregorian->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $execution->status }}">
                                                {{ __('execution.status.' . $execution->status) }}
                                            </span>
                                        </td>
                                        <td style="text-align: center; font-weight: bold;">{{ $execution->completion_percentage }}%</td>
                                        <td>{{ number_format($execution->actual_amount, 2) }} ر.س</td>
                                        <td>{{ $execution->notes ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" style="text-align: center; color: #999;">لا يوجد بيانات تنفيذ مسجلة</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endforeach

        <div class="footer">
            <span>نظام إدارة المشاريع - تقرير آلي</span> | 
            <span class="page-number"></span> | 
            <span>تاريخ الطباعة: {{ date('H:i Y-m-d') }}</span>
        </div>
    </div>

</body>
</html>