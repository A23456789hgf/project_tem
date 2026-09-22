@extends('layouts.app')

@section('styles')
<link href="{{ asset('css/executive-activities.css') }}" rel="stylesheet">
<style>
    .project-status-badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    .status-draft { background-color: #ffc107; color: #000; }
    .status-internally_approved { background-color: #17a2b8; color: #fff; }
    .status-final { background-color: #28a745; color: #fff; }
    .info-card {
        border-left: 4px solid #007bff;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    .section-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .print-btn { 
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
    }
    .data-section {
        margin-bottom: 2rem;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .section-title {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
        padding: 1rem;
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }
    .section-content {
        padding: 1.5rem;
    }
    .detail-item {
        margin-bottom: 1rem;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 0.25rem;
        border-left: 3px solid #007bff;
    }
    .detail-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.25rem;
    }
    .detail-value {
        color: #6c757d;
        margin: 0;
    }
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
        background: #f8f9fa;
        border-radius: 0.25rem;
    }
    .table-container {
        background: white;
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .activity-card {
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .activity-header {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        color: white;
        padding: 1rem;
    }
    .cost-summary {
        background: #e8f4f8;
        padding: 1rem;
        border-radius: 0.25rem;
        margin-top: 1rem;
    }
    .objectives-combined-table {
        font-size: 0.85rem;
    }
    .objectives-combined-table th {
        background: linear-gradient(135deg, #495057 0%, #343a40 100%);
        color: white;
        border: 1px solid #dee2e6;
        padding: 0.5rem;
        vertical-align: middle;
        font-weight: 600;
    }
    .objectives-combined-table td {
        padding: 0.5rem;
        border: 1px solid #dee2e6;
        vertical-align: middle;
    }
    .objectives-combined-table .align-top {
        vertical-align: top !important;
    }
    .objectives-combined-table td[rowspan] {
        border-right: 2px solid #007bff;
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .objectives-combined-table .badge {
        font-size: 0.75rem;
    }
    @media print {
        .btn, .btn-group, .print-btn { display: none !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        .section-header, .section-title { background: #007bff !important; color: white !important; }
        .container { max-width: 100% !important; }
        .table { font-size: 11px; }
        body { font-size: 12px; }
        .data-section { page-break-inside: avoid; }
        .objectives-combined-table { font-size: 10px; }
        .objectives-combined-table small { font-size: 8px; }
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <!-- Project Header Card -->
    <div class="card shadow-lg mb-4 border-0">
        <div class="card-header section-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-1">{{ $project->project_name ?: (in_array($project->status, ['draft', 'completed_draft']) ? 'مسودة غير معنونة' : 'مشروع غير معنون') }}</h1>
                    <small class="opacity-75">رقم المشروع : {{ $project->form_number ?? 'غير محدد' }}</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge project-status-badge status-{{ $project->status ?? 'draft' }}">
                        @if($project->status === 'completed_draft')
                            <i class="fas fa-check-double me-1"></i> مسودة مكتملة
                        @elseif($project->status === 'draft')
                            <i class="fas fa-edit me-1"></i> مسودة
                        @elseif($project->status === 'internally_approved')
                            <i class="fas fa-check-double me-1"></i> معتمد داخلياً
                        @elseif($project->status === 'final')
                            <i class="fas fa-check-circle me-1"></i> نهائي
                        @else
                            <i class="fas fa-question-circle me-1"></i> {{ $project->status ?? 'غير محدد' }}
                        @endif
                    </span>
                    <div class="btn-group">
                        <button type="button" class="btn btn-light btn-sm print-btn" onclick="window.print()">
                            <i class="fas fa-print"></i> طباعة
                        </button>
                        @if($project->status !== 'final')
                        @can('projects.edit')
                        <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        @endcan
                        @endif
                        @if(in_array($project->status, ['draft', 'completed_draft']))
                        @can('projects.edit')
                        <form action="{{ route('projects.approve-internally', $project->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirmAction(this, 'هل أنت متأكد من اعتماد هذا المشروع داخلياً؟ لن يمكنك التعديل عليه إلا بعد تحويله لمسودة مرة أخرى.')">
                                <i class="fas fa-check-circle"></i> اعتماد داخلي
                            </button>
                        </form>
                        @endcan
                        @endif
                        <div class="btn-group">
                            <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i> تصدير
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('projects.export-pdf', $project->id) }}">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>تصدير PDF
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportProject()">
                                    <i class="fas fa-file-code text-info me-2"></i>تصدير JSON
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Project Overview Cards - Each in its own line -->
            
            <!-- Project Info Card -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-project-diagram me-2"></i>معلومات المشروع الأساسية
                </h5>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">البرنامج</div>
                                <p class="detail-value">{{ $project->program->name ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">المجال</div>
                                <p class="detail-value">{{ $project->domain->name ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">النطاق الفرعي</div>
                                <p class="detail-value">{{ $project->subdomain->name ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Time Duration Card -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-calendar-alt me-2"></i>المدة الزمنية للمشروع
                </h5>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ البدء (ميلادي)</div>
                                <p class="detail-value">{{ $project->start_date_gregorian ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ البدء (هجري)</div>
                                <p class="detail-value">{{ $project->start_date_hijri ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ الانتهاء (ميلادي)</div>
                                <p class="detail-value">{{ $project->end_date_gregorian ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ الانتهاء (هجري)</div>
                                <p class="detail-value">{{ $project->end_date_hijri ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mt-2">
                            <div class="detail-item">
                                <div class="detail-label">إجمالي المدة</div>
                                <p class="detail-value">
                                    @if($project->start_date_gregorian && $project->end_date_gregorian)
                                        {{ \Carbon\Carbon::parse($project->start_date_gregorian)->diffInDays(\Carbon\Carbon::parse($project->end_date_gregorian)) }} يوم
                                    @else
                                        غير محدد
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Beneficiaries Card -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-users me-2"></i>المستفيدين من المشروع
                </h5>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">عدد المستفيدين</div>
                                <p class="detail-value h4 text-primary mb-0">{{ number_format($project->number_of_beneficiaries ?? 0) }} مستفيد</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">فئات المستفيدين</div>
                                <p class="detail-value">{{ $project->beneficiary_categories ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">مجموعات المستفيدين</div>
                                <div class="detail-value">
                                    @if($project->beneficiaryGroups && $project->beneficiaryGroups->count() > 0)
                                        @foreach($project->beneficiaryGroups as $group)
                                            <span class="badge bg-info me-1">{{ $group->name }}</span>
                                        @endforeach
                                    @else
                                        غير محدد
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Priority and Status Card -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-flag me-2"></i>الأولوية والحالة
                </h5>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">مستوى الأولوية</div>
                                <p class="detail-value">
                                    @if(is_object($project->priority))
                                        <span class="badge bg-{{ $project->priority->level === 'high' ? 'danger' : ($project->priority->level === 'medium' ? 'warning' : 'secondary') }} fs-6">
                                            {{ $project->priority->name ?? $project->priority->level }}
                                        </span>
                                    @elseif(is_string($project->priority))
                                        <span class="badge bg-secondary fs-6">{{ $project->priority }}</span>
                                    @else
                                        <span class="badge bg-secondary fs-6">غير محدد</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">نوع التدخل</div>
                                <p class="detail-value">{{ $project->intervention->name ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ حفظ المسودة</div>
                                <p class="detail-value">
                                    @if($project->draft_saved_at)
                                        {{ $project->draft_saved_at->format('Y/m/d') }}
                                    @else
                                        لم يتم الحفظ كمسودة
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ الإنهاء</div>
                                <p class="detail-value">
                                    @if($project->finalized_at)
                                        <span class="text-success">{{ $project->finalized_at->format('Y/m/d') }}</span>
                                    @else
                                        لم يتم الإنهاء بعد
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info Section -->
            @if($project->target_categories)
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-route me-2"></i>معلومات إضافية
                </h5>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="detail-item">
                                <div class="detail-label">الفئات المستهدفة</div>
                                <p class="detail-value">{{ $project->target_categories }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Directives Section -->
            @if($project->main_directives || $project->subdirectives)
            <div class="data-section">
                <h5 class="section-title">
                    <i class="fas fa-directions me-2"></i>التوجيهات
                </h5>
                <div class="section-content">
                    <div class="row">
                        @if($project->main_directives)
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">التوجيهات الرئيسية</div>
                                <p class="detail-value">{{ $project->main_directives }}</p>
                            </div>
                        </div>
                        @endif
                        @if($project->subdirectives)
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">التوجيهات الفرعية</div>
                                <p class="detail-value">{{ $project->subdirectives }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Project Details Section -->
            <div class="data-section">
                <h5 class="section-title">
                    <i class="fas fa-info-circle me-2"></i>تفاصيل المشروع
                </h5>
                <div class="section-content">
                    @if($project->detail)
                        <div class="row">
                            @if($project->detail->project_introduction)
                            <div class="col-md-12 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-hand-point-right me-1 text-primary"></i>مقدمة المشروع
                                    </div>
                                    <p class="detail-value">{{ $project->detail->project_introduction }}</p>
                                </div>
                            </div>
                            @endif

                            @if($project->detail->project_summary)
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-file-alt me-1 text-primary"></i>ملخص المشروع
                                    </div>
                                    <p class="detail-value">{{ $project->detail->project_summary }}</p>
                                </div>
                            </div>
                            @endif
                            
                            @if($project->detail->problem_and_justification)
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-exclamation-triangle me-1 text-warning"></i>المشكلة والمبررات
                                    </div>
                                    <p class="detail-value">{{ $project->detail->problem_and_justification }}</p>
                                </div>
                            </div>
                            @endif
                            
                            {{-- النتائج والمخرجات المتوقعة تمت إزالتها لعدم وجودها في النموذج --}}
                            
                            @if($project->detail->project_components)
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-puzzle-piece me-1 text-secondary"></i>مكونات المشروع
                                    </div>
                                    <p class="detail-value">{{ $project->detail->project_components }}</p>
                                </div>
                            </div>
                            @endif
                            
                            @if($project->detail->expected_impact)
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-chart-line me-1 text-danger"></i>الأثر المتوقع
                                    </div>
                                    <p class="detail-value">{{ $project->detail->expected_impact }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-{{ $project->detail->is_part_of_plan ? 'check-circle text-success' : 'times-circle text-danger' }} me-2"></i>
                                <span class="fw-bold">
                                    {{ $project->detail->is_part_of_plan ? 'هذا المشروع جزء من خطة أكبر' : 'هذا المشروع مستقل وليس جزءاً من خطة أكبر' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>لم يتم إضافة تفاصيل المشروع بعد</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Locations Section -->
            <div class="data-section">
                <h5 class="section-title">
                    <i class="fas fa-map-marker-alt me-2"></i>مواقع التنفيذ
                </h5>
                <div class="section-content">
                    @if($project->locations && $project->locations->count() > 0)
                        <div class="table-container">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>المحافظة</th>
                                        <th>المديرية</th>
                                        <th>المنطقة الفرعية</th>
                                        <th>القرية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->locations as $location)
                                    <tr>
                                        <td>{{ optional($location->governorate)->name ?? 'جميع المحافظات' }}</td>
                                        <td>{{ optional($location->directorate)->name ?? 'جميع المديريات' }}</td>
                                        <td>{{ optional($location->subArea)->name ?? 'جميع المناطق الفرعية' }}</td>
                                        <td>{{ optional($location->village)->name ?? 'جميع القرى والحارات' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                            <p>لم يتم إضافة مواقع المشروع بعد</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Main Objectives Section -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-target me-2"></i>الأهداف الرئيسية
                </h5>
                <div class="section-content">
                    @if($project->mainObjectives && $project->mainObjectives->count() > 0)
                        @foreach($project->mainObjectives as $objective)
                        <div class="detail-item mb-3">
                            <div class="detail-label">الهدف الرئيسي {{ $loop->iteration }}</div>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="detail-value mb-2"><strong>الهدف:</strong> {{ $objective->objective }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="detail-value mb-2"><strong>المؤشر:</strong> {{ $objective->indicator }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="detail-value mb-0"><strong>وحدة القياس:</strong> {{ $objective->indicator_unit }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-target fa-2x mb-2"></i>
                            <p>لا توجد أهداف رئيسية</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Special Objectives, Results & Outputs Combined Table -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-bullseye me-2"></i>الأهداف الخاصة والنتائج والمخرجات
                </h5>
                <div class="section-content">
                    @if($project->specialObjectives && $project->specialObjectives->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center align-middle objectives-combined-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th rowspan="2">الهدف الخاص</th>
                                        <th colspan="4">النتائج</th>
                                        <th colspan="4">المخرجات</th>
                                    </tr>
                                    <tr>
                                        <!-- النتائج -->
                                        <th>النتيجة</th>
                                        <th>القيمة المستهدفة</th>
                                        <th>نوع المؤشر</th>
                                        <th>وحدة المؤشر</th>
                                        <!-- المخرجات -->
                                        <th>المخرج</th>
                                        <th>القيمة المستهدفة</th>
                                        <th>نوع المؤشر</th>
                                        <th>وحدة المؤشر</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->specialObjectives as $objective)
                                        @php
                                            $results = $objective->results;
                                            $totalRows = 0;
                                            foreach ($results as $result) {
                                                $totalRows += max(1, $result->outputs->count());
                                            }
                                        @endphp
                                        @foreach($results as $result)
                                            @php
                                                $outputs = $result->outputs;
                                                $outputCount = $outputs->count();
                                                $resultRowspan = $outputCount ?: 1;
                                            @endphp
                                            @foreach($outputs as $index => $output)
                                                <tr>
                                                    @if($loop->first && $loop->parent->first)
                                                        <td rowspan="{{ $totalRows }}" class="align-top">
                                                            <strong>{{ $objective->objective }}</strong>
                                                        </td>
                                                    @endif
                                                    @if($loop->first)
                                                        <td rowspan="{{ $resultRowspan }}" class="align-top">
                                                            {{ $result->result_name }}
                                                        </td>
                                                        <td rowspan="{{ $resultRowspan }}" class="align-top">
                                                            <span class="badge bg-success">{{ number_format($result->target_value, 2) }}</span>
                                                        </td>
                                                        <td rowspan="{{ $resultRowspan }}" class="align-top">
                                                            {{ $result->indicator_type }}
                                                        </td>
                                                        <td rowspan="{{ $resultRowspan }}" class="align-top">
                                                            {{ $result->indicator_unit }}
                                                        </td>
                                                    @endif
                                                    <td>{{ $output->output }}</td>
                                                    <td><span class="badge bg-warning">{{ number_format($output->target_value, 2) }}</span></td>
                                                    <td>{{ $output->indicator_type }}</td>
                                                    <td>{{ $output->indicator_unit }}</td>
                                                </tr>
                                            @endforeach
                                            @if($outputCount == 0)
                                                <tr>
                                                    @if($loop->first)
                                                        <td rowspan="{{ $totalRows }}" class="align-top">
                                                            <strong>{{ $objective->objective }}</strong>
                                                        </td>
                                                    @endif
                                                    <td>{{ $result->result_name }}</td>
                                                    <td><span class="badge bg-success">{{ number_format($result->target_value, 2) }}</span></td>
                                                    <td>{{ $result->indicator_type }}</td>
                                                    <td>{{ $result->indicator_unit }}</td>
                                                    <td colspan="4" class="text-muted">لا توجد مخرجات</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-bullseye fa-2x mb-2"></i>
                            <p>لا توجد أهداف خاصة</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Project Risks Section -->
            @if($project->risks && $project->risks->count() > 0)
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>مخاطر المشروع
                </h5>
                <div class="section-content">
                    <div class="table-container">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>الخطر</th>
                                    <th>مستوى الخطورة</th>
                                    <th>الحل المقترح</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->risks as $risk)
                                <tr>
                                    <td>{{ $risk->risk }}</td>
                                    <td>
                                        <span class="badge bg-{{ $risk->risk_rate >= 8 ? 'danger' : ($risk->risk_rate >= 5 ? 'warning' : 'success') }}">
                                            {{ $risk->risk_rate }}/10
                                        </span>
                                    </td>
                                    <td>{{ $risk->proposed_solution }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Supervising Entities Section -->
            @if($project->supervisingAuthorities && $project->supervisingAuthorities->count() > 0)
                <h5 class="section-title">
                    <i class="fas fa-eye me-2"></i>الجهات الإشرافية
                </h5>
                <div class="section-content">
                    <div class="table-container">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>نوع الجهة</th>
                                    <th>اسم الجهة</th>
                                    <th>الجهة الأم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->supervisingAuthorities as $entity)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge bg-{{ $entity->authority_type === 'internal' ? 'primary' : 'secondary' }}">
                                            {{ $entity->authority_type === 'internal' ? 'داخلية' : 'خارجية' }}
                                        </span>
                                    </td>
                                    <td>{{ $entity->authority->agency_name ?? 'غير محدد' }}</td>
                                    <td>{{ $entity->parent->agency_name ?? 'لا يوجد' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- Participating Entities Section -->
            @if($project->participatingEntities && $project->participatingEntities->count() > 0)
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-handshake me-2"></i>الجهات المشاركة
                </h5>
                <div class="section-content">
                    <div class="table-container">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>نوع الجهة</th>
                                    <th>اسم الجهة</th>
                                    <th>الجهة الأم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->participatingEntities as $entity)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge bg-{{ $entity->authority_type === 'internal' ? 'primary' : 'secondary' }}">
                                            {{ $entity->authority_type === 'internal' ? 'داخلية' : 'خارجية' }}
                                        </span>
                                    </td>
                                    <td>{{ $entity->authority->agency_name ?? 'غير محدد' }}</td>
                                    <td>{{ $entity->parent->agency_name ?? 'لا يوجد' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Implementing Entities Section -->
            @if($project->implementingEntities && $project->implementingEntities->count() > 0)
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-tasks me-2"></i>الجهات المنفذة
                </h5>
                <div class="section-content">
                    <div class="table-container">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>نوع الجهة</th>
                                    <th>اسم الجهة</th>
                                    <th>الجهة الأم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->implementingEntities as $entity)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge bg-{{ $entity->authority_type === 'internal' ? 'primary' : 'secondary' }}">
                                            {{ $entity->authority_type === 'internal' ? 'داخلية' : 'خارجية' }}
                                        </span>
                                    </td>
                                    <td>{{ $entity->authority->agency_name ?? 'غير محدد' }}</td>
                                    <td>{{ $entity->parent->agency_name ?? 'لا يوجد' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Project Costs and Financing Section -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-dollar-sign me-2"></i>التكاليف والتمويل
                </h5>
                <div class="section-content">
                    @if($project->cost)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="detail-item">
                                    <div class="detail-label">التكلفة الإجمالية</div>
                                    <p class="detail-value h5 text-success mb-0">
                                        {{ number_format($project->cost->total_cost, 2) }} ريال سعودي
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        @if($project->financings && $project->financings->count() > 0)
                        <h6 class="mb-3 text-primary border-bottom pb-2">
                            <i class="fas fa-chart-pie me-2"></i>مصادر التمويل
                        </h6>
                        <div class="table-container">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>المصدر</th>
                                        <th>المبلغ</th>
                                        <th>النسبة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->financings as $financing)
                                    <tr>
                                        <td>{{ $financing->funding_source }}</td>
                                        <td class="text-success fw-bold">{{ number_format($financing->funding_amount, 2) }} ر.س</td>
                                        <td>
                                            <span class="badge bg-info">{{ $financing->funding_percentage }}%</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info border-0 mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            لم يتم إضافة مصادر التمويل بعد
                        </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <i class="fas fa-calculator fa-2x mb-2"></i>
                            <p>لم يتم إضافة بيانات التكاليف بعد</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Project Metadata Section -->
            <div class="data-section">
                <h5 class="section-title">
                    <i class="fas fa-info me-2"></i>معلومات إضافية
                </h5>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ الإنشاء</div>
                                <p class="detail-value">{{ $project->created_at ? $project->created_at->format('Y/m/d H:i') : 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">آخر تحديث</div>
                                <p class="detail-value">{{ $project->updated_at ? $project->updated_at->format('Y/m/d H:i') : 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">معرف المشروع</div>
                                <p class="detail-value">#{{ $project->id }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.container');
    if (container) {
        container.setAttribute('data-project-id', '{{ $project->id }}');
    }

    // Export function
    window.exportProject = function() {
        const projectData = {
            id: {{ $project->id }},
            name: '{{ $project->project_name ?? "غير محدد" }}',
            form_number: '{{ $project->form_number ?? "غير محدد" }}',
            status: '{{ $project->status ?? "draft" }}',
            created_at: '{{ $project->created_at ? $project->created_at->format('Y-m-d H:i:s') : "غير محدد" }}',
            updated_at: '{{ $project->updated_at ? $project->updated_at->format('Y-m-d H:i:s') : "غير محدد" }}'
        };
        
        const dataStr = JSON.stringify(projectData, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
        const exportFileName = `project_${projectData.form_number}_${new Date().toISOString().split('T')[0]}.json`;
        
        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileName);
        linkElement.click();
    };

    // Print enhancements
    const printStyles = `
        <style>
            @media print {
                .btn, .btn-group, .print-btn { display: none !important; }
                .card { border: 1px solid #ddd !important; box-shadow: none !important; }
                .section-header, .section-title { background: #007bff !important; color: white !important; }
                .container { max-width: 100% !important; }
                .table { font-size: 11px; }
                body { font-size: 12px; }
                .data-section { page-break-inside: avoid; margin-bottom: 1rem; }
                .activity-card { page-break-inside: avoid; }
            }
        </style>
    `;
    document.head.insertAdjacentHTML('beforeend', printStyles);
    
    window.addEventListener('beforeprint', function() {
        document.title = 'مشروع {{ $project->project_name ?? "غير محدد" }} - {{ $project->form_number ?? "غير محدد" }}';
    });

    // Initialize tooltips
    const statusBadge = document.querySelector('.project-status-badge');
    if (statusBadge) {
        let tooltipText = '';
        if (statusBadge.classList.contains('status-draft')) {
            tooltipText = 'هذا المشروع في حالة مسودة ويمكن تعديله';
        } else if (statusBadge.classList.contains('status-final')) {
            tooltipText = 'هذا المشروع تم إنهاؤه ولا يمكن تعديله';
        }
        
        if (tooltipText) {
            statusBadge.setAttribute('title', tooltipText);
            statusBadge.setAttribute('data-bs-toggle', 'tooltip');
        }
    }
    
    // Initialize Bootstrap tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Smooth scrolling for internal links
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>
@endsection