@extends('layouts.app')

@section('title', 'إدارة وتكوين مسارات الاعتماد المركزية')

@section('styles')
<style>
    /* ═══════════════════════════════════════════════════════════════
       Stat Cards
       ═══════════════════════════════════════════════════════════════ */
    .stat-card {
        border-radius: 0.85rem;
        border: 1px solid #e2e8f0;
        padding: 1rem;
        background: #fff;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: transform 0.15s, box-shadow 0.15s;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    }
    .stat-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .stat-icon.bg-blue   { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .stat-icon.bg-green  { background: linear-gradient(135deg, #10b981, #047857); }
    .stat-icon.bg-amber  { background: linear-gradient(135deg, #f59e0b, #b45309); }
    .stat-icon.bg-red    { background: linear-gradient(135deg, #ef4444, #b91c1c); }
    .stat-card .stat-value { font-weight: 700; font-size: 1.15rem; color: #1e293b; line-height: 1; }
    .stat-card .stat-label { font-size: 0.72rem; color: #64748b; margin-top: 0.15rem; }

    /* ═══════════════════════════════════════════════════════════════
       Entity Card
       ═══════════════════════════════════════════════════════════════ */
    .entity-card {
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        background: #fff;
        overflow: hidden;
        transition: box-shadow 0.2s, border-color 0.2s;
        margin-bottom: 1.25rem;
    }
    .entity-card:hover {
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.09);
        border-color: #cbd5e1;
    }
    .entity-card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .entity-card-header .entity-name {
        font-weight: 700;
        font-size: 0.98rem;
        color: #1e293b;
        line-height: 1.2;
    }
    .entity-card-header .entity-meta {
        font-size: 0.72rem;
        color: #64748b;
        margin-top: 0.15rem;
    }
    .entity-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .entity-card-header .card-chips {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }
    .chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.28rem 0.7rem;
        border-radius: 50rem;
        font-size: 0.7rem;
        font-weight: 600;
        border: 1px solid transparent;
        white-space: nowrap;
        line-height: 1.2;
    }
    .chip-stages  { background: #e0e7ff; color: #3730a3; border-color: #a5b4fc; }
    .chip-final   { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    .chip-nofinal { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
    .chip-exec    { background: #dcfce7; color: #166534; border-color: #86efac; }

    /* ═══════════════════════════════════════════════════════════════
       Stages Table (inside entity card)
       ═══════════════════════════════════════════════════════════════ */
    .stages-table {
        width: 100%;
        margin-bottom: 0;
        font-size: 0.78rem;
    }
    .stages-table thead th {
        background: #f1f5f9;
        color: #334155;
        font-weight: 700;
        font-size: 0.7rem;
        padding: 0.55rem 0.7rem;
        border-bottom: 2px solid #cbd5e1;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
    }
    .stages-table tbody td {
        padding: 0.6rem 0.7rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        text-align: center;
    }
    .stages-table tbody tr:hover { background: #f8fafc; }
    .stages-table tbody tr.is-final-row {
        background: #fffbeb;
        border-right: 3px solid #f59e0b;
    }
    .stages-table tbody tr.is-final-row:hover { background: #fef3c7; }

    .stage-order {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #1e293b;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.75rem;
    }

    /* ═══════════════════════════════════════════════════════════════
       Action Badges
       ═══════════════════════════════════════════════════════════════ */
    .action-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.5rem;
        border-radius: 50rem;
        font-size: 0.63rem;
        font-weight: 600;
        border: 1px solid transparent;
        margin: 0.1rem;
        white-space: nowrap;
    }
    .act-submit { background: #e0f2fe; color: #0369a1; border-color: #7dd3fc; }
    .act-approve { background: #dcfce7; color: #166534; border-color: #86efac; }
    .act-return { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
    .act-reject { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    .act-comment { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .act-upload { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }
    .act-consult { background: #e0e7ff; color: #3730a3; border-color: #a5b4fc; }
    .act-forward { background: #e0e7ff; color: #3730a3; border-color: #a5b4fc; }
    .act-need-action { background: #ffedd5; color: #9a3412; border-color: #fdba74; }

    /* ═══════════════════════════════════════════════════════════════
       Final & Exec Badges
       ═══════════════════════════════════════════════════════════════ */
    .badge-final {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.55rem;
        border-radius: 50rem;
        font-size: 0.65rem;
        font-weight: 700;
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    .badge-exec {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.55rem;
        border-radius: 50rem;
        font-size: 0.65rem;
        font-weight: 700;
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }

    /* ═══════════════════════════════════════════════════════════════
       Action Buttons in header
       ═══════════════════════════════════════════════════════════════ */
    .entity-actions .btn { font-size: 0.72rem; padding: 0.3rem 0.7rem; }

    /* ═══════════════════════════════════════════════════════════════
       Empty state
       ═══════════════════════════════════════════════════════════════ */
    .empty-state-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid" dir="rtl">

    {{-- ═══ Header ═══ --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-primary fw-bold">
                <i class="fas fa-sitemap me-2"></i>مراحل المراجعة والاعتماد
            </h4>
            <small class="text-muted">بطاقة لكل جهة تحتوي على جميع مراحلها وإجراءاتها ومرفقاتها</small>
        </div>
        @can('stages.create')
        <a href="{{ route('admin.entity-stages.create', ['tab' => $tab, 'workflow_type' => $workflowType]) }}"
           class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="fas fa-plus me-1"></i> إضافة مسار جديد
        </a>
        @endcan
    </div>

    {{-- ═══ Stats Row ═══ --}}
    @if(isset($stats))
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fas fa-{{ $tab === 'internal' ? 'building' : 'landmark' }}"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['total_entities'] }}</div>
                    <div class="stat-label">{{ $tab === 'internal' ? 'جهة داخلية' : 'جهة خارجية' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['total_stages'] }}</div>
                    <div class="stat-label">إجمالي المراحل</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-amber"><i class="fas fa-flag-checkered"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['entities_with_final'] }}</div>
                    <div class="stat-label">مسارات مكتملة</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-red"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['entities_without_final'] }}</div>
                    <div class="stat-label">بدون مرحلة نهائية</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ Tabs: Internal / External ═══ --}}
    <ul class="nav nav-tabs mb-4 border-bottom border-primary">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'internal' ? 'active fw-bold border-primary border-bottom-0' : 'text-muted' }}"
               href="{{ route('admin.entity-stages.index', ['tab' => 'internal', 'workflow_type' => $workflowType]) }}">
                <i class="fas fa-building me-1"></i> الجهات الداخلية
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'external' ? 'active fw-bold border-primary border-bottom-0' : 'text-muted' }}"
               href="{{ route('admin.entity-stages.index', ['tab' => 'external', 'workflow_type' => $workflowType]) }}">
                <i class="fas fa-landmark me-1"></i> الجهات الخارجية والمسارات
            </a>
        </li>
    </ul>

    {{-- ═══ Workflow Types Bar ═══ --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <span class="fw-bold text-dark">
                <i class="fas fa-layer-group text-primary me-2"></i>اختر نوع المعاملة:
            </span>
        </div>
        <div class="card-body pt-0">
            <div class="d-flex gap-2 flex-wrap">
                @foreach($workflowTypes as $wfKey => $wfTitle)
                    <a href="{{ route('admin.entity-stages.index', ['workflow_type' => $wfKey, 'tab' => $tab]) }}"
                       class="btn btn-sm rounded-pill {{ $workflowType === $wfKey ? 'btn-primary shadow-sm fw-bold' : 'btn-outline-secondary' }}">
                        {{ $wfTitle }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ Filter Form ═══ --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.entity-stages.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="workflow_type" value="{{ $workflowType }}">

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">
                        <i class="fas fa-search text-muted me-1"></i> بحث بالاسم
                    </label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           value="{{ request('search') }}"
                           placeholder="ابحث باسم الجهة...">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">
                        <i class="fas fa-filter text-muted me-1"></i> فلترة بالجهة
                    </label>
                    @if($tab === 'internal')
                        <select name="entity_id" class="form-select form-select-sm select2-search">
                            <option value="">جميع الجهات الداخلية</option>
                            {{-- ✅ نستخدم $entities بدل استعلام مباشر في الـ view --}}
                            @foreach($entities as $entityOption)
                                <option value="{{ $entityOption->id }}" @selected(request('entity_id') == $entityOption->id)>
                                    {{ $entityOption->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <select name="authority_id" class="form-select form-select-sm select2-search">
                            <option value="">جميع الجهات الخارجية</option>
                            @foreach($entities as $authOption)
                                <option value="{{ $authOption->id }}" @selected(request('authority_id') == $authOption->id)>
                                    {{ $authOption->agency_name ?? $authOption->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                </div>

                <div class="col-md-2">
                    @if(request('search') || request('entity_id') || request('authority_id'))
                        <a href="{{ route('admin.entity-stages.index', ['tab' => $tab, 'workflow_type' => $workflowType]) }}"
                           class="btn btn-outline-secondary btn-sm w-100 rounded-pill">
                            <i class="fas fa-times me-1"></i> إلغاء
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ Alerts ═══ --}}
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('warning') }}
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- Entity Cards                                                   --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @forelse($entities as $entity)
        @php
            $stages      = $stagesByEntity->get($entity->id, collect());
            $totalStages = $stages->count();
            $finalStage  = $stages->firstWhere('is_final', true);

            $entityName = $tab === 'internal'
                ? $entity->name
                : ($entity->agency_name ?? $entity->name);

            // ✅ الحل: استخدام getParentModel() لتفادي LazyLoadingViolationException
            // إذا كنت قد أضفت ->with('parent') في الكنترولر، سيستخدم العلاقة المحمّلة مباشرة.
            if ($tab === 'internal') {
                $parentModel = $entity->getParentModel();
                $entitySubtitle = $parentModel
                    ? 'تابعة لـ: ' . $parentModel->name
                    : 'جهة داخلية رئيسية';
            } else {
                $entitySubtitle = 'جهة خارجية' . (! empty($entity->code) ? ' - كود: ' . $entity->code : '');
            }
        @endphp

        <div class="entity-card">
            {{-- ─── Card Header ─── --}}
            <div class="entity-card-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="entity-icon">
                        <i class="fas fa-{{ $tab === 'internal' ? 'building' : 'landmark' }}"></i>
                    </div>
                    <div>
                        <div class="entity-name">{{ $entityName }}</div>
                        <div class="entity-meta">
                            <i class="fas fa-info-circle me-1"></i>{{ $entitySubtitle }}
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    {{-- Chips --}}
                    <div class="card-chips">
                        <span class="chip chip-stages">
                            <i class="fas fa-layer-group"></i> {{ $totalStages }} مرحلة
                        </span>

                        @if($finalStage)
                            <span class="chip chip-final" title="المرحلة النهائية: {{ $finalStage->stage_name_ar }}">
                                <i class="fas fa-flag-checkered"></i>
                                نهائية: {{ \Illuminate\Support\Str::limit($finalStage->stage_name_ar, 20) }}
                            </span>
                        @else
                            <span class="chip chip-nofinal" title="لم يتم تحديد مرحلة نهائية">
                                <i class="fas fa-exclamation-triangle"></i> بدون مرحلة نهائية
                            </span>
                        @endif

                        @if($stages->contains('triggers_execution', true))
                            <span class="chip chip-exec">
                                <i class="fas fa-play-circle"></i> تُفعِّل التنفيذ
                            </span>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="entity-actions d-flex gap-1">
                        @can('stages.edit')
                        <a href="{{ route('admin.entity-stages.edit', [
                                'entity' => $entity->id,
                                'tab' => $tab,
                                'workflow_type' => $workflowType
                            ]) }}"
                           class="btn btn-sm btn-outline-primary rounded-pill">
                            <i class="fas fa-edit me-1"></i> تعديل
                        </a>
                        @endcan

                        @can('stages.delete')
                        <form action="{{ route('admin.entity-stages.destroy-path', $entity->id) }}"
                              method="POST" class="d-inline"
                              onsubmit="return confirm('⚠️ هل أنت متأكد من حذف مسار هذه الجهة بالكامل؟ سيتم حذف جميع المراحل.');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <input type="hidden" name="workflow_type" value="{{ $workflowType }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" title="حذف المسار بالكامل">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- ─── Card Body: Stages Table ─── --}}
            @if($stages->isEmpty())
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    لا توجد مراحل مسجّلة لهذه الجهة في هذا النوع من المسار.
                </div>
            @else
                <div class="table-responsive">
                    <table class="stages-table">
                        <thead>
                            <tr>
                                <th style="width: 55px;">#</th>
                                <th style="width: 200px; text-align: right;">المرحلة</th>
                                <th style="width: 200px; text-align: right;">المسؤول</th>
                                <th>الإجراءات المسموحة</th>
                                <th style="width: 110px;">المرفقات</th>
                                <th style="width: 150px;">نوع النهاية</th>
                                <th style="width: 100px;">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stages as $stage)
                                @php
                                    $allowedActions = is_array($stage->available_actions)
                                        ? $stage->available_actions
                                        : (json_decode($stage->available_actions, true) ?: []);

                                    $reqAttachments = is_array($stage->required_attachments)
                                        ? $stage->required_attachments
                                        : (json_decode($stage->required_attachments, true) ?: []);

                                    $isFinal      = ! empty($stage->is_final);
                                    $triggersExec = ! empty($stage->triggers_execution);

                                    $actionClassMap = [
                                        'submit'            => 'act-submit',
                                        'approve'           => 'act-approve',
                                        'return'            => 'act-return',
                                        'reject'            => 'act-reject',
                                        'comment'           => 'act-comment',
                                        'upload_attachment' => 'act-upload',
                                        'consult'           => 'act-consult',
                                        'forward'           => 'act-forward',
                                        'need_action'       => 'act-need-action',
                                    ];
                                @endphp
                                <tr class="{{ $isFinal ? 'is-final-row' : '' }}">
                                    {{-- الترتيب --}}
                                    <td>
                                        <span class="stage-order">{{ $stage->stage_order }}</span>
                                    </td>

                                    {{-- المرحلة --}}
                                    <td style="text-align: right;">
                                        <div class="fw-bold text-dark" style="font-size: 0.82rem;">
                                            {{ $stage->stage_name_ar ?: ($stage->stageEnum()?->label() ?? $stage->stage) }}
                                        </div>
                                        <small class="text-muted font-monospace" style="font-size: 0.68rem;">
                                            {{ $stage->stage }}
                                        </small>
                                    </td>

                                    {{-- المسؤول --}}
                                    <td style="text-align: right;">
                                        @if($stage->hasValidResponsibleUser() && $stage->responsibleUser)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                                                     style="width: 28px; height: 28px; font-size: 0.7rem; flex-shrink: 0;">
                                                    {{ mb_substr($stage->responsibleUser->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="small fw-semibold">{{ $stage->responsibleUser->name }}</div>
                                                    <small class="text-muted" style="font-size: 0.65rem;">
                                                        {{ $stage->responsibleUser->work ?? 'موظف' }}
                                                    </small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="badge bg-light text-muted border">أي موظف مخوّل</span>
                                        @endif
                                    </td>

                                    {{-- الإجراءات --}}
                                    <td>
                                        <div class="d-flex flex-wrap justify-content-center gap-1">
                                            @forelse($allowedActions as $act)
                                                @php
                                                    $lbl      = $actionLabels[$act] ?? $act;
                                                    $shortLbl = trim(explode('(', $lbl)[0]);
                                                    $cls      = $actionClassMap[$act] ?? 'act-comment';
                                                @endphp
                                                <span class="action-chip {{ $cls }}">{{ $shortLbl }}</span>
                                            @empty
                                                <span class="text-muted small">—</span>
                                            @endforelse
                                        </div>
                                    </td>

                                    {{-- المرفقات --}}
                                    <td>
                                        @if(! empty($reqAttachments))
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary rounded-pill px-2 py-1"
                                                  title="{{ collect($reqAttachments)->pluck('label')->implode('، ') }}"
                                                  style="font-size: 0.68rem;">
                                                <i class="fas fa-paperclip me-1"></i> {{ count($reqAttachments) }}
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>

                                    {{-- نوع النهاية --}}
                                    <td>
                                        @if($isFinal)
                                            <span class="badge-final" title="آخر مرحلة — إغلاق المسار">
                                                <i class="fas fa-flag-checkered"></i> نهائية
                                            </span>
                                        @endif
                                        @if($triggersExec)
                                            <span class="badge-exec mt-1" title="عند الاعتماد يبدأ التنفيذ">
                                                <i class="fas fa-play-circle"></i> تُفعِّل التنفيذ
                                            </span>
                                        @endif
                                        @if(! $isFinal && ! $triggersExec)
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>

                                    {{-- الحالة --}}
                                    <td>
                                        @if($stage->is_active)
                                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1"
                                                  style="font-size: 0.68rem;">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i> نشطة
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1"
                                                  style="font-size: 0.68rem;">
                                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i> معطلة
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- ─── Route Info (External only) ─── --}}
            @if($tab === 'external' && isset($routes) && $routes->has($entity->id))
                @php $routeInfo = $routes->get($entity->id); @endphp
                <div class="px-3 py-2 border-top bg-light">
                    <small class="text-muted" style="font-size: 0.72rem;">
                        <i class="fas fa-route me-1 text-primary"></i>
                        <strong>مسار الربط:</strong>
                        @if($routeInfo->destination_type === 'ministry')
                            يُوجَّه إلى <span class="badge bg-info text-dark ms-1">الوزارة (Ministry Root)</span>
                        @else
                            {{-- destinationAuthority محمّلة مسبقاً في الكنترولر عبر with() --}}
                            يُوجَّه إلى <span class="badge bg-secondary ms-1">{{ $routeInfo->destinationAuthority?->agency_name ?? 'جهة أخرى' }}</span>
                        @endif
                        @if(! $routeInfo->is_active)
                            <span class="badge bg-danger ms-2">معطّل</span>
                        @endif
                    </small>
                </div>
            @endif
        </div>
    @empty
        {{-- ─── Empty State ─── --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-5 text-center">
                <div class="empty-state-icon">
                    <i class="fas fa-sitemap text-secondary" style="font-size: 1.8rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">لا توجد مسارات مهيأة بعد</h5>
                <p class="text-muted small mb-4">
                    لم يتم تكوين أي مسار اعتماد لهذا النوع من المعاملات
                    @if(request('search') || request('entity_id') || request('authority_id'))
                        (توجد فلترة مطبّقة)
                    @endif
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    @if(request('search') || request('entity_id') || request('authority_id'))
                        <a href="{{ route('admin.entity-stages.index', ['tab' => $tab, 'workflow_type' => $workflowType]) }}"
                           class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="fas fa-times me-1"></i> إلغاء الفلاتر
                        </a>
                    @endif
                    @can('stages.create')
                        <a href="{{ route('admin.entity-stages.create', ['tab' => $tab, 'workflow_type' => $workflowType]) }}"
                           class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-plus me-1"></i> إنشاء مسار جديد الآن
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    @endforelse

</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2-search').select2({
            dir: "rtl",
            theme: "bootstrap-5",
            width: '100%'
        });
    });
</script>
@endsection