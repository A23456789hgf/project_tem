<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة تقرير الوضع المالي للمشروع - {{ $project->project_name ?? 'المشروع' }}</title>
    
    <link rel="stylesheet" href="{{ asset('css/libs/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/libs/font-awesome.all.min.css') }}">
    <x-letterhead-styles :letterhead="$letterhead" />

    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 15mm 10mm;
        }

        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            color: #1a202c;
            font-size: 11px;
            line-height: 1.4;
            padding: 15px;
            margin: 0;
        }

        .report-header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .report-title {
            font-size: 18px;
            font-weight: 800;
            color: #4f46e5;
            margin-bottom: 4px;
        }

        .report-meta {
            font-size: 11px;
            color: #4a5568;
        }

        .summary-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            background-color: #f8fafc;
            text-align: center;
        }

        .summary-box.budget { border-top: 3px solid #4f46e5; }
        .summary-box.expense { border-top: 3px solid #ef4444; }
        .summary-box.remaining { border-top: 3px solid #10b981; }

        .summary-label {
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: 800;
            color: #1e293b;
        }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10.5px;
        }

        .table-custom th, .table-custom td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .table-custom thead th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
        }

        .table-custom tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <!-- أزرار الإجراءات للطباعة ومحدد الترويسة -->
    <div class="no-print mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <x-letterhead-selector :currentLetterhead="$letterhead" />
        <div>
            <button onclick="window.print();" class="btn btn-primary btn-sm fw-bold">
                <i class="fas fa-print me-1"></i> طباعة التقرير
            </button>
            <button onclick="window.close();" class="btn btn-secondary btn-sm me-2">
                <i class="fas fa-times me-1"></i> إغلاق
            </button>
        </div>
    </div>

    <!-- ترويسة الجهة الرسمية -->
    <x-letterhead-header :letterhead="$letterhead" customTitle="تقرير الوضع المالي للمشروع" :documentNumber="$project->form_number" />

    <!-- معلومات التقرير السريعة -->
    <div class="report-header d-flex justify-content-between align-items-center">
        <div>
            <div class="report-meta">
                <span>اسم المشروع: <strong>{{ $project->project_name }}</strong></span> | 
                <span>كود ERPNext: <strong>{{ $project->erpnext_project_id ?? $project->frappe_project_name ?? '-' }}</strong></span> | 
                <span>تاريخ الطباعة: <strong>{{ date('Y-m-d H:i') }}</strong></span>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-indigo text-white p-2 fs-6">نسبة الصرف: {{ $financialStatus['execution_percentage'] }}%</span>
        </div>
    </div>

    <!-- ملخص الميزانية والمصروفات -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="summary-box budget">
                <div class="summary-label">الميزانية المعتمدة</div>
                <div class="summary-value">{{ number_format($financialStatus['budget'], 2) }} ر.ي</div>
            </div>
        </div>
        <div class="col-4">
            <div class="summary-box expense">
                <div class="summary-label">المصروف الفعلي</div>
                <div class="summary-value text-danger">{{ number_format($financialStatus['actual_expense'], 2) }} ر.ي</div>
            </div>
        </div>
        <div class="col-4">
            <div class="summary-box remaining">
                <div class="summary-label">المتبقي من الميزانية</div>
                <div class="summary-value text-success">{{ number_format($financialStatus['remaining'], 2) }} ر.ي</div>
            </div>
        </div>
    </div>

    <!-- تقسيم المصروفات حسب البنود -->
    <div class="row mb-4">
        <div class="col-6">
            <h6 class="fw-bold text-dark mb-2">البنود المالية المرتبطة (Linked)</h6>
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>البند المالي</th>
                        <th class="text-end">المبلغ الفعلي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($financialStatus['linked_expenses'] as $item => $amt)
                        <tr>
                            <td>{{ $item }}</td>
                            <td class="text-end fw-bold font-monospace">{{ number_format($amt, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">لا توجد بنود مرتبطة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="col-6">
            <h6 class="fw-bold text-dark mb-2">المصروفات غير المرتبطة (Unlinked)</h6>
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>الوصف / البند</th>
                        <th class="text-end">المبلغ الفعلي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($financialStatus['unlinked_expenses'] as $item => $amt)
                        <tr>
                            <td>{{ $item }}</td>
                            <td class="text-end fw-bold font-monospace">{{ number_format($amt, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">لا توجد مصروفات غير مرتبطة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(!empty($financialStatus['has_budget_mismatch']))
        <div class="alert alert-warning p-2 mb-3 border text-dark" style="font-size: 10px;">
            <strong>تنبيه محاسبي:</strong> الميزانية المعتمدة رسمياً للمشروع هي <strong>{{ number_format($financialStatus['budget'], 2) }} ر.ي</strong> (من واقع اعتماد تكلفة المشروع). مجموع تقديرات الأنشطة المسجلة هو {{ number_format($financialStatus['activities_total_budget'], 2) }} ر.ي. تعتمد ميزانية المشروع الرسمية بـ {{ number_format($financialStatus['budget'], 2) }} ر.ي.
        </div>
    @endif

    <!-- ميزانية الأنشطة والإجراءات التنفيذية -->
    <h6 class="fw-bold text-dark mb-2">تفاصيل الميزانية المعتمدة للأنشطة والإجراءات التنفيذية</h6>
    @if(!empty($financialStatus['activity_breakdown']))
        <table class="table-custom mb-4">
            <thead>
                <tr>
                    <th width="30">#</th>
                    <th>النشاط التنفيذي</th>
                    <th>الإجراء التنفيذي</th>
                    <th>البند المالي</th>
                    <th class="text-center">الكمية</th>
                    <th class="text-end">تكلفة الوحدة</th>
                    <th class="text-end">الميزانية المعتمدة (ر.ي)</th>
                    <th class="text-center">حالة الصرف في ERPNext</th>
                </tr>
            </thead>
            <tbody>
                @foreach($financialStatus['activity_breakdown'] as $idx => $act)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="fw-bold">{{ $act['activity_name'] }}</td>
                        <td>{{ $act['action_name'] }}</td>
                        <td>{{ $act['financial_item_name'] }}</td>
                        <td class="text-center font-monospace">{{ number_format($act['quantity'], 2) }}</td>
                        <td class="text-end font-monospace">{{ number_format($act['unit_cost'], 2) }}</td>
                        <td class="text-end fw-bold font-monospace">{{ number_format($act['approved_budget'], 2) }}</td>
                        <td class="text-center text-muted">غير مرتبط (Unlinked)</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- جدول مطابقة ميزانية البنود المالية مع المصروف الفعلي -->
    @if(!empty($financialStatus['item_reconciliation']))
        <h6 class="fw-bold text-dark mb-2">مطابقة ميزانية البنود المالية مع المصروف الفعلي (Item Budget Reconciliation)</h6>
        <table class="table-custom mb-4">
            <thead>
                <tr>
                    <th>البند المالي</th>
                    <th class="text-end">الميزانية المخصصة للأنشطة (ر.ي)</th>
                    <th class="text-end">المصروف الفعلي المرتبط (Linked)</th>
                    <th class="text-end">الفارق / المتبقي (ر.ي)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($financialStatus['item_reconciliation'] as $rec)
                    <tr>
                        <td class="fw-bold">{{ $rec['item_name'] }}</td>
                        <td class="text-end font-monospace">{{ number_format($rec['approved_budget'], 2) }}</td>
                        <td class="text-end font-monospace">{{ number_format($rec['linked_expense'], 2) }}</td>
                        <td class="text-end font-monospace fw-bold">{{ number_format($rec['variance'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- قيود المشروع التفصيلية -->
    <h6 class="fw-bold text-dark mb-2">قيود اليومية الخاصة بالمشروع من ERPNext</h6>
    @if(!empty($financialStatus['gl_entries']))
        <table class="table-custom">
            <thead>
                <tr>
                    <th width="30">#</th>
                    <th>التاريخ</th>
                    <th>رقم السند</th>
                    <th>الحساب</th>
                    <th>بند النفقة</th>
                    <th class="text-end">مدين</th>
                    <th class="text-end">دائن</th>
                    <th>البيان</th>
                </tr>
            </thead>
            <tbody>
                @foreach($financialStatus['gl_entries'] as $idx => $gl)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $gl['posting_date'] ?? '-' }}</td>
                        <td>{{ $gl['voucher_no'] ?? '-' }}</td>
                        <td>{{ $gl['account'] ?? '-' }}</td>
                        <td>{{ $gl['claim_expense_type'] ?? '-' }}</td>
                        <td class="text-end text-danger font-monospace">{{ number_format($gl['debit'] ?? 0, 2) }}</td>
                        <td class="text-end text-success font-monospace">{{ number_format($gl['credit'] ?? 0, 2) }}</td>
                        <td>{{ $gl['remarks'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-light text-center border">لا توجد قيود يومية مسجلة للمشروع.</div>
    @endif

    <!-- التوقيعات -->
    <div class="signature-section">
        <div class="row text-center mt-4">
            <div class="col-4">
                <p class="fw-bold mb-4">مدير المشروع</p>
                <p>__________________</p>
            </div>
            <div class="col-4">
                <p class="fw-bold mb-4">المراجع المالي</p>
                <p>__________________</p>
            </div>
            <div class="col-4">
                <p class="fw-bold mb-4">مدير الإدارة المالية</p>
                <p>__________________</p>
            </div>
        </div>
    <!-- تذييل التقرير الرسمي -->
    <x-letterhead-footer :letterhead="$letterhead" documentName="تقرير الوضع المالي للمشروع" />

</body>
</html>
