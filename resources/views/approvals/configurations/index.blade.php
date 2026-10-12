@extends('layouts.app')

@section('title', 'إدارة وتكوين مسارات الاعتماد المركزية')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" dir="rtl">
    {{-- Header --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-sitemap text-info me-2"></i> إدارة وتكوين مسارات الاعتماد المركزية
                    </h3>
                    <p class="text-white-50 mb-0 small">
                        المصدر المركزي الموحد لإعداد مسارات الموافقات، عدد المراحل، المسؤوليات، المرفقات المطلوبة، والإجراءات المسموحة للمشاريع وكافة أنواع الطلبات
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('approvals.index') }}" class="btn btn-outline-light rounded-pill px-3 btn-sm fw-semibold">
                        <i class="fas fa-clipboard-check me-1"></i> مركز المعاملات النشطة
                    </a>
                    @can('stages.create')
                    <a href="{{ route('approvals.configurations.create', ['tab' => $tab, 'workflow_type' => $workflowType]) }}" class="btn btn-primary rounded-pill px-4 btn-sm fw-semibold shadow-sm">
                        <i class="fas fa-plus me-1"></i> إضافة مسار جديد
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Main Navigation Tabs (Transactions vs Configuration Engine) --}}
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-4 shadow-sm border">
        <li class="nav-item">
            <a class="nav-link rounded-pill fw-bold text-secondary" href="{{ route('approvals.index') }}">
                <i class="fas fa-tasks me-1"></i> المعاملات والطلبات النشطة
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active rounded-pill fw-bold bg-primary text-white" href="{{ route('approvals.configurations.index') }}">
                <i class="fas fa-cogs me-1"></i> إعداد ومسارات الاعتماد (Workflow Engine)
            </a>
        </li>
    </ul>

    {{-- Workflow Types Filter Bar --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <span class="fw-bold text-dark"><i class="fas fa-layer-group text-primary me-2"></i>اختر نوع المعاملة / المسار:</span>
        </div>
        <div class="card-body pt-0">
            <div class="d-flex gap-2 flex-wrap">
                @foreach($workflowTypes as $wfKey => $wfTitle)
                    <a href="{{ route('approvals.configurations.index', ['workflow_type' => $wfKey, 'tab' => $tab, 'entity_id' => request('entity_id'), 'authority_id' => request('authority_id')]) }}" 
                       class="btn btn-sm rounded-pill {{ $workflowType === $wfKey ? 'btn-primary shadow-sm fw-bold' : 'btn-outline-secondary' }}">
                        {{ $wfTitle }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Entity Scope Tabs (Internal vs External) --}}
    <ul class="nav nav-tabs mb-4 border-bottom border-primary">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'internal' ? 'active fw-bold border-primary border-bottom-0' : 'text-muted' }}" 
               href="{{ route('approvals.configurations.index', ['tab' => 'internal', 'workflow_type' => $workflowType]) }}">
                <i class="fas fa-building me-1"></i> الجهات الداخلية (Internal Entities)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'external' ? 'active fw-bold border-primary border-bottom-0' : 'text-muted' }}" 
               href="{{ route('approvals.configurations.index', ['tab' => 'external', 'workflow_type' => $workflowType]) }}">
                <i class="fas fa-landmark me-1"></i> الجهات الخارجية ومسارات الربط (External Authorities)
            </a>
        </li>
    </ul>

    {{-- Filter Form --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('approvals.configurations.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="workflow_type" value="{{ $workflowType }}">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">فلترة بالجهة المحددة:</label>
                    @if($tab === 'internal')
                    <select name="entity_id" class="form-select select2-search" onchange="this.form.submit()">
                        <option value="">جميع الجهات الداخلية</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" @selected(request('entity_id') == $entity->id)>
                                {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>
                    @else
                    <select name="authority_id" class="form-select select2-search" onchange="this.form.submit()">
                        <option value="">جميع الجهات الخارجية</option>
                        @foreach($authorities as $authority)
                            <option value="{{ $authority->id }}" @selected(request('authority_id') == $authority->id)>
                                {{ $authority->name }}
                            </option>
                        @endforeach
                    </select>
                    @endif
                </div>
                <div class="col-md-2">
                    @if(request('entity_id') || request('authority_id'))
                        <a href="{{ route('approvals.configurations.index', ['tab' => $tab, 'workflow_type' => $workflowType]) }}" class="btn btn-outline-secondary w-100 btn-sm">
                            <i class="fas fa-times me-1"></i> إلغاء الفلتر
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4"><i class="fas fa-check-circle me-1"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4"><i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}</div>
    @endif

    {{-- Stages Table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-list-ol text-primary me-2"></i>
                مراحل مسار [{{ $workflowTypes[$workflowType] ?? $workflowType }}]
            </h6>
            <span class="badge bg-light text-dark border">
                إجمالي المراحل: {{ $stages->total() }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small">
                        <tr>
                            <th class="py-3 px-4">الجهة</th>
                            <th class="py-3 px-4 text-center">الترتيب</th>
                            <th class="py-3 px-4">المرحلة</th>
                            <th class="py-3 px-4">المسؤول المحدد</th>
                            <th class="py-3 px-4">الإجراءات المتاحة</th>
                            <th class="py-3 px-4">المرفقات المطلوبة</th>
                            <th class="py-3 px-4 text-center">الحالة</th>
                            <th class="py-3 px-4 text-center">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stages as $stage)
                            @php
                                $allowedActions = is_array($stage->available_actions) ? $stage->available_actions : (json_decode($stage->available_actions, true) ?: []);
                                $reqAttachments = is_array($stage->required_attachments) ? $stage->required_attachments : (json_decode($stage->required_attachments, true) ?: []);
                            @endphp
                            <tr>
                                <td class="px-4 fw-bold text-dark">
                                    {{ $tab === 'internal' ? ($stage->entity?->name ?? 'غير محدد') : ($stage->authority?->name ?? 'غير محدد') }}
                                </td>
                                <td class="px-4 text-center">
                                    <span class="badge bg-secondary rounded-pill px-2 py-1">#{{ $stage->stage_order }}</span>
                                </td>
                                <td class="px-4">
                                    <div class="fw-semibold text-primary">{{ $stage->stage_name_ar ?: $stage->stage }}</div>
                                    <small class="text-muted font-monospace">{{ $stage->stage }}</small>
                                </td>
                                <td class="px-4">
                                    @if($stage->responsibleUser)
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-user-check text-success"></i>
                                            <div>
                                                <div class="small fw-semibold">{{ $stage->responsibleUser->name }}</div>
                                                <small class="text-muted">{{ $stage->responsibleUser->work ?? $stage->responsibleUser->department }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-light text-muted border">أي مخول بالجهة</span>
                                    @endif
                                </td>
                                <td class="px-4">
                                    <div class="d-flex gap-1 flex-wrap">
                                        @foreach($allowedActions as $act)
                                            @php
                                                $badgeClass = match($act) {
                                                    'submit' => 'bg-info bg-opacity-10 text-info border border-info',
                                                    'approve' => 'bg-success bg-opacity-10 text-success border border-success',
                                                    'reject' => 'bg-danger bg-opacity-10 text-danger border border-danger',
                                                    'return' => 'bg-warning bg-opacity-10 text-warning border border-warning',
                                                    'comment' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary',
                                                    'upload_attachment' => 'bg-primary bg-opacity-10 text-primary border border-primary',
                                                    default => 'bg-light text-dark border',
                                                };
                                                $actionLabels = \App\Models\EntityApprovalStage::AVAILABLE_ACTIONS;
                                                $lbl = $actionLabels[$act] ?? $act;
                                                // Shorten label for badge
                                                $shortLbl = explode('(', $lbl)[0];
                                            @endphp
                                            <span class="badge {{ $badgeClass }} px-2 py-1 small rounded-pill">
                                                {{ trim($shortLbl) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4">
                                    @if(!empty($reqAttachments))
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1 rounded-pill">
                                            <i class="fas fa-paperclip me-1"></i> {{ count($reqAttachments) }} مرفق
                                        </span>
                                    @else
                                        <span class="text-muted small">لا توجد</span>
                                    @endif
                                </td>
                                <td class="px-4 text-center">
                                    @if($stage->is_active)
                                        <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">نشط</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">معطل</span>
                                    @endif
                                </td>
                                <td class="px-4 text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        @can('stages.create')
                                        <a href="{{ route('approvals.configurations.edit', ['stage' => ($tab === 'internal' ? $stage->entity_id : $stage->authority_id), 'tab' => $tab, 'workflow_type' => $workflowType]) }}" 
                                           class="btn btn-sm btn-outline-primary rounded-pill px-3" title="تعديل مسار الجهة">
                                            <i class="fas fa-edit me-1"></i> تعديل
                                        </a>
                                        <form action="{{ route('approvals.configurations.toggle', $stage->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="tab" value="{{ $tab }}">
                                            <button type="submit" class="btn btn-sm {{ $stage->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-circle" 
                                                    title="{{ $stage->is_active ? 'تعطيل' : 'تفعيل' }}">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('approvals.configurations.destroy', $stage->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه المرحلة؟')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="tab" value="{{ $tab }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="حذف المرحلة">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-3 text-secondary d-block"></i>
                                    لا توجد مراحل مسار مهيأة لهذا النوع من المعاملات في الجهات المحددة حالياً.
                                    <div class="mt-3">
                                        @can('stages.create')
                                        <a href="{{ route('approvals.configurations.create', ['tab' => $tab, 'workflow_type' => $workflowType]) }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                            <i class="fas fa-plus me-1"></i> تكوين مسار جديد الآن
                                        </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($stages->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $stages->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
