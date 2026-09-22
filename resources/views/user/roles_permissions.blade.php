@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <nav aria-label="breadcrumb" class="d-inline-block">
                <ol class="breadcrumb breadcrumb-dark mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-home me-1"></i> الرئيسية</a></li>
                    <li class="breadcrumb-item active" aria-current="page">الأدوار والصلاحيات</li>
                </ol>
            </nav>
            <h1 class="h2 fw-bold text-dark mt-2 mb-1">إدارة الأدوار والصلاحيات</h1>
            <p class="text-muted small">إدارة هيكل الأدوار وتعيين الصلاحيات لكافة وحدات النظام</p>
        </div>
        <div>
             @can('roles.create')
             <a href="{{ route('roles.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus-circle me-2"></i> إضافة دور جديد
            </a>
             @endcan
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 dashboard-card shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-white bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-shield-alt fa-2x text-white"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="card-title text-white-50 mb-0 small">إجمالي الصلاحيات</h5>
                            <h2 class="fw-bold text-white mb-0">{{ $permissionGroups->flatten()->count() }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 dashboard-card shadow-sm h-100" style="background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-white bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-user-tag fa-2x text-white"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="card-title text-white-50 mb-0 small">عدد الأدوار</h5>
                            <h2 class="fw-bold text-white mb-0">{{ count($roles) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 dashboard-card shadow-sm h-100" style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%) !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-white bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-layer-group fa-2x text-white"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="card-title text-white-50 mb-0 small">الوحدات</h5>
                            <h2 class="fw-bold text-white mb-0">{{ count($permissionGroups) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 dashboard-card shadow-sm h-100" style="background: linear-gradient(135deg, #718096 0%, #4a5568 100%) !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-white bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-users fa-2x text-white"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="card-title text-white-50 mb-0 small">المستخدمين</h5>
                            <h2 class="fw-bold text-white mb-0">{{ $userCount }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    

    <!-- Roles Management Tab -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-list me-2"></i> قائمة الأدوار</h5>
        </div>
        <div class="card-body p-0">
             <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">اسم الدور</th>
                            <th>الوصف</th>
                            <th class="text-center">المستخدمين</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td class="ps-4 fw-bold">{{ $role->name }}</td>
                                <td class="text-muted small">{{ Str::limit($role->description, 50) }}</td>
                                <td class="text-center"><span class="badge bg-secondary rounded-pill">{{ $role->users_count }}</span></td>
                                <td class="text-center">
                                    <span class="badge {{ $role->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $role->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        @can('roles.edit')
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-outline-primary" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                        @can('roles.edit')
                                        <form action="{{ route('roles.toggle', $role) }}" method="POST" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-outline-{{ $role->is_active ? 'warning' : 'success' }}" 
                                                title="{{ $role->is_active ? 'تعطيل' : 'تفعيل' }}">
                                                <i class="fas {{ $role->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            </button>
                                        </form>
                                        @endcan
                                        @if($role->name !== 'Admin' && $role->users_count == 0)
                                        @can('roles.delete')
                                        <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirmAction(this, 'هل أنت متأكد من حذف الدور؟')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                        @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Permissions Matrix -->
    <div class="card shadow-sm border-0">
         <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-th me-2"></i> مصفوفة الصلاحيات</h5>
            <div class="d-flex gap-2 flex-wrap">
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="permissionSearch" class="form-control border-start-0" placeholder="بحث في الصلاحيات...">
                </div>
                <button type="button" class="btn btn-outline-success px-3" onclick="activateAll()" title="تفعيل جميع الصلاحيات">
                    <i class="fas fa-check-double me-1"></i> تفعيل الكل
                </button>
                <button type="button" class="btn btn-outline-danger px-3" onclick="deactivateAll()" title="تعطيل جميع الصلاحيات">
                    <i class="fas fa-times-circle me-1"></i> تعطيل الكل
                </button>
                <button type="submit" form="permissions-form" class="btn btn-primary btn-sm px-3 fw-bold">
                    <i class="fas fa-save me-1"></i> حفظ التغييرات
                </button>
            </div>
        </div>
        
        @can('roles.permissions.edit')
        <form id="permissions-form" action="{{ route('roles.permissions.update') }}" method="POST">
        @endcan
            @csrf
            
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                    <table class="table table-hover table-bordered mb-0 align-middle">
                        <thead class="bg-light sticky-top" style="z-index: 5;">
                            <tr>
                                <th class="ps-3 bg-light" style="width: 300px; min-width: 300px;">الصلاحية</th>
                                @foreach($roles as $role)
                                    <th class="text-center bg-light" style="min-width: 100px;">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="fw-bold mb-1">{{ $role->name }}</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input role-toggle" type="checkbox" data-role="{{ $role->name }}" title="تحديد الكل لـ {{ $role->name }}">
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="permissionsTableBody">
                            @foreach($permissionGroups as $module => $permissions)
                                <tr class="table-primary module-header">
                                    <td colspan="{{ count($roles) + 1 }}" class="fw-bold py-2 px-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-layer-group me-2 opacity-50"></i>
                                                {{ $module }}
                                                <span class="badge bg-primary rounded-pill ms-2">{{ $permissions->count() }}</span>
                                            </div>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input module-check-all" type="checkbox" title="تحديد كل الصلاحيات في هذه الوحدة لكل الأدوار">
                                                <label class="form-check-label small">تحديد الوحدة (لكل الأدوار)</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @foreach($permissions as $perm)
                                    <tr class="permission-row" data-search="{{ $perm->name }} {{ $module }}">
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">{{ $perm->name }}</div>
                                            <small class="text-muted text-xs">{{ $perm->slug }}</small>
                                        </td>
                                        @foreach($roles as $role)
                                            <td class="text-center bg-white">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input perm-check" type="checkbox" 
                                                        name="permissions[{{ $role->name }}][]" 
                                                        value="{{ $perm->id }}"
                                                        data-role="{{ $role->name }}"
                                                        data-slug="{{ $perm->slug }}"
                                                        {{ isset($matrix[$role->name][$perm->id]) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top py-3 text-center">
                 <p class="mb-0 text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    يمكنك استخدام الاختصار <kbd>Ctrl + S</kbd> لحفظ التغييرات بسرعة.
                 </p>
            </div>
        </form>
    </div>
</div>

<style>
    :root {
        --primary-navy: #0B1446;
        --secondary-navy: #10225F;
        --golden-text: #D4AF37;
        --golden-light: #E3C36A;
    }

    .dashboard-card {
        transition: transform 0.2s;
        background: linear-gradient(135deg, #0B1446 0%, #10225F 100%);
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
    }

    .dashboard-card .card-title,
    .dashboard-card h2,
    .dashboard-card h5,
    .dashboard-card i,
    .dashboard-card .fw-bold {
        color: var(--golden-text) !important;
    }

    .dashboard-card .text-muted {
        color: var(--golden-light) !important;
        opacity: 0.9;
    }

    .form-check-input:checked {
        background-color: var(--primary-navy);
        border-color: var(--primary-navy);
    }
    
    .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(11, 20, 70, 0.25);
    }

    .permission-row {
        transition: all 0.3s ease;
    }
    
    .permission-row:hover {
        background-color: rgba(11, 20, 70, 0.08);
        transform: translateX(3px);
    }

    .module-header {
        background-color: #f8f9fa !important;
    }

    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #6c757d;
    }
    
    .badge.bg-light {
        padding: 0.35rem 0.65rem;
        font-weight: 500;
        color: var(--primary-navy);
    }
    
    .module-btn.active {
        background-color: var(--primary-navy) !important;
        color: white !important;
        border-color: var(--primary-navy) !important;
    }

    .btn-primary {
        background-color: var(--primary-navy);
        border-color: var(--primary-navy);
    }

    .btn-primary:hover {
        background-color: var(--secondary-navy);
        border-color: var(--secondary-navy);
    }

    .btn-outline-primary {
        color: var(--primary-navy);
        border-color: var(--primary-navy);
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-navy);
        border-color: var(--primary-navy);
    }

    .table-responsive::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 4px;
    }

    .role-toggle:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('permissionSearch');
        const permCheckboxes = document.querySelectorAll('.perm-check');
        const roleToggles = document.querySelectorAll('.role-toggle');
        const rows = document.querySelectorAll('.permission-row');
        const moduleHeaders = document.querySelectorAll('.module-header');

        // Role Toggle (Select All Column)
        roleToggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const roleName = this.dataset.role;
                const isChecked = this.checked;
                const checkboxes = document.querySelectorAll(`.perm-check[data-role="${roleName}"]`);
                checkboxes.forEach(cb => {
                    cb.checked = isChecked;
                });
            });
        });

        // Parent-Child Selection Logic (Within each role)
        document.querySelectorAll('.perm-check').forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    const roleName = this.dataset.role;
                    const slug = this.dataset.slug || '';
                    const tbody = this.closest('tbody');
                    
                    if (slug.includes('.view') || slug.includes('.sidebar')) {
                        // Check all children in this module for this role
                        const prefix = slug.split('.')[0];
                        const children = tbody.querySelectorAll(`.perm-check[data-role="${roleName}"][data-slug^="${prefix}."]`);
                        children.forEach(c => c.checked = true);
                    } else {
                        // Check parents (view/sidebar) for this role
                        const prefix = slug.split('.')[0];
                        const parents = tbody.querySelectorAll(`.perm-check[data-role="${roleName}"][data-slug$=".view"], .perm-check[data-role="${roleName}"][data-slug$=".sidebar"]`);
                        parents.forEach(p => {
                            if (p.dataset.slug.startsWith(prefix)) {
                                p.checked = true;
                            }
                        });
                    }
                }
            });
        });

        // Module Toggle (Select All Row/Module for all roles)
        document.querySelectorAll('.module-check-all').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const headerRow = this.closest('tr');
                let nextRow = headerRow.nextElementSibling;
                while (nextRow && nextRow.classList.contains('permission-row')) {
                    const checkboxes = nextRow.querySelectorAll('.perm-check');
                    checkboxes.forEach(cb => {
                        cb.checked = this.checked;
                    });
                    nextRow = nextRow.nextElementSibling;
                }
            });
        });

        // Search Functionality
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            
            rows.forEach(row => {
                const text = row.dataset.search.toLowerCase();
                if (text.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Hide module headers if no children are visible
            let currentHeader = null;
            let visibleCount = 0;
            const tbodyChildren = document.getElementById('permissionsTableBody').children;
            
            for (let tr of tbodyChildren) {
                if (tr.classList.contains('module-header')) {
                    if (currentHeader) {
                        currentHeader.style.display = visibleCount > 0 ? '' : 'none';
                    }
                    currentHeader = tr;
                    visibleCount = 0;
                } else if (tr.classList.contains('permission-row')) {
                    if (tr.style.display !== 'none') {
                        visibleCount++;
                    }
                }
            }
            // Check last header
            if (currentHeader) {
                currentHeader.style.display = visibleCount > 0 ? '' : 'none';
            }
        });

        // Activate/Deactivate All functions
        window.activateAll = function() {
            permCheckboxes.forEach(cb => cb.checked = true);
        };

        window.deactivateAll = function() {
            permCheckboxes.forEach(cb => cb.checked = false);
        };

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                document.getElementById('permissions-form').submit();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
            }
            
            if (e.key === 'Escape') {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            }
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

@endsection
