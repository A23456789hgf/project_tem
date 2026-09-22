@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" dir="rtl">
    <div class="row">
        <div class="col-12">
            {{-- Role Basic Info --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary font-weight-bold">
                        <i class="fas fa-eye me-2"></i>تفاصيل الدور: {{ $role->name }}
                    </h5>
                    <div class="d-flex gap-2">
                        @can('roles.edit')
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-primary px-4">
                            <i class="fas fa-edit me-2"></i>تعديل
                        </a>
                        @endcan
                        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-arrow-right me-2"></i>رجوع
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                                    <span class="text-muted">اسم الدور:</span>
                                    <span class="font-weight-bold">{{ $role->name }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                                    <span class="text-muted">الحالة:</span>
                                    <span class="badge {{ $role->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $role->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                                    <span class="text-muted">عدد المستخدمين:</span>
                                    <span class="badge badge-info px-3">{{ $role->users_count }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                                    <span class="text-muted">صلاحيات كاملة:</span>
                                    @if($role->full_access)
                                        <span class="badge badge-danger">نعم (Admin Access)</span>
                                    @else
                                        <span class="badge badge-secondary">لا</span>
                                    @endif
                                </li>
                                <li class="list-group-item d-flex flex-column bg-transparent px-0 border-0">
                                    <span class="text-muted mb-2">الوصف:</span>
                                    <p class="mb-0 text-sm text-dark">{{ $role->description ?: 'لا يوجد وصف' }}</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Permissions Matrix (Read Only View) --}}
            <div class="permissions-view shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="bg-light p-3 border-bottom">
                    <h6 class="mb-0 font-weight-bold"><i class="fas fa-shield-alt me-2"></i>الصلاحيات الممنوحة</h6>
                </div>
                @include('roles.partials._permissions_matrix', ['submitButtonText' => null, 'viewOnly' => true])
            </div>
        </div>
    </div>
</div>

<style>
    /* Premium View Mode for Permissions Matrix */
    .permission-matrix-wrapper {
        pointer-events: none;
    }
    
    /* Re-enable pointer events for search and scroll, but not for inputs */
    .permission-matrix-wrapper #permissionSearch {
        pointer-events: auto;
    }
    
    .permission-matrix-wrapper .matrix-header {
        pointer-events: auto;
    }

    /* Hide standard interactive elements that are now redundant */
    .module-check-all, 
    #fullAccessToggle, 
    #globalScopeSelector, 
    #globalGeoScopeSelector,
    #applyToAllBtn,
    #applyGeoToAllBtn,
    button[type="submit"] {
        display: none !important;
    }

    /* Adjust appearance of 'checked' items to look like badges/labels */
    .modern-perm-card {
        cursor: default !important;
        border-style: solid !important;
        background: #f8fafc !important;
    }
    
    .modern-perm-card input:checked + .p-info .p-name {
        color: var(--primary-color) !important;
    }

    /* Ensure disabled selects look clean */
    .premium-scope-dropdown:disabled, 
    .custom-select-premium:disabled {
        background-color: #f8fafc !important;
        color: #334155 !important;
        opacity: 1 !important;
        border-color: #e2e8f0 !important;
        cursor: default;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
</style>
@endsection
