@extends('layouts.app')

@section('title', 'مراجعة بيانات الاستيراد')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">مراجعة بيانات الاستيراد - الجهات</h6>
                    <div class="btn-group">
                        <a href="{{ route('authorities.showImport') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> العودة للاستيراد
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- رسالة نجاح -->
                    

                    <!-- معلومات الملف -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-excel fa-2x text-success mb-2"></i>
                                    <h6>اسم الملف</h6>
                                    <p class="mb-0">{{ $fileName ?? 'غير محدد' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-list-ol fa-2x text-primary mb-2"></i>
                                    <h6>عدد السجلات</h6>
                                    <p class="mb-0">{{ $data->count() ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                                    <h6>الأخطاء المكتشفة</h6>
                                    <p class="mb-0">{{ $errors->count() ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- نموذج الاستيراد -->
                    @can('authorities.import')
                    <form action="{{ route('authorities.process-import') }}" method="POST" id="importForm" class="import-form">
                    @endcan
                        @csrf
                        <input type="hidden" name="file_path" value="{{ $filePath ?? '' }}">
                        <input type="hidden" name="confirmation" value="1">

                        <!-- خيارات الاستيراد -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">خيارات الاستيراد</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="import_mode" id="add_only" value="add">
                                            <label class="form-check-label" for="add_only">
                                                <strong>إضافة الجديد فقط:</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mb-3">إضافة الجهات الجديدة وتجاهل الموجودة مسبقاً</small>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="import_mode" id="update_existing" value="update">
                                            <label class="form-check-label" for="update_existing">
                                                <strong>تحديث الموجود فقط:</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mb-3">تحديث بيانات الجهات الموجودة وتجاهل الجديدة</small>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="import_mode" id="add_and_update" value="add_update" checked>
                                            <label class="form-check-label" for="add_and_update">
                                                <strong>إضافة وتحديث واستبدال معاً:</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mb-3">استبدال وتحديث بيانات السجلات الموجودة بالأحدث وإضافة السجلات الجديدة</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- شريط التحكم في عرض السجلات -->
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
                            <div class="d-flex align-items-center">
                                <label for="rowsPerPageSelect" class="me-2 mb-0 fw-bold small text-primary"><i class="fas fa-eye me-1"></i> عرض البيانات قبل الاستيراد:</label>
                                <select id="rowsPerPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="updatePreviewRowsDisplay()">
                                    <option value="20">20 سجل</option>
                                    <option value="50" selected>50 سجل</option>
                                    <option value="all">الكل ({{ count($data ?? []) }})</option>
                                </select>
                            </div>
                            <div id="previewRowsCountInfo" class="text-muted small">
                                يتم عرض أول <span id="displayedRowsCount" class="fw-bold">50</span> من إجمالي <span class="fw-bold">{{ count($data ?? []) }}</span> سجل للمعاينة
                            </div>
                        </div>

                        <!-- جدول البيانات -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="previewDataTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th>اسم الجهة</th>
                                        <th>الجهة الأب</th>
                                        <th>المحافظة</th>
                                        <th>المديرية</th>
                                        <th>نوع الجهة</th>
                                        <th>الحالة</th>
                                        <th>النتيجة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($data ?? []) as $index => $row)
                                        <tr class="preview-data-row {{ isset($errors[$index]) ? 'table-danger' : 'table-success' }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ $row['agency_name'] ?? $row['اسم_الجهة'] ?? $row['asm_algh'] ?? $row['asm_aljhh'] ?? '' }}
                                                @if(isset($errors[$index]['agency_name']))
                                                    <br><small class="text-danger">{{ $errors[$index]['agency_name'] }}</small>
                                                @elseif(in_array(trim($row['agency_name'] ?? $row['اسم_الجهة'] ?? $row['asm_algh'] ?? $row['asm_aljhh'] ?? ''), $existingAgencies ?? []))
                                                    <br><span class="badge bg-info text-dark mt-1" style="font-size: 0.75rem;"><i class="fas fa-sync-alt me-1"></i> موجودة مسبقاً (جاهزة للتحديث)</span>
                                                @endif
                                            </td>
                                            <td>{{ $row['parent_id'] ?? $row['parent_name'] ?? $row['algh_alab'] ?? $row['الجهة_الأب'] ?? '-' }}</td>
                                            <td>{{ $row['governorate_id'] ?? $row['governorate_name'] ?? $row['almhafth'] ?? $row['المحافظة'] ?? '-' }}</td>
                                            <td>{{ $row['directorate_id'] ?? $row['directorate_name'] ?? $row['almdyryh'] ?? $row['المديرية'] ?? '-' }}</td>
                                            <td>{{ $row['type_entity_id'] ?? $row['type_entity_name'] ?? $row['noa_algh'] ?? $row['نوع_الجهة'] ?? '-' }}</td>
                                            <td>
                                                <span class="badge badge-{{ ($row['is_active'] ?? $row['alhal'] ?? 1) ? 'success' : 'danger' }}">
                                                    {{ ($row['is_active'] ?? $row['alhal'] ?? 1) ? 'نشط' : 'غير نشط' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(isset($errors[$index]))
                                                    <i class="fas fa-exclamation-triangle text-danger" title="يحتوي على أخطاء"></i>
                                                @else
                                                    <i class="fas fa-check-circle text-success" title="صالح للاستيراد"></i>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <p>لا توجد بيانات للعرض</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- أزرار التحكم -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                @if($errors->count() > 0)
                                    <div class="alert alert-warning mb-0 py-2 px-3 small d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle me-2 text-warning fs-5"></i>
                                        <span>
                                            <strong>تنبيه:</strong> يوجد {{ $errors->count() }} صفوف تحتوي على ملاحظات أو أخطاء. عند الضغط على استيراد، سيتم معالجة الصفوف الصالحة وإصدار تقرير مفصل بالصفوف التي تعذر استيرادها مع أسباب الخطأ.
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="btn-group">
                                <a href="{{ route('authorities.showImport') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                                <button type="submit" class="btn btn-primary shadow-sm">
                                    <i class="fas fa-upload"></i> استيراد البيانات
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function updatePreviewRowsDisplay() {
    const select = document.getElementById('rowsPerPageSelect');
    if (!select) return;
    const value = select.value;
    const rows = document.querySelectorAll('#previewDataTable tbody tr.preview-data-row');
    const total = rows.length;
    let limit = total;
    
    if (value !== 'all') {
        limit = parseInt(value, 10);
    }
    
    let displayed = 0;
    rows.forEach((row, index) => {
        if (index < limit) {
            row.style.display = '';
            displayed++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const countSpan = document.getElementById('displayedRowsCount');
    if (countSpan) {
        countSpan.textContent = displayed;
    }
}

$(document).ready(function() {
    updatePreviewRowsDisplay();
});
</script>
@endsection