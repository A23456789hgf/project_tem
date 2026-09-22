@extends('layouts.app')

@section('content')
    @include('configuration.shared_styles')
    <link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

    <style>
        /* ضبط حجم أسهم ترقيم الصفحات (Pagination) */
        .pagination .page-link svg {
            width: 14px !important;
            height: 14px !important;
            vertical-align: middle;
        }

        /* تأكيد إضافي لتناسق أزرار التنقل الافتراضية في لارافل */
        nav[role="navigation"] svg {
            max-height: 14px;
            max-width: 14px;
        }

        /* ضبط حجم أسهم القوائم المنسدلة (Select) لتطابق النسق المدمج */
        .form-select,
        .form-select-sm {
            background-size: 14px 12px;
            font-size: 0.85rem;
        }

        /* ضبط أسهم التمدد والطي في الشجرة (Easy Tree) */
        .easy-tree li.parent_li>span::before,
        .easy-tree li.parent_li>span svg {
            font-size: 14px !important;
            width: 14px !important;
            height: 14px !important;
        }

        /* تنسيقات مخصصة للأعمدة */
        .badge-type {
            background-color: #e7f3ff;
            color: #0066cc;
            border: 1px solid #b3d9ff;
        }

        .badge-geo {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .badge-geo-dir {
            background-color: #e2e3ff;
            color: #383d9e;
            border: 1px solid #c3c4ff;
        }

        .badge-role {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .badge-active {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .creator-cell {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.85rem;
        }

        .table-compact thead th {
            font-size: 0.85rem;
            white-space: nowrap;
        }

        /* تنسيق رؤوس الأعمدة القابلة للفرز */
        .sortable-th {
            position: relative;
            user-select: none;
        }

        .sortable-th a {
            transition: color 0.2s ease;
            white-space: nowrap;
        }

        .sortable-th a:hover {
            color: #0d6efd !important;
        }

        .sortable-th a:hover svg {
            opacity: 1;
        }

        .sortable-th svg {
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        /* تنسيق الفلاتر */
        .filter-section {
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.25rem;
            display: block;
        }

        .filter-select {
            transition: all 0.2s ease;
        }

        .filter-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        .active-filters-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            background-color: #e7f3ff;
            color: #0066cc;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>

    <x-index-page title="إدارة الجهات" icon="sitemap">

        <x-slot name="headerActions">
            <div class="btn-group shadow-sm me-2" role="group">
                <a href="{{ route('authorities.index', ['view' => 'list'] + request()->except('view', 'page')) }}"
                    class="btn {{ $view == 'list' ? 'btn-primary-compact' : 'btn-outline-secondary btn-compact' }}">
                    <x-icon name="list" size="14" class="me-1" /> القائمة
                </a>
                <a href="{{ route('authorities.index', ['view' => 'tree'] + request()->except('view', 'page')) }}"
                    class="btn {{ $view == 'tree' ? 'btn-primary-compact' : 'btn-outline-secondary btn-compact' }}">
                    <x-icon name="sitemap" size="14" class="me-1" /> الشجرة
                </a>
            </div>

            @php
                $createParams = (request()->has('parent_id') && request('parent_id') !== 'null')
                    ? ['parent_id' => request('parent_id')]
                    : [];
            @endphp
            @canany(['authorities.create', 'authorities.update'])
<a href="{{ route('authorities.create', $createParams) }}" class="btn btn-primary-compact shadow-sm">
                <x-icon name="plus" size="14" class="me-1" /> إضافة جهة
            </a>
@endcanany

            @can('authorities.bulk-edit')
                <button type="button" id="bulk-edit-btn" class="btn btn-warning btn-compact shadow-sm ms-2">
                    <x-icon name="edit" size="14" class="me-1" /> تعديل جماعي (<span id="bulk-selected-count">الكل</span>)
                </button>
            @endcan

            @can('authorities.delete')
                <button type="button" id="bulk-delete-btn" class="btn btn-danger btn-compact shadow-sm ms-2 d-none" onclick="submitBulkDelete()">
                    <x-icon name="trash" size="14" class="me-1" /> حذف جماعي (<span id="bulk-delete-count">0</span>)
                </button>
                <form id="bulk-delete-form" action="{{ route('authorities.bulk.destroy') }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" id="bulk-delete-ids">
                </form>
            @endcan

          @can('authorities.import')
          <a href="{{ route('authorities.import') }}"
   class="btn btn-outline-info btn-compact shadow-sm ms-2">
    <x-icon name="file-import" size="14" class="me-1" />
    استيراد
</a>
          @endcan

@can('authorities.export')
<a href="{{ route('authorities.export') }}"
   class="btn btn-outline-success btn-compact shadow-sm ms-2">
    <x-icon name="download" size="14" class="me-1" />
    تصدير
</a>
@endcan

<div class="dropdown d-inline-block ms-2">
    <button class="btn btn-outline-secondary btn-compact dropdown-toggle shadow-sm"
            type="button"
            id="dataDropdown"
            data-bs-toggle="dropdown"
            aria-expanded="false">
        <x-icon name="database" size="14" class="me-1" />
        إضافية
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2"
        aria-labelledby="dataDropdown"
        style="min-width: 220px; border-radius: 12px;">

        <li>
            <a class="dropdown-item py-2 px-3"
               href="{{ route('authorities.import.template') }}">
                <x-icon name="file-excel" class="me-2 text-warning" />
                تحميل نموذج الاستيراد
            </a>
        </li>

    </ul>
</div>
        </x-slot>

        <x-slot name="filters">
            <form method="GET" id="filterForm" class="filter-section">
                <input type="hidden" name="view" value="{{ $view }}">
                @if(request()->has('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                @if(request()->has('order'))
                    <input type="hidden" name="order" value="{{ request('order') }}">
                @endif
                
                {{-- عرض الفلاتر النشطة --}}
                @php
                    $activeFiltersCount = 0;
                    if(request('authority_filter') && request('authority_filter') !== 'all') $activeFiltersCount++;
                    if(request('parent_id')) $activeFiltersCount++;
                    if(request('type_entity_id')) $activeFiltersCount++;
                    if(request('governorate_id')) $activeFiltersCount++;
                    if(request('directorate_id')) $activeFiltersCount++;
                    if(request('status')) $activeFiltersCount++;
                    if(request()->filled('is_funded')) $activeFiltersCount++;
                    if(request('search')) $activeFiltersCount++;
                @endphp

                @if($activeFiltersCount > 0)
                    <div class="mb-3">
                        <span class="active-filters-badge">
                            <x-icon name="filter" size="12" />
                            {{ $activeFiltersCount }} فلتر نشط
                        </span>
                        <a href="{{ route('authorities.index', ['view' => $view]) }}" 
                           class="btn btn-link btn-sm text-decoration-none ms-2">
                            <x-icon name="x-circle" size="12" class="me-1" />
                            مسح الكل
                        </a>
                    </div>
                @endif

                <div class="row g-3">
                    {{-- الصف الأول: الفلاتر الأساسية --}}
                    <div class="col-md-3">
                        <label class="filter-label">نطاق العرض</label>
                        <select name="authority_filter" class="form-select form-select-sm filter-select auto-filter">
                            <option value="all" {{ ($authorityFilter ?? 'all') === 'all' ? 'selected' : '' }}>🌐 جميع الجهات</option>
                            <option value="none" {{ ($authorityFilter ?? '') === 'none' ? 'selected' : '' }}>🚫 لا يعرض شيئاً</option>
                            <option value="same_governorate" {{ ($authorityFilter ?? '') === 'same_governorate' ? 'selected' : '' }}>🏙️ نطاق المحافظة</option>
                            <option value="same_directorate" {{ ($authorityFilter ?? '') === 'same_directorate' ? 'selected' : '' }}>🏢 نطاق المديرية</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">الحالة</label>
                        <select name="status" class="form-select form-select-sm filter-select auto-filter">
                            <option value="">📊 جميع الحالات</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>✅ نشط</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>⏸️ غير نشط</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">جهة ممولة</label>
                        <select name="is_funded" class="form-select form-select-sm filter-select auto-filter">
                            <option value="">💰 جميع الجهات (التمويل)</option>
                            <option value="1" {{ request('is_funded') === '1' ? 'selected' : '' }}>💎 جهة ممولة</option>
                            <option value="0" {{ request('is_funded') === '0' ? 'selected' : '' }}>⚪ غير ممولة</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">الجهة الرئيسية</label>
                        <select name="parent_id" class="form-select form-select-sm filter-select auto-filter">
                            <option value="">🏛️ جميع الجهات</option>
                            <option value="null" {{ request('parent_id') == 'null' ? 'selected' : '' }}>⭐ الجهات الرئيسية فقط</option>
                            @foreach($allAuthorities as $authority)
                                <option value="{{ $authority->id }}" {{ request('parent_id') == $authority->id ? 'selected' : '' }}>
                                    {{ $authority->agency_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- نوع الجهة - استخدام الكائنات --}}
                    <div class="col-md-3">
                        <label class="filter-label">نوع الجهة</label>
                        <select name="type_entity_id" class="form-select form-select-sm filter-select auto-filter">
                            <option value="">🏷️ جميع الأنواع</option>
                            @if(isset($typeEntities) && $typeEntities->isNotEmpty())
                                @foreach($typeEntities as $type)
                                    <option value="{{ $type->id }}" {{ request('type_entity_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>لا توجد أنواع جهات متاحة</option>
                            @endif
                        </select>
                    </div>

                    {{-- الصف الثاني: الفلاتر الجغرافية --}}
                    <div class="col-md-3">
                        <label class="filter-label">المحافظة</label>
                        <select name="governorate_id" id="governorate_id" class="form-select form-select-sm filter-select auto-filter">
                            <option value="">🗺️ جميع المحافظات</option>
                            {{-- التعديل هنا: استخدام $id => $name بدلاً من $gov->id --}}
                            @if(isset($governorates) && $governorates->isNotEmpty())
                                @foreach($governorates as $id => $name)
                                    <option value="{{ $id }}" {{ request('governorate_id') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">المديرية</label>
                        <select name="directorate_id" id="directorate_id" class="form-select form-select-sm filter-select auto-filter">
                            <option value="">📍 جميع المديريات</option>
                            {{-- التعديل هنا: استخدام $id => $name بدلاً من $dir->id --}}
                            @if(isset($directorates) && $directorates->isNotEmpty())
                                @foreach($directorates as $id => $name)
                                    <option value="{{ $id }}" {{ request('directorate_id') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">البحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white">
                                <x-icon name="search" size="14" class="text-muted" />
                            </span>
                            <input type="text" name="search" class="form-control filter-select" 
                                   placeholder="ابحث باسم الجهة..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary-compact flex-grow-1 shadow-sm">
                            <x-icon name="filter" size="14" class="me-1" /> تطبيق
                        </button>
                        <a href="{{ route('authorities.index', ['view' => $view]) }}"
                            class="btn btn-outline-secondary btn-compact">
                            <x-icon name="x-circle" size="14" class="me-1" /> إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </x-slot>

        @if($view == 'tree')
            <x-slot name="content">
                <div class="tree-container p-4 rounded-3 border bg-light shadow-sm">
                    @if($authorities->count() > 0)
                        <div id="authority-tree" class="full-page-tree">
                            <ul class="easy-tree">
                                @foreach($authorities as $authority)
                                    @include('configuration.authorities.partials.tree-node', ['authority' => $authority, 'level' => 0])
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">لا توجد جهات مسجدة حالياً</div>
                    @endif
                </div>
            </x-slot>
        @else
            <x-slot name="table">
                @php
                    $currentSort = request('sort', 'agency_name');
                    $currentOrder = request('order', 'asc');
                    
                    $getSortUrl = function($column) use ($currentSort, $currentOrder) {
                        $newOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
                        $params = request()->query();
                        $params['sort'] = $column;
                        $params['order'] = $newOrder;
                        return request()->url() . '?' . http_build_query($params);
                    };
                    
                    $getSortIcon = function($column) use ($currentSort, $currentOrder) {
                        if ($currentSort !== $column) {
                            return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="opacity-40" viewBox="0 0 16 16"><path d="M7.646 3.646a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8 4.707 5.354 7.354a.5.5 0 1 1-.708-.708l3-3zM8.354 12.354a.5.5 0 0 0-.708 0l-3-3a.5.5 0 1 0-.708.708L7 12.293l2.646-2.647a.5.5 0 0 0-.708-.708l-3 3z"/></svg>';
                        }
                        if ($currentOrder === 'asc') {
                            return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="text-primary" viewBox="0 0 16 16"><path d="M8 4l4 5H4z"/></svg>';
                        }
                        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="text-primary" viewBox="0 0 16 16"><path d="M8 12l4-5H4z"/></svg>';
                    };
                @endphp

                <div class="table-responsive">
                    <table class="table table-hover table-compact align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                @can('authorities.bulk-edit')
                                    <th style="width: 40px;" class="text-center">
                                        <input type="checkbox" class="form-check-input shadow-sm" id="select-all-authorities"
                                            title="تحديد الكل">
                                    </th>
                                @endcan
                                
 
                                <th class="text-start sortable-th">
                                    <a href="{{ $getSortUrl('type_entity') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>نوع الجهة</span>
                                        {!! $getSortIcon('type_entity') !!}
                                    </a>
                                </th>

                                <th class="text-start sortable-th">
                                    <a href="{{ $getSortUrl('is_funded') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>جهة ممولة</span>
                                        {!! $getSortIcon('is_funded') !!}
                                    </a>
                                </th>

                                <th class="text-start sortable-th">
                                    <a href="{{ $getSortUrl('agency_name') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>اسم الجهة</span>
                                        {!! $getSortIcon('agency_name') !!}
                                    </a>
                                </th>

                                <th class="text-start sortable-th">
                                    <a href="{{ $getSortUrl('father_name') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>الجهة الرئيسية</span>
                                        {!! $getSortIcon('father_name') !!}
                                    </a>
                                </th>

                                <th class="text-start sortable-th">
                                    <a href="{{ $getSortUrl('governorate') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>المحافظة</span>
                                        {!! $getSortIcon('governorate') !!}
                                    </a>
                                </th>

                                <th class="text-start sortable-th">
                                    <a href="{{ $getSortUrl('directorate') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>المديرية</span>
                                        {!! $getSortIcon('directorate') !!}
                                    </a>
                                </th>

                                <th class="text-start">أنشأ بواسطة</th>

                                <th class="text-start sortable-th">
                                    <a href="{{ $getSortUrl('created_at') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>تاريخ الإنشاء</span>
                                        {!! $getSortIcon('created_at') !!}
                                    </a>
                                </th>

                                <th class="text-center" style="width: 180px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($authorities as $authority)
                                <tr>
                                    @can('authorities.bulk-edit')
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input authority-checkbox shadow-sm"
                                                value="{{ $authority->id }}">
                                        </td>
                                    @endcan

                                    <td class="text-center">
                                        {{ $loop->iteration + (($authorities->currentPage() - 1) * $authorities->perPage()) }}
                                    </td>

                                    <td>
                                        @if($authority->typeEntity)
                                            <span class="badge badge-compact badge-type">
                                                <x-icon name="tag" size="10" class="me-1" />
                                                {{ $authority->typeEntity->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($authority->is_funded)
                                            <span class="badge badge-compact" style="background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc;">
                                                <x-icon name="check-circle" size="10" class="me-1" />
                                                ممولة
                                            </span>
                                        @else
                                            <span class="badge badge-compact" style="background-color: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6;">
                                                <x-icon name="minus-circle" size="10" class="me-1" />
                                                غير ممولة
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-name fw-semibold text-primary">
                                        <a href="{{ route('authorities.show', $authority->id) }}"
                                           class="text-decoration-none text-primary">
                                            <x-icon name="sitemap" class="text-primary me-2 opacity-75" size="14" />
                                            {{ $authority->agency_name }}
                                        </a>
                                        @if(!$authority->is_active)
                                            <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">غير نشط</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($authority->parent)
                                            <a href="{{ route('authorities.show', $authority->parent->id) }}"
                                               class="text-decoration-none">
                                                <span class="badge badge-compact badge-role">
                                                    <x-icon name="building" size="10" class="me-1" />
                                                    {{ $authority->parent->agency_name }}
                                                </span>
                                            </a>
                                        @else
                                            <span class="badge badge-compact badge-active">
                                                <x-icon name="star" size="10" class="me-1" />
                                                جهة رئيسية
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($authority->governorate)
                                            <span class="badge badge-compact badge-geo">
                                                <x-icon name="map-marker-alt" size="10" class="me-1" />
                                                {{ $authority->governorate->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($authority->directorate)
                                            <span class="badge badge-compact badge-geo-dir">
                                                <x-icon name="map-pin" size="10" class="me-1" />
                                                {{ $authority->directorate->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($authority->creator && $authority->creator->name)
                                            <div class="creator-cell">
                                                <x-icon name="user-circle" size="14" class="text-muted" />
                                                <span>{{ $authority->creator->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="date-cell">
                                            <span class="date-main small">{{ $authority->created_at->format('Y-m-d') }}</span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('authorities.show', $authority->id) }}"
                                                class="btn btn-icon btn-action-view" title="عرض">
                                                <x-icon name="eye" size="14" />
                                            </a>
                                            @can('authorities.edit')
                                            <a href="{{ route('authorities.edit', $authority->id) }}"
                                                class="btn btn-icon btn-action-edit" title="تعديل">
                                                <x-icon name="edit" size="14" />
                                            </a>
                                            @endcan
                                            @canany(['authorities.create', 'authorities.update'])
<a href="{{ route('authorities.create', ['parent_id' => $authority->id]) }}"
                                                class="btn btn-icon btn-action-enable" title="إضافة تابعة">
                                                <x-icon name="plus" size="14" />
                                            </a>
@endcanany
                                            @can('authorities.delete')
                                                <form action="{{ route('authorities.destroy', $authority->id) }}" method="POST"
                                                    class="d-inline" onsubmit="return confirmAction(this, 'هل تريد الحذف؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-icon btn-action-delete" title="حذف">
                                                        <x-icon name="trash" size="14" />
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->can('authorities.bulk-edit') ? 11 : 10 }}"
                                        class="text-center py-4 text-muted">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <x-icon name="info-circle" size="32" class="mb-2 opacity-50" />
                                            <p class="mb-0">لا توجد جهات مطابقة للبحث</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-slot>

            <x-slot name="pagination">
                <div class="text-muted small">
                    عرض {{ $authorities->firstItem() ?? 0 }} - {{ $authorities->lastItem() ?? 0 }} من إجمالي
                    {{ $authorities->total() }}
                </div>
                <div>
                    {{ $authorities->appends(request()->query())->links() }}
                </div>
            </x-slot>
        @endif

        @include('configuration.authorities.partials.bulk-edit-modal')
    </x-index-page>

    @push('scripts')
        <script src="{{ asset('js/authorities-bulk-actions.js') }}"></script>
        <script>
            /**
             * دالة تأكيد الإجراءات (مثل الحذف)
             */
            function confirmAction(form, message) {
                if (confirm(message)) {
                    return true;
                }
                return false;
            }

            /**
             * التطبيق التلقائي للفلترة عند تغيير أي حقل select
             */
            document.addEventListener('DOMContentLoaded', function() {
                const filterForm = document.getElementById('filterForm');
                const autoFilters = document.querySelectorAll('.auto-filter');
                let debounceTimer;

                autoFilters.forEach(function(select) {
                    select.addEventListener('change', function() {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(function() {
                            filterForm.submit();
                        }, 300);
                    });
                });

                // تحميل المديريات عند تغيير المحافظة
                const governorateSelect = document.getElementById('governorate_id');
                const directorateSelect = document.getElementById('directorate_id');

                if (governorateSelect && directorateSelect) {
                    governorateSelect.addEventListener('change', function() {
                        const governorateId = this.value;
                        
                        directorateSelect.innerHTML = '<option value="">📍 جميع المديريات</option>';
                        
                        if (governorateId) {
                            fetch(`/authorities/directorates/${governorateId}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.data) {
                                        data.data.forEach(directorate => {
                                            const option = document.createElement('option');
                                            option.value = directorate.id;
                                            option.textContent = directorate.name;
                                            directorateSelect.appendChild(option);
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error loading directorates:', error);
                                });
                        }
                    });
                }
                
                // إضافة مستمع للأحداث لإظهار زر الحذف الجماعي
                document.addEventListener('change', function(e) {
                    if (e.target && (e.target.classList.contains('authority-checkbox') || e.target.id === 'select-all-authorities')) {
                        const checkedCount = document.querySelectorAll('.authority-checkbox:checked').length;
                        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
                        if (bulkDeleteBtn) {
                            if (checkedCount > 0) {
                                bulkDeleteBtn.classList.remove('d-none');
                                document.getElementById('bulk-delete-count').textContent = checkedCount;
                            } else {
                                bulkDeleteBtn.classList.add('d-none');
                            }
                        }
                    }
                });
            });
            
            /**
             * إرسال نموذج الحذف الجماعي
             */
            function submitBulkDelete() {
                const selectedIds = Array.from(document.querySelectorAll('.authority-checkbox:checked')).map(cb => cb.value);
                if (selectedIds.length === 0) return;
                
                if (confirmAction(null, 'هل أنت متأكد من حذف الجهات المحددة؟')) {
                    document.getElementById('bulk-delete-ids').value = selectedIds.join(',');
                    document.getElementById('bulk-delete-form').submit();
                }
            }
        </script>
        <script>
            // Ensure Bootstrap Dropdown is initialized for the Data button
            document.addEventListener('DOMContentLoaded', function() {
                var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
                var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                    return new bootstrap.Dropdown(dropdownToggleEl)
                });
            });
        </script>
    @endpush
@endsection