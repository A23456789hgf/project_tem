@extends('layouts.app')

@section('content')
    <x-index-page title="صلاحيات الأدوار" icon="shield">
        <x-slot name="filters">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-bold">اختر الدور الوظيفي:</label>
                    <select class="form-select border-primary shadow-none" onchange="location = this.value;">
                        <option value="" disabled selected>-- اختر الدور --</option>
                        @foreach($roles as $role)
                            <option value="{{ route('roles-permissions.index', ['role_id' => $role->id]) }}" 
                                {{ isset($selectedRole) && $selectedRole->id == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-8 text-md-start mt-3 mt-md-0">
                    @if(isset($selectedRole))
                        <div class="d-flex flex-wrap gap-2 justify-content-md-start">
                            @can('roles-permissions.edit')
                            <button type="button" class="btn btn-dark px-4" id="globalSelectAll">
                                <i class="fas fa-check-double"></i> تحديد الكل (كل الوحدات)
                            </button>
                            <button type="button" class="btn btn-outline-danger px-4" id="globalDeselectAll">
                                <i class="fas fa-times"></i> إلغاء تحديد الكل
                            </button>
                            <button type="submit" form="permissionsForm" class="btn btn-primary px-5 shadow">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </x-slot>

        <x-slot name="table">
            @if(isset($selectedRole))
            @can('roles-permissions.edit')
            <form action="{{ route('roles-permissions.update') }}" method="POST" id="permissionsForm">
            @endcan
                @csrf
                <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">

                <table class="table table-bordered align-middle mb-0">
                    <thead class="bg-primary text-white text-center">
                        <tr>
                            <th style="width: 20%;">اسم الوحدة</th>
                            <th style="width: 80%;">الصلاحيات المتاحة</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($allModuleNames as $moduleName)
                    <tr>
                        <td class="bg-light text-center fw-bold text-primary">
                            {{ $moduleTranslations[strtolower($moduleName)] ?? $moduleName }}
                            <div class="mt-2 text-muted small fw-normal">
                                @can('roles-permissions.edit')
                                <label class="cursor-pointer">
                                    <input type="checkbox" class="module-check-all"> تحديد الوحدة
                                </label>
                                @endcan
                            </div>
                        </td>
                        <td class="p-3">
                            <div class="row g-2">
                                @php
                                    $modulePerms = collect($categorized['sidebars'][$moduleName] ?? [])
                                                   ->concat($categorized['pages'][$moduleName] ?? [])
                                                   ->concat($categorized['actions'][$moduleName] ?? []);
                                @endphp

                                @foreach($modulePerms as $permission)
                                <div class="col-md-6">
                                    <div class="permission-item p-2 border rounded">
                                        <div class="form-check m-0">
                                            <input class="form-check-input perm-checkbox" type="checkbox" 
                                                   name="permissions[]" 
                                                   value="{{ $permission->slug }}" 
                                                   id="p_{{ $permission->id }}"
                                                   {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                   @cannot('roles-permissions.edit') disabled @endcannot>
                                            <label class="form-check-label small" for="p_{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                </table>
            </form>
            @else
                <div class="text-center py-5 text-muted">
                    <x-icon name="shield" size="48" class="mb-3 text-secondary" style="opacity: 0.5;" />
                    <h5>الرجاء اختيار دور وظيفي لعرض وتعديل صلاحياته</h5>
                </div>
            @endif
        </x-slot>
    </x-index-page>

<style>
    .permission-item { transition: all 0.2s; background: #fff; }
    .permission-item:hover { background: #f0f7ff; border-color: #0d6efd !important; }
    .cursor-pointer { cursor: pointer; }
    .table-bordered td, .table-bordered th { border: 1px solid #dee2e6 !important; }
    .bg-primary { background-color: #0d6efd !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. زر تحديد الكل الشامل (لكل الصفحات والوحدات)
    const btnSelectAll = document.getElementById('globalSelectAll');
    const btnDeselectAll = document.getElementById('globalDeselectAll');
    const allCheckboxes = document.querySelectorAll('.perm-checkbox');

    if(btnSelectAll) {
        btnSelectAll.addEventListener('click', function() {
            allCheckboxes.forEach(cb => cb.checked = true);
            // تحديث مربعات تحديد الوحدة أيضاً
            document.querySelectorAll('.module-check-all').forEach(mcb => mcb.checked = true);
        });
    }

    if(btnDeselectAll) {
        btnDeselectAll.addEventListener('click', function() {
            allCheckboxes.forEach(cb => cb.checked = false);
            document.querySelectorAll('.module-check-all').forEach(mcb => mcb.checked = false);
        });
    }

    // 2. زر تحديد موديول معين فقط
    document.querySelectorAll('.module-check-all').forEach(moduleToggle => {
        moduleToggle.addEventListener('change', function() {
            const row = this.closest('tr');
            const rowCheckboxes = row.querySelectorAll('.perm-checkbox');
            rowCheckboxes.forEach(cb => cb.checked = this.checked);
        });
    });
    // 3. Parent-Child Selection Logic
    allCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                const slug = this.value;
                const row = this.closest('tr');
                
                if (slug.includes('.view') || slug.includes('.sidebar')) {
                    const prefix = slug.split('.')[0];
                    const children = row.querySelectorAll(`.perm-checkbox[value^="${prefix}."]`);
                    children.forEach(c => c.checked = true);
                } else {
                    const prefix = slug.split('.')[0];
                    const parents = row.querySelectorAll(`.perm-checkbox[value$=".view"], .perm-checkbox[value$=".sidebar"]`);
                    parents.forEach(p => {
                        if (p.value.startsWith(prefix)) {
                            p.checked = true;
                        }
                    });
                }
            }
        });
    });
});
</script>
@endsection