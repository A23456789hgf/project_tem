@extends('layouts.app')

@section('styles')
<style>
    /* Premium Design System */
    :root {
        --premium-slate: #1e293b;
        --premium-indigo: #4f46e5;
        --premium-emerald: #10b981;
        --premium-amber: #f59e0b;
        --premium-rose: #e11d48;
        --premium-glass: rgba(255, 255, 255, 0.7);
        --premium-shadow-sm: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --premium-shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --premium-shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    }

    body { font-family: 'Outfit', 'Inter', 'Cairo', sans-serif; }

    .content-wrapper { background: #f1f5f9; min-height: 100vh; }

    /* Elegant Container */
    .premium-container {
        background: white;
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: var(--premium-shadow-lg);
        overflow: hidden;
        margin-bottom: 2.5rem;
        transition: transform 0.3s ease;
    }

    .premium-header {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        padding: 1.5rem 2rem;
        color: white;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    /* Floating Row Design */
    .elegant-table {
        width: 100%;
        min-width: 800px;
        border-collapse: separate;
        border-spacing: 0 12px;
        padding: 0 1.5rem 1.5rem 1.5rem;
    }

    .elegant-table thead th {
        padding: 1rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border: none;
        text-align: right;
    }

    .elegant-table tr.floating-row {
        background: white;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .elegant-table tr.floating-row td {
        padding: 1.25rem 1rem;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.95rem;
        color: #334155;
    }

    .elegant-table tr.floating-row td:first-child {
        border-right: 1px solid #f1f5f9;
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    .elegant-table tr.floating-row td:last-child {
        border-left: 1px solid #f1f5f9;
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }

    .elegant-table tr.floating-row:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 12px 20px -8px rgba(0,0,0,0.15);
        z-index: 10;
    }

    .elegant-table tr.floating-row:hover td {
        border-color: rgba(79, 70, 229, 0.2);
        background: rgba(79, 70, 229, 0.01);
    }

    /* Status Badges */
    .badge-premium {
        padding: 0.5rem 1.25rem;
        font-weight: 600;
        border-radius: 50px;
        font-size: 0.75rem;
        text-transform: capitalize;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid transparent;
    }

    .badge-premium-blue { background: #eff6ff; color: #1d4ed8; border-color: #dbeafe; }
    .badge-premium-green { background: #f0fdf4; color: #15803d; border-color: #dcfce7; }
    .badge-premium-amber { background: #fffbeb; color: #b45309; border-color: #fef3c7; }
    .badge-premium-rose { background: #fff1f2; color: #be123c; border-color: #ffe4e6; }

    /* Nested Premium */
    .premium-nested-container {
        padding: 1rem 2rem;
        background: #f8fafc;
        border-radius: 0 0 12px 12px;
    }

    .premium-nested-item {
        background: white;
        margin-bottom: 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: border-color 0.2s;
    }

    .premium-nested-item:hover {
        border-color: var(--premium-indigo);
    }

    /* Animations */
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-premium {
        animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
</style>
@endsection

@section('content')
<div class="content-wrapper">
    <div class="container-fluid py-5">

        <!-- Page Header -->
        <div class="page-header mb-5 animate-fade-in">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-muted">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-muted">المشاريع</a></li>
                            <li class="breadcrumb-item active text-primary fw-bold">تفاصيل المشروع</li>
                        </ol>
                    </nav>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="fas fa-layer-group me-2 text-primary"></i> عرض تفاصيل المشروع
                    </h1>
                    <p class="text-muted small mb-0"> رقم النموذج: {{ $project->form_number ?? '#' }}</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="d-flex justify-content-md-end gap-2 flex-wrap">
                        @if($project->status === 'awaiting_disbursement_plan')
                            @if($project->disbursementPlan && auth()->user()->hasPermission('disbursements.view', $project))
                                <a href="{{ route('projects.disbursement-plan.show', $project->id) }}" class="btn btn-warning shadow-sm fw-bold">
                                    <i class="fas fa-coins me-2"></i>خطة الصرف
                                </a>
                            @elseif(!$project->disbursementPlan && auth()->user()->hasPermission('disbursements.create', $project))
                                <a href="{{ route('projects.disbursement-plan.create', $project->id) }}" class="btn btn-warning shadow-sm fw-bold">
                                    <i class="fas fa-plus-circle me-2"></i>إعداد خطة الصرف
                                </a>
                            @endif
                        @endif

                        @if(in_array($project->status, ['in_progress', 'in_execution'], true))
                            @can('viewSchedule', $project)
                            <a href="{{ route('projects.schedule', $project->id) }}" class="btn btn-white shadow-sm fw-bold auth-perm-projects-schedule">
                                <i class="fas fa-calendar-alt me-2 text-info"></i>الجدول الزمني
                            </a>
                            @endcan
                        @endif

                        {{-- ===== CONFIRM DRAFT BUTTON ===== --}}
                        @if(in_array($project->status, ['draft', 'completed_draft']) && $project->isDraftComplete())
                            @can('finalize', $project)
                            <button type="button" class="btn btn-primary shadow-sm fw-bold auth-perm-projects-finalize" onclick="confirmDraftModal()">
                                <i class="fas fa-check-circle me-2"></i>تأكيد المسودة
                            </button>
                            @endcan
                        @endif

                        {{-- ===== REVERT TO DRAFT BUTTON ===== --}}
                        @if(($project->current_stage_order ?? 1) == 1)
                            @can('revert', $project)
                            <button type="button" class="btn btn-warning shadow-sm fw-bold auth-perm-projects-revert" onclick="revertToDraftModal()">
                                <i class="fas fa-undo-alt me-2"></i>الرجوع إلى مسودة
                            </button>
                            @endcan
                        @endif

                        @if(in_array($project->status, ['in_progress', 'in_execution'], true))
                            @if($project->disbursementPlan && auth()->user()->hasPermission('disbursements.view', $project))
                                <a href="{{ route('projects.disbursement-plan.show', $project->id) }}" class="btn btn-outline-warning shadow-sm fw-bold">
                                    <i class="fas fa-coins me-2"></i>خطة الصرف
                                </a>
                            @endif

                            @can('execute', $project)
                            <a href="{{ route('projects.execution', $project->id) }}" class="btn btn-success shadow-sm fw-bold auth-perm-projects-execute">
                                <i class="fas fa-rocket me-2"></i>التنفيذ
                            </a>
                            @endcan

                            @can('viewAny', [App\Models\Task::class, $project])
                            <a href="{{ route('projects.tasks.index', $project->id) }}" class="btn btn-info text-white shadow-sm fw-bold auth-perm-tasks-view">
                                <i class="fas fa-tasks me-2"></i>المهام
                            </a>
                            @endcan

                            @can('achievements', $project)
                            <a href="{{ route('projects.achievements.create', $project->id) }}" class="btn btn-primary text-white shadow-sm fw-bold auth-perm-projects-achievements">
                                <i class="fas fa-trophy me-2"></i>تسجيل إنجاز
                            </a>
                            @endcan
                        @endif

                        @can('print', $project)
                        <a href="{{ route('projects.print', $project->id) }}" target="_blank" class="btn btn-dark shadow-sm auth-perm-projects-print">
                            <i class="fas fa-print me-2"></i>طباعة
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== CONFIRM DRAFT MODAL ===== --}}
        <div class="modal fade" id="confirmDraftModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-primary text-white rounded-top-4 border-0 py-3">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-check-circle me-2"></i>تأكيد المسودة
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 flex-shrink-0">
                                <i class="fas fa-paper-plane text-primary fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-2">هل أنت متأكد من تأكيد المسودة؟</h6>
                                <p class="text-muted mb-0 small">بعد التأكيد سيتم إرسال المشروع تلقائياً إلى مراحل الاعتماد. لن تتمكن من تعديل البيانات بعد التأكيد إلا بالرجوع إلى مسودة.</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>إلغاء
                        </button>
                        @can('projects.finalize')
                        <form action="{{ route('projects.finalize', $project->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary fw-bold">
                                <i class="fas fa-check-circle me-2"></i>نعم، تأكيد المسودة
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== REVERT TO DRAFT MODAL ===== --}}
        @if(($project->current_stage_order ?? 1) == 1)
            @can('revert', $project)
            <div class="modal fade" id="revertToDraftModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header bg-warning text-dark rounded-top-4 border-0 py-3">
                            <h5 class="modal-title fw-bold">
                                <i class="fas fa-undo-alt me-2"></i>الرجوع إلى مسودة
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-warning bg-opacity-15 rounded-circle p-3 flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-warning fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">هل تريد الرجوع إلى حالة المسودة؟</h6>
                                    <p class="text-muted mb-0 small">سيتم إلغاء عملية التأكيد وإرجاع المشروع إلى حالة المسودة. يمكنك تعديل البيانات ثم إعادة التأكيد لاحقاً.</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 px-4 pb-4 gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>إلغاء
                            </button>
                            <form action="{{ route('projects.revert-draft', $project->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning fw-bold">
                                    <i class="fas fa-undo-alt me-2"></i>نعم، الرجوع إلى مسودة
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endcan
        @endif

        <!-- Project Tracking Information -->
        <div class="card shadow-sm border-0 rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-user-clock me-2"></i> بيانات التسجيل
                </h5>
                <div class="qr-code-badge bg-light p-2 rounded-3 border">
                    <img src="{{ $qrCodeBase64 }}" alt="Project QR" style="width: 80px; height: 80px;">
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 border-start border-4 border-primary h-100">
                            <div class="info-label">منشئ المشروع</div>
                            <div class="info-value fw-bold">{{ optional($project->createdBy)->name ?? 'غير محدد' }}</div>
                            <div class="small text-muted mt-1">{{ $project->creator_entity_name }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 border-start border-4 border-info h-100">
                            <div class="info-label">تاريخ الإنشاء</div>
                            <div class="info-value dir-ltr">{{ $project->created_at ? $project->created_at->format('Y-m-d H:i') : '-' }}</div>
                        </div>
                    </div>
                    @if($project->updated_by_user_id)
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 border-start border-4 border-warning h-100">
                            <div class="info-label">آخر تعديل</div>
                            <div class="info-value fw-bold">{{ optional($project->updatedBy)->name ?? 'غير محدد' }}</div>
                            <div class="small text-muted mt-1">{{ $project->updated_at ? $project->updated_at->format('Y-m-d H:i') : '-' }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- SECTION 1: البيانات الأساسية والأهداف والنتائج --}}
        {{-- ============================================================ --}}

        <!-- Project Basic Data -->
        <div id="basic-data-section" class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-info-circle me-2"></i> البيانات الأساسية
                </h5>
                <div id="projectStatusBar" class="d-flex gap-2">
                    @include('projects.partials.project-status-badge')
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="info-label d-block">اسم المشروع</label>
                            <div class="fs-5 fw-bold text-dark">{{ $project->project_name ?: (in_array($project->status, ['draft', 'completed_draft']) ? 'مسودة غير معنونة' : 'مشروع غير معنون') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="info-label">البرنامج</div>
                        <div class="info-value">{{ optional($project->program)->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="info-label">المجال الرئيسي</div>
                        <div class="info-value">{{ optional($project->domain)->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="info-label">المجال الفرعي</div>
                        <div class="info-value">{{ optional($project->subdomain)->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="info-label">التدخل</div>
                        <div class="info-value">{{ optional($project->intervention)->name ?? '-' }}</div>
                    </div>
                    <div class="col-12"><hr class="text-muted opacity-25"></div>
                    <div class="col-md-6 col-lg-6">
                        <div class="info-label">الأولوية</div>
                        <div class="info-value">
                            @if(optional($project->priority)->priority)
                                <span class="badge bg-secondary">{{ $project->priority->priority }}</span>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div class="col-12"><hr class="text-muted opacity-25"></div>
                    <div class="col-md-6 col-lg-3">
                        <div class="info-label">تاريخ البداية (م)</div>
                        <div class="info-value font-monospace">{{ $project->start_date_gregorian ? \Carbon\Carbon::parse($project->start_date_gregorian)->format('Y-m-d') : '-' }}</div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="info-label">تاريخ النهاية (م)</div>
                        <div class="info-value font-monospace">{{ $project->end_date_gregorian ? \Carbon\Carbon::parse($project->end_date_gregorian)->format('Y-m-d') : '-' }}</div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="info-label">تاريخ البداية (هـ)</div>
                        <div class="info-value">{{ $project->start_date_hijri ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="info-label">تاريخ النهاية (هـ)</div>
                        <div class="info-value">{{ $project->end_date_hijri ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Locations -->
        @if($project->locations && $project->locations->count() > 0)
        <div id="locations-section" class="premium-container animate-premium delay-1">
            <div class="premium-header">
                <div class="bg-white bg-opacity-20 rounded-circle p-2">
                    <i class="fas fa-map-marker-alt text-white"></i>
                </div>
                <h5 class="mb-0 fw-bold">مواقع المشروع</h5>
            </div>
            <div class="table-responsive">
                <table class="elegant-table">
                    <thead>
                        <tr>
                            <th width="80">#</th>
                            <th>المحافظة</th>
                            <th>المديرية</th>
                            <th>المنطقة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($project->locations as $location)
                        <tr class="floating-row">
                            <td class="fw-bold text-muted ps-4">{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ optional($location->governorate)->name ?? 'جميع المحافظات' }}</td>
                            <td>{{ optional($location->directorate)->name ?? 'جميع المديريات' }}</td>
                            <td class="text-muted">{{ optional($location->subArea)->name ?? $location->area ?? 'جميع المناطق/العزل' }} / {{ optional($location->village)->name ?? 'جميع القرى/الحارات' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted opacity-50">
                                <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                                <p>لا توجد مواقع مسجلة</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Project Details -->
        <div id="details-section" class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-file-alt me-2"></i> تفاصيل المشروع
                </h5>
            </div>
            <div class="card-body p-4">
                @if($project->detail)
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="d-flex align-items-center mb-3">
                                <div class="info-label mb-0 me-3">هل المشروع جزء من الخطة؟</div>
                                <div>
                                    @if($project->detail->is_part_of_plan)
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="fas fa-check me-1"></i> نعم</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill"><i class="fas fa-times me-1"></i> لا</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-light p-3 rounded-3">
                                <div class="info-label text-primary mb-2">ملخص المشروع</div>
                                <p class="mb-0 text-dark" style="line-height: 1.6;">{{ $project->detail->project_summary ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-label mb-2">المشكلة ومبررات التدخل</div>
                            <p class="mb-0 text-muted">{{ $project->detail->problem_and_justification ?? '-' }}</p>
                        </div>
                        <div class="col-12"><hr class="text-muted opacity-25"></div>
                        <div class="col-md-6">
                            <div class="info-label mb-2">مكونات المشروع</div>
                            <p class="mb-0 text-muted">{{ $project->detail->project_components ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label mb-2">الأثر المتوقع</div>
                            <p class="mb-0 text-muted">{{ $project->detail->expected_impact ?? '-' }}</p>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <p>لا توجد تفاصيل إضافية مسجلة</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Overall Objective -->
        <div class="content-section">
            <div class="section-header">
                <i class="fas fa-target"></i>
                الهدف الرئيسي
            </div>

            @if($project->mainObjectives && $project->mainObjectives->count() > 0)
                <div class="info-grid">
                    @foreach($project->mainObjectives as $objective)
                        <div class="info-item">
                            <div class="info-label">الهدف الرئيسي</div>
                            <div class="info-value">{{ $objective->objective ?? 'غير محدد' }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="no-data">
                    <i class="fas fa-inbox me-2"></i>لا توجد أهداف رئيسية
                </div>
            @endif
        </div>

        <!-- Specific Objectives, Results, and Outputs -->
        <div class="content-section">
            <div class="section-header">
                <i class="fas fa-list-check"></i>
                الأهداف الخاصة والنتائج والمخرجات
            </div>

            @if($project->specialObjectives && $project->specialObjectives->count() > 0)
                @foreach($project->specialObjectives as $objective)
                    <div class="subsection-title">
                        <i class="fas fa-angle-left ms-2"></i>الهدف الخاص: {{ $objective->objective ?? 'غير محدد' }}
                    </div>

                    @if($objective->results && $objective->results->count() > 0)
                        <div class="mt-4 mb-3 d-flex align-items-center gap-2 text-indigo-600 fw-bold">
                            <div class="bg-indigo-50 p-1 rounded">
                                <i class="fas fa-check-double small"></i>
                            </div>
                            <span>النتائج المستهدفة:</span>
                        </div>
                        <div class="table-responsive">
                            <table class="elegant-table">
                                <thead>
                                    <tr>
                                        <th width="60">#</th>
                                        <th width="250">النتيجة</th>
                                        <th>الوصف التفصيلي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($objective->results as $result)
                                    <tr class="floating-row">
                                        <td class="ps-3 text-slate-400 fw-bold">{{ $loop->iteration }}</td>
                                        <td class="fw-bold text-slate-800">{{ $result->result_name ?? 'غير محدد' }}</td>
                                        <td class="text-slate-500 small">{{ $result->description ?? 'بدون وصف' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-slate-400">لا توجد نتائج</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @foreach($objective->results as $result)
                            @if($result->outputs && $result->outputs->count() > 0)
                                <div class="mt-4 mb-3 d-flex align-items-center gap-2 text-emerald-600 fw-bold ms-4">
                                    <div class="bg-emerald-50 p-1 rounded">
                                        <i class="fas fa-arrow-left small"></i>
                                    </div>
                                    <span>المخرجات لـ: {{ $result->result_name ?? 'غير محدد' }}</span>
                                </div>
                                <div class="table-responsive ms-4">
                                    <table class="elegant-table">
                                        <thead>
                                            <tr>
                                                <th width="60">#</th>
                                                <th>المخرج</th>
                                                <th>الوصف</th>
                                                <th width="150" class="text-center">الكمية المتوقعة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($result->outputs as $output)
                                            <tr class="floating-row">
                                                <td class="ps-3 text-slate-400 fw-bold">{{ $loop->iteration }}</td>
                                                <td class="fw-bold text-slate-800">{{ $output->output ?? 'غير محدد' }}</td>
                                                <td class="text-slate-500 small">{{ $output->description ?? 'بدون وصف' }}</td>
                                                <td class="text-center">
                                                    <span class="badge-premium badge-premium-blue">{{ $output->target_value ?? '-' }}</span>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-slate-400">لا توجد مخرجات</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @else
                <div class="no-data">
                    <i class="fas fa-inbox me-2"></i>لا توجد أهداف خاصة
                </div>
            @endif
        </div>

        {{-- ============================================================ --}}
        {{-- SECTION 2: المخاطر والجهات والأنشطة والتكاليف --}}
        {{-- ============================================================ --}}

        <!-- الوضع المالي للمشروع (Phase 4) -->
        @include('projects.partials.financial_status')

        <!-- Risks and Stakeholders -->
        <div id="technical-details-section" class="content-section">
            <div class="section-header">
                <i class="fas fa-exclamation-triangle"></i>
                المخاطر والجهات ذات الصلة
            </div>

            <!-- Risks -->
            @if($project->risks && $project->risks->count() > 0)
                <div class="subsection-title mb-4 mt-4 fw-bold text-slate-700">
                    <i class="fas fa-exclamation-triangle ms-2 text-rose-500"></i>المخاطر المحتملة واستراتيجيات التخفيف
                </div>
                <div class="table-responsive">
                    <table class="elegant-table">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>نوع الخطر</th>
                                <th width="150" class="text-center">مستوى الخطورة</th>
                                <th>الحلول المقترحة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($project->risks as $risk)
                            <tr class="floating-row">
                                <td class="ps-3 text-slate-400 fw-bold">{{ $loop->iteration }}</td>
                                <td class="fw-bold text-slate-800">{{ $risk->risk ?? 'غير محدد' }}</td>
                                <td class="text-center">
                                    @php
                                        $premiumBadge = match($risk->risk_rate) {
                                            'high' => 'badge-premium-rose',
                                            'medium' => 'badge-premium-amber',
                                            'low' => 'badge-premium-green',
                                            default => 'badge-premium-blue'
                                        };
                                        $riskIcon = match($risk->risk_rate) {
                                            'high' => 'fa-fire',
                                            'medium' => 'fa-exclamation-circle',
                                            'low' => 'fa-check-circle',
                                            default => 'fa-question-circle'
                                        };
                                    @endphp
                                    <span class="badge-premium {{ $premiumBadge }}">
                                        <i class="fas {{ $riskIcon }}"></i>
                                        {{ $risk->risk_rate ?? 'غير محدد' }}
                                    </span>
                                </td>
                                <td class="small text-slate-500">{{ $risk->proposed_solution ?? 'بدون حل' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-slate-400">لا توجد مخاطر مسجلة</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Supervising Authorities -->
            @php
                $supervisingAuthorities = $project->supervisingAuthorities()->with(['authority', 'parent'])->get();
            @endphp

            @if($supervisingAuthorities->count() > 0)
                <div class="subsection-title mb-4 fw-bold text-slate-700">
                    <i class="fas fa-building ms-2 text-indigo-500"></i>الجهات المشرفة
                </div>
                <div class="table-responsive">
                    <table class="elegant-table">
                        <thead>
                            <tr>
                                <th width="80">#</th>
                                <th>اسم الجهة</th>
                                <th width="150" class="text-center">النوع</th>
                                <th>الجهة التابعة لها</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($supervisingAuthorities as $authority)
                            <tr class="floating-row">
                                <td class="text-center fw-bold text-slate-400">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                                            <i class="fas fa-eye small"></i>
                                        </div>
                                        <span class="fw-bold text-slate-700">{{ $authority->authority->agency_name ?? 'غير محدد' }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($authority->authority_type == 'internal')
                                        <span class="badge-premium badge-premium-blue">
                                            <i class="fas fa-home"></i>داخلية
                                        </span>
                                    @else
                                        <span class="badge-premium badge-premium-green">
                                            <i class="fas fa-external-link-alt"></i>خارجية
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($authority->parent && $authority->parent->agency_name)
                                        <div class="d-flex align-items-center gap-2 text-slate-500 small">
                                            <i class="fas fa-level-up-alt fa-rotate-90 opacity-40"></i>
                                            <span>{{ $authority->parent->agency_name }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-300 fst-italic">تبعية مباشرة</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Implementing Entities -->
            @if($project->implementingEntities && $project->implementingEntities->count() > 0)
                <div class="subsection-title mb-4 mt-5 fw-bold text-slate-700">
                    <i class="fas fa-cogs ms-2 text-indigo-500"></i>الجهات المنفذة
                </div>
                <div class="table-responsive">
                    <table class="elegant-table">
                        <thead>
                            <tr>
                                <th width="80">#</th>
                                <th>الجهة</th>
                                <th width="150" class="text-center">النوع</th>
                                <th>الجهة الرئيسية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($project->implementingEntities as $entity)
                            <tr class="floating-row">
                                <td class="text-center fw-bold text-slate-400">{{ $loop->iteration }}</td>
                                <td class="fw-bold text-slate-700">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-building text-slate-300"></i>
                                        {{ $entity->authority->agency_name ?? ($entity->internalEntity->name ?? 'غير محدد') }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($entity->authority_type === 'internal')
                                        <span class="badge-premium badge-premium-blue">داخلية</span>
                                    @else
                                        <span class="badge-premium badge-premium-green">خارجية</span>
                                    @endif
                                </td>
                                <td class="text-slate-500 small">
                                    @if($entity->authority_type == 'internal')
                                        @php
                                            $internalEnt = collect($internalEntities ?? [])->firstWhere('entity_name', optional($entity->internalEntity)->name);
                                        @endphp
                                        {{ $internalEnt['father_name'] ?? 'لا يوجد جهة أب' }}
                                    @else
                                        {{ $entity->parent->agency_name ?? 'لا يوجد' }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Participating & Beneficiary Entities -->
            @if(($project->participatingEntities && $project->participatingEntities->count() > 0) || ($project->beneficiaryEntities && $project->beneficiaryEntities->count() > 0))
                <div class="subsection-title mb-4 mt-5 fw-bold text-slate-700">
                    <i class="fas fa-users-cog ms-2 text-indigo-500"></i>الجهات المشاركة والمستفيدة
                </div>
                <div class="table-responsive">
                    <table class="elegant-table">
                        <thead>
                            <tr>
                                <th width="80">#</th>
                                <th width="150" class="text-center">الدور / النوع</th>
                                <th>الجهة</th>
                                <th>الجهة الأم / التبعية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $index = 1; @endphp
                            @foreach($project->participatingEntities as $entity)
                            <tr class="floating-row">
                                <td class="text-center fw-bold text-slate-400">{{ $index++ }}</td>
                                <td class="text-center">
                                    <span class="badge-premium badge-premium-amber">
                                        <i class="fas fa-handshake"></i> مشاركة
                                    </span>
                                </td>
                                <td class="fw-bold text-slate-700">{{ optional($entity->authority)->agency_name ?? 'غير محدد' }}</td>
                                <td class="text-slate-500 small">{{ optional($entity->parent)->agency_name ?? 'تبعية رئيسية' }}</td>
                            </tr>
                            @endforeach
                            @foreach($project->beneficiaryEntities as $entity)
                            <tr class="floating-row">
                                <td class="text-center fw-bold text-slate-400">{{ $index++ }}</td>
                                <td class="text-center">
                                    <span class="badge-premium badge-premium-rose">
                                        <i class="fas fa-hand-holding-heart"></i> مستفيدة
                                    </span>
                                </td>
                                <td class="fw-bold text-slate-700">{{ optional($entity->authority)->agency_name ?? 'غير محدد' }}</td>
                                <td class="text-slate-500 small">{{ optional($entity->parent)->agency_name ?? 'تبعية رئيسية' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Preliminary Activities and Costs -->
        @if($project->preliminaryActivities && $project->preliminaryActivities->count() > 0)
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-play-circle me-2"></i> الأنشطة التمهيدية
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="accordion accordion-flush" id="prelimActivitiesAccordion">
                    @foreach($project->preliminaryActivities as $actIndex => $activity)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="prelimHeading{{ $actIndex }}">
                                <button class="accordion-button {{ $actIndex !== 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#prelimCollapse{{ $actIndex }}" aria-expanded="{{ $actIndex === 0 ? 'true' : 'false' }}">
                                    <div class="d-flex align-items-center w-100 justify-content-between pe-3">
                                        <div>
                                            <span class="badge bg-secondary rounded-pill me-2">{{ $loop->iteration }}</span>
                                            <span class="fw-bold text-dark">{{ $activity->name ?? 'نشاط تمهيدي' }}</span>
                                        </div>
                                        <span class="badge bg-light text-dark border">الوزن: {{ $activity->weight ?? '0' }}%</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="prelimCollapse{{ $actIndex }}" class="accordion-collapse collapse {{ $actIndex === 0 ? 'show' : '' }}" aria-labelledby="prelimHeading{{ $actIndex }}" data-bs-parent="#prelimActivitiesAccordion">
                                <div class="accordion-body bg-light">
                                    @if($activity->procedures && $activity->procedures->count() > 0)
                                        <div class="table-responsive mt-3">
                                            <table class="elegant-table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>اسم الإجراء</th>
                                                        <th width="80" class="text-center">الأهمية</th>
                                                        <th>وسائل التحقق</th>
                                                        <th width="100" class="text-center">المدة</th>
                                                        <th width="140" class="text-end">ميزانية الإجراء</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($activity->procedures as $procedure)
                                                    <tr class="floating-row">
                                                        <td class="fw-bold text-slate-700 ps-4">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="fas fa-chevron-left x-small text-slate-300"></i>
                                                                {{ $procedure->procedure_name ?? '-' }}
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge-premium badge-premium-blue">{{ $procedure->weight ?? '-' }}%</span>
                                                        </td>
                                                        <td class="small text-slate-500">{{ $procedure->verification_means ?? '-' }}</td>
                                                        <td class="text-center small text-slate-600 font-monospace">{{ $procedure->duration_days ?? '-' }} ي</td>
                                                        <td class="text-end fw-bold text-emerald-600 font-monospace pe-3">
                                                            {{ $procedure->costs && $procedure->costs->sum('total') ? number_format($procedure->costs->sum('total'), 2) : '0.00' }}
                                                        </td>
                                                    </tr>
                                                    @if($procedure->costs && $procedure->costs->count() > 0)
                                                        <tr>
                                                            <td colspan="5" class="py-0 px-4">
                                                                <div class="premium-nested-container">
                                                                    @foreach($procedure->costs as $cost)
                                                                    <div class="premium-nested-item">
                                                                        <div class="d-flex align-items-center gap-3">
                                                                            <div class="text-indigo-400 opacity-50"><i class="fas fa-coins x-small"></i></div>
                                                                            <span class="fw-medium text-slate-600">{{ $cost->financialItem->name ?? 'بند' }}</span>
                                                                            <span class="badge border text-slate-400 rounded-pill x-small px-2">{{ $cost->unit->unit_name ?? '-' }}</span>
                                                                        </div>
                                                                        <div class="d-flex align-items-center gap-4">
                                                                            <span class="text-slate-400 x-small font-monospace">{{ number_format($cost->amount ?? 0, 2) }} × {{ $cost->quantity ?? 0 }}</span>
                                                                            <span class="fw-bold text-indigo-600 font-monospace">{{ number_format($cost->total ?? 0, 2) }}</span>
                                                                        </div>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-muted small fst-italic text-center py-2">لا توجد إجراءات مسجلة</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @if($project->preliminaryFinancialSummaries && $project->preliminaryFinancialSummaries->count() > 0)
            <div class="card-footer bg-light p-3">
                <h6 class="fw-bold text-dark mb-2 small text-uppercase">ملخص مالي (تمهيدي)</h6>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($project->preliminaryFinancialSummaries as $summary)
                    <div class="badge bg-white text-dark border p-2 shadow-sm rounded-3 d-flex align-items-center">
                        <span class="text-muted me-2">{{ $summary->item ?? '-' }}:</span>
                        <span class="fw-bold font-monospace text-primary">{{ number_format($summary->amount ?? 0, 2) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Executive Activities and Costs -->
        @if($project->executiveActivities && $project->executiveActivities->count() > 0)
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-cogs me-2"></i> الأنشطة التنفيذية
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="accordion accordion-flush" id="execActivitiesAccordion">
                    @foreach($project->executiveActivities as $actIndex => $activity)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="execHeading{{ $actIndex }}">
                                <button class="accordion-button {{ $actIndex !== 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#execCollapse{{ $actIndex }}" aria-expanded="{{ $actIndex === 0 ? 'true' : 'false' }}">
                                    <div class="d-flex align-items-center w-100 justify-content-between pe-3">
                                        <div>
                                            <span class="badge bg-success bg-opacity-75 rounded-pill me-2">{{ $loop->iteration }}</span>
                                            <span class="fw-bold text-dark">{{ $activity->name ?? 'نشاط تنفيذي' }}</span>
                                        </div>
                                        <span class="badge bg-light text-dark border">الوزن: {{ $activity->weight ?? '0' }}%</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="execCollapse{{ $actIndex }}" class="accordion-collapse collapse {{ $actIndex === 0 ? 'show' : '' }}" aria-labelledby="execHeading{{ $actIndex }}" data-bs-parent="#execActivitiesAccordion">
                                <div class="accordion-body bg-light">
                                    <div class="row g-2 mb-3 small text-muted">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-box-open me-2"></i> المخرج: {{ optional($activity->resultOutput)->output ?? (is_string($activity->output) ? $activity->output : null) ?? '-' }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-exclamation-circle me-2"></i> الخطر: {{ optional($activity->projectRisk)->risk ?? (is_string($activity->risk) ? $activity->risk : null) ?? '-' }}
                                            </div>
                                        </div>
                                    </div>

                                    @if($activity->actions && $activity->actions->count() > 0)
                                        <div class="table-responsive mt-3">
                                            <table class="elegant-table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>اسم الإجراء التنفيذي</th>
                                                        <th width="80" class="text-center">الأهمية</th>
                                                        <th>وسائل التحقق</th>
                                                        <th width="100" class="text-center">المدة</th>
                                                        <th width="140" class="text-end">ميزانية الإجراء</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($activity->actions as $action)
                                                    <tr class="floating-row">
                                                        <td class="fw-bold text-slate-700 ps-4">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="fas fa-play x-small text-indigo-300"></i>
                                                                {{ $action->action_name ?? '-' }}
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge-premium badge-premium-blue">{{ $action->weight ?? '-' }}%</span>
                                                        </td>
                                                        <td class="small text-slate-500">{{ $action->verification_means ?? '-' }}</td>
                                                        <td class="text-center small text-slate-600 font-monospace">{{ $action->duration_days ?? '-' }} ي</td>
                                                        <td class="text-end fw-bold text-emerald-600 font-monospace pe-3">
                                                            {{ $action->costs && $action->costs->sum('total') ? number_format($action->costs->sum('total'), 2) : '0.00' }}
                                                        </td>
                                                    </tr>
                                                    @if($action->costs && $action->costs->count() > 0)
                                                        <tr>
                                                            <td colspan="5" class="py-0 px-4">
                                                                <div class="premium-nested-container">
                                                                    @foreach($action->costs as $cost)
                                                                    <div class="premium-nested-item">
                                                                        <div class="d-flex align-items-center gap-3">
                                                                            <div class="text-indigo-400 opacity-50"><i class="fas fa-coins x-small"></i></div>
                                                                            <span class="fw-medium text-slate-600">{{ $cost->financialItem->name ?? 'بند' }}</span>
                                                                            <span class="badge border text-slate-400 rounded-pill x-small px-2">{{ $cost->unit->unit_name ?? '-' }}</span>
                                                                        </div>
                                                                        <div class="d-flex align-items-center gap-4">
                                                                            <span class="text-slate-400 x-small font-monospace">{{ number_format($cost->amount ?? 0, 2) }} × {{ $cost->quantity ?? 0 }}</span>
                                                                            <span class="fw-bold text-indigo-600 font-monospace">{{ number_format($cost->total ?? 0, 2) }}</span>
                                                                        </div>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-muted small fst-italic text-center py-2">لا توجد إجراءات مسجلة</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @if($project->executiveFinancialSummaries && $project->executiveFinancialSummaries->count() > 0)
            <div class="card-footer bg-light p-3">
                <h6 class="fw-bold text-dark mb-2 small text-uppercase">ملخص مالي (تنفيذي)</h6>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($project->executiveFinancialSummaries as $summary)
                    <div class="badge bg-white text-dark border p-2 shadow-sm rounded-3 d-flex align-items-center">
                        <span class="text-muted me-2">{{ $summary->item ?? '-' }}:</span>
                        <span class="fw-bold font-monospace text-primary">{{ number_format($summary->amount ?? 0, 2) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Financing and Costs -->
        @if($project->financings && $project->financings->count() > 0)
        <div class="modern-table-card animate-up delay-3">
            <div class="glass-header d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 text-success rounded-circle p-2">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h5 class="mb-0 fw-bold text-dark">مصادر التمويل والتكاليف</h5>
            </div>
            <div class="table-responsive">
                <table class="modern-table align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">المصدر</th>
                            <th>الجهة</th>
                            <th class="text-center">النوع</th>
                            <th class="text-end">المبلغ</th>
                            <th class="text-center">النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->financings as $financing)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ optional($financing->fundingSource)->name ?? '-' }}</td>
                            <td>{{ optional($financing->authority)->agency_name ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-primary border">{{ optional($financing->financingType)->name ?? '-' }}</span>
                            </td>
                            <td class="text-end font-monospace text-success fw-bold">{{ number_format($financing->financing_amount ?? 0, 2) }}</td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px; width: 60px; border-radius: 10px; background: #edf2f7;">
                                        <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: {{ $financing->financing_percentage ?? 0 }}%"></div>
                                    </div>
                                    <span class="small fw-bold">{{ number_format($financing->financing_percentage ?? 0, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($project->cost)
            <div class="card-footer bg-light p-4">
                <div class="row g-4 text-center">
                    <div class="col-md-4">
                        <div class="text-muted small mb-1">تكاليف التحضير</div>
                        <div class="h5 mb-0 fw-bold">{{ number_format($project->cost->preparatory_cost ?? 0, 2) }}</div>
                    </div>
                    <div class="col-md-4 border-start border-end">
                        <div class="text-muted small mb-1">تكاليف التنفيذ</div>
                        <div class="h5 mb-0 fw-bold">{{ number_format($project->cost->execution_cost ?? 0, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-primary small mb-1 fw-bold">إجمالي التكاليف</div>
                        <div class="h4 mb-0 fw-bold text-primary">{{ number_format($project->cost->total_cost ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- ============================================================ --}}
        {{-- SECTION 3: الحالة / مرحلة التنفيذ (بدون مسار الموافقات) --}}
        {{-- ============================================================ --}}

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-clipboard-check me-2"></i> حالة المشروع
                </h5>
            </div>
            <div class="card-body p-4">
                @if(in_array($project->status, ['in_progress', 'in_execution'], true))
                    <div class="alert alert-success border-0 shadow-sm rounded-3 p-4 mb-0 animate-fade-in">
                        <div class="d-flex align-items-center">
                            <div class="bg-success text-white rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-rocket fa-2x"></i>
                            </div>
                            <div>
                                <h4 class="alert-heading fw-bold mb-1">المشروع في مرحلة التنفيذ</h4>
                                <p class="mb-0 text-success-emphasis">تم اعتماد المشروع بالكامل وهو الآن في مرحلة التنفيذ والمتابعة.</p>
                            </div>
                        </div>
                    </div>
                @elseif($project->status === 'awaiting_disbursement_plan')
                    <div class="alert alert-warning border-0 shadow-sm rounded-3 p-4 mb-0 animate-fade-in">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning text-dark rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                    <i class="fas fa-coins fa-2x"></i>
                                </div>
                                <div>
                                    <h4 class="alert-heading fw-bold mb-1">المشروع بانتظار إعداد خطة الصرف</h4>
                                    <p class="mb-0 text-muted">تمت الموافقة النهائية على المشروع بنجاح، ويتطلب مسار الصرف إعداد خطة الدفعات واعتمادها للبدء بالتنفيذ.</p>
                                </div>
                            </div>
                            <div>
                                @if($project->disbursementPlan && auth()->user()->hasPermission('disbursements.view', $project))
                                    <a href="{{ route('projects.disbursement-plan.show', $project->id) }}" class="btn btn-warning fw-bold px-4 rounded-pill">
                                        <i class="fas fa-eye me-1"></i> عرض خطة الصرف
                                    </a>
                                @elseif(!$project->disbursementPlan && auth()->user()->hasPermission('disbursements.create', $project))
                                    <a href="{{ route('projects.disbursement-plan.create', $project->id) }}" class="btn btn-warning fw-bold px-4 rounded-pill">
                                        <i class="fas fa-plus-circle me-1"></i> إعداد خطة الصرف الآن
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif($project->status === 'completed')
                    <div class="alert alert-info border-0 shadow-sm rounded-3 p-4 mb-0 animate-fade-in">
                        <div class="d-flex align-items-center">
                            <div class="bg-info text-white rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-check-double fa-2x"></i>
                            </div>
                            <div>
                                <h4 class="alert-heading fw-bold mb-1">المشروع مكتمل ومغلق</h4>
                                <p class="mb-0 text-muted">تم استكمال جميع الأعمال وإخلاء كافة الدفعات المالية واعتماد إغلاق المشروع بنجاح.</p>
                            </div>
                        </div>
                    </div>
                @elseif(in_array($project->status, ['draft', 'completed_draft']))
                    @if(!$project->isDraftComplete())
                        <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-0 animate-fade-in">
                            <div class="d-flex align-items-start">
                                <div class="bg-warning text-white rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; flex-shrink: 0;">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h4 class="alert-heading fw-bold mb-2">المشروع غير مكتمل</h4>
                                    <p class="mb-3">يجب إكمال جميع البيانات المطلوبة قبل إرسال المشروع للموافقة.</p>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold">نسبة الإكمال</span>
                                            <span class="badge bg-warning">{{ $project->getCompletionPercentage() }}%</span>
                                        </div>
                                        <div class="progress" style="height: 12px; border-radius: 10px;">
                                            <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated"
                                                 role="progressbar"
                                                 style="width: {{ $project->getCompletionPercentage() }}%"
                                                 aria-valuenow="{{ $project->getCompletionPercentage() }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    <div class="bg-white bg-opacity-75 rounded-3 p-3 border border-warning border-opacity-25">
                                        <h6 class="fw-bold mb-3 text-dark">
                                            <i class="fas fa-clipboard-list me-2"></i>الأقسام المطلوبة:
                                        </h6>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center p-2 rounded {{ $project->validateBasicInfo() ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-light text-muted border border-secondary border-opacity-25' }}">
                                                    <i class="fas {{ $project->validateBasicInfo() ? 'fa-check-circle' : 'fa-circle' }} me-2"></i>
                                                    <span class="small fw-semibold">البيانات الأساسية</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center p-2 rounded {{ $project->validateObjectives() ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-light text-muted border border-secondary border-opacity-25' }}">
                                                    <i class="fas {{ $project->validateObjectives() ? 'fa-check-circle' : 'fa-circle' }} me-2"></i>
                                                    <span class="small fw-semibold">الأهداف والنتائج</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center p-2 rounded {{ $project->validateLocations() ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-light text-muted border border-secondary border-opacity-25' }}">
                                                    <i class="fas {{ $project->validateLocations() ? 'fa-check-circle' : 'fa-circle' }} me-2"></i>
                                                    <span class="small fw-semibold">المواقع</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center p-2 rounded {{ $project->validateStakeholders() ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-light text-muted border border-secondary border-opacity-25' }}">
                                                    <i class="fas {{ $project->validateStakeholders() ? 'fa-check-circle' : 'fa-circle' }} me-2"></i>
                                                    <span class="small fw-semibold">الجهات ذات الصلة</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center p-2 rounded {{ $project->validateActivities() ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-light text-muted border border-secondary border-opacity-25' }}">
                                                    <i class="fas {{ $project->validateActivities() ? 'fa-check-circle' : 'fa-circle' }} me-2"></i>
                                                    <span class="small fw-semibold">الأنشطة</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center p-2 rounded {{ $project->validateFinancing() ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-light text-muted border border-secondary border-opacity-25' }}">
                                                    <i class="fas {{ $project->validateFinancing() ? 'fa-check-circle' : 'fa-circle' }} me-2"></i>
                                                    <span class="small fw-semibold">التمويل والتكاليف</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 d-flex gap-2">
                                        @can('update', $project)
                                        <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-warning auth-perm-projects-edit">
                                            <i class="fas fa-edit me-2"></i>إكمال البيانات الناقصة
                                        </a>
                                        @endcan
                                        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                            <i class="fas fa-sync-alt me-2"></i>تحديث الحالة
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width: 100px; height: 100px;">
                                    <i class="fas fa-paper-plane text-primary" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">المسودة مكتملة وجاهزة للتأكيد</h4>
                            <p class="text-muted mb-4 mx-auto" style="max-width: 480px;">
                                جميع بيانات المشروع مكتملة. اضغط على "تأكيد المسودة" لإرسال المشروع إلى مراحل الاعتماد الرسمية.
                            </p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                @can('finalize', $project)
                                <button type="button" class="btn btn-primary btn-lg fw-bold px-5 shadow-sm auth-perm-projects-finalize" onclick="confirmDraftModal()">
                                    <i class="fas fa-check-circle me-2"></i>تأكيد المسودة
                                </button>
                                @endcan
                                @can('update', $project)
                                <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-outline-secondary btn-lg px-4 auth-perm-projects-edit">
                                    <i class="fas fa-edit me-2"></i>تعديل البيانات
                                </a>
                                @endcan
                            </div>
                        </div>
                    @endif
                @else
                    {{-- Pending approval or other statuses --}}
                    <div class="alert alert-info border-0 shadow-sm rounded-4 p-3 mb-3 animate-fade-in">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle fs-4 me-3 text-info"></i>
                            <div>
                                <strong>المشروع في مرحلة الاعتماد</strong>
                                <p class="mb-0 small text-info-emphasis">المشروع الآن قيد مراحل الاعتماد والموافقة. لا يمكن تعديل البيانات إلا في حال تم إرجاع المشروع إلى الجهة المنشئة للمراجعة.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-body p-4 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-clipboard-check text-primary fs-2"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">إدارة ومتابعة الاعتمادات</h5>
                            <p class="text-muted mb-3 mx-auto" style="max-width: 520px;">
                                تتم متابعة مسار الاعتماد واتخاذ القرارات الإدارية والفنية الخاصة بهذا المشروع حصرياً من خلال <strong>مركز المراجعة والاعتمادات</strong>.
                            </p>
                            <a href="{{ route('approvals.show', $project->id) }}" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                                <i class="fas fa-external-link-alt me-2"></i> الانتقال إلى مركز المراجعة والاعتمادات
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Activity History -->
                <div class="mt-5">
                    <div class="bg-light rounded-3 p-3">
                        @include('projects.partials.activity-history-display')
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Bootstrap loaded via footer component (local asset) --}}
<script>
    // Confirm Draft Modal
    function confirmDraftModal() {
        const modal = new bootstrap.Modal(document.getElementById('confirmDraftModal'));
        modal.show();
    }

    // Revert to Draft Modal
    function revertToDraftModal() {
        const modal = new bootstrap.Modal(document.getElementById('revertToDraftModal'));
        modal.show();
    }
</script>
@endsection