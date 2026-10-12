@extends('layouts.app')

@section('styles')
<style>
    /* ─── إعادة ضبط العرض الكامل ─── */
    .plans-index {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }
    .plans-index .container,
    .plans-index .container-fluid {
        max-width: 100% !important;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    /* ─── تصغير الخطوط العامة ─── */
    .plans-index,
    .plans-index .form-control,
    .plans-index .form-select,
    .plans-index .btn,
    .plans-index .dropdown-item,
    .plans-index .badge,
    .plans-index .small {
        font-size: 0.72rem !important;
    }
    .plans-index h6 { font-size: 0.8rem !important; }
    .plans-index .form-label {
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

    /* ─── الجدول بعرض الشاشة ─── */
    .plans-table-wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    .plans-table {
        width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    .plans-table thead th {
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
    .plans-table tbody td {
        padding: 0.35rem 0.3rem;
        font-size: 0.7rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .plans-table tbody tr:hover {
        background: #f8fafc;
    }

    /* ─── منع التفاف النص والتمرير الأفقي ─── */
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

    /* ─── البادجات الفنية الصغيرة ─── */
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
    .badge-artistic.badge-role {
        background: #ede9fe;
        color: #5b21b6;
        border-color: #c4b5fd;
    }
    .badge-artistic.badge-active {
        background: #dcfce7;
        color: #166534;
        border-color: #86efac;
    }

    /* ─── أزرار الإجراءات الصغيرة الفنية ─── */
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
        filter: brightness(1.1);
        color: white;
    }
    .btn-action-artistic.btn-view { background: #3b82f6; color: white; }
    .btn-action-artistic.btn-log { background: #8b5cf6; color: white; }
    .btn-action-artistic.btn-edit { background: #f59e0b; color: white; }
    .btn-action-artistic.btn-enable { background: #10b981; color: white; }
    .btn-action-artistic.btn-delete { background: #ef4444; color: white; }

    /* ─── رقم الخطة ─── */
    .plan-number {
        font-family: monospace;
        font-weight: 700;
        font-size: 0.68rem;
        color: #1e293b;
        background: #f1f5f9;
        padding: 0.15rem 0.4rem;
        border-radius: 5px;
        border: 1px solid #cbd5e1;
        white-space: nowrap;
    }

    /* ─── أزرار الهيدر ─── */
    .header-btn {
        border-radius: 8px;
        padding: 0.35rem 0.75rem;
        font-weight: 600;
        font-size: 0.72rem !important;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border: none;
        white-space: nowrap;
        text-decoration: none;
    }
    .header-btn.btn-primary-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .header-btn.btn-primary-gradient:hover {
        color: white;
        filter: brightness(1.08);
    }
    .header-btn.btn-outline-gradient {
        background: white;
        border: 1px solid #e2e8f0;
        color: #475569;
    }
    .header-btn.btn-outline-gradient:hover {
        background: #f8fafc;
        color: #1e293b;
    }

    /* ─── Dropdown ─── */
    .dropdown-menu-artistic {
        border: none;
        border-radius: 10px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        padding: 0.3rem;
        font-size: 0.7rem !important;
        min-width: 180px;
    }
    .dropdown-menu-artistic .dropdown-item {
        border-radius: 6px;
        padding: 0.4rem 0.6rem;
        font-size: 0.7rem !important;
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

    /* ─── أحجام الأعمدة المحددة ─── */
    .plans-table th:nth-child(1), .plans-table td:nth-child(1) { width: 35px; }
    .plans-table th:nth-child(2), .plans-table td:nth-child(2) { width: 90px; }
    .plans-table th:nth-child(3), .plans-table td:nth-child(3) { width: 110px; }
    .plans-table th:nth-child(4), .plans-table td:nth-child(4) { width: auto; min-width: 120px; }
    .plans-table th:nth-child(5), .plans-table td:nth-child(5) { width: 100px; }
    .plans-table th:nth-child(6), .plans-table td:nth-child(6) { width: 85px; }
    .plans-table th:nth-child(7), .plans-table td:nth-child(7) { width: 155px; }

    /* ─── التجاوب مع الشاشات ─── */
    @media (max-width: 992px) {
        .hide-lg { display: none !important; }
        .plans-table th:nth-child(5), .plans-table td:nth-child(5) { display: none; }
    }
    @media (max-width: 768px) {
        .plans-table th:nth-child(6), .plans-table td:nth-child(6) { display: none; }
    }

    /* ─── منع التمرير الأفقي على مستوى الصفحة ─── */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
</style>
@endsection

@section('content')
<div class="plans-index">
    <x-index-page title="إدارة الخطط" icon="file-invoice" :paginator="$plans">
        <x-slot name="headerActions">
            @can('plans.create')
                <a href="{{ route('plans.create') }}" class="header-btn btn-primary-gradient auth-perm-plans-create">
                    <x-icon name="plus" size="12" /> إضافة خطة
                </a>
            @endcan

            @if(
                auth()->user()->can('batchPrint', App\Models\Plan::class) ||
                auth()->user()->can('comprehensiveBatchPrint', App\Models\Plan::class) ||
                auth()->user()->can('plans.export') ||
                auth()->user()->can('plans.import')
            )
                <div class="dropdown">
                    <button class="header-btn btn-outline-gradient dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <x-icon name="cogs" size="12" /> عمليات
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-artistic">
                        @can('plans.batch-print')
                            <li>
                                <a class="dropdown-item" href="{{ route('plans.batch-print', ['search' => request('search')]) }}" target="_blank">
                                    <x-icon name="table" class="text-primary" size="12" /> المصفوفة التشغيلية
                                </a>
                            </li>
                        @endcan
                        @can('plans.comprehensive-batch-print')
                            <li>
                                <a class="dropdown-item" href="{{ route('plans.batch-print.comprehensive', ['search' => request('search')]) }}" target="_blank">
                                    <x-icon name="layers" class="text-info" size="12" /> المصفوفة الشاملة
                                </a>
                            </li>
                        @endcan
                        @if(Gate::check('plans.export') || Gate::check('plans.import'))
                            <li><hr class="dropdown-divider"></li>
                        @endif
                        @can('plans.export')
                            <li>
                                <a class="dropdown-item auth-perm-plans-export" href="{{ route('plans.export', ['search' => request('search')]) }}">
                                    <x-icon name="file-excel" class="text-success" size="12" /> تصدير Excel
                                </a>
                            </li>
                        @endcan
                        @can('plans.import')
                            <li>
                                <a class="dropdown-item" href="{{ route('plans.show-import') }}">
                                    <x-icon name="file-import" class="text-warning" size="12" /> استيراد Excel
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            @endif
        </x-slot>

        <x-slot name="filters">
            <form id="auto-filter-form" action="{{ route('plans.index') }}" method="GET">
                <div class="filter-card">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-10">
                            <label for="search" class="form-label">بحث</label>
                            <input type="text" class="form-control auto-filter" id="search" name="search"
                                value="{{ request('search') }}" placeholder="اسم الجهة أو الأولوية أو رقم الخطة...">
                        </div>
                        <div class="col-12 col-md-2 d-flex gap-1 align-items-end">
                            <button type="submit" class="header-btn btn-primary-gradient w-100 justify-content-center">
                                <x-icon name="search" size="12" /> بحث
                            </button>
                            <a href="{{ route('plans.index') }}" class="header-btn btn-outline-gradient" title="إعادة تعيين">
                                <x-icon name="refresh-ccw" size="12" />
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="table">
            <div class="plans-table-wrapper">
                <table class="table plans-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center no-wrap">#</th>
                            <th class="text-center no-wrap">رقم الخطة</th>
                            <th class="no-wrap">الفترة / الأولوية</th>
                            <th class="no-wrap">الجهة المقدمة</th>
                            <th class="no-wrap hide-lg">بواسطة</th>
                            <th class="text-center no-wrap">التاريخ</th>
                            <th class="text-center no-wrap">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td class="text-center no-wrap">
                                    <span class="text-muted fw-bold">{{ $loop->iteration }}</span>
                                </td>
                                <td class="text-center no-wrap">
                                    <span class="plan-number">{{ $plan->plan_number }}</span>
                                </td>
                                <td class="no-wrap">
                                    <span class="badge-artistic badge-role">
                                        @if($plan->start_date_g && $plan->end_date_g)
                                            {{ \Carbon\Carbon::parse($plan->start_date_g)->format('Y/m/d') }} - {{ \Carbon\Carbon::parse($plan->end_date_g)->format('Y/m/d') }}
                                        @elseif(is_object($plan->priority))
                                            {{ $plan->priority->priority ?? $plan->priority->name ?? 'ـ' }}
                                        @else
                                            {{ $plan->priority ?? 'ـ' }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark text-truncate-custom d-block" title="{{ $plan->submitting_organization_name ?? '' }}">
                                        {{ $plan->submitting_organization_name ?? 'ـ' }}
                                    </span>
                                </td>
                                <td class="no-wrap hide-lg">
                                    <span class="text-muted text-truncate-custom d-block">
                                        {{ $plan->creator->name ?? 'ـ' }}
                                    </span>
                                </td>
                                <td class="text-center no-wrap">
                                    <span class="text-dark">{{ $plan->created_at->format('Y/m/d') }}</span>
                                </td>
                                <td class="text-center no-wrap">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        @can('plans.view', $plan)
                                            <a href="{{ route('plans.show', $plan->id) }}" class="btn-action-artistic btn-view auth-perm-plans-view" title="عرض الخطة">
                                                <x-icon name="eye" size="10" />
                                            </a>
                                        @endcan

                                        @canany(['plans.implementation', 'plans.update', 'plans.edit'], $plan)
                                            <a href="{{ route('plans.implementation', $plan->id) }}" class="btn-action-artistic btn-enable" title="إضافة أنشطة تنفيذية">
                                                <x-icon name="plus-circle" size="10" />
                                            </a>
                                        @endcanany

                                        @can('plans.print', $plan)
                                            <a href="{{ route('plans.print', $plan->id) }}" target="_blank" class="btn-action-artistic btn-log auth-perm-plans-print" title="طباعة الخطة">
                                                <x-icon name="print" size="10" />
                                            </a>
                                        @endcan

                                        @canany(['plans.print-implementation', 'plans.implementation.print', 'plans.print'], $plan)
                                            <a href="{{ route('plans.implementation.print', $plan->id) }}" target="_blank" class="btn-action-artistic btn-edit" title="طباعة التنفيذ">
                                                <x-icon name="file-text" size="10" />
                                            </a>
                                        @endcanany

                                        @canany(['plans.update', 'plans.edit'], $plan)
                                            <a href="{{ route('plans.edit', $plan->id) }}" class="btn-action-artistic btn-edit auth-perm-plans-edit" title="تعديل الخطة">
                                                <x-icon name="edit-2" size="10" />
                                            </a>
                                        @endcanany

                                        @can('plans.delete', $plan)
                                            <form action="{{ route('plans.destroy', $plan->id) }}" method="POST" class="d-inline auth-perm-plans-delete">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action-artistic btn-delete" title="حذف" onclick="return confirmAction(this, 'هل أنت متأكد من الحذف؟')">
                                                    <x-icon name="trash-2" size="10" />
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <x-icon name="file-invoice" size="24" class="text-muted" />
                                        </div>
                                        <h6 class="text-dark fw-bold mb-1">لا توجد خطط حالياً</h6>
                                        <p class="text-muted small mb-3">لم يتم العثور على أي خطط مسجلة تطابق معايير البحث.</p>
                                        @can('plans.create')
                                            <a href="{{ route('plans.create') }}" class="header-btn btn-primary-gradient d-inline-flex">
                                                <x-icon name="plus" size="12" /> إضافة خطة جديدة
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-slot>
    </x-index-page>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('auto-filter-form');
        if (!filterForm) return;

        const searchInput = document.getElementById('search');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => filterForm.submit(), 500);
            });
        }
    });
</script>
@endpush
@endsectionlectorAll('.dropdown-toggle'))

    dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl)
    })

})

</script>
@endsection
