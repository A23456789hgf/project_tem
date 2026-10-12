@extends('layouts.app')

@section('styles')
<style>
    /* ─── إعادة ضبط العرض الكامل ─── */
    .users-index {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }
    .users-index .container,
    .users-index .container-fluid {
        max-width: 100% !important;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    /* ─── تصغير الخطوط العامة ─── */
    .users-index,
    .users-index .form-control,
    .users-index .form-select,
    .users-index .btn,
    .users-index .dropdown-item,
    .users-index .badge,
    .users-index .small {
        font-size: 0.72rem !important;
    }
    .users-index h6 { font-size: 0.8rem !important; }
    .users-index .form-label {
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
    .users-table-wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    .users-table {
        width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    .users-table thead th {
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
    .users-table tbody td {
        padding: 0.35rem 0.3rem;
        font-size: 0.7rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .users-table tbody tr:hover {
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
    .badge-artistic.badge-active {
        background: #dcfce7;
        color: #166534;
        border-color: #86efac;
    }
    .badge-artistic.badge-disabled {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fca5a5;
    }
    .badge-artistic.badge-role {
        background: #ede9fe;
        color: #5b21b6;
        border-color: #c4b5fd;
    }
    .badge-artistic.badge-geo {
        background: #e0f2fe;
        color: #0369a1;
        border-color: #7dd3fc;
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
    .btn-action-artistic.btn-disable { background: #6b7280; color: white; }
    .btn-action-artistic.btn-delete { background: #ef4444; color: white; }

    /* ─── رقم المستخدم ─── */
    .user-number {
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
    .users-table th:nth-child(1), .users-table td:nth-child(1) { width: 35px; }
    .users-table th:nth-child(2), .users-table td:nth-child(2) { width: 65px; }
    .users-table th:nth-child(3), .users-table td:nth-child(3) { width: auto; min-width: 110px; }
    .users-table th:nth-child(4), .users-table td:nth-child(4) { width: 90px; }
    .users-table th:nth-child(5), .users-table td:nth-child(5) { width: 90px; }
    .users-table th:nth-child(6), .users-table td:nth-child(6) { width: 110px; }
    .users-table th:nth-child(7), .users-table td:nth-child(7) { width: 65px; }
    .users-table th:nth-child(8), .users-table td:nth-child(8) { width: 85px; }
    .users-table th:nth-child(9), .users-table td:nth-child(9) { width: 100px; }
    .users-table th:nth-child(10), .users-table td:nth-child(10) { width: 135px; }

    /* ─── التجاوب مع الشاشات ─── */
    @media (max-width: 1200px) {
        .hide-xl { display: none !important; }
        .users-table th:nth-child(9), .users-table td:nth-child(9) { display: none; }
    }
    @media (max-width: 992px) {
        .hide-lg { display: none !important; }
        .users-table th:nth-child(8), .users-table td:nth-child(8) { display: none; }
    }
    @media (max-width: 768px) {
        .users-table th:nth-child(4), .users-table td:nth-child(4),
        .users-table th:nth-child(6), .users-table td:nth-child(6) { display: none; }
    }

    /* ─── منع التمرير الأفقي على مستوى الصفحة ─── */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
</style>
@endsection

@section('content')
<div class="users-index">
    <x-index-page title="إدارة المستخدمين" icon="users">

        <x-slot name="headerActions">
            @can('users.create')
                <a href="{{ route('users.create') }}" class="header-btn btn-primary-gradient auth-perm-users-create">
                    <x-icon name="plus" size="12" /> إضافة مستخدم
                </a>
            @endcan
        </x-slot>

        <x-slot name="filters">
            <form id="auto-filter-form" action="{{ route('users.index') }}" method="GET">
                <div class="filter-card">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-3 col-lg-3">
                            <label for="search" class="form-label">بحث</label>
                            <input type="text" class="form-control auto-filter" id="search" name="search"
                                value="{{ request('search') }}" placeholder="الاسم، الرقم، الهاتف...">
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <label for="role_id" class="form-label">الدور</label>
                            <select class="form-select auto-filter" id="role_id" name="role_id" onchange="this.form.submit()">
                                <option value="">كل الأدوار</option>
                                @foreach($roles as $id => $name)
                                    <option value="{{ $id }}" {{ request('role_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <label for="entity_id" class="form-label">الجهة</label>
                            <select class="form-select auto-filter" id="entity_id" name="entity_id" onchange="this.form.submit()">
                                <option value="">كل الجهات</option>
                                @foreach($entities as $entity)
                                    <option value="{{ $entity->id }}" {{ request('entity_id') == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <label for="organization_type" class="form-label">نوع الجهة</label>
                            <select class="form-select auto-filter" id="organization_type" name="organization_type" onchange="this.form.submit()">
                                <option value="">كل الأنواع</option>
                                <option value="internal" @selected(request('organization_type') === 'internal')>داخلية</option>
                                <option value="external" @selected(request('organization_type') === 'external')>خارجية</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <label for="authority_id" class="form-label">الجهة الخارجية</label>
                            <select class="form-select auto-filter" id="authority_id" name="authority_id" onchange="this.form.submit()">
                                <option value="">كل الجهات الخارجية</option>
                                @foreach($authorities as $authority)
                                    <option value="{{ $authority->id }}" @selected((string) request('authority_id') === (string) $authority->id)>{{ $authority->agency_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <label for="status" class="form-label">الحالة</label>
                            <select class="form-select auto-filter" id="status" name="status" onchange="this.form.submit()">
                                <option value="">كل الحالات</option>
                                <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>نشط</option>
                                <option value="Disabled" {{ request('status') === 'Disabled' ? 'selected' : '' }}>معطل</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <label class="form-label">الفرز</label>
                            <div class="d-flex gap-1">
                                <select name="sort_by" class="form-select auto-filter" onchange="this.form.submit()">
                                    <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>تاريخ</option>
                                    <option value="user_id" {{ request('sort_by') === 'user_id' ? 'selected' : '' }}>رقم</option>
                                    <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>اسم</option>
                                    <option value="phone" {{ request('sort_by') === 'phone' ? 'selected' : '' }}>هاتف</option>
                                    <option value="role_id" {{ request('sort_by') === 'role_id' ? 'selected' : '' }}>دور</option>
                                    <option value="entity_id" {{ request('sort_by') === 'entity_id' ? 'selected' : '' }}>جهة</option>
                                    <option value="status" {{ request('sort_by') === 'status' ? 'selected' : '' }}>حالة</option>
                                </select>
                                <select name="sort_order" class="form-select auto-filter" style="width: 45px; flex-shrink: 0; padding: 0.2rem;" onchange="this.form.submit()">
                                    <option value="desc" {{ request('sort_order', 'desc') === 'desc' ? 'selected' : '' }}>▼</option>
                                    <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>▲</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-auto d-flex align-items-end">
                            <a href="{{ route('users.index') }}" class="header-btn btn-outline-gradient" title="إعادة تعيين">
                                <x-icon name="refresh-ccw" size="12" />
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="table">
            <div class="users-table-wrapper">
                <table class="table users-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center no-wrap">#</th>
                            <th class="text-center no-wrap">رقم المستخدم</th>
                            <th>الاسم الكامل</th>
                            <th class="no-wrap">الهاتف</th>
                            <th class="text-center no-wrap">الدور</th>
                            <th class="no-wrap">الجهة</th>
                            <th class="text-center no-wrap">الحالة</th>
                            <th class="text-center no-wrap hide-lg">تاريخ الإنشاء</th>
                            <th class="no-wrap hide-xl">المنشئ</th>
                            <th class="text-center no-wrap">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="text-center no-wrap">
                                    <span class="text-muted fw-bold">{{ $loop->iteration }}</span>
                                </td>
                                <td class="text-center no-wrap">
                                    <span class="user-number">{{ $user->user_id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="fw-bold text-dark text-truncate-custom" title="{{ $user->name }}">
                                            {{ $user->name }}
                                        </span>
                                        @if($user->is_financial_user)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" title="مستخدم مالي مزامن مع ERPNext" style="font-size: 0.6rem; padding: 1px 4px;">
                                                <i class="fas fa-coins me-1"></i> مالي
                                            </span>
                                        @endif
                                        @if($user->is_geographic_subset)
                                            <span class="badge-artistic badge-geo" title="نطاق جغرافي محدد">
                                                <x-icon name="map-pin" size="8" />
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="no-wrap">
                                    <span class="text-muted" dir="ltr">{{ $user->phone }}</span>
                                </td>
                                <td class="text-center no-wrap">
                                    <span class="badge-artistic badge-role">
                                        {{ $user->role->name ?? 'ـ' }}
                                    </span>
                                </td>
                                <td class="no-wrap">
                                    @php($organization = $user->organization())
                                    <span class="text-dark text-truncate-custom d-block" title="{{ $organization['model']?->name ?? $organization['model']?->agency_name ?? '' }}">
                                        {{ $organization['model']?->name ?? $organization['model']?->agency_name ?? 'ـ' }}
                                    </span>
                                </td>
                                <td class="text-center no-wrap">
                                    @if($user->status === 'Active')
                                        <span class="badge-artistic badge-active">نشط</span>
                                    @else
                                        <span class="badge-artistic badge-disabled">معطل</span>
                                    @endif
                                </td>
                                <td class="text-center no-wrap hide-lg">
                                    <div class="d-flex flex-column align-items-center" style="line-height: 1.1;">
                                        <span class="text-dark">{{ $user->created_at->format('Y-m-d') }}</span>
                                        <small class="text-muted" style="font-size: 0.6rem;">{{ $user->created_at->format('h:i A') }}</small>
                                    </div>
                                </td>
                                <td class="no-wrap hide-xl">
                                    @if($user->creator_username || $user->createdBy)
                                        <div class="d-flex flex-column" style="line-height: 1.1;">
                                            <span class="text-dark text-truncate-custom">
                                                {{ $user->creator_username ?? $user->createdBy->user_id ?? $user->createdBy->username ?? 'ـ' }}
                                            </span>
                                            <small class="text-muted text-truncate-custom" style="font-size: 0.6rem;">
                                                {{ $user->creatorEntity->name ?? $user->createdBy->entity->name ?? 'ـ' }}
                                            </small>
                                        </div>
                                    @else
                                        <span class="text-muted">ـ</span>
                                    @endif
                                </td>
                                <td class="text-center no-wrap">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        @can('users.view')
                                            <a href="{{ route('users.show', $user) }}" class="btn-action-artistic btn-view auth-perm-users-view" title="عرض">
                                                <x-icon name="eye" size="10" />
                                            </a>
                                            <a href="{{ route('users.activity-log', $user) }}" class="btn-action-artistic btn-log auth-perm-users-view" title="سجل النشاط">
                                                <x-icon name="clock" size="10" />
                                            </a>
                                        @endcan

                                        @if($user->username !== 'root')
                                            @can('users.edit')
                                                <a href="{{ route('users.edit', $user) }}" class="btn-action-artistic btn-edit auth-perm-users-edit" title="تعديل">
                                                    <x-icon name="edit-2" size="10" />
                                                </a>
                                            @endcan

                                            @if($user->status === 'Active')
                                                @can('users.disable')
                                                    <form action="{{ route('users.disable', $user) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn-action-artistic btn-disable auth-perm-users-disable" title="تعطيل" onclick="return confirmAction(this, 'هل أنت متأكد من عملية التعطيل؟')">
                                                            <x-icon name="pause" size="10" />
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('users.enable')
                                                    <form action="{{ route('users.enable', $user) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn-action-artistic btn-enable auth-perm-users-enable" title="تفعيل" onclick="return confirmAction(this, 'هل أنت متأكد من عملية التفعيل؟')">
                                                            <x-icon name="play" size="10" />
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif

                                            @can('users.delete')
                                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-action-artistic btn-delete auth-perm-users-delete" title="حذف" onclick="return confirmAction(this, 'هل أنت متأكد من حذف المستخدم؟')">
                                                        <x-icon name="trash-2" size="10" />
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <x-icon name="users" size="24" class="text-muted" />
                                        </div>
                                        <h6 class="text-dark fw-bold mb-1">لا يوجد مستخدمين</h6>
                                        <p class="text-muted small mb-3">لم يتم العثور على أي مستخدمين يطابقون معايير البحث.</p>
                                        @can('users.create')
                                            <a href="{{ route('users.create') }}" class="header-btn btn-primary-gradient d-inline-flex">
                                                <x-icon name="plus" size="12" /> إضافة مستخدم جديد
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

        <x-slot name="pagination">
            {{ $users->withQueryString()->links() }}
        </x-slot>
        <x-slot name="total">
            {{ $users->total() }}
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
@endsection
