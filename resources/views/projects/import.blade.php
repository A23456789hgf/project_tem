@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="fas fa-file-import me-2 text-primary"></i>استيراد المشاريع
                    </h2>
                    <p class="text-muted small mb-0">رفع ملف Excel لاستيراد المشاريع بشكل جماعي</p>
                </div>
                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للمشاريع
                </a>
            </div>

            <!-- Download Template Card -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="fw-bold text-dark mb-2">
                                <i class="fas fa-download me-2 text-success"></i>تحميل القالب
                            </h5>
                            <p class="text-muted mb-0">قم بتحميل ملف Excel القالب وملء البيانات قبل الاستيراد</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="{{ route('projects.download-template') }}" class="btn btn-success shadow-sm">
                                <i class="fas fa-file-excel me-2"></i>تحميل القالب
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Form Card -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class="fas fa-upload me-2"></i>رفع ملف الاستيراد
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form id="importForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">ملف Excel</label>
                                <input type="file" name="file" id="fileInput" class="form-control" accept=".xlsx,.xls,.csv">
                                <small class="text-muted">الصيغ المدعومة: XLSX, XLS, CSV (الحد الأقصى: 10 ميجابايت)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">نوع العملية</label>
                                <select name="operation" id="operationSelect" class="form-select">
                                    <option value="insert">إضافة فقط (Insert)</option>
                                    <option value="update">تحديث فقط (Update)</option>
                                    <option value="both">إضافة وتحديث (Both)</option>
                                </select>
                                <small class="text-muted">اختر ما إذا كنت تريد إضافة مشاريع جديدة أو تحديث الموجودة</small>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary shadow-sm px-4">
                                <i class="fas fa-eye me-2"></i>معاينة البيانات
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Section (Hidden by default) -->
            <div id="previewSection" class="card shadow-sm border-0 rounded-4 mb-4" style="display: none;">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class="fas fa-table me-2"></i>معاينة البيانات
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <label for="rowsPerPageSelect" class="small text-muted mb-0">عرض</label>
                        <select id="rowsPerPageSelect" class="form-select form-select-sm" style="width: auto;">
                            <option value="20">20 سجل</option>
                            <option value="50">50 سجل</option>
                            <option value="100">100 سجل</option>
                            <option value="500">500 سجل</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="previewTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="60">#</th>
                                    <th>اسم المشروع</th>
                                    <th>البرنامج</th>
                                    <th>المجال</th>
                                    <th>المجال الفرعي</th>
                                    <th>التدخل</th>
                                    <th>الأولوية</th>
                                    <th>إجمالي التكلفة</th>
                                    <th>المصروف</th>
                                    <th>المتبقي</th>
                                    <th>تاريخ البداية</th>
                                    <th>تاريخ النهاية</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody">
                                <!-- Preview data will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-info px-3 py-2">
                                <i class="fas fa-info-circle me-1"></i>
                                إجمالي الصفوف: <span id="totalRows">0</span>
                            </span>
                        </div>
                        <button type="button" id="confirmImportBtn" class="btn btn-success shadow-sm px-4">
                            <i class="fas fa-check me-2"></i>تأكيد الاستيراد
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Section (Hidden by default) -->
            <div id="resultsSection" class="card shadow-sm border-0 rounded-4" style="display: none;">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-success">
                        <i class="fas fa-check-circle me-2"></i>نتائج الاستيراد
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3" id="reportStats">
                        <!-- Statistics will be inserted here -->
                    </div>
                    <div id="failuresSection" class="mt-4" style="display: none;">
                        <h6 class="fw-bold text-danger mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>الأخطاء
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-danger">
                                    <tr>
                                        <th width="200">اسم المشروع (الصف)</th>
                                        <th>الخطأ</th>
                                    </tr>
                                </thead>
                                <tbody id="failuresTableBody">
                                    <!-- Failures will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                        
                        @can('projects.create')
                        <form id="downloadFailuresForm" method="POST" action="{{ route('projects.failure-report') }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="failures_json" id="failuresJsonInput">
                            <input type="hidden" name="failed_export_file" id="failedExportFileInput">
                        </form>
                        @endcan
                    </div>

                    <!-- Updated (Duplicates) Section -->
                    <div id="updatedSection" class="mt-4" style="display: none;">
                        <h6 class="fw-bold text-warning mb-3">
                            <i class="fas fa-sync-alt me-2"></i>المشاريع التي تم تحديثها (مكررة الاسم في الإكسل أو موجودة مسبقاً)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-warning">
                                    <tr>
                                        <th width="120">رقم الصف</th>
                                        <th width="250">اسم المشروع</th>
                                        <th>ملاحظة / السبب</th>
                                    </tr>
                                </thead>
                                <tbody id="updatedTableBody">
                                    <!-- Updated rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Skipped Section -->
                    <div id="skippedSection" class="mt-4" style="display: none;">
                        <h6 class="fw-bold text-secondary mb-3">
                            <i class="fas fa-ban me-2"></i>الصفوف المتخطاة / الفارغة
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-secondary">
                                    <tr>
                                        <th width="150">رقم الصف</th>
                                        <th>سبب التخطي</th>
                                    </tr>
                                </thead>
                                <tbody id="skippedTableBody">
                                    <!-- Skipped rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('projects.index') }}" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>عرض المشاريع
                        </a>
                        <button type="button" class="btn btn-danger" id="downloadFailuresBtn" style="display: none;" onclick="document.getElementById('downloadFailuresForm').submit()">
                            <i class="fas fa-download me-2"></i>تحميل تقرير المشاريع غير المستوردة
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-redo me-2"></i>استيراد جديد
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     Phase 2 — Missing Dropdown Values Treatment Panel
═══════════════════════════════════════════════════════════════ --}}
<div class="container-fluid pb-5" id="dropdownMissingSection" style="display:none;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header border-bottom py-3" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1 fw-bold text-warning-emphasis">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                قيم Dropdown غير موجودة — تحتاج إلى معالجة
                            </h5>
                            <p class="mb-0 text-muted small">عالج هذه القيم ثم أعد استيراد المشاريع المتخطاة. كل قرار يُطبَّق على جميع المشاريع التي تحتوي نفس القيمة.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a id="downloadDropdownReportBtn" href="#" class="btn btn-outline-secondary btn-sm" target="_blank">
                                <i class="fas fa-file-excel me-1"></i>تحميل تقرير القيم الناقصة
                            </a>
                            <button id="reImportBtn" class="btn btn-success btn-sm" onclick="reImportSkipped()">
                                <i class="fas fa-sync me-1"></i>إعادة استيراد المشاريع المتخطاة
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" id="missingValuesTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:40px;">#</th>
                                    <th>الحقل</th>
                                    <th>القيمة من Excel</th>
                                    <th class="text-center">المشاريع المتأثرة</th>
                                    <th>الحالة</th>
                                    <th class="text-center">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody id="missingValuesTableBody">
                                {{-- Populated by JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
                {{-- Re-import results (shown after re-import) --}}
                <div id="reImportResultsSection" style="display:none;" class="card-footer bg-white border-top p-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2 text-primary"></i>نتائج إعادة الاستيراد</h6>
                    <div class="row g-3" id="reImportStats"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     Mapping Modal
═══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="mappingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-wrench me-2 text-primary"></i>
                    معالجة القيمة: <span id="modalFieldLabel" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border rounded-3 mb-4">
                    <div class="row">
                        <div class="col-sm-4 text-muted small fw-semibold">الحقل:</div>
                        <div class="col-sm-8" id="modalFieldKeyDisplay"></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-sm-4 text-muted small fw-semibold">القيمة الأصلية:</div>
                        <div class="col-sm-8 fw-bold text-danger" id="modalOriginalValue"></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-sm-4 text-muted small fw-semibold">المشاريع المتأثرة:</div>
                        <div class="col-sm-8" id="modalAffectedCount"></div>
                    </div>
                </div>

                {{-- Step 1: Choose action --}}
                <div id="stepChooseAction">
                    <p class="fw-semibold mb-3">اختر طريقة المعالجة:</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card h-100 border-2 border-primary-subtle cursor-pointer action-card" onclick="selectAction('replace')" role="button">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-exchange-alt fa-2x text-primary mb-2"></i>
                                    <h6 class="fw-bold mb-1">استبدال</h6>
                                    <p class="text-muted small mb-0">ربط بقيمة موجودة مسبقاً في نفس الجدول</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-2 border-success-subtle cursor-pointer action-card" onclick="selectAction('add')" role="button">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-plus-circle fa-2x text-success mb-2"></i>
                                    <h6 class="fw-bold mb-1">إضافة</h6>
                                    <p class="text-muted small mb-0">إضافة القيمة الأصلية كما هي إلى النظام</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-2 border-warning-subtle cursor-pointer action-card" onclick="selectAction('edit_add')" role="button">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-edit fa-2x text-warning mb-2"></i>
                                    <h6 class="fw-bold mb-1">تعديل وإضافة</h6>
                                    <p class="text-muted small mb-0">تعديل القيمة ثم إضافتها إلى النظام</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2a: Replace --}}
                <div id="stepReplace" style="display:none;">
                    <div class="d-flex align-items-center mb-3">
                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="backToActions()"><i class="fas fa-arrow-right"></i></button>
                        <h6 class="mb-0 fw-bold">استبدال بقيمة موجودة</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">اختر القيمة البديلة:</label>
                        <select id="replaceTargetSelect" class="form-select form-select-lg">
                            <option value="">جاري التحميل...</option>
                        </select>
                        <div class="form-text text-muted">يتم عرض القيم من نفس الجدول فقط</div>
                    </div>
                </div>

                {{-- Step 2b: Add --}}
                <div id="stepAdd" style="display:none;">
                    <div class="d-flex align-items-center mb-3">
                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="backToActions()"><i class="fas fa-arrow-right"></i></button>
                        <h6 class="mb-0 fw-bold">إضافة القيمة الأصلية</h6>
                    </div>
                    <div class="alert alert-success rounded-3">
                        <i class="fas fa-info-circle me-2"></i>
                        سيتم إضافة القيمة التالية إلى النظام:
                        <strong id="addValuePreview" class="d-block mt-1 fs-5"></strong>
                    </div>
                    <p class="text-muted small">في حال وجود قيمة مطابقة مسبقاً، لن يتم إنشاء سجل جديد وسيتم استخدام السجل الموجود.</p>
                </div>

                {{-- Step 2c: Edit + Add --}}
                <div id="stepEditAdd" style="display:none;">
                    <div class="d-flex align-items-center mb-3">
                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="backToActions()"><i class="fas fa-arrow-right"></i></button>
                        <h6 class="mb-0 fw-bold">تعديل القيمة وإضافتها</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">القيمة الأصلية:</label>
                        <div class="form-control bg-light text-muted" id="editOriginalDisplay"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">القيمة المعدَّلة <span class="text-danger">*</span></label>
                        <input type="text" id="editedValueInput" class="form-control form-control-lg" placeholder="أدخل القيمة المعدلة..." dir="rtl">
                        <div class="form-text">ستُضاف هذه القيمة إلى النظام وتُستخدم في جميع المشاريع المتأثرة.</div>
                    </div>
                </div>

                {{-- Saving spinner --}}
                <div id="mappingSavingSpinner" style="display:none;" class="text-center py-3">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">جاري الحفظ...</p>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="saveMappingBtn" onclick="saveMapping()" style="display:none;">
                    <i class="fas fa-save me-1"></i>حفظ المعالجة
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Import Loading Modal -->
<div class="modal fade" id="importLoadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary mb-4" role="status" style="width: 3.5rem; height: 3.5rem; border-width: 0.35em;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="fw-bold text-dark mb-2">جاري استيراد المشاريع</h4>
                <p class="text-muted mb-4" id="importStatusText">الرجاء عدم إغلاق الصفحة أو تحديثها...</p>
                <div class="progress rounded-pill mb-2" style="height: 8px; max-width: 80%; margin: 0 auto;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="importProgressBar" role="progressbar" style="width: 0%"></div>
                </div>
                <small class="text-secondary d-block mt-2" id="importProgressPercent">0%</small>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let filePath = null;
