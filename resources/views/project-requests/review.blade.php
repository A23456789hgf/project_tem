@extends('layouts.app')

@section('styles')
<link href="{{ asset('css/executive-activities.css') }}" rel="stylesheet">
<style>
    .project-status-badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    .status-draft { background-color: #6c757d; color: #fff; }
    .status-submitted { background-color: #17a2b8; color: #fff; }
    .status-approved { background-color: #28a745; color: #fff; }
    .status-rejected { background-color: #dc3545; color: #fff; }
    
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
    <!-- Project Request Header Card -->
    <div class="card shadow-lg mb-4 border-0">
        <div class="card-header section-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-1">{{ $projectRequest->project_name ?? 'اسم المشروع غير محدد' }}</h1>
                    <small class="opacity-75">رقم الطلب : {{ $projectRequest->request_number ?? 'غير محدد' }}</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge project-status-badge status-{{ $projectRequest->status ?? 'draft' }}">
                        <i class="fas fa-info-circle me-1"></i> {{ $projectRequest->status_label }}
                    </span>
                    <div class="btn-group">
                        <button type="button" class="btn btn-light btn-sm print-btn" onclick="window.print()">
                            <i class="fas fa-print"></i> طباعة
                        </button>
                        @if($projectRequest->status === 'draft')
                        @can('project-requests.edit')
                        <a href="{{ route('project-requests.edit', $projectRequest->id) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        @endcan
                        @can('project-requests.edit')
                        <form action="{{ route('project-requests.submit', $projectRequest->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirmAction(this, 'هل أنت متأكد من إرسال هذا الطلب؟ لن يمكنك التعديل عليه بعد الإرسال.')">
                                <i class="fas fa-paper-plane"></i> إرسال الطلب
                            </button>
                        </form>
                        @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Project Info Card -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-project-diagram me-2"></i>معلومات الطلب الأساسية
                </h5>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">البرنامج</div>
                                <p class="detail-value">{{ $projectRequest->program->name ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">المجال</div>
                                <p class="detail-value">{{ $projectRequest->domain->name ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-label">النطاق الفرعي</div>
                                <p class="detail-value">{{ $projectRequest->subdomain->name ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Time Duration Card -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-calendar-alt me-2"></i>المدة الزمنية المقترحة
                </h5>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ البدء (ميلادي)</div>
                                <p class="detail-value">{{ $projectRequest->start_date_gregorian ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ البدء (هجري)</div>
                                <p class="detail-value">{{ $projectRequest->start_date_hijri ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ الانتهاء (ميلادي)</div>
                                <p class="detail-value">{{ $projectRequest->end_date_gregorian ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-item">
                                <div class="detail-label">تاريخ الانتهاء (هجري)</div>
                                <p class="detail-value">{{ $projectRequest->end_date_hijri ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Beneficiaries Card -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-users me-2"></i>المستفيدين
                </h5>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">عدد المستفيدين المتوقع</div>
                                <p class="detail-value h4 text-primary mb-0">{{ number_format($projectRequest->number_of_beneficiaries ?? 0) }} مستفيد</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">فئات المستفيدين</div>
                                <div class="detail-value">
                                    @php
                                        $categories = is_array($projectRequest->target_categories) ? $projectRequest->target_categories : json_decode($projectRequest->target_categories, true) ?? [];
                                    @endphp
                                    @if(count($categories) > 0)
                                        @foreach($categories as $category)
                                            <span class="badge bg-info me-1">{{ $category }}</span>
                                        @endforeach
                                    @else
                                        {{ $projectRequest->target_categories ?: 'غير محدد' }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Details Section -->
            <div class="data-section">
                <h5 class="section-title">
                    <i class="fas fa-info-circle me-2"></i>تفاصيل المشروع المقترح
                </h5>
                <div class="section-content">
                    @if($projectRequest->detail)
                        <div class="row">
                            @if($projectRequest->detail->project_introduction)
                            <div class="col-md-12 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label">مقدمة المشروع</div>
                                    <p class="detail-value">{{ $projectRequest->detail->project_introduction }}</p>
                                </div>
                            </div>
                            @endif

                            @if($projectRequest->detail->project_summary)
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label">ملخص المشروع</div>
                                    <p class="detail-value">{{ $projectRequest->detail->project_summary }}</p>
                                </div>
                            </div>
                            @endif
                            
                            @if($projectRequest->detail->problem_and_justification)
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label">المشكلة والمبررات</div>
                                    <p class="detail-value">{{ $projectRequest->detail->problem_and_justification }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="empty-state">
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
                    @if($projectRequest->locations && $projectRequest->locations->count() > 0)
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
                                    @foreach($projectRequest->locations as $location)
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
                    @if($projectRequest->mainObjectives && $projectRequest->mainObjectives->count() > 0)
                        @foreach($projectRequest->mainObjectives as $objective)
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
                            <p>لا توجد أهداف رئيسية</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Costs and Financing Section -->
            <div class="data-section mb-4">
                <h5 class="section-title">
                    <i class="fas fa-dollar-sign me-2"></i>التكاليف والتمويل
                </h5>
                <div class="section-content">
                    @if($projectRequest->cost)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="detail-item">
                                    <div class="detail-label">التكلفة التقديرية الإجمالية</div>
                                    <p class="detail-value h5 text-success mb-0">
                                        {{ number_format($projectRequest->cost->total_cost, 2) }} ريال سعودي
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        @if($projectRequest->financings && $projectRequest->financings->count() > 0)
                        <h6 class="mb-3 text-primary border-bottom pb-2">مصادر التمويل المقترحة</h6>
                        <div class="table-container">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>المصدر</th>
                                        <th>المبلغ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($projectRequest->financings as $financing)
                                    <tr>
                                        <td>{{ $financing->funding_source }}</td>
                                        <td class="text-success fw-bold">{{ number_format($financing->funding_amount, 2) }} ر.س</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <p>لم يتم إضافة بيانات التكاليف بعد</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Print title
    window.addEventListener('beforeprint', function() {
        document.title = 'طلب مشروع {{ $projectRequest->project_name ?? "غير محدد" }} - {{ $projectRequest->request_number ?? "غير محدد" }}';
    });
});
</script>
@endsection
