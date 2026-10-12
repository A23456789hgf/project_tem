@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="m-0 fw-bold text-primary">
            <i class="fas fa-file-alt me-2"></i> تفاصيل الخطة التشغيلية
        </h4>
        <div class="d-flex gap-2">
            <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
            @can('plans.print', $plan)
            <a href="{{ route('plans.print', $plan->id) }}" target="_blank" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> طباعة الخطة التشغيلية
            </a>
            @endcan
            @canany(['plans.print-implementation', 'plans.implementation.print', 'plans.print'], $plan)
            <a href="{{ route('plans.implementation.print', $plan->id) }}" target="_blank" class="btn btn-warning">
                <i class="fas fa-file-pdf me-1"></i> طباعة الخطة التنفيذية
            </a>
            @endcanany
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Details Card -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-dark">المعلومات العامة</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">الرقم الموحد للخطة</label>
                            <div class="fw-bold fs-5 text-primary bg-light p-2 rounded text-center letter-spacing-1">
                                {{ $plan->plan_number }}
                            </div>
                        </div>
                        <div class="col-md-6 text-center">
                            <div class="qr-code-wrapper p-3 border rounded bg-white d-inline-block">
                                <img src="{{ $qrCodeData }}" alt="QR Code" style="width: 120px;">
                                <div class="mt-2 small text-muted">كود التحقق السريع</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">فترة الخطة / الأولوية</label>
                            <div class="fw-bold">
                                @if($plan->start_date_g && $plan->end_date_g)
                                    من {{ \Carbon\Carbon::parse($plan->start_date_g)->format('Y/m/d') }} إلى {{ \Carbon\Carbon::parse($plan->end_date_g)->format('Y/m/d') }} ({{ $plan->duration }} يوم)
                                @elseif(is_object($plan->priority))
                                    {{ $plan->priority->priority ?? $plan->priority->name ?? 'غير محدد' }}
                                @elseif(!empty($plan->priority))
                                    {{ $plan->priority }}
                                @else
                                    غير محدد
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">الجهة المقدمة</label>
                            <div class="fw-bold">{{ $plan->submitting_organization_name }}</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">تاريخ الإنشاء</label>
                            <div class="fw-bold">{{ $plan->created_at->format('Y/m/d H:i') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block mb-1">بواسطة</label>
                            <div class="fw-bold">{{ $plan->creator->name }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Table Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark">المشاريع المرفقة ({{ $plan->projects->count() }})</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">اسم المشروع</th>
                                    <th>الأهمية</th>
                                    <th>الحالة</th>
                                    <th>التكلفة</th>
                                    <th>الجهة المشاركة</th>
                                    <th class="pe-4 text-center">التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($plan->projects as $project)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $project->name }}</div>
                                    </td>
                                    <td>
                                        @if($project->importance == 'very_important')
                                            <span class="badge bg-danger-subtle text-danger px-2 py-1">هام جداً</span>
                                        @elseif($project->importance == 'important')
                                            <span class="badge bg-warning-subtle text-warning px-2 py-1">هام</span>
                                        @else
                                            <span class="badge bg-light text-dark px-2 py-1">عادي</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($project->status == 'new')
                                            <span class="badge bg-success-subtle text-success px-2 py-1">جديد</span>
                                        @else
                                            <span class="badge bg-primary-subtle text-primary px-2 py-1">مرحل</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ number_format($project->cost, 2) }} {{ $project->cost_type }}</div>
                                    </td>
                                    <td>{{ $project->participatingEntity->name }}</td>
                                    <td class="pe-4 text-center">
                                        <button type="button" class="btn btn-sm btn-link" data-bs-toggle="collapse" data-bs-target="#proj-{{ $project->id }}">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse bg-light-subtle" id="proj-{{ $project->id }}">
                                    <td colspan="5" class="p-4">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="small text-muted d-block">المؤشرات</label>
                                                <p class="small mb-0">{{ $project->indicators ?: 'لا يوجد' }}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="small text-muted d-block">المخرجات</label>
                                                <p class="small mb-0">{{ $project->outputs ?: 'لا يوجد' }}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="small text-muted d-block">الأساس / المستهدف</label>
                                                <p class="small mb-0">{{ $project->baseline ?: '-' }} / {{ $project->target_value ?: 0 }}</p>
                                            </div>
                                            <div class="col-12 mt-3">
                                                @if($project->activities->count() > 0)
                                                    <div class="nested-activities-wrapper">
                                                        @foreach($project->activities as $aIndex => $activity)
                                                        <div class="activity-group mb-3 border rounded bg-white shadow-sm">
                                                            <div class="activity-header p-3 bg-light d-flex justify-content-between align-items-center">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 28px; height: 28px;">
                                                                        <i class="fas fa-list-check small"></i>
                                                                    </div>
                                                                    <div>
                                                                        <h6 class="mb-0 fw-bold text-dark">{{ $activity->name }}</h6>
                                                                        <span class="text-muted small">نشاط مستوى 1</span>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <span class="badge bg-dark rounded-pill px-3">{{ $activity->weight }}% الأهمية</span>
                                                                    @if($activity->actions->count() > 0)
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="collapse" data-bs-target="#act-{{ $activity->id }}">
                                                                        <i class="fas fa-chevron-down"></i>
                                                                    </button>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            @if($activity->actions->count() > 0)
                                                            <div class="collapse show" id="act-{{ $activity->id }}">
                                                                <div class="p-0">
                                                                    <table class="table table-sm table-hover mb-0 align-middle">
                                                                        <thead class="bg-light-subtle">
                                                                            <tr class="text-muted small">
                                                                                <th class="ps-5 py-2">الإجراء / الخطوة التنفيذية</th>
                                                                                <th class="text-center py-2" style="width: 100px;">الوزن</th>
                                                                                <th class="text-center py-2">الفترة الزمنية</th>
                                                                                <th class="text-center py-2" style="width: 80px;">الأيام</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($activity->actions as $index => $action)
                                                                            <tr class="action-row {{ $loop->last ? 'last-item' : '' }}">
                                                                                <td class="hierarchy-branch {{ $loop->last ? 'last-item' : '' }} ps-5">
                                                                                    <div class="d-flex align-items-center">
                                                                                        <i class="fas fa-bolt text-warning me-2 small"></i>
                                                                                        <span class="fw-medium">{{ $action->name }}</span>
                                                                                    </div>
                                                                                </td>
                                                                                <td class="text-center">
                                                                                    <span class="badge bg-light text-dark border">{{ $action->weight }}%</span>
                                                                                </td>
                                                                                <td class="text-center small text-muted">
                                                                                    <i class="far fa-calendar-alt me-1"></i>
                                                                                    {{ $action->start_date_g ? $action->start_date_g->format('Y/m/d') : '-' }} 
                                                                                    <i class="fas fa-long-arrow-alt-left mx-1"></i>
                                                                                    {{ $action->end_date_g ? $action->end_date_g->format('Y/m/d') : '-' }}
                                                                                </td>
                                                                                <td class="text-center fw-bold text-primary">{{ $action->duration }}</td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                            @endif
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="p-3 border rounded bg-light text-center">
                                                        <span class="text-muted small">لا توجد أنشطة أو إجراءات مضافة لهذا المشروع.</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Summary -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-dark">ملخص مالي</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label class="text-muted small d-block mb-1">إجمالي التكلفة التقديرية (YER)</label>
                        <h3 class="fw-bold text-dark mb-0">
                            {{ number_format($plan->projects->where('cost_type', 'YER')->sum('cost'), 2) }}
                        </h3>
                    </div>
                    
                    <div class="d-grid gap-2">
                        @can('plans.edit', $plan)
                        <a href="{{ route('plans.edit', $plan->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> تعديل بيانات الخطة
                        </a>
                        @endcan
                        <hr>
                        <div class="small text-muted">
                            <i class="fas fa-info-circle me-1"></i> تم إنشاء هذا الرقم آلياً للنظام لضمان دقة التوثيق والأرشفة الرقمية.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .letter-spacing-1 { letter-spacing: 1px; }
    .bg-success-subtle { background-color: #e6fffa; }
    .bg-primary-subtle { background-color: #ebf4ff; }
    
    /* Hierarchy Visuals */
    .hierarchy-branch {
        position: relative;
        padding-right: 30px !important;
    }
    .hierarchy-branch::before {
        content: "";
        position: absolute;
        right: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #dee2e6;
    }
    .hierarchy-branch.last-item::before {
        bottom: 50%;
    }
    .hierarchy-branch::after {
        content: "";
        position: absolute;
        right: 15px;
        top: 50%;
        width: 10px;
        height: 2px;
        background-color: #dee2e6;
    }
    
    .action-row {
        background-color: #fcfdfe;
        transition: all 0.2s;
    }
    .action-row:hover {
        background-color: #f1f5fb;
    }
    
    .nested-table-container {
        border-right: 3px solid #0d6efd;
        border-radius: 0 4px 4px 0;
    }

    @media print {
        .btn, .config-actions, .card-footer { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #eee !important; }
        .sticky-top { position: relative !important; top: 0 !important; }
    }
</style>
@endsection