let previewDataCache = [];
let rowsPerPage = 20;

const rowsPerPageSelect = document.getElementById('rowsPerPageSelect');
rowsPerPageSelect.addEventListener('change', function() {
    rowsPerPage = parseInt(this.value, 10) || 20;
    renderPreviewTable();
});

// Handle form submission for preview
document.getElementById('importForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري المعاينة...';
    
    try {
        const response = await fetch('{{ route("projects.preview-import") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            filePath = data.file_path;
            displayPreview(data.preview_data, data.total_rows);
        } else {
            alert('خطأ: ' + data.message);
        }
    } catch (error) {
        alert('حدث خطأ أثناء المعاينة: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// Display preview data
function displayPreview(previewData, totalRows) {
    previewDataCache = Array.isArray(previewData) ? previewData : [];
    renderPreviewTable();
    
    document.getElementById('totalRows').textContent = totalRows;
    document.getElementById('previewSection').style.display = 'block';
    document.getElementById('previewSection').scrollIntoView({ behavior: 'smooth' });
}

function renderPreviewTable() {
    const tbody = document.getElementById('previewTableBody');
    tbody.innerHTML = '';

    const visibleRows = previewDataCache.slice(0, rowsPerPage);

    visibleRows.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center text-muted">${row.row_number}</td>
            <td class="fw-semibold">${row.project_name}</td>
            <td>${row.program}</td>
            <td>${row.domain}</td>
            <td>${row.subdomain}</td>
            <td>${row.intervention}</td>
            <td>${row.priority}</td>
            <td class="font-monospace small text-primary fw-bold">${row.total_cost !== undefined && row.total_cost !== null ? row.total_cost : '-'}</td>
            <td class="font-monospace small text-warning">${row.spent_amount !== undefined && row.spent_amount !== null ? row.spent_amount : '-'}</td>
            <td class="font-monospace small text-success">${row.remaining_amount !== undefined && row.remaining_amount !== null ? row.remaining_amount : '-'}</td>
            <td class="font-monospace small">${row.start_date}</td>
            <td class="font-monospace small">${row.end_date}</td>
            <td><span class="badge bg-secondary">${row.status}</span></td>
        `;
        tbody.appendChild(tr);
    });
}

let progressInterval;
function startImportLoading() {
    const modal = new bootstrap.Modal(document.getElementById('importLoadingModal'));
    modal.show();
    
    let percent = 0;
    const progressBar = document.getElementById('importProgressBar');
    const progressPercent = document.getElementById('importProgressPercent');
    const statusText = document.getElementById('importStatusText');
    
    progressBar.style.width = '0%';
    progressPercent.textContent = '0%';
    statusText.textContent = 'جاري تهيئة الملف واستخراج البيانات...';
    
    progressInterval = setInterval(() => {
        if (percent < 95) {
            let increment = 1;
            if (percent < 30) increment = 4;
            else if (percent < 60) increment = 2;
            else if (percent < 85) increment = 1;
            else increment = 0.5;
            
            percent = Math.min(95, percent + increment);
            progressBar.style.width = percent + '%';
            progressPercent.textContent = Math.round(percent) + '%';
            
            if (percent >= 30 && percent < 60) {
                statusText.textContent = 'جاري التحقق من صحة الحقول والمطابقة...';
            } else if (percent >= 60 && percent < 85) {
                statusText.textContent = 'جاري استيراد وحفظ بيانات المشاريع في قاعدة البيانات...';
            } else if (percent >= 85) {
                statusText.textContent = 'جاري ربط الأنشطة والجهات والتمويلات التابعة...';
            }
        }
    }, 300);
}

function stopImportLoading(success = true) {
    clearInterval(progressInterval);
    const progressBar = document.getElementById('importProgressBar');
    const progressPercent = document.getElementById('importProgressPercent');
    const statusText = document.getElementById('importStatusText');
    
    progressBar.style.width = '100%';
    progressPercent.textContent = '100%';
    statusText.textContent = success ? 'تم الاستيراد بنجاح!' : 'حدث خطأ أثناء الاستيراد';
    
    setTimeout(() => {
        const modalEl = document.getElementById('importLoadingModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }, 800);
}

// Handle confirm import
document.getElementById('confirmImportBtn').addEventListener('click', async function() {
    if (!filePath) {
        alert('لم يتم العثور على الملف. يرجى إعادة رفع الملف.');
        return;
    }
    
    const operation = document.getElementById('operationSelect').value;
    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الاستيراد...';
    startImportLoading();
    
    try {
        const response = await fetch('{{ route("projects.process-import") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                file_path: filePath,
                operation: operation
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            stopImportLoading(true);
            displayResults(data.report, data.failures, data.skipped, data.updated);
            // Phase 2 — show missing dropdown section if any
            if (data.grouped_missing && data.grouped_missing.length > 0) {
                initDropdownMissingSection(data);
            }
        } else {
            stopImportLoading(false);
            alert('خطأ: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        stopImportLoading(false);
        alert('حدث خطأ أثناء الاستيراد: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

// Display import results
function displayResults(report, failures, skipped, updated) {
    const statsHtml = `
        <div class="col">
            <div class="p-3 bg-light rounded-3 border-start border-4 border-info">
                <div class="small text-muted">إجمالي الصفوف</div>
                <div class="h4 fw-bold mb-0">${report.total_rows || 0}</div>
            </div>
        </div>
        <div class="col">
            <div class="p-3 bg-light rounded-3 border-start border-4 border-success">
                <div class="small text-muted">تم الإضافة (جديد)</div>
                <div class="h4 fw-bold mb-0 text-success">${report.successful_inserts || 0}</div>
            </div>
        </div>
        <div class="col">
            <div class="p-3 bg-light rounded-3 border-start border-4 border-warning">
                <div class="small text-muted">تم التحديث (مكرر)</div>
                <div class="h4 fw-bold mb-0 text-warning">${report.successful_updates || 0}</div>
            </div>
        </div>
        <div class="col">
            <div class="p-3 bg-light rounded-3 border-start border-4 border-secondary">
                <div class="small text-muted">متخطى / فارغ</div>
                <div class="h4 fw-bold mb-0 text-secondary">${report.skipped_rows || 0}</div>
            </div>
        </div>
        ${(report.skipped_dropdown_rows || 0) > 0 ? `
        <div class="col">
            <div class="p-3 bg-light rounded-3 border-start border-4" style="border-color:#f97316!important">
                <div class="small text-muted">تخطي (قيم ناقصة)</div>
                <div class="h4 fw-bold mb-0" style="color:#ea580c">${report.skipped_dropdown_rows || 0}</div>
            </div>
        </div>` : ''}
        <div class="col">
            <div class="p-3 bg-light rounded-3 border-start border-4 border-danger">
                <div class="small text-muted">فشل</div>
                <div class="h4 fw-bold mb-0 text-danger">${report.failed_rows || 0}</div>
            </div>
        </div>
    `;
    
    document.getElementById('reportStats').innerHTML = statsHtml;
    
    if (failures && failures.length > 0) {
        const failuresTbody = document.getElementById('failuresTableBody');
        failuresTbody.innerHTML = '';
        
        failures.forEach(failure => {
            const tr = document.createElement('tr');
            const errs = Array.isArray(failure.errors) ? failure.errors.join(', ') : (failure.reason || failure.errors || 'خطأ غير معروف');
            tr.innerHTML = `
                <td class="fw-bold">${failure.row || failure.project_name || '—'}</td>
                <td>${errs}</td>
            `;
            failuresTbody.appendChild(tr);
        });
        
        document.getElementById('failuresJsonInput').value = JSON.stringify(failures);
        if (report && report.failed_export_file) {
            document.getElementById('failedExportFileInput').value = report.failed_export_file;
        } else {
            document.getElementById('failedExportFileInput').value = '';
        }
        document.getElementById('downloadFailuresBtn').style.display = 'inline-block';
        document.getElementById('failuresSection').style.display = 'block';
    } else {
        document.getElementById('downloadFailuresBtn').style.display = 'none';
        document.getElementById('failuresSection').style.display = 'none';
    }

    if (updated && updated.length > 0) {
        const updatedTbody = document.getElementById('updatedTableBody');
        updatedTbody.innerHTML = '';
        
        updated.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center font-monospace">${item.row_number || '—'}</td>
                <td class="fw-bold">${item.row || '—'}</td>
                <td class="text-muted small">${item.reason || '—'}</td>
            `;
            updatedTbody.appendChild(tr);
        });
        document.getElementById('updatedSection').style.display = 'block';
    } else {
        document.getElementById('updatedSection').style.display = 'none';
    }

    if (skipped && skipped.length > 0) {
        const skippedTbody = document.getElementById('skippedTableBody');
        skippedTbody.innerHTML = '';
        
        skipped.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center font-monospace">${item.row || '—'}</td>
                <td class="text-muted small">${item.reason || '—'}</td>
            `;
            skippedTbody.appendChild(tr);
        });
        document.getElementById('skippedSection').style.display = 'block';
    } else {
        document.getElementById('skippedSection').style.display = 'none';
    }
    
    document.getElementById('previewSection').style.display = 'none';
    document.getElementById('resultsSection').style.display = 'block';
    document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });
}
</script>

<script>
// ═══════════════════════════════════════════════════════════════
// Phase 2 — Dropdown Missing Values Treatment
// ═══════════════════════════════════════════════════════════════

let importSession   = null;
let skippedFile     = null;
let groupedMissing  = [];   // deduplicated list
let mappingStatus   = {};   // key → {action, targetId, targetValue, statusLabel}
let currentFieldKey      = null;
let currentOriginalValue = null;
let currentAction        = null;

const STATUS_CONFIG = {
    pending:  { icon: '🔴', text: 'غير معالجة',   cls: 'text-danger'  },
    replaced: { icon: '🟣', text: 'تم استبدالها', cls: 'text-purple'  },
    added:    { icon: '🔵', text: 'تمت الإضافة',  cls: 'text-primary' },
    done:     { icon: '🟢', text: 'تمت المعالجة', cls: 'text-success' },
};

function initDropdownMissingSection(data) {
    importSession  = data.import_session;
    skippedFile    = data.skipped_file;
    groupedMissing = data.grouped_missing || [];

    // Set download link
    if (data.dropdown_export_url) {
        document.getElementById('downloadDropdownReportBtn').href = data.dropdown_export_url;
    }

    // Initialize status map
    groupedMissing.forEach(item => {
        const key = item.field_key + ':::' + item.original_value.toLowerCase().trim();
        mappingStatus[key] = { status: 'pending' };
    });

    renderMissingTable(groupedMissing);
    document.getElementById('dropdownMissingSection').style.display = 'block';
    setTimeout(() => document.getElementById('dropdownMissingSection').scrollIntoView({ behavior: 'smooth' }), 300);
}

function renderMissingTable(items) {
    const tbody = document.getElementById('missingValuesTableBody');
    tbody.innerHTML = '';
    items.forEach((item, idx) => {
        const key = item.field_key + ':::' + item.original_value.toLowerCase().trim();
        const st  = mappingStatus[key] || { status: 'pending' };
        const cfg = STATUS_CONFIG[st.status] || STATUS_CONFIG.pending;
        const projectsList = (item.affected_projects || []).slice(0, 3).join('، ');
        const moreProjects = (item.affected_projects || []).length > 3 ? ` وآخرون (+${item.affected_projects.length - 3})` : '';
        const targetDisplay = st.targetValue ? `<br><small class="text-success">→ ${st.targetValue}</small>` : '';

        const tr = document.createElement('tr');
        tr.id = `missing-row-${idx}`;
        tr.innerHTML = `
            <td class="text-center text-muted">${idx + 1}</td>
            <td><span class="badge bg-primary-subtle text-primary-emphasis">${item.field_label}</span></td>
            <td class="fw-semibold text-danger">${item.original_value}${targetDisplay}</td>
            <td class="text-center">
                <span class="badge bg-secondary-subtle text-secondary-emphasis" title="${projectsList}${moreProjects}">
                    ${item.affected_count} مشروع
                </span>
            </td>
            <td class="${cfg.cls}">${cfg.icon} ${cfg.text}</td>
            <td class="text-center">
                ${st.status === 'pending'
                    ? `<button class="btn btn-sm btn-outline-warning" onclick="openMappingModal('${item.field_key}','${item.original_value.replace(/'/g,"\\'").replace(/"/g,'&quot;')}','${item.field_label}',${item.affected_count},'${(item.affected_projects||[]).join(',')}',${idx})">
                           <i class="fas fa-wrench me-1"></i>معالجة
                       </button>`
                    : `<button class="btn btn-sm btn-outline-secondary" onclick="openMappingModal('${item.field_key}','${item.original_value.replace(/'/g,"\\'").replace(/"/g,'&quot;')}','${item.field_label}',${item.affected_count},'${(item.affected_projects||[]).join(',')}',${idx})">
                           <i class="fas fa-edit me-1"></i>تعديل
                       </button>`
                }
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function openMappingModal(fieldKey, originalValue, fieldLabel, affectedCount, projectsStr, rowIdx) {
    currentFieldKey      = fieldKey;
    currentOriginalValue = originalValue;
    currentAction        = null;

    document.getElementById('modalFieldLabel').textContent     = fieldLabel;
    document.getElementById('modalFieldKeyDisplay').textContent = fieldKey;
    document.getElementById('modalOriginalValue').textContent   = originalValue;
    document.getElementById('modalAffectedCount').innerHTML     = `<span class="badge bg-warning text-dark">${affectedCount} مشروع</span>`;

    // Reset modal
    backToActions();
    document.getElementById('saveMappingBtn').style.display = 'none';

    const modal = new bootstrap.Modal(document.getElementById('mappingModal'));
    modal.show();
}

function selectAction(action) {
    currentAction = action;
    document.getElementById('stepChooseAction').style.display = 'none';
    document.getElementById('stepReplace').style.display  = action === 'replace'  ? '' : 'none';
    document.getElementById('stepAdd').style.display      = action === 'add'      ? '' : 'none';
    document.getElementById('stepEditAdd').style.display  = action === 'edit_add' ? '' : 'none';
    document.getElementById('saveMappingBtn').style.display = '';

    if (action === 'replace') loadDropdownOptions();
    if (action === 'add')     document.getElementById('addValuePreview').textContent = currentOriginalValue;
    if (action === 'edit_add') {
        document.getElementById('editOriginalDisplay').textContent = currentOriginalValue;
        document.getElementById('editedValueInput').value = currentOriginalValue;
    }
}

function backToActions() {
    currentAction = null;
    document.getElementById('stepChooseAction').style.display = '';
    document.getElementById('stepReplace').style.display  = 'none';
    document.getElementById('stepAdd').style.display      = 'none';
    document.getElementById('stepEditAdd').style.display  = 'none';
    document.getElementById('saveMappingBtn').style.display = 'none';
}

async function loadDropdownOptions() {
    const select = document.getElementById('replaceTargetSelect');
    select.innerHTML = '<option value="">جاري تحميل الخيارات...</option>';
    select.disabled = true;
    try {
        const resp = await fetch(`{{ url('/projects/import/dropdown-options') }}/${currentFieldKey}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await resp.json();
        select.innerHTML = '<option value="">— اختر —</option>';
        (data.options || []).forEach(opt => {
            const o = document.createElement('option');
            o.value = opt.id;
            o.textContent = opt.label;
            select.appendChild(o);
        });
        select.disabled = false;
    } catch {
        select.innerHTML = '<option value="">فشل تحميل الخيارات</option>';
        select.disabled = false;
    }
}

async function saveMapping() {
    const btn = document.getElementById('saveMappingBtn');
    btn.disabled = true;
    document.getElementById('mappingSavingSpinner').style.display = '';

    const payload = {
        import_session: importSession,
        field_key:      currentFieldKey,
        original_value: currentOriginalValue,
        action:         currentAction,
    };

    if (currentAction === 'replace') {
        const sel = document.getElementById('replaceTargetSelect');
        if (!sel.value) { alert('يرجى اختيار قيمة بديلة.'); btn.disabled = false; document.getElementById('mappingSavingSpinner').style.display = 'none'; return; }
        payload.target_id = parseInt(sel.value);
    } else if (currentAction === 'edit_add') {
        const edited = document.getElementById('editedValueInput').value.trim();
        if (!edited) { alert('يرجى إدخال القيمة المعدَّلة.'); btn.disabled = false; document.getElementById('mappingSavingSpinner').style.display = 'none'; return; }
        payload.edited_value = edited;
    }

    try {
        const resp = await fetch('{{ route("projects.import-save-mapping") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (!data.success) { alert('خطأ: ' + data.message); btn.disabled = false; document.getElementById('mappingSavingSpinner').style.display = 'none'; return; }

        // Update local status
        const key = currentFieldKey + ':::' + currentOriginalValue.toLowerCase().trim();
        const statusMap = { replace: 'replaced', add: 'added', edit_add: 'added' };
        mappingStatus[key] = {
            status:      statusMap[currentAction] || 'done',
            targetId:    data.mapping?.target_id,
            targetValue: data.mapping?.target_value,
        };

        // Close modal & refresh table
        bootstrap.Modal.getInstance(document.getElementById('mappingModal')).hide();
        renderMissingTable(groupedMissing);

    } catch (e) {
        alert('حدث خطأ أثناء الحفظ: ' + e.message);
    } finally {
        btn.disabled = false;
        document.getElementById('mappingSavingSpinner').style.display = 'none';
    }
}

async function reImportSkipped() {
    const btn = document.getElementById('reImportBtn');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري إعادة الاستيراد...';

    try {
        const resp = await fetch('{{ route("projects.import-re-import-skipped") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ import_session: importSession, skipped_file: skippedFile })
        });
        const data = await resp.json();
        if (!data.success) { alert('خطأ: ' + (data.message || 'خطأ غير معروف')); return; }

        displayReImportResults(data.report);

        // Refresh the table with remaining missing values
        if (data.still_grouped_missing && data.still_grouped_missing.length > 0) {
            groupedMissing = data.still_grouped_missing;
            data.still_grouped_missing.forEach(item => {
                const key = item.field_key + ':::' + item.original_value.toLowerCase().trim();
                if (!mappingStatus[key] || mappingStatus[key].status !== 'pending') return;
                mappingStatus[key] = { status: 'pending' };
            });
            renderMissingTable(groupedMissing);
            btn.innerHTML = '<i class="fas fa-sync me-1"></i>إعادة استيراد ما تبقى';
        } else {
            document.getElementById('dropdownMissingSection').style.display = 'none';
        }
    } catch (e) {
        alert('حدث خطأ: ' + e.message);
        btn.innerHTML = origText;
    } finally {
        btn.disabled = false;
    }
}

function displayReImportResults(report) {
    const statsHtml = `
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border-start border-4 border-success text-center">
                <div class="small text-muted">تم استيرادها الآن</div>
                <div class="h3 fw-bold text-success mb-0">${report.now_imported || 0}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border-start border-4" style="border-color:#f97316!important" text-center">
                <div class="small text-muted">ما زالت متخطاة</div>
                <div class="h3 fw-bold mb-0" style="color:#ea580c">${report.still_skipped || 0}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border-start border-4 border-danger text-center">
                <div class="small text-muted">أخطاء</div>
                <div class="h3 fw-bold text-danger mb-0">${report.errors || 0}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border-start border-4 border-info text-center">
                <div class="small text-muted">إجمالي المعالجة</div>
                <div class="h3 fw-bold mb-0">${report.total_skipped || 0}</div>
            </div>
        </div>
    `;
    document.getElementById('reImportStats').innerHTML = statsHtml;
    document.getElementById('reImportResultsSection').style.display = '';
}
</script>
@endpush
@endsection
