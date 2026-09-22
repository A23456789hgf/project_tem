@extends('layouts.app')

@section('title', 'التعديل الجماعي والمفرد للجهات')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- رأس الصفحة -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-edit me-2"></i> التعديل الجماعي والمفرد لسجلات الجهات ({{ $authorities->count() }} سجل)</h5>
                    <a href="{{ route('authorities.index') }}" class="btn btn-light btn-sm shadow-sm">
                        <i class="fas fa-arrow-right me-1"></i> العودة لقائمة الجهات
                    </a>
                </div>
            </div>

            <!-- بطاقة التعديل الجماعي السريع -->
            <div class="card shadow-sm mb-4 border-0 bg-light">
                <div class="card-header bg-dark text-white py-2">
                    <h6 class="mb-0 small fw-bold"><i class="fas fa-magic me-2"></i> تعميم خيار واحد على جميع السجلات (مثال: اجعل كل السجلات تابعة لمحافظة معينة أو حالة معينة أو نطاق معين)</h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">اجعل كل السجلات للحالة:</label>
                            <select id="bulk_status_apply" class="form-select form-select-sm border-warning" onchange="applyBulkValuesToRows()">
                                <option value="">-- اختر لتعميم الحالة --</option>
                                <option value="1">نشط</option>
                                <option value="0">غير نشط</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">اجعل كل السجلات للتمويل:</label>
                            <select id="bulk_funded_apply" class="form-select form-select-sm border-warning" onchange="applyBulkValuesToRows()">
                                <option value="">-- اختر لتعميم التمويل --</option>
                                <option value="1">ممولة</option>
                                <option value="0">غير ممولة</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">اجعل كل السجلات للجهة الأب:</label>
                            <select id="bulk_parent_apply" class="form-select form-select-sm border-warning" onchange="applyBulkValuesToRows()">
                                <option value="">-- اختر لتعميم الأب --</option>
                                <option value="null">بدون جهة أب (رئيسية)</option>
                                @foreach($allAuthorities as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->agency_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">اجعل كل السجلات للمحافظة:</label>
                            <select id="bulk_gov_apply" class="form-select form-select-sm border-warning" onchange="applyBulkValuesToRows()">
                                <option value="">-- اختر لتعميم المحافظة --</option>
                                <option value="null">بدون محافظة</option>
                                @foreach($governorates as $gov)
                                    <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">اجعل كل السجلات للمديرية:</label>
                            <select id="bulk_dir_apply" class="form-select form-select-sm border-warning" onchange="applyBulkValuesToRows()">
                                <option value="">-- اختر لتعميم المديرية --</option>
                                <option value="null">بدون مديرية</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">اجعل كل السجلات لنوع الجهة:</label>
                            <select id="bulk_type_entity_apply" class="form-select form-select-sm border-warning" onchange="applyBulkValuesToRows()">
                                <option value="">-- اختر لتعميم النوع --</option>
                                <option value="null">بدون تحديد</option>
                                @if(isset($typeEntities))
                                    @foreach($typeEntities as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-12 mt-3 text-end">
                            <button type="button" class="btn btn-warning btn-sm fw-bold px-4 shadow-sm" onclick="applyBulkValuesToRows(true)">
                                <i class="fas fa-check-double me-1"></i> تطبيق أو إعادة تعميم الخيارات المختارة على جميع السجلات المعروضة
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- بطاقة الفرز والبحث -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body py-3 bg-white border rounded">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="tableSearchInput" class="form-control" placeholder="بحث سريع باسم الجهة في الجدول..." onkeyup="filterTableRows()">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select id="filterStatusSelect" class="form-select form-select-sm" onchange="filterTableRows()">
                                <option value="">كل الحالات</option>
                                <option value="1">نشط فقط</option>
                                <option value="0">غير نشط فقط</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterGovSelect" class="form-select form-select-sm" onchange="filterTableRows()">
                                <option value="">كل المحافظات</option>
                                @foreach($governorates as $gov)
                                    <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="sortTableRows()">
                                <i class="fas fa-sort me-1"></i> ترتيب حسب الاسم
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- نموذج الحفظ وجدول البيانات -->
            @can('authorities.bulk.save')
            <form action="{{ route('authorities.bulk.save') }}" method="POST" id="bulkSaveForm">
            @endcan
                @csrf
                <div class="card shadow mb-4 border-0">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light">
                        <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-table me-2"></i> جدول التعديل المفرد والجماعي</h6>
                        <button type="submit" class="btn btn-success fw-bold shadow-sm px-4">
                            <i class="fas fa-save me-2"></i> حفظ جميع التعديلات الآن
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0" id="authoritiesEditTable">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="19%">اسم الجهة <span class="text-danger">*</span></th>
                                        <th width="9%">الحالة</th>
                                        <th width="9%">جهة ممولة</th>
                                        <th width="17%">الجهة الأب</th>
                                        <th width="14%">المحافظة</th>
                                        <th width="14%">المديرية</th>
                                        <th width="15%">نوع الجهة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($authorities as $index => $auth)
                                        <tr class="auth-edit-row" data-name="{{ $auth->agency_name }}" data-status="{{ $auth->is_active ? '1' : '0' }}" data-gov="{{ $auth->governorate_id }}">
                                            <td class="text-center fw-bold bg-light">{{ $index + 1 }}</td>
                                            <td>
                                                <input type="hidden" name="authorities[{{ $auth->id }}][id]" value="{{ $auth->id }}">
                                                <input type="text" name="authorities[{{ $auth->id }}][agency_name]" class="form-control form-control-sm row-agency-name" value="{{ $auth->agency_name }}" required>
                                            </td>
                                            <td>
                                                <select name="authorities[{{ $auth->id }}][is_active]" class="form-select form-select-sm row-is-active" onchange="updateRowDataAttr(this)">
                                                    <option value="1" {{ $auth->is_active ? 'selected' : '' }}>نشط</option>
                                                    <option value="0" {{ !$auth->is_active ? 'selected' : '' }}>غير نشط</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="authorities[{{ $auth->id }}][is_funded]" class="form-select form-select-sm row-is-funded">
                                                    <option value="1" {{ $auth->is_funded ? 'selected' : '' }}>ممولة</option>
                                                    <option value="0" {{ !$auth->is_funded ? 'selected' : '' }}>غير ممولة</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="authorities[{{ $auth->id }}][parent_id]" class="form-select form-select-sm row-parent-id">
                                                    <option value="null">بدون جهة أب (رئيسية)</option>
                                                    @foreach($allAuthorities as $parent)
                                                        @if($parent->id != $auth->id && !str_starts_with($parent->agency_name, '='))
                                                            <option value="{{ $parent->id }}" {{ $auth->parent_id == $parent->id ? 'selected' : '' }}>
                                                                {{ $parent->agency_name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="authorities[{{ $auth->id }}][governorate_id]" class="form-select form-select-sm row-gov-id" data-row-id="{{ $auth->id }}" onchange="handleRowGovChange(this, {{ $auth->id }})">
                                                    <option value="null">بدون محافظة</option>
                                                    @foreach($governorates as $gov)
                                                        <option value="{{ $gov->id }}" {{ $auth->governorate_id == $gov->id ? 'selected' : '' }}>
                                                            {{ $gov->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="authorities[{{ $auth->id }}][directorate_id]" class="form-select form-select-sm row-dir-id" id="dir_select_{{ $auth->id }}">
                                                    <option value="null">بدون مديرية</option>
                                                    @foreach($directorates as $dir)
                                                        @if($auth->governorate_id && $dir->governorate_id == $auth->governorate_id)
                                                            <option value="{{ $dir->id }}" {{ $auth->directorate_id == $dir->id ? 'selected' : '' }}>
                                                                {{ $dir->name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="authorities[{{ $auth->id }}][type_entity_id]" class="form-select form-select-sm row-type-entity-id">
                                                    <option value="null">بدون تحديد</option>
                                                    @if(isset($typeEntities))
                                                        @foreach($typeEntities as $type)
                                                            <option value="{{ $type->id }}" {{ $auth->type_entity_id == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer py-3 bg-light text-end">
                        <a href="{{ route('authorities.index') }}" class="btn btn-secondary me-2">إلغاء</a>
                        <button type="submit" class="btn btn-success fw-bold shadow-sm px-5">
                            <i class="fas fa-save me-2"></i> حفظ جميع التعديلات
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const allDirectorates = @json($directorates);
let sortAsc = true;

// تغيير قائمة المديريات في شريط التطبيق الجماعي عند اختيار محافظة
document.getElementById('bulk_gov_apply').addEventListener('change', function() {
    const govId = this.value;
    const dirSelect = document.getElementById('bulk_dir_apply');
    dirSelect.innerHTML = '<option value="">-- بدون تغيير --</option><option value="null">بدون مديرية</option>';
    
    if (govId && govId !== 'null') {
        const dirs = allDirectorates.filter(d => d.governorate_id == govId);
        dirs.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.name;
            dirSelect.appendChild(opt);
        });
    }
});

// تطبيق القيم الجماعية على الصفوف المعروضة
function applyBulkValuesToRows(isBtnClick = false) {
    const statusVal = document.getElementById('bulk_status_apply').value;
    const fundedVal = document.getElementById('bulk_funded_apply') ? document.getElementById('bulk_funded_apply').value : '';
    const parentVal = document.getElementById('bulk_parent_apply').value;
    const govVal = document.getElementById('bulk_gov_apply').value;
    const dirVal = document.getElementById('bulk_dir_apply').value;
    const typeEntityVal = document.getElementById('bulk_type_entity_apply').value;

    const rows = document.querySelectorAll('#authoritiesEditTable tbody tr.auth-edit-row');
    let count = 0;

    rows.forEach(row => {
        if (row.style.display !== 'none') {
            count++;
            if (statusVal !== '') {
                const sel = row.querySelector('.row-is-active');
                if (sel) { sel.value = statusVal; updateRowDataAttr(sel); }
            }
            if (fundedVal !== '') {
                const sel = row.querySelector('.row-is-funded');
                if (sel) { sel.value = fundedVal; }
            }
            if (parentVal !== '') {
                const sel = row.querySelector('.row-parent-id');
                if (sel) {
                    const optionExists = Array.from(sel.options).some(o => o.value == parentVal);
                    if (optionExists) sel.value = parentVal;
                }
            }
            if (govVal !== '') {
                const sel = row.querySelector('.row-gov-id');
                if (sel) {
                    sel.value = govVal;
                    const rowId = sel.getAttribute('data-row-id');
                    handleRowGovChange(sel, rowId, dirVal !== '' ? dirVal : null);
                }
            } else if (dirVal !== '') {
                const selDir = row.querySelector('.row-dir-id');
                if (selDir) selDir.value = dirVal;
            }
            if (typeEntityVal !== '') {
                const selType = row.querySelector('.row-type-entity-id');
                if (selType) selType.value = typeEntityVal;
            }
        }
    });

    if (typeof toastr !== 'undefined') {
        toastr.success(`تم تعميم الخيارات على ${count} سجل معروض في الجدول.`);
    } else if (isBtnClick) {
        alert(`تم تعميم الخيارات على ${count} سجل معروض في الجدول.`);
    }
}

// تغيير المحافظة في سطر معين
function handleRowGovChange(govSelect, rowId, selectedDirId = null) {
    const govId = govSelect.value;
    const dirSelect = document.getElementById('dir_select_' + rowId);
    if (!dirSelect) return;

    dirSelect.innerHTML = '<option value="null">بدون مديرية</option>';
    
    // تحديث data attribute للفرز
    const row = govSelect.closest('tr');
    if (row) row.setAttribute('data-gov', govId);

    if (govId && govId !== 'null') {
        const dirs = allDirectorates.filter(d => d.governorate_id == govId);
        dirs.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.name;
            if (selectedDirId && selectedDirId == d.id) {
                opt.selected = true;
            }
            dirSelect.appendChild(opt);
        });
    }
}

function updateRowDataAttr(statusSelect) {
    const row = statusSelect.closest('tr');
    if (row) row.setAttribute('data-status', statusSelect.value);
}

// فرز والبحث في الجدول
function filterTableRows() {
    const search = document.getElementById('tableSearchInput').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatusSelect').value;
    const govFilter = document.getElementById('filterGovSelect').value;

    const rows = document.querySelectorAll('#authoritiesEditTable tbody tr.auth-edit-row');

    rows.forEach(row => {
        const nameInput = row.querySelector('.row-agency-name');
        const name = nameInput ? nameInput.value.toLowerCase() : '';
        const status = row.getAttribute('data-status');
        const gov = row.getAttribute('data-gov');

        let match = true;
        if (search && !name.includes(search)) match = false;
        if (statusFilter !== '' && status !== statusFilter) match = false;
        if (govFilter !== '' && gov !== govFilter) match = false;

        row.style.display = match ? '' : 'none';
    });
}

// ترتيب الصفوف حسب الاسم
function sortTableRows() {
    const tbody = document.querySelector('#authoritiesEditTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr.auth-edit-row'));

    rows.sort((a, b) => {
        const nameA = (a.querySelector('.row-agency-name').value || '').trim();
        const nameB = (b.querySelector('.row-agency-name').value || '').trim();
        return sortAsc ? nameA.localeCompare(nameB, 'ar') : nameB.localeCompare(nameA, 'ar');
    });

    sortAsc = !sortAsc;
    rows.forEach(row => tbody.appendChild(row));
}
</script>
@endsection
