@extends('layouts.app')

@section('title', 'معاينة ومطابقة استيراد التمويلات')

@section('content')
<div class="container-fluid py-3">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-file-excel text-success me-2"></i> معاينة ومطابقة استيراد التمويلات</h3>
            <p class="text-muted mb-0">تحقق من مطابقة الأسماء مع قاعدة البيانات، وقم بالفرز حسب نوع المطابقة واختيار أسلوب الاستيراد.</p>
        </div>
        <a href="{{ route('projects.financings.import.form') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fas fa-arrow-right me-1"></i> إلغاء ورجوع
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger shadow-sm rounded-3 mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <form id="importForm" action="{{ route('projects.financings.import.process') }}" method="POST">
        @csrf
        <input type="hidden" name="file_path" value="{{ $path }}">
        <input type="hidden" name="single_project_name" id="singleProjectNameInput" value="">

        <!-- 1. Column Mapping Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0 text-dark">
                    <span class="badge bg-primary rounded-circle p-2 me-2">1</span> مطابقة أعمدة الملف (Column Mapping)
                </h5>
                <span class="text-muted small">يرجى التحقق من تعيين حقل اسم المشروع والحقول المالية</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 45%;">عمود الملف المرفوع (Excel Column)</th>
                                <th style="width: 55%;">الحقل المقابل في النظام (System Field)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($headers as $header)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><i class="far fa-file-alt text-muted me-2"></i> {{ $header }}</div>
                                    </td>
                                    <td>
                                        <select name="mapping[{{ $header }}]" class="form-select rounded-pill">
                                            <option value="">-- تجاهل هذا العمود (Ignore) --</option>
                                            @foreach($systemFields as $key => $label)
                                                <option value="{{ $key }}" @if(isset($mappings[$header]) && $mappings[$header] == $key) selected @endif>
                                                    {{ $label }} ({{ $key }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. Unregistered Entities Options (الجهات ومصادر التمويل غير المسجلة) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="fw-bold mb-0 text-dark">
                    <span class="badge bg-primary rounded-circle p-2 me-2">2</span> خيارات التعامل مع الجهات غير المسجلة (التبديل أو الإضافة بدون جهة)
                </h5>
                <span class="badge bg-light text-muted border px-3 py-1 rounded-pill small">
                    إضافة بدون جهة (NULL) أو التبديل لجهة محددة
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold small text-dark">
                            <i class="fas fa-landmark text-primary me-1"></i> في حال كانت الجهة الممولة في الملف غير مسجلة بالنظام:
                        </label>
                        <select name="fallback_authority_id" class="form-select rounded-pill">
                            <option value="">-- إضافة التمويل من دون جهة (فارغة / NULL) لحين تعديل بيانات المشروع لاحقاً (الافتراضي) --</option>
                            @if(isset($authorities))
                                @foreach($authorities as $auth)
                                    <option value="{{ $auth->id }}">تبديل إلى: {{ $auth->agency_name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <small class="text-muted d-block mt-1">
                            إذا لم توجد الجهة في قاعدة البيانات، سيتم استيراد تمويل المشروع بدون جهة (أو تبديلها للجهة المختارة) دون إيقاف الاستيراد.
                        </small>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold small text-dark">
                            <i class="fas fa-hand-holding-usd text-success me-1"></i> في حال كان مصدر التمويل غير مسجل بالنظام:
                        </label>
                        <select name="fallback_funding_source_id" class="form-select rounded-pill">
                            <option value="">-- إضافة التمويل من دون مصدر تمويل (فارغ / NULL) (الافتراضي) --</option>
                            @if(isset($fundingSources))
                                @foreach($fundingSources as $fs)
                                    <option value="{{ $fs->id }}">تبديل إلى: {{ $fs->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <small class="text-muted d-block mt-1">
                            إذا لم يوجد مصدر التمويل في قاعدة البيانات، سيتم استيراد التمويل بدون مصدر (أو تبديله للمصدر المختار).
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- 3. Import Mode (Replace vs Merge) -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0 text-dark">
                            <span class="badge bg-primary rounded-circle p-2 me-2">3</span> أسلوب حفظ التمويلات (Import Mode)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="p-3 border rounded-3 mb-3 bg-light">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="modeReplace" value="replace" checked>
                                <label class="form-check-label fw-bold text-dark" for="modeReplace">
                                    <i class="fas fa-sync-alt text-warning me-1"></i> استبدال التمويلات الحالية (Replace Mode)
                                </label>
                            </div>
                            <small class="text-muted d-block ps-4 mt-1">
                                سيتم حذف تمويلات المشروع السابقة واستبدالها بالتمويلات الجديدة الموجودة في الملف.
                            </small>
                        </div>

                        <div class="p-3 border rounded-3 bg-light">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="modeMerge" value="merge">
                                <label class="form-check-label fw-bold text-dark" for="modeMerge">
                                    <i class="fas fa-plus-circle text-success me-1"></i> دمج / إضافة إلى التمويلات الحالية (Merge Mode)
                                </label>
                            </div>
                            <small class="text-muted d-block ps-4 mt-1">
                                سيتم إضافة التمويلات الجديدة للمشروع مع الاحتفاظ بتمويلاته السابقة وتخطي السجلات المكررة.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Import Scope (All vs Selected vs Single) -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0 text-dark">
                            <span class="badge bg-primary rounded-circle p-2 me-2">4</span> نطاق الاستيراد (Import Scope)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="p-3 border rounded-3 mb-2 bg-light">
                            <div class="form-check">
                                <input class="form-check-input scope-radio" type="radio" name="import_scope" id="scopeAll" value="all" checked>
                                <label class="form-check-label fw-bold text-dark" for="scopeAll">
                                    <i class="fas fa-layer-group text-primary me-1"></i> استيراد الجميع (Import All)
                                </label>
                            </div>
                            <small class="text-muted d-block ps-4 mt-1">
                                معالجة واستيراد كافة المشاريع المتطابقة في الملف دفعة واحدة.
                            </small>
                        </div>

                        <div class="p-3 border rounded-3 mb-2 bg-light">
                            <div class="form-check">
                                <input class="form-check-input scope-radio" type="radio" name="import_scope" id="scopeSelected" value="selected">
                                <label class="form-check-label fw-bold text-dark" for="scopeSelected">
                                    <i class="fas fa-tasks text-info me-1"></i> استيراد بالتحديد / الفرز (Selected Matches)
                                </label>
                            </div>
                            <small class="text-muted d-block ps-4 mt-1">
                                استيراد فقط المشاريع المحددة أو المفلترة بواسطة نوع المطابقة بالأسفل.
                            </small>
                        </div>

                        <div class="p-3 border rounded-3 bg-light">
                            <div class="form-check">
                                <input class="form-check-input scope-radio" type="radio" name="import_scope" id="scopeSingle" value="single">
                                <label class="form-check-label fw-bold text-dark" for="scopeSingle">
                                    <i class="fas fa-forward-step text-secondary me-1"></i> استيراد صف صف / مشروع محدد (Row-by-Row)
                                </label>
                            </div>
                            <small class="text-muted d-block ps-4 mt-1">
                                اختيار مشروع فردي واستيراده مباشرة عبر زر الإجراء السريع بجانب كل مشروع.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Data Preview & Match Type Filtering Section -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <span class="badge bg-primary rounded-circle p-2 me-2">5</span> معاينة ومقارنة المشاريع والفرز حسب نوع المطابقة
                        </h5>
                        <small class="text-muted">مقارنة الاسم المستورد مع الاسم الموجود في قاعدة البيانات ونوع المطابقة</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill" id="selectedCountBadge">
                            المحدد: 0 من إجمالي {{ $matchStats['total'] ?? count($groupedProjects) }} مشروع
                        </span>
                    </div>
                </div>

                <!-- Match Type Filter Tabs / Badges -->
                <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 filter-btn active" data-filter="all">
                        <i class="fas fa-list me-1"></i> الكل ({{ $matchStats['total'] ?? 0 }})
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 filter-btn" data-filter="matched_exact">
                        <i class="fas fa-bullseye me-1"></i> مطابقة تامة ({{ $matchStats['matched_exact'] ?? 0 }})
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 filter-btn" data-filter="matched_normalized">
                        <i class="fas fa-spell-check me-1"></i> بعد توحيد الاسم ({{ $matchStats['matched_normalized'] ?? 0 }})
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 filter-btn" data-filter="matched_fuzzy" style="color: #4f46e5; border-color: #4f46e5;">
                        <i class="fas fa-project-diagram me-1"></i> مطابقة ذكية تقريبية ({{ $matchStats['matched_fuzzy'] ?? 0 }})
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 filter-btn" data-filter="needs_review">
                        <i class="fas fa-user-clock me-1"></i> يحتاج مراجعة ({{ $matchStats['needs_review'] ?? 0 }})
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 filter-btn" data-filter="ambiguous">
                        <i class="fas fa-question-circle me-1"></i> مطابقة غامضة ({{ $matchStats['ambiguous'] ?? 0 }})
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn" data-filter="not_found">
                        <i class="fas fa-times-circle me-1"></i> غير موجود ({{ $matchStats['not_found'] ?? 0 }})
                    </button>
                </div>

                <!-- Quick Selection Controls -->
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top bg-light p-2 rounded-3">
                    <span class="small fw-bold text-muted me-1"><i class="fas fa-magic me-1"></i> تحديد سريع:</span>
                    <button type="button" class="btn btn-xs btn-sm btn-success rounded-pill px-3" id="btnSelectSafeMatches">
                        <i class="fas fa-shield-alt me-1"></i> تحديد كافة المطابقات الآمنة (تامة + موحدة + ذكية)
                    </button>
                    <button type="button" class="btn btn-xs btn-sm btn-outline-primary rounded-pill px-3" id="btnSelectExactOnly">
                        <i class="fas fa-bullseye me-1"></i> المطابقات التامة فقط
                    </button>
                    <button type="button" class="btn btn-xs btn-sm btn-outline-dark rounded-pill px-3" id="btnSelectAll">
                        <i class="fas fa-check-square me-1"></i> تحديد الكل
                    </button>
                    <button type="button" class="btn btn-xs btn-sm btn-outline-secondary rounded-pill px-3" id="btnDeselectAll">
                        <i class="far fa-square me-1"></i> إلغاء التحديد
                    </button>
                </div>
            </div>

            <div class="card-body p-3">
                <div class="accordion" id="projectsAccordion">
                    @foreach($groupedProjects as $pItem)
                        @php
                            $projectName = $pItem['excel_project_name'];
                            $projId = 'proj_' . md5($projectName);
                            $rowCount = $pItem['rows_count'];
                            $status = $pItem['status'];
                            $isSafeMatch = in_array($status, ['matched_exact', 'matched_normalized', 'matched_fuzzy'], true);
                        @endphp

                        <div class="card border rounded-3 mb-3 project-card-item shadow-sm" data-match-type="{{ $status }}">
                            <div class="card-header bg-white p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <input class="form-check-input project-checkbox fs-5" type="checkbox" name="selected_projects[]" value="{{ $projectName }}" id="check_{{ $projId }}" data-status="{{ $status }}" {{ $isSafeMatch ? 'checked' : '' }}>
                                    
                                    <div>
                                        <!-- Excel Project Name (المستورد) -->
                                        <label class="form-check-label fw-bold text-dark mb-0 cursor-pointer d-block" for="check_{{ $projId }}">
                                            <i class="far fa-file-excel text-success me-1"></i> <span class="text-muted small">المستورد:</span> {{ $projectName }}
                                        </label>
                                        
                                        <!-- Database Project Name (الموجود في قاعدة البيانات) -->
                                        <div class="mt-1 small">
                                            <span class="text-muted"><i class="fas fa-database text-primary me-1"></i> في النظام:</span>
                                            @if(!empty($pItem['matched_project_name']))
                                                <strong class="text-primary">{{ $pItem['matched_project_name'] }}</strong>
                                                <span class="badge bg-light text-dark border ms-1">#{{ $pItem['matched_project_id'] }}</span>
                                            @else
                                                <span class="text-danger fst-italic">-- لم يتم التعرف على مشروع مطابق --</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <!-- Match Type Badge with Score -->
                                    @if($status === 'matched_exact')
                                        <span class="badge bg-success px-3 py-2 rounded-pill">
                                            <i class="fas fa-bullseye me-1"></i> مطابقة تامة
                                        </span>
                                    @elseif($status === 'matched_normalized')
                                        <span class="badge bg-primary px-3 py-2 rounded-pill">
                                            <i class="fas fa-spell-check me-1"></i> بعد توحيد الاسم
                                        </span>
                                    @elseif($status === 'matched_fuzzy')
                                        <span class="badge px-3 py-2 rounded-pill text-white" style="background-color: #4f46e5;">
                                            <i class="fas fa-project-diagram me-1"></i> مطابقة ذكية ({{ number_format(($pItem['score'] ?? 0) * 100, 1) }}%)
                                        </span>
                                    @elseif($status === 'needs_review')
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                            <i class="fas fa-user-clock me-1"></i> يحتاج مراجعة ({{ number_format(($pItem['score'] ?? 0) * 100, 1) }}%)
                                        </span>
                                    @elseif($status === 'ambiguous')
                                        <span class="badge bg-danger px-3 py-2 rounded-pill">
                                            <i class="fas fa-question-circle me-1"></i> مطابقة غامضة (تكرار)
                                        </span>
                                    @else
                                        <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                            <i class="fas fa-times-circle me-1"></i> لم يتم العثور عليه
                                        </span>
                                    @endif

                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-1">
                                        {{ $rowCount }} أسطر
                                    </span>

                                    <!-- Single Project Direct Import Button -->
                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 btn-import-single" data-project-name="{{ $projectName }}">
                                        <i class="fas fa-play me-1"></i> استيراد هذا فقط
                                    </button>

                                    <!-- Collapse Button -->
                                    <button class="btn btn-sm btn-link text-muted p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_{{ $projId }}">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Candidates / Diagnostics Box if needs review or ambiguous -->
                            @if(!empty($pItem['candidates']) && count($pItem['candidates']) > 0 && ($status === 'needs_review' || $status === 'ambiguous'))
                                <div class="px-3 py-2 bg-light border-top border-bottom" style="font-size: 0.85rem;">
                                    <div class="d-flex align-items-center gap-2 text-secondary fw-semibold">
                                        <i class="fas fa-info-circle text-warning"></i> 
                                        <span>أقرب المشاريع المرشحة في النظام:</span>
                                        @foreach($pItem['candidates'] as $cand)
                                            <span class="badge bg-white text-dark border">
                                                {{ $cand['project_name'] }}
                                                @if(isset($cand['score'])) ({{ number_format($cand['score'] * 100, 1) }}%) @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div id="collapse_{{ $projId }}" class="collapse">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light small">
                                                <tr>
                                                    @foreach($headers as $header)
                                                        @if($header !== ($projectHeader ?? ''))
                                                            <th>{{ $header }}</th>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($pItem['rows'] as $row)
                                                    <tr>
                                                        @foreach($headers as $header)
                                                            @if($header !== ($projectHeader ?? ''))
                                                                <td>{{ $row[$header] ?? '' }}</td>
                                                            @endif
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Submit & Actions Footer -->
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm border mb-4">
            <a href="{{ route('projects.financings.import.form') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-times me-1"></i> إلغاء
            </a>
            <button type="submit" id="btnSubmitImport" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                <i class="fas fa-check-circle me-2"></i> تأكيد وتنفيذ الاستيراد للمشاريع المحددة
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.project-checkbox');
    const projectCards = document.querySelectorAll('.project-card-item');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const countBadge = document.getElementById('selectedCountBadge');
    const selectAllBtn = document.getElementById('btnSelectAll');
    const deselectAllBtn = document.getElementById('btnDeselectAll');
    const selectSafeBtn = document.getElementById('btnSelectSafeMatches');
    const selectExactBtn = document.getElementById('btnSelectExactOnly');
    const form = document.getElementById('importForm');
    const singleInput = document.getElementById('singleProjectNameInput');
    const singleBtns = document.querySelectorAll('.btn-import-single');

    function updateCount() {
        const checked = document.querySelectorAll('.project-checkbox:checked').length;
        const total = checkboxes.length;
        countBadge.textContent = `المحدد: ${checked} من إجمالي ${total} مشروع`;
    }

    // 1. Filtering by Match Type
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');

            projectCards.forEach(card => {
                const matchType = card.getAttribute('data-match-type');
                if (filter === 'all' || matchType === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // 2. Selection Shortcuts
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = true);
            document.getElementById('scopeSelected').checked = true;
            updateCount();
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('scopeSelected').checked = true;
            updateCount();
        });
    }

    if (selectSafeBtn) {
        selectSafeBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => {
                const status = cb.getAttribute('data-status');
                cb.checked = ['matched_exact', 'matched_normalized', 'matched_fuzzy'].includes(status);
            });
            document.getElementById('scopeSelected').checked = true;
            updateCount();
        });
    }

    if (selectExactBtn) {
        selectExactBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => {
                const status = cb.getAttribute('data-status');
                cb.checked = (status === 'matched_exact');
            });
            document.getElementById('scopeSelected').checked = true;
            updateCount();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            document.getElementById('scopeSelected').checked = true;
            updateCount();
        });
    });

    // 3. Single Project Direct Import
    singleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const pName = this.getAttribute('data-project-name');
            if (confirm(`هل أنت متأكد من استيراد تمويلات المشروع '${pName}' فقط؟`)) {
                singleInput.value = pName;
                document.getElementById('scopeSingle').checked = true;
                form.submit();
            }
        });
    });

    updateCount();
});
</script>
@endsection
