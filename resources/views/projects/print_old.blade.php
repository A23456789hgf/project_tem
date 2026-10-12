<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>طباعة - {{ $project->project_name ?? 'عرض المشروع' }}</title>
    <style>
        /* Base */
        * { box-sizing: border-box; }
        body {
            font-family: 'Tahoma', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            color: #333;
            margin: 0;
            background: #fff;
            font-size: 11px;
            line-height: 1.6;
        }
        .container { width: 95%; margin: 0 auto; padding: 16px 0 32px; }

        /* Header */
        .doc-header {
            border: 2px solid #2c3e50;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0 18px;
            background: #f7f9fb;
        }
        .doc-title {
            margin: 0 0 6px 0; font-weight: 700; color: #2c3e50; font-size: 18px;
        }
        .doc-subtitle { color: #6c757d; margin: 0; font-size: 11px; }
        .badge {
            display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 10px; font-weight: 700; color: #fff;
        }
        .status-draft { background: #ffc107; color: #000; }
        .status-final { background: #28a745; }
        .status-unknown { background: #6c757d; }

        /* Sections */
        .section { margin: 14px 0; page-break-inside: avoid; }
        .section-title {
            background: #007bff; color: #fff; padding: 8px 12px; margin: 0 0 10px 0; border-radius: 4px; font-weight: 700; font-size: 12px;
        }
        .card { background: #fff; border: 1px solid #e9ecef; border-radius: 4px; padding: 10px; }

        /* Grid two columns */
        .grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .grid-item { flex: 0 0 calc(50% - 4px); background: #f8f9fa; border-left: 2px solid #007bff; border-radius: 3px; padding: 6px 8px; }
        .label { font-weight: 700; color: #495057; display: block; margin-bottom: 2px; font-size: 10px; }
        .value { color: #555; font-size: 10px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; font-size: 10px; margin: 8px 0; }
        th, td { border: 1px solid #dee2e6; padding: 6px; }
        thead th { background: #34495e; color: #fff; text-align: center; font-size: 9px; }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        tbody tr:hover { background: #e9ecef; }

        /* Activity Card */
        .activity-card {
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .activity-header {
            background: #16a085;
            color: white;
            padding: 8px 10px;
            font-weight: bold;
            border-radius: 4px 4px 0 0;
            font-size: 10px;
        }

        .activity-content {
            padding: 8px;
            background: white;
        }

        .procedure-item {
            background: #f8f9fa;
            padding: 6px 8px;
            margin: 6px 0;
            border-right: 2px solid #3498db;
            border-radius: 0 2px 2px 0;
            font-size: 9px;
        }

        .cost-table-nested {
            width: 100%;
            margin: 6px 0;
            border-collapse: collapse;
        }

        .cost-table-nested th,
        .cost-table-nested td {
            border: 1px solid #dee2e6;
            padding: 4px;
            font-size: 9px;
        }

        .type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
            color: white;
        }

        .objective-badge { background: #007bff; }
        .result-badge { background: #28a745; }
        .output-badge { background: #ffc107; color: #000; }
        .preliminary-badge { background: #17a2b8; }
        .executive-badge { background: #dc3545; }

        /* Cost Summary */
        .cost-summary {
            background: #e8f4f8;
            border: 1px solid #3498db;
            border-radius: 3px;
            padding: 8px;
            margin: 8px 0;
            font-weight: bold;
            font-size: 10px;
        }

        .cost-summary .total-label {
            color: #2c3e50;
        }

        .cost-summary .total-value {
            color: #27ae60;
        }

        /* Footer */
        .doc-footer { text-align: center; color: #6c757d; font-size: 10px; margin-top: 18px; border-top: 1px solid #e9ecef; padding-top: 10px; }

        /* Cover Page */
        .cover-page {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            page-break-after: always;
            padding: 20px;
            background: linear-gradient(135deg, #f0f7f0 0%, #e8f5e9 100%);
        }

        .cover-page .organization-name {
            font-size: 24px;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        .cover-page .project-name {
            font-size: 48px;
            font-weight: 700;
            color: #1b7a00;
            margin-bottom: 50px;
            word-wrap: break-word;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .cover-page .qr-code-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding: 40px;
            border: 3px solid #1b7a00;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(27, 122, 0, 0.15);
        }

        .cover-page .qr-code-container img {
            width: 250px;
            height: 250px;
            border: 2px solid #1b7a00;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }

        .cover-page .qr-label {
            font-size: 16px;
            color: #2d5016;
            font-weight: 600;
        }

        /* Print tweaks */
        @media print {
            @page { margin: 10mm; }
            body { font-size: 10px; }
            .no-print { display: none !important; }
            .section { page-break-inside: avoid; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <!-- Cover Page -->
        <div class="cover-page">
            <div class="organization-name">اللجنة الزراعية والسمكية العليا</div>
            <h1 class="project-name">{{ $project->project_name ?? 'اسم المشروع غير محدد' }}</h1>
            <div class="qr-code-container">
                @if($qrCodeImage)
                    <img src="{{ $qrCodeImage }}" 
                         alt="QR Code"
                         style="width: 250px; height: 250px; border: 2px solid #1b7a00; border-radius: 8px; padding: 10px; background: white;">
                @else
                    <div style="width: 250px; height: 250px; border: 2px solid #1b7a00; border-radius: 8px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">QR Code</div>
                @endif
                <div class="qr-label">رمز الاستجابة السريعة</div>
            </div>
            <div style="margin-top: 40px; font-size: 12px; color: #555;">
                @if($user)
                    <p>المستخدم: <strong>{{ $user->name ?? 'غير محدد' }}</strong></p>
                @endif
                @if($printDate)
                    <p>تاريخ الطباعة: <strong>{{ $printDate }}</strong></p>
                @endif
            </div>
        </div>

        <!-- Header -->
        <div class="doc-header">
            <div style="display:flex; justify-content: space-between; align-items:center; gap: 10px;">
                <div>
                    <h1 class="doc-title">{{ $project->project_name ?? 'اسم المشروع غير محدد' }}</h1>
                    <p class="doc-subtitle">رقم المشروع/النموذج: {{ $project->form_number ?? 'غير محدد' }}</p>
                </div>
                <div>
                    @php
                        $status = $project->status ?? 'draft';
                        $badgeClass = $status === 'final' ? 'status-final' : ($status === 'draft' ? 'status-draft' : 'status-unknown');
                        $statusLabel = $status === 'final' ? 'نهائي' : ($status === 'draft' ? 'مسودة' : ($status ?: 'غير محدد'));
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>

        <!-- Basic Info -->
        <div class="section">
            <h2 class="section-title">البيانات الأساسية</h2>
            <div class="card">
                <div class="grid">
                    <div class="grid-item">
                        <span class="label">البرنامج</span>
                        <div class="value">{{ $project->program->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="grid-item">
                        <span class="label">المجال</span>
                        <div class="value">{{ $project->domain->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="grid-item">
                        <span class="label">المجال الفرعي</span>
                        <div class="value">{{ $project->subdomain->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="grid-item">
                        <span class="label">نوع التدخل</span>
                        <div class="value">{{ $project->intervention->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="grid-item">
                        <span class="label">الأولوية</span>
                        <div class="value">
                            @if(is_object($project->priority))
                                {{ $project->priority->name ?? $project->priority->priority ?? 'غير محدد' }}
                            @else
                                {{ $project->priority ?? 'غير محدد' }}
                            @endif
                        </div>
                    </div>
                    <div class="grid-item">
                        <span class="label">عدد المستفيدين</span>
                        <div class="value">{{ isset($project->number_of_beneficiaries) ? number_format($project->number_of_beneficiaries) : 'غير محدد' }}</div>
                    </div>
                    <div class="grid-item">
                        <span class="label">تاريخ البداية (ميلادي)</span>
                        <div class="value">{{ optional($project->start_date_gregorian)->format('Y-m-d') ?? $project->start_date_gregorian ?? 'غير محدد' }}</div>
                    </div>
                    <div class="grid-item">
                        <span class="label">تاريخ النهاية (ميلادي)</span>
                        <div class="value">{{ optional($project->end_date_gregorian)->format('Y-m-d') ?? $project->end_date_gregorian ?? 'غير محدد' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Details -->
        @if($project->detail)
        <div class="section">
            <h2 class="section-title">تفاصيل المشروع</h2>
            <div class="card">
                <div class="grid">
                    <div class="grid-item" style="flex: 0 0 100%">
                        <span class="label">ملخص المشروع</span>
                        <div class="value">{{ $project->detail->project_summary ?? '—' }}</div>
                    </div>
                    <div class="grid-item" style="flex: 0 0 100%">
                        <span class="label">المشكلة والمبررات</span>
                        <div class="value">{{ $project->detail->problem_and_justification ?? '—' }}</div>
                    </div>
                    <div class="grid-item" style="flex: 0 0 100%">
                        <span class="label">النتائج المتوقعة</span>
                        <div class="value">{{ $project->detail->expected_results ?? '—' }}</div>
                    </div>
                    <div class="grid-item" style="flex: 0 0 100%">
                        <span class="label">المخرجات المتوقعة</span>
                        <div class="value">{{ $project->detail->expected_outputs ?? '—' }}</div>
                    </div>
                    <div class="grid-item" style="flex: 0 0 100%">
                        <span class="label">مكونات المشروع</span>
                        <div class="value">{{ $project->detail->project_components ?? 'غير محدد' }}</div>
                    </div>
                    <div class="grid-item" style="flex: 0 0 100%">
                        <span class="label">الأثر المتوقع</span>
                        <div class="value">{{ $project->detail->expected_impact ?? 'غير محدد' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Locations -->
        @if($project->locations && $project->locations->count())
        <div class="section">
            <h2 class="section-title">مواقع التنفيذ</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>المحافظة</th>
                            <th>المديرية</th>
                            <th>المنطقة الفرعية</th>
                            <th>القرية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->locations as $loc)
                            <tr>
                                <td>{{ $loc->governorate->name ?? '—' }}</td>
                                <td>{{ $loc->directorate->name ?? '—' }}</td>
                                <td>{{ $loc->subArea->name ?? '—' }}</td>
                                <td>{{ $loc->village->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- General Objective -->
        @if($project->mainObjectives && $project->mainObjectives->count())
        <div class="section">
            <h2 class="section-title">الهدف العام</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>الهدف</th>
                            <th>المؤشر</th>
                            <th>وحدة المؤشر</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->mainObjectives as $objective)
                        <tr>
                            <td>{{ $objective->objective ?? '—' }}</td>
                            <td>{{ $objective->indicator ?? '—' }}</td>
                            <td>{{ $objective->indicator_unit ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Special Objectives -->
        @if($project->specialObjectives && $project->specialObjectives->count())
        <div class="section">
            <h2 class="section-title">الأهداف الخاصة والنتائج والمخرجات</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>البند</th>
                            <th>المؤشر</th>
                            <th>القيمة المستهدفة</th>
                            <th>الوحدة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->specialObjectives as $objective)
                            <tr>
                                <td><span class="type-badge objective-badge">هدف خاص</span></td>
                                <td colspan="4">{{ $objective->objective ?? '—' }}</td>
                            </tr>
                            @if($objective->results && $objective->results->count())
                                @foreach($objective->results as $result)
                                    <tr>
                                        <td><span class="type-badge result-badge">نتيجة</span></td>
                                        <td>{{ $result->result_name ?? '—' }}</td>
                                        <td>{{ $result->indicator_type ?? '—' }}</td>
                                        <td>{{ $result->target_value ?? '—' }}</td>
                                        <td>{{ $result->indicator_unit ?? '—' }}</td>
                                    </tr>
                                    @if($result->outputs && $result->outputs->count())
                                        @foreach($result->outputs as $output)
                                            <tr>
                                                <td><span class="type-badge output-badge">مخرج</span></td>
                                                <td>{{ $output->output ?? '—' }}</td>
                                                <td>{{ $output->indicator_type ?? '—' }}</td>
                                                <td>{{ $output->target_value ?? '—' }}</td>
                                                <td>{{ $output->indicator_unit ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Risks -->
        @if($project->risks && $project->risks->count())
        <div class="section">
            <h2 class="section-title">إدارة المخاطر</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>نوع المخاطرة</th>
                            <th>المخاطرة</th>
                            <th>مستوى المخاطر</th>
                            <th>الحل المقترح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->risks as $risk)
                            <tr>
                                <td>{{ $risk->risk_type ?? '—' }}</td>
                                <td>{{ $risk->risk ?? '—' }}</td>
                                <td>
                                    <span class="badge" style="background: {{
                                        $risk->risk_rate === 'مرتفع' ? '#dc3545' :
                                        ($risk->risk_rate === 'متوسط' ? '#ffc107' : '#28a745')
                                    }}">
                                        {{ $risk->risk_rate ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $risk->proposed_solution ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Supervising Authorities -->
        @if($project->supervisingAuthorities && $project->supervisingAuthorities->count())
        <div class="section">
            <h2 class="section-title">الجهات المشرفة</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>الجهة</th>
                            <th>الجهة الأم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->supervisingAuthorities as $auth)
                            <tr>
                                <td><span class="badge" style="background: {{ $auth->type === 'internal' ? '#007bff' : '#dc3545' }}">
                                    {{ $auth->type === 'internal' ? 'داخلية' : 'خارجية' }}
                                </span></td>
                                <td>{{ $auth->authority->name ?? '—' }}</td>
                                <td>{{ $auth->authority->parent->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Implementing Entities -->
        @if($project->implementingEntities && $project->implementingEntities->count())
        <div class="section">
            <h2 class="section-title">الجهات المنفذة</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>الجهة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->implementingEntities as $entity)
                            <tr>
                                <td>{{ $entity->entity->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Participating Entities -->
        @if($project->participatingEntities && $project->participatingEntities->count())
        <div class="section">
            <h2 class="section-title">الجهات المشاركة</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>الجهة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->participatingEntities as $entity)
                            <tr>
                                <td>{{ $entity->entity->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Beneficiary Entities -->
        @if($project->beneficiaryEntities && $project->beneficiaryEntities->count())
        <div class="section">
            <h2 class="section-title">الجهات المستفيدة</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>الجهة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->beneficiaryEntities as $entity)
                            <tr>
                                <td>{{ $entity->entity->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Preliminary Activities -->
        @if($project->preliminaryActivities && $project->preliminaryActivities->count())
        <div class="section">
            <h2 class="section-title">الأنشطة التمهيدية والإجراءات والتكاليف</h2>
            <div class="card">
                @foreach($project->preliminaryActivities as $activity)
                    <div class="activity-card">
                        <div class="activity-header">
                            <strong>{{ $activity->name ?? 'نشاط بدون اسم' }}</strong> - <span class="type-badge preliminary-badge">تمهيدي</span> ({{ $activity->weight_percentage ?? 0 }}%)
                        </div>
                        <div class="activity-content">
                            @if($activity->procedures && $activity->procedures->count())
                                @foreach($activity->procedures as $procedure)
                                    <div class="procedure-item">
                                        <strong>إجراء:</strong> {{ $procedure->name ?? 'بدون اسم' }}<br>
                                        <strong>الوزن:</strong> {{ $procedure->weight_percentage ?? 0 }}% | 
                                        <strong>البدء:</strong> {{ $procedure->start_date ? \Carbon\Carbon::parse($procedure->start_date)->format('Y-m-d') : '—' }} | 
                                        <strong>الانتهاء:</strong> {{ $procedure->end_date ? \Carbon\Carbon::parse($procedure->end_date)->format('Y-m-d') : '—' }} | 
                                        <strong>المدة:</strong> {{ $procedure->duration_days ?? 0 }} أيام<br>
                                        <strong>وسائل التحقق:</strong> {{ $procedure->verification_means ?? '—' }}
                                        
                                        @if($procedure->costs && $procedure->costs->count())
                                            <table class="cost-table-nested">
                                                <thead>
                                                    <tr>
                                                        <th>البند المالي</th>
                                                        <th>الوحدة</th>
                                                        <th>الكمية</th>
                                                        <th>السعر (ر.س)</th>
                                                        <th>الإجمالي (ر.س)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($procedure->costs as $cost)
                                                        <tr>
                                                            <td>{{ $cost->financial_item->name ?? '—' }}</td>
                                                            <td>{{ $cost->unit ?? '—' }}</td>
                                                            <td>{{ $cost->amount ?? 0 }}</td>
                                                            <td>{{ number_format($cost->quantity ?? 0) }}</td>
                                                            <td>{{ number_format(($cost->amount ?? 0) * ($cost->quantity ?? 0)) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach

                <!-- Preliminary Financial Summary -->
                @php
                    $preliminaryFinancialSummary = [];
                    if($project->preliminaryActivities) {
                        foreach($project->preliminaryActivities as $activity) {
                            if($activity->procedures) {
                                foreach($activity->procedures as $procedure) {
                                    if($procedure->costs) {
                                        foreach($procedure->costs as $cost) {
                                            $itemName = $cost->financial_item->name ?? 'غير محدد';
                                            $total = ($cost->amount ?? 0) * ($cost->quantity ?? 0);
                                            if (!isset($preliminaryFinancialSummary[$itemName])) {
                                                $preliminaryFinancialSummary[$itemName] = 0;
                                            }
                                            $preliminaryFinancialSummary[$itemName] += $total;
                                        }
                                    }
                                }
                            }
                        }
                    }
                @endphp
                @if(count($preliminaryFinancialSummary) > 0)
                    <div style="margin-top: 12px;">
                        <h4 style="background: #e8f4f8; padding: 6px 8px; border-radius: 3px; margin: 0 0 8px 0; font-size: 11px;">ملخص التكاليف التمهيدية</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>البند المالي</th>
                                    <th>الإجمالي (ر.س)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($preliminaryFinancialSummary as $itemName => $total)
                                    <tr>
                                        <td>{{ $itemName }}</td>
                                        <td>{{ number_format($total) }}</td>
                                    </tr>
                                @endforeach
                                <tr style="background: #d4edda; font-weight: bold;">
                                    <td>الإجمالي العام</td>
                                    <td>{{ number_format(array_sum($preliminaryFinancialSummary)) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Executive Activities -->
        @if($project->executiveActivities && $project->executiveActivities->count())
        <div class="section">
            <h2 class="section-title">الأنشطة التنفيذية والإجراءات والتكاليف والمكلفون</h2>
            <div class="card">
                @php
                    $executiveActivitiesData = [];
                    foreach($project->executiveActivities as $activity) {
                        $activityRowCount = 0;
                        $actionsData = [];
                        
                        if($activity->actions && $activity->actions->count() > 0) {
                            foreach($activity->actions as $action) {
                                $costsData = [];
                                $costCount = 0;
                                
                                if($action->costs && $action->costs->count() > 0) {
                                    foreach($action->costs as $cost) {
                                        $costsData[] = $cost;
                                        $costCount++;
                                    }
                                } else {
                                    $costsData[] = null;
                                    $costCount = 1;
                                }
                                
                                $assignedEntities = [];
                                if($action->assigned_entities && $action->assigned_entities->count() > 0) {
                                    foreach($action->assigned_entities as $assigned) {
                                        $assignedEntities[] = $assigned;
                                    }
                                }
                                
                                $actionsData[] = [
                                    'action' => $action,
                                    'costs' => $costsData,
                                    'assigned' => $assignedEntities,
                                    'rowspan' => $costCount > 0 ? $costCount : 1
                                ];
                                $activityRowCount += ($costCount > 0 ? $costCount : 1);
                            }
                        }
                        
                        $executiveActivitiesData[] = [
                            'activity' => $activity,
                            'actions' => $actionsData,
                            'rowspan' => $activityRowCount > 0 ? $activityRowCount : 1
                        ];
                    }
                @endphp
                
                <table style="font-size: 9px; margin-bottom: 10px;">
                    <thead>
                        <tr>
                            <th style="width: 12%;">النشاط</th>
                            <th style="width: 14%;">الإجراء</th>
                            <th style="width: 7%; text-align: center;">الوزن%</th>
                            <th style="width: 12%;">المكلفون</th>
                            <th style="width: 12%;">البند المالي</th>
                            <th style="width: 8%; text-align: center;">المبلغ</th>
                            <th style="width: 8%; text-align: center;">الكمية</th>
                            <th style="width: 10%; text-align: center; font-weight: 700;">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($executiveActivitiesData as $actData)
                            @php $firstActivityRow = true; @endphp
                            @foreach($actData['actions'] as $actionIndex => $actionData)
                                @php $firstActionRow = true; @endphp
                                @foreach($actionData['costs'] as $costIndex => $cost)
                                    <tr>
                                        @if($firstActivityRow && $costIndex == 0)
                                            <td rowspan="{{ $actData['rowspan'] }}" class="activity-header align-middle" style="padding: 4px;">
                                                <strong style="color: #7a2e00;">{{ $actData['activity']->name ?? '—' }}</strong>
                                                <div style="font-size: 8px; margin-top: 2px;">وزن: {{ $actData['activity']->weight ?? 0 }}%</div>
                                            </td>
                                            @php $firstActivityRow = false; @endphp
                                        @endif

                                        @if($firstActionRow)
                                            <td rowspan="{{ $actionData['rowspan'] }}" class="action-row" style="padding: 4px;">
                                                <strong>{{ $actionData['action']->action ?? '—' }}</strong>
                                            </td>
                                            <td rowspan="{{ $actionData['rowspan'] }}" class="action-row" style="padding: 4px; text-align: center;">
                                                {{ $actionData['action']->weight ?? 0 }}%
                                            </td>
                                            <td rowspan="{{ $actionData['rowspan'] }}" class="action-row" style="padding: 4px; font-size: 8px;">
                                                @if($actionData['assigned'] && count($actionData['assigned']) > 0)
                                                    @foreach($actionData['assigned'] as $assigned)
                                                        • {{ $assigned->authority?->agency_name ?? $assigned->agency_name ?? '—' }}<br>
                                                    @endforeach
                                                @else
                                                    <em>—</em>
                                                @endif
                                            </td>
                                            @php $firstActionRow = false; @endphp
                                        @endif

                                        @if($cost)
                                            <td class="cost-row" style="padding: 4px;">{{ $cost->financialItem?->name ?? '—' }}</td>
                                            <td class="cost-row" style="padding: 4px; text-align: center;">{{ number_format($cost->amount ?? 0, 2) }}</td>
                                            <td class="cost-row" style="padding: 4px; text-align: center;">{{ $cost->quantity ?? 0 }}</td>
                                            <td class="cost-row" style="padding: 4px; text-align: center; font-weight: 700; color: #28a745;">{{ number_format($cost->total ?? 0, 2) }}</td>
                                        @else
                                            <td class="cost-row" colspan="4" style="text-align: center; padding: 4px; font-style: italic;">—</td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                </table>

                <!-- Executive Financial Summary -->
                @php
                    $executiveFinancialSummary = [];
                    foreach($project->executiveActivities as $activity) {
                        foreach($activity->actions as $action) {
                            foreach($action->costs as $cost) {
                                $itemName = $cost->financialItem?->name ?? 'غير محدد';
                                if (!isset($executiveFinancialSummary[$itemName])) {
                                    $executiveFinancialSummary[$itemName] = 0;
                                }
                                $executiveFinancialSummary[$itemName] += $cost->total ?? 0;
                            }
                        }
                    }
                    $executiveTotal = array_sum($executiveFinancialSummary);
                @endphp

                @if(count($executiveFinancialSummary) > 0)
                <div class="cost-summary" style="background: #f0fff0; border: 1px solid #1e8449; padding: 8px; margin-top: 10px;">
                    <h4 style="background: #e8f5e9; padding: 6px 8px; border-radius: 3px; margin: 0 0 8px 0; font-size: 11px; color: #155724;">ملخص التكاليف التنفيذية</h4>
                    <table style="font-size: 10px;">
                        <thead style="background: #155724; color: white;">
                            <tr>
                                <th>البند المالي</th>
                                <th style="text-align: center;">الإجمالي (ر.س)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($executiveFinancialSummary as $itemName => $total)
                            <tr>
                                <td>{{ $itemName }}</td>
                                <td style="text-align: center;">{{ number_format($total, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr style="background: #c3e6cb; font-weight: 600;">
                                <td>المجموع الكلي</td>
                                <td style="text-align: center;">{{ number_format($executiveTotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Financing -->
        @if($project->financings && $project->financings->count())
        <div class="section">
            <h2 class="section-title">ملخص التمويل</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>الجهة/المصدر</th>
                            <th>نوع التمويل</th>
                            <th>شكل التمويل</th>
                            <th>المبلغ (ر.س)</th>
                            <th>النسبة %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalFinancing = 0;
                            foreach($project->financings as $f) {
                                $totalFinancing += $f->amount ?? 0;
                            }
                        @endphp
                        @foreach($project->financings as $f)
                            <tr>
                                <td>{{ $f->authority->name ?? $f->fundingSource->name ?? '—' }}</td>
                                <td>{{ $f->financingType->name ?? '—' }}</td>
                                <td>{{ $f->financingForm->name ?? ($f->subFinancingForm->name ?? '—') }}</td>
                                <td>{{ isset($f->amount) ? number_format($f->amount) : '—' }}</td>
                                <td>{{ $totalFinancing > 0 ? round(($f->amount ?? 0) / $totalFinancing * 100, 2) : 0 }}%</td>
                            </tr>
                        @endforeach
                        <tr style="background: #d4edda; font-weight: bold;">
                            <td colspan="3">الإجمالي</td>
                            <td>{{ number_format($totalFinancing) }}</td>
                            <td>100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Unified Schedule -->
        @php
            $allSchedules = [];
            if($project->preliminaryActivities) {
                foreach($project->preliminaryActivities as $activity) {
                    foreach($activity->procedures as $procedure) {
                        $allSchedules[] = [
                            'type' => 'preliminary',
                            'activity' => $activity->name ?? 'غير محدد',
                            'name' => $procedure->name ?? 'غير محدد',
                            'start_date' => $procedure->start_date ? strtotime($procedure->start_date) : null,
                            'end_date' => $procedure->end_date ? strtotime($procedure->end_date) : null,
                            'duration' => $procedure->duration_days ?? 0
                        ];
                    }
                }
            }
            if($project->executiveActivities) {
                foreach($project->executiveActivities as $activity) {
                    foreach($activity->actions as $action) {
                        $allSchedules[] = [
                            'type' => 'executive',
                            'activity' => $activity->name ?? 'غير محدد',
                            'name' => $action->name ?? 'غير محدد',
                            'start_date' => $action->start_date ? strtotime($action->start_date) : null,
                            'end_date' => $action->end_date ? strtotime($action->end_date) : null,
                            'duration' => $action->duration_days ?? 0
                        ];
                    }
                }
            }
            usort($allSchedules, function($a, $b) {
                return ($a['start_date'] ?? PHP_INT_MAX) - ($b['start_date'] ?? PHP_INT_MAX);
            });
        @endphp
        @if(count($allSchedules) > 0)
        <div class="section">
            <h2 class="section-title">الجدول الزمني الموحد</h2>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>النشاط</th>
                            <th>الإجراء</th>
                            <th>تاريخ البدء</th>
                            <th>تاريخ الانتهاء</th>
                            <th>المدة (أيام)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allSchedules as $schedule)
                            <tr>
                                <td><span class="badge" style="background: {{ $schedule['type'] === 'preliminary' ? '#17a2b8' : '#dc3545' }}">
                                    {{ $schedule['type'] === 'preliminary' ? 'تمهيدي' : 'تنفيذي' }}
                                </span></td>
                                <td>{{ $schedule['activity'] }}</td>
                                <td>{{ $schedule['name'] }}</td>
                                <td>{{ $schedule['start_date'] ? date('Y-m-d', $schedule['start_date']) : '—' }}</td>
                                <td>{{ $schedule['end_date'] ? date('Y-m-d', $schedule['end_date']) : '—' }}</td>
                                <td>{{ $schedule['duration'] }} أيام</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="doc-footer">
            تم إنشاء صفحة الطباعة في: {{ now()->format('Y-m-d H:i') }}
        </div>

        <div class="no-print" style="text-align:center; margin-top:10px;">
            <button onclick="window.print()" style="padding:8px 14px; border:0; background:#28a745; color:#fff; border-radius:4px; cursor:pointer;">إعادة الطباعة</button>
        </div>
    </div>
</body>
</html>
