@extends('layouts.app')

@section('styles')
<style>
    /* ─── إعادة ضبط العرض الكامل ─── */
    .letterheads-index {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .letterheads-index .container,
    .letterheads-index .container-fluid {
        max-width: 100% !important;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    /* ─── تصغير الخطوط العامة ─── */
    .letterheads-index,
    .letterheads-index .form-control,
    .letterheads-index .form-select,
    .letterheads-index .btn,
    .letterheads-index .dropdown-item,
    .letterheads-index .badge,
    .letterheads-index .small {
        font-size: 0.72rem !important;
    }

    .letterheads-index h6 { font-size: 0.8rem !important; }

    .letterheads-index .form-label {
        font-size: 0.65rem !important;
        font-weight: 600;
        margin-bottom: 0.2rem;
    }

    /* ─── الفلاتر ─── */
    .filter-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.6rem;
        margin-bottom: 0.5rem;
    }

    .filter-card .form-control,
    .filter-card .form-select {
        font-size: 0.7rem !important;
        padding: 0.3rem 0.5rem;
        height: 30px;
        border-radius: 6px;
    }

    .filter-card .row.g-2 { --bs-gutter-y: 0.35rem; }

    /* ─── أزرار الهيدر ─── */
    .header-btn {
        border-radius: 8px;
        padding: 0.35rem 0.75rem;
        font-weight: 600;
        font-size: 0.72rem !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        border: none;
        white-space: nowrap;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .header-btn.btn-primary-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
    }

    .header-btn.btn-primary-gradient:hover {
        color: #fff;
        filter: brightness(1.08);
    }

    .header-btn.btn-outline-gradient {
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #475569;
    }

    .header-btn.btn-outline-gradient:hover,
    .header-btn.btn-outline-gradient.active {
        background: #f8fafc;
        color: #1e293b;
        border-color: #cbd5e1;
    }

    /* ─── الجدول بعرض الشاشة ─── */
    .letterheads-table-wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }

    .letterheads-table {
        width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
        border-collapse: collapse;
    }

    .letterheads-table thead th {
        background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
        color: #334155;
        font-weight: 700;
        font-size: 0.68rem;
        padding: 0.4rem 0.3rem;
        border-bottom: 2px solid #cbd5e1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: center;
        vertical-align: middle;
    }

    .letterheads-table tbody td {
        padding: 0.35rem 0.3rem;
        font-size: 0.7rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .letterheads-table tbody tr:hover { background: #f8fafc; }

    .no-wrap {
        white-space: nowrap !important;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .text-truncate-custom {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ─── البادجات الفنية ─── */
    .badge-artistic {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50rem;
        font-size: 0.65rem !important;
        font-weight: 600;
        border: 1px solid transparent;
        white-space: nowrap;
        line-height: 1.2;
    }

    .badge-internal { background: #e0f2fe; color: #075985; border-color: #7dd3fc; }
    .badge-external { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
    .badge-global { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }
    .badge-active { background: #dcfce7; color: #166534; border-color: #86efac; }
    .badge-inactive { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    .badge-default { background: #fef9c3; color: #854d0e; border-color: #fde047; }
    .badge-normal { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }

    /* ─── أزرار الإجراءات الصغيرة ─── */
    .btn-action-artistic {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.65rem;
        padding: 0;
        vertical-align: middle;
        text-decoration: none;
    }

    .btn-action-artistic:hover {
        transform: translateY(-1px);
        filter: brightness(1.08);
        color: #fff;
    }

    .btn-action-artistic.btn-default { background: #10b981; color: #fff; }
    .btn-action-artistic.btn-edit { background: #f59e0b; color: #fff; }
    .btn-action-artistic.btn-delete { background: #ef4444; color: #fff; }

    /* ─── اسم الترويسة ─── */
    .letterhead-name {
        font-weight: 700;
        color: #1e293b;
    }

    /* ─── الحالة الفارغة ─── */
    .empty-state { padding: 2rem 1rem; text-align: center; }

    .empty-state .empty-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    /* ─── عرض الشجرة ─── */
    .tree-wrapper {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.65rem;
    }

    .tree-node {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        margin-bottom: 0.4rem;
        overflow: hidden;
    }

    .tree-node-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.45rem 0.6rem;
        background: #f8fafc;
    }

    .tree-node-title {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 700;
        color: #334155;
    }

    .tree-node-children {
        border-top: 1px dashed #e2e8f0;
        padding: 0.4rem 0.5rem 0.1rem;
    }

    .tree-letterheads {
        padding: 0.35rem 0.6rem 0.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    /* ─── أحجام الأعمدة ─── */
    .letterheads-table th:nth-child(1), .letterheads-table td:nth-child(1) { width: 35px; }
    .letterheads-table th:nth-child(2), .letterheads-table td:nth-child(2) { width: 145px; }
    .letterheads-table th:nth-child(3), .letterheads-table td:nth-child(3) { width: auto; min-width: 150px; }
    .letterheads-table th:nth-child(4), .letterheads-table td:nth-child(4) { width: 95px; }
    .letterheads-table th:nth-child(5), .letterheads-table td:nth-child(5) { width: 105px; }
    .letterheads-table th:nth-child(6), .letterheads-table td:nth-child(6) { width: 95px; }
    .letterheads-table th:nth-child(7), .letterheads-table td:nth-child(7) { width: 95px; }
    .letterheads-table th:nth-child(8), .letterheads-table td:nth-child(8) { width: 95px; }
    .letterheads-table th:nth-child(9), .letterheads-table td:nth-child(9) { width: 100px; }

    @media (max-width: 1100px) {
        .hide-xl { display: none !important; }
        .letterheads-table th:nth-child(8), .letterheads-table td:nth-child(8) { display: none; }
    }

    @media (max-width: 900px) {
        .letterheads-table th:nth-child(5), .letterheads-table td:nth-child(5) { display: none; }
    }

    @media (max-width: 768px) {
        .letterheads-table th:nth-child(4), .letterheads-table td:nth-child(4) { display: none; }
        .filter-actions { width: 100%; }
    }

    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
</style>
@endsection

@section('content')
<div class="letterheads-index">
    <x-index-page title="إدارة الترويسات" icon="file-text" :paginator="$letterheads">

        <x-slot name="headerActions">
            <div class="d-flex flex-wrap gap-1 align-items-center">
                <div class="d-inline-flex gap-1" role="group" aria-label="نمط العرض">
                    <a href="{{ route('letterheads.index', array_merge(request()->except('page'), ['view_mode' => 'table'])) }}"
                       class="header-btn btn-outline-gradient {{ $viewMode === 'table' ? 'active' : '' }}"
                       title="عرض جدولي">
                        <x-icon name="table" size="12" /> جدول
                    </a>
                    <a href="{{ route('letterheads.index', array_merge(request()->except('page'), ['view_mode' => 'tree'])) }}"
                       class="header-btn btn-outline-gradient {{ $viewMode === 'tree' ? 'active' : '' }}"
                       title="عرض هرمي">
                        <x-icon name="git-branch" size="12" /> شجرة
                    </a>
                </div>

                @can('create', App\Models\OrganizationLetterhead::class)
                    <a href="{{ route('letterheads.create') }}" class="header-btn btn-primary-gradient">
                        <x-icon name="plus" size="12" /> إضافة ترويسة
                    </a>
                @endcan
            </div>
        </x-slot>

        <x-slot name="filters">
            <form id="auto-filter-form" action="{{ route('letterheads.index') }}" method="GET">
                <input type="hidden" name="view_mode" value="{{ $viewMode }}">

                <div class="filter-card">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-5">
                            <label for="search" class="form-label">بحث</label>
                            <input type="text"
                                   class="form-control auto-filter"
                                   id="search"
                                   name="search"
                                   value="{{ $search ?? request('search') }}"
                                   placeholder="اسم الترويسة أو اسم الجهة...">
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="org_type" class="form-label">نوع الجهة</label>
                            <select class="form-select auto-submit-filter" id="org_type" name="org_type">
                                <option value="all" {{ ($orgType ?? request('org_type')) === 'all' || blank($orgType ?? request('org_type')) ? 'selected' : '' }}>الكل</option>
                                <option value="internal" {{ ($orgType ?? request('org_type')) === 'internal' ? 'selected' : '' }}>جهة داخلية</option>
                                <option value="external" {{ ($orgType ?? request('org_type')) === 'external' ? 'selected' : '' }}>جهة خارجية</option>
                                <option value="system_global" {{ ($orgType ?? request('org_type')) === 'system_global' ? 'selected' : '' }}>عامة للنظام</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="governorate_id" class="form-label">المحافظة</label>
                            <select class="form-select auto-submit-filter" id="governorate_id" name="governorate_id">
                                <option value="">كل المحافظات</option>
                                @foreach(($governorates ?? collect()) as $governorate)
                                    <option value="{{ $governorate->id }}" {{ (string) ($governorateId ?? request('governorate_id')) === (string) $governorate->id ? 'selected' : '' }}>
                                        {{ $governorate->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-2 d-flex gap-1 align-items-end filter-actions">
                            <button type="submit" class="header-btn btn-primary-gradient flex-grow-1">
                                <x-icon name="search" size="12" /> بحث
                            </button>
                            <a href="{{ route('letterheads.index', ['view_mode' => $viewMode]) }}"
                               class="header-btn btn-outline-gradient"
                               title="إعادة تعيين الفلاتر">
                                <x-icon name="refresh-ccw" size="12" />
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="table">
            @if($viewMode === 'tree')
                <div class="tree-wrapper">
                    @php
                        $renderTreeNode = function ($node, $level = 0) use (&$renderTreeNode) {
                            $name = data_get($node, 'name')
                                ?? data_get($node, 'organization_name')
                                ?? data_get($node, 'agency_name')
                                ?? data_get($node, 'label')
                                ?? 'جهة';

                            $type = data_get($node, 'type')
                                ?? data_get($node, 'organization_type')
                                ?? '';

                            $children = data_get($node, 'children', []);
                            $letterheads = data_get($node, 'letterheads', []);

                            if ($children instanceof \Illuminate\Support\Collection) {
                                $children = $children->all();
                            }
                            if ($letterheads instanceof \Illuminate\Support\Collection) {
                                $letterheads = $letterheads->all();
                            }

                            $typeLabel = match ($type) {
                                'internal' => 'داخلية',
                                'external' => 'خارجية',
                                'system_global' => 'عامة',
                                default => null,
                            };

                            echo '<div class="tree-node" style="margin-right:' . ((int) $level * 12) . 'px">';
                            echo '<div class="tree-node-header">';
                            echo '<div class="tree-node-title">';
                            echo '<span>▸</span><span class="text-truncate-custom" title="' . e($name) . '">' . e($name) . '</span>';
                            echo '</div>';

                            if ($typeLabel) {
                                echo '<span class="badge-artistic badge-normal">' . e($typeLabel) . '</span>';
                            }
                            echo '</div>';

                            if (! empty($letterheads)) {
                                echo '<div class="tree-letterheads">';
                                foreach ($letterheads as $lh) {
                                    $lhName = data_get($lh, 'name', 'ترويسة');
                                    $isDefault = (bool) data_get($lh, 'is_default', false);
                                    $isGlobal = (bool) data_get($lh, 'is_global_default', false);
                                    $badgeClass = ($isDefault || $isGlobal) ? 'badge-default' : 'badge-normal';
                                    echo '<span class="badge-artistic ' . $badgeClass . '">' . e($lhName) . '</span>';
                                }
                                echo '</div>';
                            }

                            if (! empty($children)) {
                                echo '<div class="tree-node-children">';
                                foreach ($children as $child) {
                                    $renderTreeNode($child, $level + 1);
                                }
                                echo '</div>';
                            }

                            echo '</div>';
                        };
                    @endphp

                    @forelse($hierarchyTree as $node)
                        @php($renderTreeNode($node))
                    @empty
                        <div class="empty-state">
                            <div class="empty-icon">
                                <x-icon name="git-branch" size="24" class="text-muted" />
                            </div>
                            <h6 class="text-dark fw-bold mb-1">لا توجد بيانات هرمية</h6>
                            <p class="text-muted small mb-0">لم يتم العثور على جهات أو ترويسات ضمن نطاق صلاحياتك.</p>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="letterheads-table-wrapper">
                    <table class="table letterheads-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-center no-wrap">#</th>
                                <th class="no-wrap">اسم الترويسة</th>
                                <th class="no-wrap">الجهة</th>
                                <th class="text-center no-wrap">النوع</th>
                                <th class="text-center no-wrap">المحافظة</th>
                                <th class="text-center no-wrap">الافتراضية</th>
                                <th class="text-center no-wrap">الحالة</th>
                                <th class="text-center no-wrap hide-xl">بواسطة</th>
                                <th class="text-center no-wrap">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($letterheads ?? collect() as $letterhead)
                                @php
                                    $governorateName = $letterhead->internalEntity?->governorate?->name
                                        ?? $letterhead->authority?->governorate?->name
                                        ?? 'ـ';

                                    $organizationName = $letterhead->organization_name ?? 'ـ';
                                @endphp

                                <tr>
                                    <td class="text-center no-wrap">
                                        <span class="text-muted fw-bold">
                                            {{ ($letterheads?->firstItem() ?? 1) + $loop->index }}
                                        </span>
                                    </td>

                                    <td class="no-wrap">
                                        <span class="letterhead-name text-truncate-custom d-block" title="{{ $letterhead->name }}">
                                            {{ $letterhead->name }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="fw-bold text-dark text-truncate-custom d-block" title="{{ $organizationName }}">
                                            {{ $organizationName }}
                                        </span>
                                    </td>

                                    <td class="text-center no-wrap">
                                        @switch($letterhead->organization_type)
                                            @case('internal')
                                                <span class="badge-artistic badge-internal">داخلية</span>
                                                @break
                                            @case('external')
                                                <span class="badge-artistic badge-external">خارجية</span>
                                                @break
                                            @case('system_global')
                                                <span class="badge-artistic badge-global">عامة</span>
                                                @break
                                            @default
                                                <span class="badge-artistic badge-normal">{{ $letterhead->organization_type ?? 'ـ' }}</span>
                                        @endswitch
                                    </td>

                                    <td class="text-center no-wrap">
                                        <span class="text-dark text-truncate-custom d-block" title="{{ $governorateName }}">
                                            {{ $governorateName }}
                                        </span>
                                    </td>

                                    <td class="text-center no-wrap">
                                        @if($letterhead->is_global_default)
                                            <span class="badge-artistic badge-global" title="الترويسة الافتراضية العامة للنظام">
                                                عامة
                                            </span>
                                        @elseif($letterhead->is_default)
                                            <span class="badge-artistic badge-default">افتراضية</span>
                                        @else
                                            <span class="badge-artistic badge-normal">عادية</span>
                                        @endif
                                    </td>

                                    <td class="text-center no-wrap">
                                        @if($letterhead->is_active)
                                            <span class="badge-artistic badge-active">نشطة</span>
                                        @else
                                            <span class="badge-artistic badge-inactive">معطلة</span>
                                        @endif
                                    </td>

                                    <td class="text-center no-wrap hide-xl">
                                        <span class="text-muted text-truncate-custom d-block" title="{{ $letterhead->creator->name ?? '' }}">
                                            {{ $letterhead->creator->name ?? 'ـ' }}
                                        </span>
                                    </td>

                                    <td class="text-center no-wrap">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            @can('update', $letterhead)
                                                @if(!$letterhead->is_default && !$letterhead->is_global_default && Route::has('letterheads.set-default'))
                                                    <form action="{{ route('letterheads.set-default', $letterhead) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn-action-artistic btn-default"
                                                                title="تعيين كافتراضية"
                                                                onclick="return confirmAction(this, 'هل تريد تعيين هذه الترويسة كافتراضية؟')">
                                                            <x-icon name="star" size="10" />
                                                        </button>
                                                    </form>
                                                @endif

                                                <a href="{{ route('letterheads.edit', $letterhead) }}"
                                                   class="btn-action-artistic btn-edit"
                                                   title="تعديل الترويسة">
                                                    <x-icon name="edit-2" size="10" />
                                                </a>
                                            @endcan

                                            @can('delete', $letterhead)
                                                <form action="{{ route('letterheads.destroy', $letterhead) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn-action-artistic btn-delete"
                                                            title="حذف الترويسة"
                                                            onclick="return confirmAction(this, 'هل أنت متأكد من حذف هذه الترويسة؟')">
                                                        <x-icon name="trash-2" size="10" />
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <x-icon name="file-text" size="24" class="text-muted" />
                                            </div>
                                            <h6 class="text-dark fw-bold mb-1">لا توجد ترويسات حالياً</h6>
                                            <p class="text-muted small mb-3">لم يتم العثور على ترويسات تطابق معايير البحث الحالية.</p>

                                            @can('create', App\Models\OrganizationLetterhead::class)
                                                <a href="{{ route('letterheads.create') }}" class="header-btn btn-primary-gradient d-inline-flex">
                                                    <x-icon name="plus" size="12" /> إضافة ترويسة جديدة
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </x-slot>
    </x-index-page>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('auto-filter-form');
        if (!filterForm) return;

        const searchInput = document.getElementById('search');
        const autoSubmitFilters = document.querySelectorAll('.auto-submit-filter');

        if (searchInput) {
            let debounceTimer;

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => filterForm.submit(), 500);
            });
        }

        autoSubmitFilters.forEach(function (element) {
            element.addEventListener('change', function () {
                filterForm.submit();
            });
        });
    });
</script>
@endpush
@endsection
