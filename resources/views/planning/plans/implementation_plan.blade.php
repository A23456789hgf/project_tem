@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 bg-light-subtle">
    @can('plans.edit')
    <form action="{{ route('plans.implementation.update', $plan->id) }}" method="POST" id="implementationPlanForm">
    @endcan
        @csrf
        @method('PUT')
        
        <!-- Premium Header Card -->
        <div class="card shadow rounded-4 border-0 mb-4 overflow-hidden">
            <div class="card-header py-4 px-4 sticky-top" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-bottom: 5px solid #f97316;">
                <div class="d-flex justify-content-between align-items-center text-white">
                    <div>
                        <h4 class="m-0 fw-bold text-orange-gradient">
                            <i class="fas fa-clipboard-check me-2"></i> الخطة التنفيذية #{{ $plan->plan_number }}
                        </h4>
                        <p class="small text-white-50 mb-0 mt-1">إدارة واعتماد الأنشطة لجميع مشاريع الخطة التشغيلية</p>
                    </div>
                    <div class="d-flex gap-2">
                        @canany(['plans.print-implementation', 'plans.implementation.print', 'plans.print'], $plan)
                        <a href="{{ route('plans.implementation.print', $plan->id) }}" target="_blank" class="btn btn-sm btn-info rounded-pill px-3 text-white">
                            <i class="fas fa-print me-1"></i> طباعة الخطة
                        </a>
                        @endcanany
                        <a href="{{ route('plans.show', $plan->id) }}" class="btn btn-sm btn-outline-light rounded-pill px-3">
                            <i class="fas fa-eye me-1"></i> الخطة الأصلية
                        </a>
                        <a href="{{ route('plans.index') }}" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                            <i class="fas fa-list me-1"></i> قائمة الخطط
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                  
                    <div class="col-lg-3 col-md-6">
                        <div class="p-3 rounded-4 bg-white border shadow-sm h-100">
                            <label class="d-block small text-muted fw-bold mb-1">الجهة المقدمة</label>
                            <div class="fw-bold text-dark">{{ $plan->submittingEntity->name }}</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="p-3 rounded-4 bg-white border shadow-sm h-100">
                            <label class="d-block small text-muted fw-bold mb-1">تاريخ الإنشاء</label>
                            <div class="fw-bold text-dark">{{ $plan->created_at->format('Y/m/d') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="p-3 rounded-4 bg-white border shadow-sm h-100">
                            <label class="d-block small text-muted fw-bold mb-1">إجمالي المشاريع</label>
                            <div class="h4 m-0 fw-black text-primary">{{ $plan->projects->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Read-Only Operational Plan Projects Table -->
        <div class="card shadow-sm rounded-4 border-0 mb-5">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-navy"><i class="fas fa-list-ol me-2 text-warning"></i> قائمة مشاريع الخطة التشغيلية (للاطلاع)</h6>
                <span class="badge bg-light text-muted border">بيانات للقراءة فقط</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-subtle">
                        <tr class="small text-muted text-uppercase fw-bold">
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>اسم المشروع</th>
                            <th>الأهمية</th>
                            <th>الحالة</th>
                            <th>التكلفة التقديرية</th>
                            <th>الجهة المشاركة</th>
                            <th class="text-center pe-4">إضافة أنشطة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plan->projects as $index => $project)
                        <tr>
                            <td class="ps-4 fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-navy">{{ $project->name }}</div>
                                <div class="small text-muted">ID: #{{ $project->id }}</div>
                            </td>
                            <td>
                                @if($project->importance == 'very_important')
                                    <span class="badge bg-danger-subtle text-danger">هام جداً</span>
                                @elseif($project->importance == 'important')
                                    <span class="badge bg-warning-subtle text-warning">هام</span>
                                @else
                                    <span class="badge bg-light text-dark">عادي</span>
                                @endif
                            </td>
                            <td>
                                @if($project->status == 'new')
                                    <span class="badge bg-success-subtle text-success">جديد</span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary">مرحل</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-primary">{{ number_format($project->cost, 2) }} {{ $project->cost_type }}</div>
                            </td>
                            <td>{{ $project->participatingEntity->name }}</td>
                            <td class="text-center pe-4">
                                <button type="button" class="btn btn-sm btn-navy-gradient rounded-circle p-2 shortcut-to-project" 
                                        data-target="project-{{ $project->id }}" 
                                        title="إضافة أنشطة للمشروع"
                                        style="width: 35px; height: 35px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Hierarchical Implementation Table -->
        <div class="implementation-wrapper">
            @foreach($plan->projects as $project)
                <div class="project-container mb-5" id="project-{{ $project->id }}" data-project-id="{{ $project->id }}">
                    <!-- Project Title Row / Card -->
                    <div class="project-header-card d-flex justify-content-between align-items-center mb-0 p-3 rounded-top-4" style="background: #f8fafc; border: 1px solid #e2e8f0; border-bottom: none;">
                        <h6 class="m-0 fw-bold text-navy">
                            <i class="fas fa-folder-open text-warning me-2"></i> المشروع: {{ $project->name }}
                        </h6>
                        <div class="d-flex align-items-center gap-3">
                            <div class="small fw-bold text-muted d-none d-md-block"> <i class="fas fa-money-bill-wave me-1"></i> التكلفة المعتمدة: <span class="text-primary">{{ number_format($project->cost, 2) }} {{ $project->cost_type }}</span></div>
                            <button type="button" class="btn btn-sm btn-orange-gradient rounded-pill px-3 btn-add-activity" data-project-id="{{ $project->id }}">
                                <i class="fas fa-plus-circle me-1"></i> إضافة نشاط للمشروع
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive rounded-bottom-4 shadow-sm border overflow-hidden">
                        <table class="table-clean w-100 mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;" class="text-center ps-3">#</th>
                                    <th>النشاط الرئيسي</th>
                                    <th style="width: 120px;" class="text-center">الوزن %</th>
                                    <th style="width: 150px;" class="text-center">الإجراءات التنفيذية</th>
                                    <th style="width: 60px;" class="text-center pe-3">إدارة</th>
                                </tr>
                            </thead>
                            <tbody class="activities-tbody">
                                @foreach($project->activities as $actIndex => $activity)
                                    <tr class="activity-row level-1" data-index="{{ $actIndex }}">
                                        <td class="text-center ps-3">
                                            <button type="button" class="btn btn-sm btn-link p-0 expand-btn" title="توسيع">
                                                <i class="fas fa-chevron-left expand-icon text-muted"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <input type="text" name="projects[{{ $project->id }}][activities][{{ $actIndex }}][name]" 
                                                   class="activity-input-styled fw-bold" 
                                                   value="{{ $activity->name }}" placeholder="أدخل اسم النشاط الرئيسي..." required>
                                        </td>
                                        <td>
                                            <input type="number" name="projects[{{ $project->id }}][activities][{{ $actIndex }}][weight]" 
                                                   class="weight-input-styled activity-weight text-center" 
                                                   value="{{ $activity->weight }}" step="0.01" min="0" max="100" required>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn-manage-actions-pill position-relative" data-activity-index="{{ $actIndex }}">
                                                <i class="fas fa-bolt text-warning me-1"></i> الإجراءات
                                                <span class="badge rounded-pill bg-danger action-count-badge">{{ $activity->actions->count() }}</span>
                                            </button>
                                        </td>
                                        <td class="text-center pe-3">
                                            <button type="button" class="btn-delete-row text-danger border-0 bg-transparent btn-remove-row">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Nested Actions Row (Initially Hidden or Handled via Collapse) -->
                                    <tr class="actions-nested-row" style="display: none;">
                                        <td colspan="5" class="p-0 border-0">
                                            <div class="actions-sub-container py-3 px-4 ms-5 me-3 my-2 rounded-4" style="background: #fdfdfd; border: 2px dashed #f97316; box-shadow: inset 0 2px 10px rgba(0,0,0,0.02);">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <span class="fw-bold text-navy small"><i class="fas fa-tasks text-warning me-2"></i> الإجراءات التنفيذية لهذا النشاط</span>
                                                    <button type="button" class="btn btn-navy-gradient btn-sm rounded-pill btn-add-action" data-project-id="{{ $project->id }}" data-activity-index="{{ $actIndex }}">
                                                        <i class="fas fa-plus-circle me-1"></i> إضافة إجراء تنفيذي
                                                    </button>
                                                </div>
                                                <table class="table-inner-actions w-100">
                                                    <thead>
                                                        <tr>
                                                            <th>اسم الإجراء</th>
                                                            <th style="width: 80px;" class="text-center">الوزن %</th>
                                                            <th style="width: 140px;">تاريخ البدء</th>
                                                            <th style="width: 140px;">تاريخ الانتهاء</th>
                                                            <th style="width: 70px;" class="text-center">المدة</th>
                                                            <th style="width: 40px;"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($activity->actions as $actionIndex => $action)
                                                            <tr class="action-item-row">
                                                                <td>
                                                                    <input type="text" name="projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][name]" 
                                                                           class="action-input-styled" value="{{ $action->name }}" placeholder="اسم الإجراء..." required>
                                                                </td>
                                                                <td>
                                                                    <input type="number" name="projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][weight]" 
                                                                           class="action-weight-styled action-weight" value="{{ $action->weight }}" step="0.01" required>
                                                                </td>
                                                                <td>
                                                                    <input type="date" name="projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][start_date_g]" 
                                                                           class="form-control form-control-sm border-0 date-input start-date rounded-pill" value="{{ $action->start_date_g }}">
                                                                </td>
                                                                <td>
                                                                    <input type="date" name="projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][end_date_g]" 
                                                                           class="form-control form-control-sm border-0 date-input end-date rounded-pill" value="{{ $action->end_date_g }}">
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-navy-subtle text-navy border duration-display rounded-pill p-1 px-2">{{ $action->duration }}</span>
                                                                    <input type="hidden" name="projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][duration]" 
                                                                           class="duration-input" value="{{ $action->duration }}">
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-link text-danger p-0 btn-remove-row"><i class="fas fa-times-circle"></i></button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        
                        <div class="p-3 bg-white border-top d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-navy-gradient btn-sm rounded-pill px-4 fw-bold btn-add-activity" data-project-id="{{ $project->id }}">
                                <i class="fas fa-plus me-1"></i> إضافة نشاط رئيسي
                            </button>
                            <div class="weight-status-pill d-flex align-items-center gap-3">
                                <span class="small text-muted fw-bold">حالة توازن الأوزان:</span>
                                <div class="weight-badge px-3 py-1 rounded-pill fw-bold shadow-sm border total-weight-display">0%</div>
                                <i class="fas fa-check-circle text-success weight-status-icon d-none"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sticky Form Footer -->
        <div class="card shadow-lg border-0 mt-5 sticky-bottom rounded-4" style="z-index: 1000; bottom: 20px;">
            <div class="card-body d-flex justify-content-between align-items-center p-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="fa-stack fa-lg">
                        <i class="fas fa-circle fa-stack-2x text-warning-subtle text-opacity-25"></i>
                        <i class="fas fa-info-circle fa-stack-1x text-warning"></i>
                    </span>
                    <span class="text-navy fw-bold small">يرجى التأكد من أن مجموع أوزان الأنشطة لكل مشروع يساوي 100% قبل الاعتماد النهائي.</span>
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ route('plans.index') }}" class="btn btn-light rounded-pill px-5 border fw-bold text-muted">تراجع</a>
                    @can('plans.implementation', $plan)
                    <button type="submit" class="btn btn-orange-gradient text-white rounded-pill px-5 fw-bold shadow-lg">
                        <i class="fas fa-check-square me-2"></i> اعتماد الخطة التنفيذية
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Row Templates -->
<template id="activityRowTemplate">
    <tr class="activity-row level-1">
        <td class="text-center ps-3">
            <button type="button" class="btn btn-sm btn-link p-0 expand-btn" title="توسيع">
                <i class="fas fa-chevron-left expand-icon text-muted"></i>
            </button>
        </td>
        <td>
            <input type="text" name="projects[PROJ_ID][activities][ACT_IDX][name]" 
                   class="activity-input-styled fw-bold" placeholder="أدخل اسم النشاط الرئيسي..." required>
        </td>
        <td>
            <input type="number" name="projects[PROJ_ID][activities][ACT_IDX][weight]" 
                   class="weight-input-styled activity-weight text-center" value="0" step="0.01" min="0" max="100" required>
        </td>
        <td class="text-center">
            <button type="button" class="btn-manage-actions-pill position-relative" data-activity-index="ACT_IDX">
                <i class="fas fa-bolt text-warning me-1"></i> الإجراءات
                <span class="badge rounded-pill bg-danger action-count-badge">0</span>
            </button>
        </td>
        <td class="text-center pe-3">
            <button type="button" class="btn-delete-row text-danger border-0 bg-transparent btn-remove-row">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
    <tr class="actions-nested-row" style="display: none;">
        <td colspan="5" class="p-0 border-0">
            <div class="actions-sub-container py-3 px-4 ms-5 me-3 my-2 rounded-4" style="background: #fdfdfd; border: 2px dashed #f97316; box-shadow: inset 0 2px 10px rgba(0,0,0,0.02);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-navy small"><i class="fas fa-tasks text-warning me-2"></i> الإجراءات التنفيذية لهذا النشاط</span>
                    <button type="button" class="btn btn-navy-gradient btn-sm rounded-pill btn-add-action" data-project-id="PROJ_ID" data-activity-index="ACT_IDX">
                        <i class="fas fa-plus-circle me-1"></i> إضافة إجراء تنفيذي
                    </button>
                </div>
                <table class="table-inner-actions w-100">
                    <thead>
                        <tr>
                            <th>اسم الإجراء</th>
                            <th style="width: 80px;" class="text-center">الوزن %</th>
                            <th style="width: 140px;">تاريخ البدء</th>
                            <th style="width: 140px;">تاريخ الانتهاء</th>
                            <th style="width: 70px;" class="text-center">المدة</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </td>
    </tr>
</template>

<template id="actionRowTemplate">
    <tr class="action-item-row">
        <td>
            <input type="text" name="projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][name]" 
                   class="action-input-styled" placeholder="اسم الإجراء..." required>
        </td>
        <td>
            <input type="number" name="projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][weight]" 
                   class="action-weight-styled action-weight" value="0" step="0.01" required>
        </td>
        <td>
            <input type="date" name="projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][start_date_g]" 
                   class="form-control form-control-sm border-0 date-input start-date rounded-pill">
        </td>
        <td>
            <input type="date" name="projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][end_date_g]" 
                   class="form-control form-control-sm border-0 date-input end-date rounded-pill">
        </td>
        <td class="text-center">
            <span class="badge bg-navy-subtle text-navy border duration-display rounded-pill p-1 px-2">0</span>
            <input type="hidden" name="projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][duration]" 
                   class="duration-input" value="0">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-link text-danger p-0 btn-remove-row"><i class="fas fa-times-circle"></i></button>
        </td>
    </tr>
</template>

@endsection

@section('styles')
<style>
    :root {
        --navy-dark: #0f172a;
        --navy-light: #1e293b;
        --orange-bright: #f97316;
        --orange-hover: #ea580c;
    }

    .text-navy { color: var(--navy-dark); }
    .text-orange-gradient {
        background: linear-gradient(135deg, #fbbf24, #f97316);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .btn-navy-gradient {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        border: none;
        transition: all 0.3s;
    }
    .btn-navy-gradient:hover {
        background: linear-gradient(135deg, #334155, #1e293b);
        color: white;
        transform: translateY(-2px);
    }

    .btn-orange-gradient {
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        border: none;
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
    }
    .btn-orange-gradient:hover {
        background: linear-gradient(135deg, #ea580c, #c2410c);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4);
    }

    .table-clean thead th {
        background: #f1f5f9;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 15px;
        border: none;
    }

    .activity-row {
        background: white;
        border-bottom: 1px solid #f1f5f9 transition: all 0.2s;
    }
    .activity-row:hover { background: #f8fafc; }
    
    .activity-input-styled {
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 8px 15px;
        border-radius: 40px;
        width: 100%;
        font-size: 0.95rem;
        transition: all 0.3s;
    }
    .activity-input-styled:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        outline: none;
    }

    .weight-input-styled {
        border: 1px solid #e2e8f0;
        background: #f1f5f9;
        font-weight: bold;
        padding: 8px 5px;
        border-radius: 30px;
        width: 80px;
        transition: all 0.3s;
    }
    .weight-input-styled:focus {
        background: #fff;
        border-color: #f97316;
        outline: none;
    }

    .btn-manage-actions-pill {
        background: #fff4e5;
        border: 1px solid #ffd7ae;
        color: #c2410c;
        border-radius: 30px;
        padding: 6px 15px;
        font-size: 0.85rem;
        font-weight: 700;
        transition: all 0.3s;
    }
    .btn-manage-actions-pill:hover {
        background: #f97316;
        color: white;
        border-color: #f97316;
    }

    .action-count-badge {
        font-size: 0.65rem;
        margin-left: 5px;
    }

    .expand-btn { transition: transform 0.3s; }
    .expand-btn.expanded { transform: rotate(-90deg); }

    .table-inner-actions thead th {
        font-size: 0.7rem;
        background: transparent;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 10px;
        color: #64748b;
    }
    .action-item-row td { padding: 10px 5px; }
    .action-input-styled {
        border: 1px solid #e2e8f0;
        padding: 6px 12px;
        border-radius: 20px;
        width: 100%;
        font-size: 0.85rem;
    }
    .action-weight-styled {
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 5px;
        text-align: center;
        font-weight: bold;
        width: 60px;
        font-size: 0.85rem;
    }

    .bg-navy-subtle { background: #e2e8f0; }

    .sticky-bottom {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(15, 23, 42, 0.1);
    }
</style>
@endsection

@section('scripts')
<script>
    const ImplementationUI = {
        init: function() {
            this.bindEvents();
            this.calculateAllWeights();
        },

        bindEvents: function() {
            // Expand/Collapse Hierarchical Rows
            $(document).on('click', '.expand-btn', (e) => {
                const btn = $(e.currentTarget);
                const row = btn.closest('tr');
                const nestedRow = row.next('.actions-nested-row');
                
                btn.toggleClass('expanded');
                nestedRow.fadeToggle(300);
            });

            // "Action" Pill shortcut to expand
            $(document).on('click', '.btn-manage-actions-pill', (e) => {
                const row = $(e.currentTarget).closest('tr');
                row.find('.expand-btn').trigger('click');
            });

            // Add Activity
            $(document).on('click', '.btn-add-activity', (e) => {
                const projId = $(e.currentTarget).data('project-id');
                const tbody = $(e.currentTarget).closest('.project-container').find('.activities-tbody');
                const actIndex = tbody.find('.activity-row').length;
                
                let html = $('#activityRowTemplate').html();
                html = html.replace(/PROJ_ID/g, projId).replace(/ACT_IDX/g, actIndex);
                
                tbody.append(html);
                this.calculateAllWeights();
            });

            // Add Action
            $(document).on('click', '.btn-add-action', (e) => {
                const projId = $(e.currentTarget).data('project-id');
                const actIndex = $(e.currentTarget).data('activity-index');
                const tbody = $(e.currentTarget).closest('.actions-sub-container').find('tbody');
                const actionIndex = tbody.find('tr').length;
                
                let html = $('#actionRowTemplate').html();
                html = html.replace(/PROJ_ID/g, projId)
                           .replace(/ACT_IDX/g, actIndex)
                           .replace(/ACT_IDX_SEC/g, actionIndex);
                
                tbody.append(html);
                this.updateActionBadge(tbody.closest('.actions-nested-row').prev('.activity-row'));
            });

            // Remove Row
            $(document).on('click', '.btn-remove-row', (e) => {
                const tr = $(e.currentTarget).closest('tr');
                const parentRow = tr.closest('.actions-nested-row').prev('.activity-row');
                
                if (tr.hasClass('activity-row')) {
                    tr.next('.actions-nested-row').remove();
                }
                tr.fadeOut(200, function() { 
                    $(this).remove(); 
                    if (parentRow.length) ImplementationUI.updateActionBadge(parentRow);
                    ImplementationUI.calculateAllWeights(); 
                });
            });

            // Weight calculation
            $(document).on('input', '.activity-weight, .action-weight', () => {
                this.calculateAllWeights();
            });

            // Date & Duration calculation
            $(document).on('change', '.date-input', (e) => {
                const row = $(e.currentTarget).closest('tr');
                const start = row.find('.start-date').val();
                const end = row.find('.end-date').val();
                
                if (start && end) {
                    const startDate = new Date(start);
                    const endDate = new Date(end);
                    const diffTime = (endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    
                    row.find('.duration-display').text(diffDays > 0 ? diffDays : 0);
                    row.find('.duration-input').val(diffDays > 0 ? diffDays : 0);
                }
            });

            // Shortcut to project scrolling
            $(document).on('click', '.shortcut-to-project', function() {
                const targetId = $(this).data('target');
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                    
                    // Add a brief highlight effect
                    $(targetElement).find('.project-header-card').addClass('bg-warning-subtle');
                    setTimeout(() => {
                        $(targetElement).find('.project-header-card').removeClass('bg-warning-subtle');
                    }, 1500);
                }
            });

            // Form Global Validation
            $('#implementationPlanForm').on('submit', (e) => {
                let isValid = true;
                let errorMsgs = [];

                $('.project-container').each((i, container) => {
                    const projectName = $(container).find('.project-header-card h6').text().replace('المشروع:', '').trim();
                    
                    // 1. Validate Activities Weight
                    let projectTotal = 0;
                    $(container).find('.activity-weight').each((j, input) => {
                        projectTotal += parseFloat($(input).val()) || 0;
                    });

                    if (Math.abs(projectTotal - 100) > 0.1) {
                        isValid = false;
                        errorMsgs.push(`المشروع "${projectName}": مجموع أوزان الأنشطة يجب أن يكون 100% (المجموع الحالي: ${projectTotal.toFixed(1)}%)`);
                    }

                    // 2. Validate Actions weight within each activity
                    $(container).find('.activity-row').each((j, actRow) => {
                        const actName = $(actRow).find('.activity-input-styled').val() || 'نشاط غير مسمى';
                        const nestedRow = $(actRow).next('.actions-nested-row');
                        const actionWeights = nestedRow.find('.action-weight');
                        
                        if (actionWeights.length > 0) {
                            let actTotal = 0;
                            actionWeights.each((k, input) => {
                                actTotal += parseFloat($(input).val()) || 0;
                            });

                            if (Math.abs(actTotal - 100) > 0.1) {
                                isValid = false;
                                errorMsgs.push(`النشاط "${actName}" في مشروع "${projectName}": مجموع أوزان الإجراءات يجب أن يكون 100% (المجموع الحالي: ${actTotal.toFixed(1)}%)`);
                            }
                        }
                    });
                });

                if (!isValid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ في التحقق من الأوزان',
                        html: '<div class="text-start">' + errorMsgs.join('<br>') + '</div>',
                        confirmButtonText: 'حسناً'
                    });
                }
            });
        },

        updateActionBadge: function(activityRow) {
            const nestedRow = activityRow.next('.actions-nested-row');
            const count = nestedRow.find('.action-item-row').length;
            activityRow.find('.action-count-badge').text(count);
        },

        calculateAllWeights: function() {
            $('.project-container').each((index, container) => {
                let total = 0;
                $(container).find('.activity-weight').each((i, input) => {
                    total += parseFloat($(input).val()) || 0;
                });

                const display = $(container).find('.total-weight-display');
                const icon = $(container).find('.weight-status-icon');
                
                display.text(total.toFixed(1) + '%');
                
                if (Math.abs(total - 100) < 0.1) {
                    display.removeClass('border-danger text-danger bg-danger-subtle').addClass('border-success text-success bg-success-subtle');
                    icon.removeClass('d-none');
                } else {
                    display.removeClass('border-success text-success bg-success-subtle').addClass('border-danger text-danger bg-danger-subtle');
                    icon.addClass('d-none');
                }
            });
        }
    };

    $(document).ready(() => ImplementationUI.init());
</script>
@endsection
