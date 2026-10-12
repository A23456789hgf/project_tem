@extends('layouts.app')

@section('styles')
<style>
    :root {
        --old-primary: #1e3a8a;
        --old-primary-light: #3b82f6;
        --old-bg: #f8fafc;
        --old-card-bg: #ffffff;
        --old-border: #e2e8f0;
    }

    .old-project-wrapper {
        background-color: var(--old-bg);
        min-height: calc(100vh - 70px);
        padding: 2rem 0;
    }

    .old-card {
        background: var(--old-card-bg);
        border: 1px solid var(--old-border);
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .old-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
    }

    .old-card-header {
        background: linear-gradient(135deg, #f1f5f9 0%, #ffffff 100%);
        border-bottom: 1px solid var(--old-border);
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .old-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .old-card-title i {
        color: var(--old-primary-light);
        font-size: 1.25rem;
    }

    .stat-card {
        border-radius: 1rem;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
    }

    .stat-card.total {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        color: #ffffff;
    }

    .stat-card.spent {
        background: linear-gradient(135deg, #065f46 0%, #10b981 100%);
        color: #ffffff;
    }

    .stat-card.remaining {
        background: linear-gradient(135deg, #991b1b 0%, #ef4444 100%);
        color: #ffffff;
    }

    .stat-icon {
        position: absolute;
        left: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 3.5rem;
        opacity: 0.15;
    }

    .stat-label {
        font-size: 0.95rem;
        font-weight: 600;
        opacity: 0.9;
        margin-bottom: 0.5rem;
    }

    .stat-value {
        font-size: 1.85rem;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .info-group {
        padding: 1rem;
        background: #f8fafc;
        border-radius: 0.75rem;
        border: 1px solid #f1f5f9;
        height: 100%;
    }

    .info-label {
        font-size: 0.825rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 0.35rem;
        display: block;
    }

    .info-value {
        font-size: 1rem;
        color: #0f172a;
        font-weight: 700;
        word-break: break-word;
    }

    .badge-old {
        background-color: #f59e0b;
        color: #ffffff;
        font-weight: 700;
        padding: 0.4rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .entity-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        margin-left: 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="old-project-wrapper" dir="rtl">
    <div class="container-fluid" style="max-width: 1400px;">
        
        <!-- Header & Breadcrumbs -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-decoration-none text-muted">قائمة المشاريع</a></li>
                        <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">تفاصيل مشروع سابق</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h1 class="h3 fw-bold text-dark mb-0">
                        {{ $project->project_name }}
                    </h1>
                    <span class="badge-old shadow-sm">
                        <i class="fas fa-history"></i> مشروع سابق (قديم)
                    </span>
                    @if($project->form_number)
                    <span class="badge bg-secondary px-3 py-2 rounded-pill fw-semibold">
                        رقم النموذج: {{ $project->form_number }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                @can('view', $project)
                <a href="{{ route('projects.achievements.index', $project->id) }}"
                    class="btn shadow-sm fw-bold px-4"
                    style="background: #1e3a5f; color: white; border: none;">
                    <i class="fas fa-list-alt me-2"></i> سجل الإنجازات
                </a>
                @endcan

                @can('update', $project)
                @if($project->is_data_completed)
                    {{-- البيانات مكتملة: زر إضافة إنجاز متاح --}}
                    <a href="{{ route('projects.achievements.create', $project->id) }}"
                        class="btn shadow-sm fw-bold px-4"
                        style="background: linear-gradient(135deg, #c9a961, #a8843f); color: white; border: none;">
                        <i class="fas fa-trophy me-2"></i> تسجيل إنجاز
                    </a>
                @else
                    {{-- زر استكمال البيانات متاح --}}
                    <a href="{{ route('projects.complete-data', $project->id) }}" class="btn shadow-sm fw-bold px-4"
                        style="background: #2c5f8a; color: white; border: none;"
                        title="استكمال بيانات المشروع (مرة واحدة فقط)">
                        <i class="fas fa-list-check me-2"></i> استكمال بيانات المشروع
                    </a>
                @endif
                @endcan

                @can('print', $project)
                <a href="{{ route('projects.print', $project->id) }}" target="_blank" class="btn btn-outline-dark shadow-sm fw-bold px-4">
                    <i class="fas fa-print me-2"></i> طباعة التقرير
                </a>
                @endcan
                <a href="{{ route('projects.index') }}" class="btn btn-primary shadow-sm fw-bold px-4">
                    <i class="fas fa-arrow-right me-2"></i> عودة للمشاريع
                </a>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        @php
            $totalCost = $project->cost->total_cost ?? 0;
            $spentAmount = $project->cost->spent_amount ?? 0;
            $remainingAmount = $project->cost->remaining_amount ?? 0;
            $spentPerc = ($totalCost > 0 && $spentAmount > 0) ? min(100, round(($spentAmount / $totalCost) * 100, 1)) : 0;
        @endphp
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card total shadow-sm">
                    <i class="fas fa-coins stat-icon"></i>
                    <div>
                        <div class="stat-label">إجمالي تكلفة المشروع المعتمدة</div>
                        <div class="stat-value"><span id="approvedTotal">{{ number_format($totalCost, 2) }}</span> <small class="fs-6 fw-normal">ريال</small></div>
                    </div>
                    @if(!empty($project->cost->hijri_year))
                    <div class="mt-3 pt-3 border-top border-light border-opacity-25 small d-flex justify-content-between">
                        <span>السنة الهجرية للتمويل:</span>
                        <strong class="fw-bold">{{ $project->cost->hijri_year }} هـ</strong>
                    </div>
                    @endif
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card spent shadow-sm">
                    <i class="fas fa-hand-holding-usd stat-icon"></i>
                    <div>
                        <div class="stat-label">المبلغ المصروف الفعلي</div>
                        <div class="stat-value"><span id="actualSpent"><span class="spinner-border spinner-border-sm" role="status"></span></span> <small class="fs-6 fw-normal">ريال</small></div>
                    </div>
                    <div class="mt-3 pt-3 border-top border-light border-opacity-25 small d-flex justify-content-between align-items-center">
                        <span>نسبة الصرف من التكلفة:</span>
                        <span class="badge bg-white text-dark fw-bold px-2 py-1" id="actual-spent-perc">-</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card remaining shadow-sm">
                    <i class="fas fa-wallet stat-icon"></i>
                    <div>
                        <div class="stat-label">المبلغ المتبقي</div>
                        <div class="stat-value"><span id="remainingAmount"><span class="spinner-border spinner-border-sm" role="status"></span></span> <small class="fs-6 fw-normal">ريال</small></div>
                    </div>
                    <div class="mt-3 pt-3 border-top border-light border-opacity-25 small d-flex justify-content-between">
                        <span>حالة الرصيد:</span>
                        <strong class="fw-bold" id="remaining-amount-status">-</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Grouped Section -->
        <div class="old-card mb-4" id="expenses-grouped-section" style="display: none;">
            <div class="old-card-header">
                <h3 class="old-card-title">
                    <i class="fas fa-layer-group"></i> العمليات المالية حسب البنود
                </h3>
                <div class="d-flex align-items-center gap-2">
                    <span id="financialSyncTime" class="small text-muted d-none d-md-inline-block"></span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-refresh-expenses" onclick="fetchProjectExpenses(true)">
                        <i class="fas fa-sync-alt"></i> تحديث البيانات
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <div id="expenses-error" class="alert alert-danger" style="display: none;"></div>
                <div id="expenses-loader" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <h5>جاري تحميل بيانات المصروفات من النظام المالي...</h5>
                    <p class="text-muted">يرجى الانتظار، قد يستغرق هذا بضع ثوانٍ</p>
                </div>
                <div id="expenses-grouped-content" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle" id="expenses-grouped-table">
                            <thead class="table-light">
                                <tr>
                                    <th>البند</th>
                                    <th>الحساب</th>
                                    <th>إجمالي المصروف</th>
                                    <th>تفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data injected via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table Section -->
        <div class="old-card mb-4" id="expenses-section" style="display: none;">
            <div class="old-card-header">
                <h3 class="old-card-title">
                    <i class="fas fa-file-invoice-dollar"></i> جدول مصروفات المشروع الكامل
                </h3>
            </div>
            <div class="card-body p-4">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">من تاريخ</label>
                        <input type="date" id="filter-from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" id="filter-to" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button class="btn btn-primary btn-sm w-100" onclick="fetchProjectExpenses()">بحث</button>
                        <button class="btn btn-secondary btn-sm w-100" onclick="resetFilters()">إعادة تعيين</button>
                    </div>
                </div>
                <div id="expenses-content" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle" id="expenses-table">
                            <thead class="table-light">
                                <tr>
                                    <th>تاريخ المستند</th>
                                    <th>رقم المستند / القيد</th>
                                    <th>الحساب</th>
                                    <th>البند</th>
                                    <th>المشروع</th>
                                    <th>مركز التكلفة</th>
                                    <th>مدين</th>
                                    <th>دائن</th>
                                    <th>الصافي</th>
                                    <th>الملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data injected via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Expense Type Details -->
        <div class="modal fade" id="expenseTypeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="expenseTypeModalLabel">تفاصيل البند: <span id="modal-expense-type" class="text-primary"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center align-middle" id="modal-expenses-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>تاريخ المستند</th>
                                        <th>رقم المستند</th>
                                        <th>الحساب</th>
                                        <th>مركز التكلفة</th>
                                        <th>مدين</th>
                                        <th>دائن</th>
                                        <th>الصافي</th>
                                        <th>الملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left/Main Column: Basic Information & Technical Details -->
            <div class="col-lg-8">
                
                <!-- البيانات التصنيفية والأساسية -->
                <div class="old-card">
                    <div class="old-card-header">
                        <h3 class="old-card-title">
                            <i class="fas fa-info-circle"></i> البيانات الأساسية والتصنيف
                        </h3>
                        <span class="text-muted small">تاريخ الإضافة: {{ $project->created_at ? $project->created_at->format('Y-m-d') : '-' }}</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <span class="info-label">اسم المشروع</span>
                                    <span class="info-value">{{ $project->project_name }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <span class="info-label">البرنامج التابع له</span>
                                    <span class="info-value text-primary">{{ $project->program->name ?? 'غير محدد' }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-group">
                                    <span class="info-label">المجال الرئيسي</span>
                                    <span class="info-value">{{ $project->domain->name ?? 'غير محدد' }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-group">
                                    <span class="info-label">المجال الفرعي</span>
                                    <span class="info-value">{{ $project->subdomain->name ?? 'غير محدد' }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-group">
                                    <span class="info-label">نوع التدخل</span>
                                    <span class="info-value">{{ $project->intervention->name ?? 'غير محدد' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <span class="info-label">أولوية المشروع</span>
                                    <span class="info-value">
                                        @if(is_object($project->priority))
                                            <span class="badge bg-info text-dark px-3 py-2">{{ $project->priority->name ?? $project->priority->priority ?? '-' }}</span>
                                        @elseif(!empty($project->priority))
                                            @php
                                                $pText = match((string) $project->priority) {
                                                    'high', 'عالية', 'عالي' => 'عالية',
                                                    'medium', 'متوسطة', 'متوسط' => 'متوسطة',
                                                    'low', 'منخفضة', 'منخفض' => 'منخفضة',
                                                    default => $project->priority,
                                                };
                                            @endphp
                                            <span class="badge bg-info text-dark px-3 py-2">{{ $pText }}</span>
                                        @else
                                            غير محدد
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <span class="info-label">هل المشروع جزء من خطة معتمدة؟</span>
                                    <span class="info-value">
                                        @if(($project->detail->is_part_of_plan ?? null) === 1 || ($project->detail->is_part_of_plan ?? null) === true)
                                            <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> نعم، ضمن الخطة</span>
                                        @else
                                            <span class="text-muted">غير محدد / مستقل</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- مواقع المشروع -->
                <div class="old-card">
                    <div class="old-card-header">
                        <h3 class="old-card-title">
                            <i class="fas fa-map-marked-alt"></i> مواقع المشروع
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        @if($project->locations && $project->locations->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">#</th>
                                            <th>المحافظة</th>
                                            <th>المديرية</th>
                                            <th>المنطقة / العزلة</th>
                                            <th>القرية / الحارة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($project->locations as $location)
                                        <tr>
                                            <td class="fw-bold text-muted">{{ $loop->iteration }}</td>
                                            <td class="fw-bold text-primary">{{ optional($location->governorate)->name ?? 'جميع المحافظات' }}</td>
                                            <td>{{ optional($location->directorate)->name ?? 'جميع المديريات' }}</td>
                                            <td class="text-secondary">{{ optional($location->subArea)->name ?? $location->area ?? 'جميع المناطق / العزل' }}</td>
                                            <td class="text-secondary">{{ optional($location->village)->name ?? 'جميع القرى / الحارات' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-map-marker-alt fa-2x mb-2 text-secondary opacity-50"></i>
                                <p class="mb-0 small">لم يتم تحديد مواقع للمشروع بعد</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- التواريخ والفترة الزمنية -->
                <div class="old-card">
                    <div class="old-card-header">
                        <h3 class="old-card-title">
                            <i class="fas fa-calendar-check"></i> الفترة الزمنية للمشروع
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-group border-start border-4 border-success">
                                    <span class="info-label">تاريخ بداية المشروع (ميلادي)</span>
                                    <span class="info-value">{{ $project->start_date_gregorian ? \Carbon\Carbon::parse($project->start_date_gregorian)->format('Y-m-d') : 'غير محدد' }}</span>
                                    @if($project->start_date_hijri)
                                    <span class="d-block small text-muted mt-1">الموافق هجرياً: {{ $project->start_date_hijri }} هـ</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group border-start border-4 border-danger">
                                    <span class="info-label">تاريخ انتهاء المشروع (ميلادي)</span>
                                    <span class="info-value">{{ $project->end_date_gregorian ? \Carbon\Carbon::parse($project->end_date_gregorian)->format('Y-m-d') : 'غير محدد' }}</span>
                                    @if($project->end_date_hijri)
                                    <span class="d-block small text-muted mt-1">الموافق هجرياً: {{ $project->end_date_hijri }} هـ</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تفاصيل ومبررات المشروع (إن وجدت) -->
                @if($project->detail && ($project->detail->project_summary || $project->detail->project_introduction || $project->detail->problem_and_justification || $project->detail->project_components || $project->detail->expected_impact))
                <div class="old-card">
                    <div class="old-card-header">
                        <h3 class="old-card-title">
                            <i class="fas fa-file-alt"></i> التفاصيل والمبررات والمكونات
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        @if($project->detail->project_summary)
                        <div class="mb-4">
                            <h6 class="fw-bold text-primary mb-2"><i class="fas fa-quote-right me-1"></i> ملخص المشروع</h6>
                            <div class="p-3 bg-light rounded-3 border text-dark line-height-lg" style="white-space: pre-line;">
                                {{ $project->detail->project_summary }}
                            </div>
                        </div>
                        @endif

                        @if($project->detail->project_introduction)
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-align-justify me-1"></i> مقدمة المشروع</h6>
                            <div class="p-3 bg-light rounded-3 border text-secondary" style="white-space: pre-line;">
                                {{ $project->detail->project_introduction }}
                            </div>
                        </div>
                        @endif

                        @if($project->detail->problem_and_justification)
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-exclamation-circle me-1"></i> المشكلة ومبررات المشروع</h6>
                            <div class="p-3 bg-light rounded-3 border text-secondary" style="white-space: pre-line;">
                                {{ $project->detail->problem_and_justification }}
                            </div>
                        </div>
                        @endif

                        @if($project->detail->project_components)
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-cubes me-1"></i> مكونات المشروع وأنشطته الرئيسية</h6>
                            <div class="p-3 bg-light rounded-3 border text-secondary" style="white-space: pre-line;">
                                {{ $project->detail->project_components }}
                            </div>
                        </div>
                        @endif

                        @if($project->detail->expected_impact)
                        <div class="mb-0">
                            <h6 class="fw-bold text-success mb-2"><i class="fas fa-chart-line me-1"></i> الأثر المتوقع للمشروع</h6>
                            <div class="p-3 bg-light rounded-3 border text-secondary" style="white-space: pre-line;">
                                {{ $project->detail->expected_impact }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>

            <!-- Right Sidebar: Beneficiaries, Entities, QR & Meta -->
            <div class="col-lg-4">
                
                <!-- المستفيدون والفئات المستهدفة -->
                <div class="old-card">
                    <div class="old-card-header">
                        <h3 class="old-card-title">
                            <i class="fas fa-users"></i> المستفيدون والفئات المستهدفة
                        </h3>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="p-3 bg-primary bg-opacity-10 rounded-3 mb-3 border border-primary border-opacity-25">
                            <span class="d-block text-muted small mb-1">إجمالي عدد المستفيدين</span>
                            <span class="fs-3 fw-bold text-primary">
                                {{ $project->number_of_beneficiaries ? number_format($project->number_of_beneficiaries) : '0' }}
                            </span>
                            <span class="small text-muted">مستفيد</span>
                        </div>
                        
                        <div class="text-start mt-3">
                            <span class="info-label">الفئة المستهدفة الرئيسية</span>
                            <div class="fw-bold text-dark p-2 bg-light rounded mb-3">
                                {{ $project->targetCategory->name ?? 'غير محدد' }}
                            </div>

                            @if($project->main_directives || $project->subdirectives)
                            <span class="info-label">التوجيهات أو الملاحظات المستهدفة</span>
                            <div class="small text-secondary p-2 bg-light rounded">
                                {{ $project->main_directives }} {{ $project->subdirectives ? ' - ' . $project->subdirectives : '' }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- الجهات المنفذة والمشرفة والممولة -->
                <div class="old-card">
                    <div class="old-card-header d-flex justify-content-between align-items-center">
                        <h3 class="old-card-title mb-0">
                            <i class="fas fa-building"></i> المؤسسات والجهات ذات العلاقة
                        </h3>
                        @can('update', $project)
                        @if(!$project->is_data_completed)
                        <a href="{{ route('projects.complete-data', $project->id) }}" class="btn btn-sm shadow-sm"
                            style="background: #2c5f8a; color: white; border: none;"
                            title="استكمال بيانات المشروع (مرة واحدة فقط)">
                            <i class="fas fa-list-check me-1"></i> استكمال بيانات المشروع
                        </a>
                        @else
                        <span class="badge bg-success px-3 py-2 rounded-pill">
                            <i class="fas fa-check-circle me-1"></i> البيانات مكتملة
                        </span>
                        @endif
                        @endcan
                    </div>
                    <div class="card-body p-4">
                        <!-- الجهات المنفذة -->
                        <div class="mb-4">
                            <span class="info-label d-block mb-2 text-dark fw-bold"><i class="fas fa-cogs me-1 text-primary"></i> الجهات المنفذة</span>
                            @if($project->implementingEntities->isNotEmpty())
                                @foreach($project->implementingEntities as $entity)
                                    <div class="entity-tag">
                                        <i class="fas fa-check-circle"></i>
                                        {{ $entity->authority->agency_name ?? ($entity->internalEntity->name ?? 'جهة منفذة') }}
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted small">لم يتم تحديد جهات منفذة</span>
                            @endif
                        </div>

                        <!-- مصادر التمويل -->
                        <div class="mb-4">
                            <span class="info-label d-block mb-2 text-dark fw-bold"><i class="fas fa-hand-holding-usd me-1 text-success"></i> الجهات الممولة</span>
                            @if($project->financings->isNotEmpty())
                                @foreach($project->financings as $financing)
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-2 border">
                                        <span class="fw-semibold text-dark small">
                                            {{ $financing->authority->agency_name ?? ($financing->fundingSource->name ?? 'جهة ممولة') }}
                                        </span>
                                        @if($financing->amount)
                                        <span class="badge bg-success">
                                            {{ number_format($financing->amount, 2) }} ريال
                                        </span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted small">لم يتم تحديد جهات ممولة في النظام</span>
                            @endif
                        </div>

                        <!-- الجهات المشرفة -->
                        <div class="mb-4">
                            <span class="info-label d-block mb-2 text-dark fw-bold"><i class="fas fa-eye me-1 text-info"></i> الجهات المشرفة</span>
                            @if($project->supervisingAuthorities->isNotEmpty())
                                @foreach($project->supervisingAuthorities as $supervising)
                                    <div class="entity-tag" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534;">
                                        <i class="fas fa-shield-alt"></i>
                                        {{ $supervising->authority->agency_name ?? ($supervising->internalEntity->name ?? 'جهة مشرفة') }}
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted small">لم يتم تحديد جهات مشرفة</span>
                            @endif
                        </div>

                        <!-- الجهات المشاركة -->
                        <div class="mb-4">
                            <span class="info-label d-block mb-2 text-dark fw-bold"><i class="fas fa-handshake me-1 text-warning"></i> الجهات المشاركة</span>
                            @if($project->participatingEntities->isNotEmpty())
                                @foreach($project->participatingEntities as $entity)
                                    <div class="entity-tag" style="background: #fef9c3; border-color: #fde047; color: #854d0e;">
                                        <i class="fas fa-handshake"></i>
                                        {{ $entity->authority->agency_name ?? ($entity->internalEntity->name ?? 'جهة مشاركة') }}
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted small">لم يتم تحديد جهات مشاركة</span>
                            @endif
                        </div>

                        <!-- الجهات المستفيدة -->
                        <div class="mb-2">
                            <span class="info-label d-block mb-2 text-dark fw-bold"><i class="fas fa-hand-holding-heart me-1 text-danger"></i> الجهات المستفيدة</span>
                            @if($project->beneficiaryEntities->isNotEmpty())
                                @foreach($project->beneficiaryEntities as $entity)
                                    <div class="entity-tag" style="background: #fee2e2; border-color: #fca5a5; color: #991b1b;">
                                        <i class="fas fa-heart"></i>
                                        {{ $entity->authority->agency_name ?? ($entity->internalEntity->name ?? 'جهة مستفيدة') }}
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted small">لم يتم تحديد جهات مستفيدة</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($qrCodeBase64))
                <div class="old-card text-center p-4">
                    <span class="info-label mb-2">رمز الاستجابة السريعة (QR Code) للمشروع</span>
                    <img src="{{ $qrCodeBase64 }}" alt="QR Code" class="img-fluid rounded border p-2 bg-white" style="max-width: 150px;">
                </div>
                @endif

            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
// تفعيل tooltips على الأزرار المعطّلة
document.addEventListener('DOMContentLoaded', function () {
    var tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipEls.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
});

const projectId = {{ $project->id }};
const totalCost = {{ $totalCost }};

let allTransactions = [];
let financialRequestInProgress = false;
let financialRefreshTimer = null;

function resetFilters() {
    $('#filter-from').val('');
    $('#filter-to').val('');
    fetchProjectExpenses(true);
}

function showExpenseDetails(expenseType) {
    $('#modal-expense-type').text(expenseType);
    const tbody = $('#modal-expenses-table tbody');
    tbody.empty();
    
    const filtered = allTransactions.filter(t => {
        let type = t.claim_expense_type || '';
        type = type.trim().replace(/\s+/g, ' ');
        return type === expenseType;
    });
    
    if (filtered.length > 0) {
        filtered.forEach(entry => {
            const debit = parseFloat(entry.debit || 0);
            const credit = parseFloat(entry.credit || 0);
            const net = debit - credit;
            
            tbody.append(`
                <tr>
                    <td>${entry.posting_date || '-'}</td>
                    <td class="fw-bold">${entry.voucher_no || entry.gl_entry || '-'}</td>
                    <td>${entry.account || '-'}</td>
                    <td>${entry.cost_center || '-'}</td>
                    <td class="text-danger fw-bold">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(debit)}</td>
                    <td class="text-success fw-bold">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(credit)}</td>
                    <td class="fw-bold">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(net)}</td>
                    <td class="text-start">${entry.remarks || '-'}</td>
                </tr>
            `);
        });
    } else {
        tbody.append(`<tr><td colspan="8" class="text-muted py-3">لا توجد عمليات</td></tr>`);
    }
    
    new bootstrap.Modal(document.getElementById('expenseTypeModal')).show();
}

function fetchProjectExpenses(force = false) {
    if (financialRequestInProgress) return;
    financialRequestInProgress = true;

    if (force) {
        $('#btn-refresh-expenses').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> تحديث');
        if ($('#expenses-content').is(':hidden')) {
            $('#expenses-grouped-section, #expenses-section').show();
            $('#expenses-loader').show();
            $('#expenses-grouped-content, #expenses-content').hide();
        }
        $('#expenses-error').hide();
        
        if ($('#actualSpent').text().trim() === '') {
            $('#actualSpent, #remainingAmount').html('<span class="spinner-border spinner-border-sm"></span>');
            $('#actual-spent-perc, #remaining-amount-status').text('-');
        }
    }

    const from = $('#filter-from').val();
    const to = $('#filter-to').val();
    
    $.ajax({
        url: `/projects/${projectId}/expenses`,
        method: 'GET',
        data: { from_date: from, to_date: to, t: new Date().getTime() },
        success: function(response) {
            $('#btn-refresh-expenses').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> تحديث البيانات');
            $('#expenses-loader').hide();
            
            if (response.success) {
                $('#expenses-grouped-content, #expenses-content').show();
                allTransactions = response.transactions || response.entries || [];
                
                const summary = response.summary || {};
                const spentAmount = summary.actual_spent ?? 0;
                const remainingAmount = summary.remaining_amount ?? (totalCost - spentAmount);
                const approvedTotalAmount = summary.approved_total ?? totalCost;
                
                const spentPerc = (approvedTotalAmount > 0 && spentAmount > 0) ? Math.min(100, Math.round((spentAmount / approvedTotalAmount) * 100 * 10) / 10) : 0;
                
                $('#approvedTotal').text(new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(approvedTotalAmount));
                $('#actualSpent').text(new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(spentAmount));
                $('#remainingAmount').text(new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(remainingAmount));
                $('#actual-spent-perc').text(spentPerc + '%');
                
                if (remainingAmount >= 0) {
                    $('#remaining-amount-status').text('متبقي متاح').removeClass('text-danger').addClass('text-white');
                } else {
                    $('#remaining-amount-status').text('تجاوز التكلفة المعتمدة').removeClass('text-white').addClass('text-warning');
                }
                
                if (response.synced_at) {
                    $('#financialSyncTime').text('آخر مزامنة: ' + response.synced_at.substring(11, 19));
                }
                
                const gtbody = $('#expenses-grouped-table tbody');
                gtbody.empty();
                if (response.expense_types && response.expense_types.length > 0) {
                    response.expense_types.forEach(g => {
                        gtbody.append(`
                            <tr>
                                <td class="fw-bold">${g.claim_expense_type}</td>
                                <td>${g.account}</td>
                                <td class="fw-bold">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(g.total)}</td>
                                <td><button class="btn btn-sm btn-outline-primary" onclick="showExpenseDetails('${g.claim_expense_type}')">عرض التفاصيل</button></td>
                            </tr>
                        `);
                    });
                } else {
                    gtbody.append(`<tr><td colspan="4" class="text-muted py-3">لا توجد بنود مصروفات</td></tr>`);
                }

                const tbody = $('#expenses-table tbody');
                tbody.empty();
                if (allTransactions.length > 0) {
                    allTransactions.forEach(entry => {
                        const debit = parseFloat(entry.debit || 0);
                        const credit = parseFloat(entry.credit || 0);
                        const net = debit - credit;
                        
                        tbody.append(`
                            <tr>
                                <td>${entry.posting_date || '-'}</td>
                                <td class="fw-bold">${entry.voucher_no || entry.gl_entry || '-'}</td>
                                <td>${entry.account || '-'}</td>
                                <td>${entry.claim_expense_type || '-'}</td>
                                <td>${entry.project || '-'}</td>
                                <td>${entry.cost_center || '-'}</td>
                                <td class="text-danger fw-bold">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(debit)}</td>
                                <td class="text-success fw-bold">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(credit)}</td>
                                <td class="fw-bold">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(net)}</td>
                                <td class="text-start">${entry.remarks || '-'}</td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.append(`<tr><td colspan="10" class="text-muted py-3">لا توجد عمليات مسجلة</td></tr>`);
                }
            } else {
                if (force) $('#expenses-error').text(response.error || 'حدث خطأ').show();
            }
        },
        error: function() {
            if (force) {
                $('#btn-refresh-expenses').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> تحديث البيانات');
                $('#expenses-loader').hide();
                $('#expenses-error').text('تعذر الاتصال بخادم ERPNext. يرجى المحاولة مرة أخرى.').show();
                $('#actualSpent').html('<span class="text-danger fs-6">تعذر الجلب</span>');
                $('#remainingAmount').html('<span class="text-danger fs-6">غير متاح</span>');
            }
        },
        complete: function() {
            financialRequestInProgress = false;
        }
    });
}

$(document).ready(function() {
    fetchProjectExpenses(true);
    
    // Auto-refresh every 10 seconds
    financialRefreshTimer = setInterval(() => {
        // Only auto-refresh if the tab is visible
        if (document.visibilityState === 'visible') {
            fetchProjectExpenses(false);
        }
    }, 10000);
    
    // Fetch immediately when tab becomes visible again
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            fetchProjectExpenses(false);
        }
    });
});
</script>
@endsection
