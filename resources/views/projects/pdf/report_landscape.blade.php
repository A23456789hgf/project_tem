<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تقرير المشاريع</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm 15mm 15mm 15mm;
            @bottom-center {
                content: "الصفحة " counter(page) " من " counter(pages);
                font-family: 'dejavusans';
                font-size: 9pt;
            }
        }

        body {
            font-family: 'dejavusans', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 10pt;
            line-height: 1.4;
        }

        .page-break { page-break-after: always; }
        .no-break { page-break-inside: avoid; }

        h1 { font-size: 16pt; color: #1e3a5f; margin-bottom: 10px; }
        h2 { font-size: 14pt; color: #2d5a8f; margin-bottom: 8px; border-bottom: 2px solid #eee; padding-bottom: 5px; }
        h3 { font-size: 12pt; color: #444; margin-bottom: 6px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }

        .header-section {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #ccc;
            padding-bottom: 10px;
        }

        .project-container {
            margin-bottom: 30px;
        }
        
        .info-grid {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-grid td {
            border: none;
            padding: 4px;
        }

        .label {
            font-weight: bold;
            color: #555;
            min-width: 100px;
            display: inline-block;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            color: white;
            font-size: 9pt;
            display: inline-block;
        }
        .bg-success { background-color: #198754; }
        .bg-warning { background-color: #ffc107; color: black; }
        .bg-danger { background-color: #dc3545; }
        .bg-primary { background-color: #0d6efd; }
        .bg-secondary { background-color: #6c757d; }

        .financial-summary-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

@foreach($projects as $index => $project)
    <div class="project-container {{ !$loop->last ? 'page-break' : '' }}">
        
        <!-- Header -->
        <div class="header-section">
            <h1>{{ $project->project_name }}</h1>
            <div>رقم المشروع: <strong>{{ $project->form_number }}</strong> | الحالة: 
                @php
                    $statusTrans = [
                        'draft' => 'مسودة', 
                        'final' => 'نهائي', 
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض'
                    ];
                    $status = $project->status ?? 'unknown';
                @endphp
                <span class="badge {{ $status == 'final' || $status == 'approved' ? 'bg-success' : 'bg-warning' }}">
                    {{ $statusTrans[$status] ?? $status }}
                </span>
            </div>
        </div>

        <!-- Basic Info (2 Columns) -->
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="width: 50%; border: none; padding-left: 15px;">
                    <table class="info-grid">
                        <tr>
                            <td><span class="label">البرنامج:</span> {{ $project->program->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">المجال:</span> {{ $project->domain->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">المجال الفرعي:</span> {{ $project->subdomain->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">تاريخ البداية:</span> {{ $project->start_date_gregorian ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%; border: none;">
                    <table class="info-grid">
                        <tr>
                            <td><span class="label">نوع التدخل:</span> {{ $project->intervention->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">الأولوية:</span> {{ is_object($project->priority) ? ($project->priority->name ?? '-') : ($project->priority ?? '-') }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">المستفيدين:</span> {{ number_format($project->number_of_beneficiaries ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">تاريخ النهاية:</span> {{ $project->end_date_gregorian ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <!-- Locations -->
        @if($project->locations->count() > 0)
        <div class="no-break">
            <h3>المواقع الجغرافية</h3>
            <table>
                <thead>
                    <tr>
                        <th width="25%">المحافظة</th>
                        <th width="25%">المديرية</th>
                        <th width="25%">المنطقة</th>
                        <th width="25%">القرية</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->locations as $loc)
                    <tr>
                        <td>{{ $loc->governorate->name ?? '-' }}</td>
                        <td>{{ $loc->directorate->name ?? '-' }}</td>
                        <td>{{ $loc->subArea->name ?? '-' }}</td>
                        <td>{{ $loc->village->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Execution / Activities -->
        @if($project->executiveActivities->count() > 0)
        <div class="no-break">
            <h3>الأنشطة التنفيذية والتكاليف</h3>
            <table>
                <thead>
                    <tr>
                        <th width="25%">النشاط</th>
                        <th width="25%">الإجراء</th>
                        <th width="15%">الجهة المكلفة</th>
                        <th width="15%">البند المالي</th>
                        <th width="10%">المبلغ</th>
                        <th width="10%">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->executiveActivities as $activity)
                        @foreach($activity->actions as $action)
                            @if($action->costs->count() > 0)
                                @foreach($action->costs as $index => $cost)
                                <tr>
                                    @if($index === 0)
                                        <td rowspan="{{ $action->costs->count() }}">
                                            {{ $activity->name }}
                                        </td>
                                        <td rowspan="{{ $action->costs->count() }}">
                                            {{ $action->action }} <small class="text-muted">({{ $action->weight }}%)</small>
                                        </td>
                                        <td rowspan="{{ $action->costs->count() }}">
                                            @foreach($action->assigned_entities as $entity)
                                                <div style="font-size: 8pt;">- {{ $entity->agency_name ?? ($entity->authority->agency_name ?? '-') }}</div>
                                            @endforeach
                                        </td>
                                    @endif
                                    <td>{{ $cost->financialItem->name ?? '-' }}</td>
                                    <td>{{ number_format($cost->amount, 2) }}</td>
                                    <td>{{ number_format($cost->total, 2) }}</td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>{{ $activity->name }}</td>
                                    <td>{{ $action->action }}</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                </tr>
                            @endif
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Total Budget Summary -->
        @if($project->cost)
        <div class="financial-summary-box no-break">
            <table style="width: auto; margin: 0 auto; background: transparent;">
                <tr>
                    <td style="border:none; padding: 0 20px;"><strong>الكلفة الإجمالية:</strong> {{ number_format($project->cost->total_cost, 2) }}</td>
                    <td style="border:none; padding: 0 20px;"><strong>مساهمة المجتمع:</strong> {{ number_format($project->cost->community_contribution, 2) }}</td>
                    <td style="border:none; padding: 0 20px;"><strong>المبلغ المطلوب:</strong> {{ number_format($project->cost->required_amount, 2) }}</td>
                </tr>
            </table>
        </div>
        @endif

        <div style="text-align: center; margin-top: 20px; font-size: 8pt; color: #999;">
            تم استخراج هذا التقرير بتاريخ {{ date('Y-m-d H:i') }}
        </div>

    </div>
@endforeach

</body>
</html>
