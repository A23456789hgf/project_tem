@extends('layouts.app')

@section('styles')
    <style>
        /* ===============================
                   جدول المستخدمين - تصميم مضغوط وجميل
                   =============================== */

        .table-compact {
            font-size: 0.85rem !important;
        }

        .table-compact th,
        .table-compact td {
            padding: 0.3rem 0.6rem !important;
            vertical-align: middle !important;
            line-height: 1.3 !important;
            border-color: #e9ecef !important;
        }

        .table-compact thead th {
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
            border: none !important;
            color: #fff !important;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-compact tbody tr {
            transition: all 0.15s ease;
            cursor: default;
        }

        .table-compact tbody tr:hover {
            background: linear-gradient(90deg, rgba(52, 152, 219, 0.08) 0%, rgba(255, 255, 255, 0) 100%) !important;
            transform: translateX(3px);
            box-shadow: 2px 0 8px rgba(52, 152, 219, 0.1);
        }

        /* ===============================
                   الأيقونات الموحدة
                   =============================== */
        .action-icon {
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
        }

        .btn-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
            border-radius: 6px !important;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .btn-icon:active {
            transform: translateY(0);
        }

        /* ===============================
                   الشارات (Badges) المصممة
                   =============================== */
        .badge-compact {
            font-size: 0.72rem !important;
            font-weight: 500 !important;
            padding: 0.35em 0.6em !important;
            border-radius: 20px !important;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            line-height: 1;
            white-space: nowrap;
        }

        .badge-role {
            background: linear-gradient(135deg, #3498db, #2980b9) !important;
            color: #fff !important;
        }

        .badge-active {
            background: linear-gradient(135deg, #27ae60, #219653) !important;
            color: #fff !important;
        }

        .badge-disabled {
            background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
            color: #fff !important;
        }

        .badge-geo {
            background: linear-gradient(135deg, #9b59b6, #8e44ad) !important;
            color: #fff !important;
        }

        .badge-search {
            background: linear-gradient(135deg, #3498db, #2980b9) !important;
            color: #fff !important;
        }

        .badge-filter {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d) !important;
            color: #fff !important;
        }

        /* ===============================
                   حقول الإدخال المصممة
                   =============================== */
        .input-group-sm .input-group-text {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px 0 0 8px !important;
            border: 1px solid #dee2e6;
            border-right: none;
        }

        .form-control-sm,
        .form-select-sm {
            font-size: 0.82rem !important;
            border-radius: 8px !important;
            border: 1px solid #dee2e6 !important;
            padding: 0.35rem 0.75rem !important;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control-sm:focus,
        .form-select-sm:focus {
            border-color: #3498db !important;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15) !important;
        }

        /* ===============================
                   الأزرار المصممة
                   =============================== */
        .btn-compact {
            font-size: 0.78rem !important;
            padding: 0.25rem 0.6rem !important;
            border-radius: 8px !important;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-compact:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary-compact {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            color: #fff;
        }

        .btn-primary-compact:hover {
            background: linear-gradient(135deg, #2980b9, #2472a4);
            color: #fff;
        }

        .btn-danger-compact {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border: none;
            color: #fff;
        }

        .btn-outline-danger-compact {
            border: 1px solid #e74c3c;
            color: #e74c3c;
            background: transparent;
        }

        .btn-outline-danger-compact:hover {
            background: #e74c3c;
            color: #fff;
        }

        /* ألوان أزرار الإجراءات */
        .btn-action-view {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            border: none;
        }

        .btn-action-log {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: #fff;
            border: none;
        }

        .btn-action-edit {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: #fff;
            border: none;
        }

        .btn-action-disable {
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: #fff;
            border: none;
        }

        .btn-action-enable {
            background: linear-gradient(135deg, #27ae60, #219653);
            color: #fff;
            border: none;
        }

        .btn-action-delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: #fff;
            border: none;
        }

        /* ===============================
                   الفلاتر النشطة
                   =============================== */
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .filter-badge:hover {
            opacity: 0.9;
        }

        .filter-badge .remove-filter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            color: inherit;
            text-decoration: none;
            transition: background 0.2s;
        }

        .filter-badge .remove-filter:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* ===============================
                   تحسينات عامة
                   =============================== */
        .stats-bar {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
        }

        .stat-item strong {
            font-size: 0.9rem;
        }

        .pagination-sm .page-link {
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
            border-radius: 6px !important;
            margin: 0 2px;
        }

        /* تأثيرات التحميل والرسوم المتحركة */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-compact tbody tr {
            animation: fadeIn 0.2s ease forwards;
        }

        .table-compact tbody tr:nth-child(1) {
            animation-delay: 0.02s;
        }

        .table-compact tbody tr:nth-child(2) {
            animation-delay: 0.04s;
        }

        .table-compact tbody tr:nth-child(3) {
            animation-delay: 0.06s;
        }

        .table-compact tbody tr:nth-child(4) {
            animation-delay: 0.08s;
        }

        .table-compact tbody tr:nth-child(5) {
            animation-delay: 0.1s;
        }

        /* شريط التمرير المخصص */
        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #bdc3c7;
            border-radius: 3px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #95a5a6;
        }
    </style>
@endsection

@section('content')
    <x-index-page title="طلبات النزول" icon="list-alt">

        <x-slot name="headerActions">
            @can('requests_descend.create')
                <a href="{{ route('requests_descend.create') }}" class="btn btn-primary auth-perm-requests-descend-create">
                    <x-icon name="plus" size="14" />
                    إضافة طلب نزول
                </a>
            @endcan
        </x-slot>

        <x-slot name="filters">
            <div class="row g-2 mb-3">
                {{-- Search --}}
                <div class="col-md-4 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text border-end-0">
                            <x-icon name="search" size="13" class="text-muted" />
                        </span>
                        <input type="text" name="search" class="form-control form-control-sm border-start-0"
                            placeholder="ابحث حسب الاحتياج أو السبب أو الهدف..." value="{{ request('search') }}"
                            id="search-input" style="border-left-color: #dee2e6;">
                    </div>
                </div>

                {{-- Priority Filter --}}
                <div class="col-md-2 col-sm-6">
                    <select name="priority" class="form-select form-select-sm" id="priority-filter">
                        <option value="">جميع الأولويات</option>
                        <option value="Urgent" {{ request('priority') === 'Urgent' ? 'selected' : '' }}>عاجل</option>
                        <option value="Important" {{ request('priority') === 'Important' ? 'selected' : '' }}>هام</option>
                    </select>
                </div>

                {{-- Linked to Project Filter --}}
                <div class="col-md-3 col-sm-6">
                    <select name="is_linked_to_project" class="form-select form-select-sm" id="linked-filter">
                        <option value="">مرتبط بمشروع؟</option>
                        <option value="1" {{ request('is_linked_to_project') === '1' ? 'selected' : '' }}>نعم</option>
                        <option value="0" {{ request('is_linked_to_project') === '0' ? 'selected' : '' }}>لا</option>
                    </select>
                </div>

                {{-- Sort Controls --}}
                <div class="col-md-2 col-sm-6">
                    <div class="d-flex gap-1">
                        <select name="sort_by" class="form-select form-select-sm" id="sort-by" title="ترتيب حسب">
                            <option value="">🔄 الفرز</option>
                            <option value="id" {{ request('sort_by') == 'id' ? 'selected' : '' }}>🔢 المعرف</option>
                            <option value="priority" {{ request('sort_by') == 'priority' ? 'selected' : '' }}>⭐ الأولوية
                            </option>
                            <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>📅 التاريخ
                            </option>
                        </select>
                        <select name="sort_order" class="form-select form-select-sm" id="sort-order" style="width: 42px;"
                            title="اتجاه الفرز">
                            <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>⬇</option>
                            <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>⬆</option>
                        </select>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="col-md-1 col-sm-6">
                    <div class="d-grid gap-1">
                        <button type="button" class="btn btn-primary-compact" id="apply-filters" title="تطبيق الفلاتر">
                            <x-icon name="filter" size="13" />
                        </button>
                        @if(request()->hasAny(['search', 'priority', 'is_linked_to_project', 'sort_by']))
                            <a href="{{ route('requests_descend.index', request()->only(['per_page'])) }}"
                                class="btn btn-outline-danger-compact" title="إعادة تعيين">
                                <x-icon name="refresh-ccw" size="13" />
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Active Filters Tags --}}
            @if(request()->hasAny(['search', 'priority', 'is_linked_to_project', 'sort_by']))
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            <small class="text-muted me-1">الفلاتر النشطة:</small>
                            @if(request('search'))
                                <span class="filter-badge badge-search">
                                    🔍 {{ request('search') }}
                                    <a href="{{ route('requests_descend.index', request()->except(['search'])) }}"
                                        class="remove-filter" title="إزالة">
                                        <x-icon name="x" size="10" />
                                    </a>
                                </span>
                            @endif
                            @if(request('priority'))
                                <span class="filter-badge badge-role">
                                    ⭐ {{ request('priority') === 'Urgent' ? 'عاجل' : 'هام' }}
                                    <a href="{{ route('requests_descend.index', request()->except(['priority'])) }}"
                                        class="remove-filter" title="إزالة">
                                        <x-icon name="x" size="10" />
                                    </a>
                                </span>
                            @endif
                            @if(request()->filled('is_linked_to_project'))
                                <span class="filter-badge badge-filter">
                                    🔗 {{ request('is_linked_to_project') == '1' ? 'مرتبط' : 'غير مرتبط' }}
                                    <a href="{{ route('requests_descend.index', request()->except(['is_linked_to_project'])) }}"
                                        class="remove-filter" title="إزالة">
                                        <x-icon name="x" size="10" />
                                    </a>
                                </span>
                            @endif
                            @if(request('sort_by'))
                                <span class="filter-badge badge-filter">
                                    📋
                                    {{ request('sort_by') == 'id' ? 'المعرف' : (request('sort_by') == 'priority' ? 'الأولوية' : 'التاريخ') }}
                                    {{ request('sort_order') == 'desc' ? '⬇' : '⬆' }}
                                    <a href="{{ route('requests_descend.index', request()->except(['sort_by', 'sort_order'])) }}"
                                        class="remove-filter" title="إزالة">
                                        <x-icon name="x" size="10" />
                                    </a>
                                </span>
                            @endif
                            <a href="{{ route('requests_descend.index', request()->only(['per_page'])) }}"
                                class="text-muted small text-decoration-none ms-2">
                                <x-icon name="trash-2" size="12" /> مسح الكل
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="stats">
            <div class="stats-bar mb-3 w-100">
                <div class="d-flex gap-3 flex-wrap">
                    <span class="stat-item text-muted">
                        <x-icon name="list-alt" size="14" />
                        إجمالي الطلبات: <strong class="text-dark ms-1">{{ $requests->total() }}</strong>
                    </span>
                </div>
                <div class="text-muted small">
                    📄 صفحة <strong>{{ $requests->currentPage() }}</strong> من <strong>{{ $requests->lastPage() }}</strong>
                </div>
            </div>
        </x-slot>
        <x-slot name="table">
            <table class="table table-hover table-striped align-middle table-compact mb-0 w-100">
                <thead>
                    <tr>
                        <th style="min-width: 75px">
                            <div class="d-flex align-items-center gap-1">
                                <span>#</span>
                                @if(request('sort_by') == 'id')
                                    <x-icon name="arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}" size="10" />
                                @endif
                            </div>
                        </th>
                        <th style="min-width: 120px">
                            <div class="d-flex align-items-center gap-1">
                                <span>مرتبط بمشروع؟</span>
                            </div>
                        </th>
                        <th style="min-width: 150px">
                            <div class="d-flex align-items-center gap-1">
                                <span>اسم المشروع</span>
                            </div>
                        </th>
                        <th style="min-width: 100px">
                            <div class="d-flex align-items-center gap-1">
                                <span>الأولوية</span>
                                @if(request('sort_by') == 'priority')
                                    <x-icon name="arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}" size="10" />
                                @endif
                            </div>
                        </th>
                        <th style="min-width: 100px">
                            <div class="d-flex align-items-center gap-1">
                                <span>الحالة</span>
                            </div>
                        </th>
                        <th style="min-width: 100px">
                            <div class="d-flex align-items-center gap-1">
                                <span>التاريخ</span>
                                @if(request('sort_by') == 'created_at')
                                    <x-icon name="arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}" size="10" />
                                @endif
                            </div>
                        </th>
                        <th class="text-center" style="min-width: 180px">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $index => $requestItem)
                        <tr>
                            <td>
                                <span class="fw-bold text-primary small">#{{ $requestItem->id }}</span>
                            </td>
                            <td>
                                @if($requestItem->is_linked_to_project)
                                    <span class="badge-compact badge-active">
                                        <x-icon name="check" size="10" /> نعم
                                    </span>
                                @else
                                    <span class="badge-compact badge-disabled">
                                        <x-icon name="x" size="10" /> لا
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="text-truncate-custom-lg small fw-medium text-dark"
                                    title="{{ $requestItem->project ? $requestItem->project->project_name : 'ـ' }}">
                                    {{ $requestItem->project ? $requestItem->project->project_name : 'ـ' }}
                                </div>
                            </td>
                            <td>
                                @if($requestItem->priority == 'Urgent')
                                    <span class="badge-compact badge-disabled">عاجل</span>
                                @elseif($requestItem->priority == 'Important')
                                    <span class="badge-compact badge-role">هام</span>
                                @else
                                    <span class="text-muted">ـ</span>
                                @endif
                            </td>
                            <td>
                                @if($requestItem->status == 'draft')
                                    <span class="badge-compact badge-filter" style="background: #95a5a6;">مسودة</span>
                                @elseif($requestItem->status == 'pending_approval')
                                    <span class="badge-compact badge-role" style="background: #f39c12;">قيد الاعتماد</span>
                                @elseif($requestItem->status == 'under_technical_review')
                                    <span class="badge-compact badge-filter" style="background: #3498db;">مراجعة فنية</span>
                                @elseif($requestItem->status == 'under_financial_review')
                                    <span class="badge-compact badge-filter" style="background: #9b59b6;">مراجعة مالية</span>
                                @elseif($requestItem->status == 'approved')
                                    <span class="badge-compact badge-active" style="background: #27ae60;">معتمد</span>
                                @elseif($requestItem->status == 'rejected')
                                    <span class="badge-compact badge-disabled" style="background: #e74c3c;">مرفوض</span>
                                @elseif($requestItem->status == 'returned')
                                    <span class="badge-compact badge-role" style="background: #d35400;">مُعاد (للتعديل)</span>
                                @else
                                    <span class="text-muted">{{ $requestItem->status }}</span>
                                @endif

                                @if($requestItem->notes)
                                    <div class="small text-muted mt-1" style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" data-bs-toggle="tooltip" title="{{ $requestItem->notes }}">
                                        <i class="fas fa-info-circle me-1"></i> {{ $requestItem->notes }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="date-cell">
                                    <span
                                        class="date-main">{{ $requestItem->created_at ? $requestItem->created_at->format('Y/m/d') : 'ـ' }}</span>
                                    <span
                                        class="date-time">{{ $requestItem->created_at ? $requestItem->created_at->format('h:i A') : '' }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    @can('requests_descend.view', $requestItem)
                                        <a href="{{ route('requests_descend.show', $requestItem->id) }}"
                                            class="btn btn-action-view btn-icon" data-bs-toggle="tooltip" title="عرض التفاصيل">
                                            <x-icon name="eye" class="action-icon" />
                                        </a>
                                    @endcan

                                    @can('requests_descend.financial', $requestItem)
                                        <a href="{{ route('requests_descend.financial', $requestItem->id) }}"
                                            class="btn btn-action-view btn-icon text-success" data-bs-toggle="tooltip"
                                            title="الملف المالي">
                                            <x-icon name="dollar-sign" class="action-icon" />
                                        </a>
                                    @endcan

                                    @if(in_array($requestItem->status, ['pending_approval', 'under_technical_review', 'under_financial_review']))
                                        <button type="button" class="btn btn-action-enable btn-icon text-primary" data-bs-toggle="modal" data-bs-target="#approvalModal{{ $requestItem->id }}" title="الاعتماد والمراجعة">
                                            <x-icon name="shield" class="action-icon" />
                                        </button>
                                    @endif

                                    @if($requestItem->status == 'draft' || $requestItem->status == 'returned')
                                        @can('project-requests.approve')
                                        <form action="{{ route('requests_descend.sendForApproval', $requestItem->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="button" class="btn btn-action-enable btn-icon" onclick="confirmAction('إرسال للاعتماد', this.form)" title="إرسال للاعتماد النهائي">
                                                <x-icon name="send" class="action-icon" />
                                            </button>
                                        </form>
                                        @endcan

                                        @can('requests_descend.edit', $requestItem)
                                            <a href="{{ route('requests_descend.edit', $requestItem->id) }}"
                                                class="btn btn-action-edit btn-icon" data-bs-toggle="tooltip" title="تعديل">
                                                <x-icon name="edit-2" class="action-icon" />
                                            </a>
                                        @endcan

                                        @can('requests_descend.delete', $requestItem)
                                            <form action="{{ route('requests_descend.destroy', $requestItem->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-action-delete btn-icon"
                                                    onclick="confirmAction('حذف', this.form)" title="حذف">
                                                    <x-icon name="trash-2" class="action-icon" />
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>

                                <!-- Approval Modal -->
                                <div class="modal fade" id="approvalModal{{ $requestItem->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content border-0 rounded-4 shadow-sm text-start">
                                            <div class="modal-header bg-light border-bottom-0 rounded-top-4">
                                                <h5 class="modal-title fw-bold text-primary"><i class="fas fa-shield-alt me-2"></i> قرار الاعتماد أو المراجعة</h5>
                                                <button type="button" class="btn-close m-0 ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            @can('project-requests.approve')
                                            <form action="{{ route('requests_descend.processApproval', $requestItem->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body p-4">
                                                    <div class="mb-4">
                                                        <label class="form-label fw-bold text-dark">حدد القرار <span class="text-danger">*</span></label>
                                                        <select name="action" class="form-select border-2" required>
                                                            <option value="">-- اختر الإجراء المناسب --</option>
                                                            <option value="approve">اعتماد نهائي</option>
                                                            <option value="technical_review">إحالة للمراجعة الفنية</option>
                                                            <option value="financial_review">إحالة للمراجعة المالية</option>
                                                            <option value="return">إعادة لمنشئ الطلب (للتعديل)</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold text-dark">الملاحظات (اختياري)</label>
                                                        <textarea name="notes" class="form-control border-2" rows="3" placeholder="أدخل أي ملاحظات مرافقة للقرار..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top-0 bg-light rounded-bottom-4">
                                                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ القرار</button>
                                                </div>
                                            </form>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                                <!-- End Modal -->
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center gap-3">
                                    <div class="p-4 rounded-circle bg-light">
                                        <x-icon name="list-alt" size="40" class="text-muted" />
                                    </div>
                                    <div class="text-center">
                                        <h6 class="text-muted mb-1">لا توجد طلبات نزول حالياً</h6>
                                        <small class="text-muted d-block">يبدو أن القائمة فارغة.</small>
                                    </div>
                                    @can('requests_descend.create')
                                        <a href="{{ route('requests_descend.create') }}"
                                            class="btn btn-primary-compact btn-compact">
                                            <x-icon name="plus" size="12" /> إضافة طلب نزول
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-slot>

        <x-slot name="pagination">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-muted small">
                        📊 عرض <strong>{{ $requests->firstItem() }}</strong> إلى
                        <strong>{{ $requests->lastItem() }}</strong>
                        من <strong>{{ $requests->total() }}</strong> طلب نزول
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <label for="recordsPerPage" class="small text-muted mb-0">عرض:</label>
                        <select class="form-select form-select-sm" id="recordsPerPage"
                            style="width: auto; padding: 0.15rem 1.5rem 0.15rem 0.5rem; font-size: 0.75rem;">
                            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15 سجل</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 سجل</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 سجل</option>
                            <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500 سجل</option>
                        </select>
                    </div>
                </div>
                <div class="pagination-sm">
                    {{ $requests->appends(request()->all())->links() }}
                </div>
            </div>
        </x-slot>
    </x-index-page>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {

            // تطبيق الفلاتر عند النقر على الزر
            $('#apply-filters').click(function () {
                applyFilters();
            });

            // تطبيق الفلاتر عند تغيير عدد السجلات
            $('#recordsPerPage').change(function () {
                applyFilters();
            });

            // تطبيق الفلاتر عند ضغط Enter في حقل البحث
            $('#search-input').keypress(function (e) {
                if (e.which == 13) {
                    applyFilters();
                    return false;
                }
            });

            // دالة تطبيق الفلاتر
            function applyFilters() {
                const search = $('#search-input').val().trim();
                const priority = $('#priority-filter').val();
                const isLinked = $('#linked-filter').val();
                const sortBy = $('#sort-by').val();
                const sortOrder = $('#sort-order').val();
                const perPage = $('#recordsPerPage').val();

                let params = {};

                if (search) params.search = search;
                if (priority) params.priority = priority;
                if (isLinked) params.is_linked_to_project = isLinked;
                if (sortBy) {
                    params.sort_by = sortBy;
                    params.sort_order = sortOrder;
                }
                if (perPage) params.per_page = perPage;

                const queryString = $.param(params);
                window.location.href = '{{ route("requests_descend.index") }}' + (queryString ? '?' + queryString : '');
            }

            // تفعيل أداة التلميحات (Tooltips)
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // تأثيرات إضافية عند التحميل
            setTimeout(function () {
                $('.table-compact tbody tr').css('animation', 'fadeIn 0.3s ease forwards');
            }, 100);
        });
    </script>
@endsection