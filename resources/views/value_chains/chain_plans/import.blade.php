@extends('layouts.app')

@section('styles')
    <link href="{{ asset('css/excel-drag-drop.css') }}" rel="stylesheet">
    <style>
        .mapping-select {
            min-width: 150px;
        }

        .preview-table-wrap {
            max-height: 400px;
            overflow: auto;
        }

        .preview-table-wrap table th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 2;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-file-import text-primary me-2"></i>
                        استيراد خطط السلاسل
                    </h2>
                    <a href="{{ route('chain_plans.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i>
                        العودة للقائمة
                    </a>
                </div>

                <!-- Drag and Drop Zone -->
                <div class="card mb-4" id="uploadCard">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-excel text-success me-2"></i>
                            استيراد البيانات من Excel
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            @csrf
                            <div class="excel-drop-zone" id="dropZone">
                                <div class="drop-zone-content">
                                    <i class="fas fa-cloud-upload-alt drop-icon"></i>
                                    <p class="drop-message">اسحب ملف CSV أو Excel هنا أو انقر للاختيار</p>
                                    <p class="drop-info">
                                        <small class="text-muted">
                                            الصيغ المدعومة: CSV (.csv) أو Excel (.xlsx, .xls)<br>
                                            الحد الأقصى للحجم: 2 ميجابايت
                                        </small>
                                    </p>
                                    <input type="file" class="excel-file-input form-control d-none" id="file" name="file"
                                        accept=".csv,.xlsx,.xls" required>
                                </div>
                            </div>

                            <div class="mt-3 text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('chain_plans.download_template', ['format' => 'xlsx']) }}"
                                        class="btn btn-outline-success">
                                        <i class="fas fa-download me-1"></i>
                                        تحميل قالب Excel
                                    </a>
                                    <a href="{{ route('chain_plans.download_template', ['format' => 'csv']) }}"
                                        class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i>
                                        تحميل قالب CSV
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preview & Mapping Section -->
                <div class="card shadow-sm border-0 d-none mb-4" id="previewCard">
                    <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-table text-primary me-2"></i> معاينة البيانات واختيار
                            المطابقة</h5>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelImportBtn">
                            <i class="fas fa-times me-1"></i> إلغاء واختيار ملف آخر
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <!-- Mapping area: each field gets a dropdown to select column -->
                        <div class="mb-3" id="mappingSection">
                            <label class="form-label fw-bold d-block mb-2">مطابقة أعمدة Excel مع حقول النظام</label>
                            <div class="row g-3" id="mappingFields">
                                <!-- سيتم ملؤها ديناميكياً -->
                            </div>
                        </div>

                        <!-- Preview table: shows column headers and first few rows -->
                        <div class="preview-table-wrap">
                            <table class="table table-bordered table-sm table-hover mb-0" id="previewTable">
                                <thead>
                                    <tr id="previewHeaders">
                                        <!-- سيتم إضافة رؤوس الأعمدة هنا -->
                                    </tr>
                                </thead>
                                <tbody id="previewRows">
                                    <!-- سيتم إضافة الصفوف هنا -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end p-3">
                        @can('value_chains.import')
                        <form id="processForm" action="{{ route('chain_plans.import.process') }}" method="POST">
                            @csrf
                            <input type="hidden" name="file_path" id="file_path_input">
                            <button type="submit" class="btn btn-success px-5 fw-bold" id="confirmImportBtn">
                                <i class="fas fa-check me-1"></i> تأكيد الاستيراد
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>

                <!-- Instructions Card -->
                <div class="card" id="instructionsCard">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            تعليمات الاستيراد
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">خطوات الاستيراد:</h6>
                                <ol class="mb-3">
                                    <li>قم بتحميل ملف Excel الذي يحتوي على خطط السلاسل</li>
                                    <li>اسحب الملف إلى المنطقة أعلاه أو انقر للاختيار</li>
                                    <li>سيتم عرض أعمدة الملف وعينة من البيانات</li>
                                    <li>اختر الحقل النظامي المناسب لكل عمود من القوائم المنسدلة</li>
                                    <li>انقر على تأكيد الاستيراد لإتمام العملية</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-warning">ملاحظات مهمة:</h6>
                                <ul class="text-muted small">
                                    <li>تأكد من مطابقة أسماء المحافظات والمديريات مع النظام</li>
                                    <li>يجب أن تكون السلسلة والمجال والمؤشر موجودة في النظام</li>
                                    <li>يمكنك تجاهل أي عمود لا ترغب في استيراده</li>
                                    <li>تأكد من صحة البيانات قبل التأكيد</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            var dropZone = $('#dropZone');
            var fileInput = $('#file');
            var previewCard = $('#previewCard');
            var uploadCard = $('#uploadCard');
            var instructionsCard = $('#instructionsCard');

            // Drag and Drop events
            dropZone.on('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.addClass('drag-over');
            });

            dropZone.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.removeClass('drag-over');
            });

            dropZone.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.removeClass('drag-over');
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    fileInput[0].files = files;
                    triggerPreview();
                }
            });

            dropZone.on('click', function () {
                fileInput[0].click();
            });

            fileInput.on('change', function () {
                if (this.files.length > 0) {
                    triggerPreview();
                }
            });

            $('#cancelImportBtn').click(function () {
                previewCard.addClass('d-none');
                uploadCard.removeClass('d-none');
                instructionsCard.removeClass('d-none');
                fileInput.val('');
                // إعادة تعيين نموذج المعالجة
                $('#processForm').find('input[name="mapping[]"]').remove();
                $('#file_path_input').val('');
            });

            function triggerPreview() {
                var formData = new FormData($('#uploadForm')[0]);
                var dropMessage = dropZone.find('.drop-message');

                dropZone.addClass('loading');
                dropMessage.html('<i class="fas fa-spinner fa-spin"></i> جاري رفع ومعاينة الملف...');

                $.ajax({
                    url: '{{ route("chain_plans.import.preview") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            $('#file_path_input').val(response.file_path);
                            renderMapping(response.available_fields, response.headers);
                            renderPreviewTable(response.headers, response.preview_rows);

                            uploadCard.addClass('d-none');
                            instructionsCard.addClass('d-none');
                            previewCard.removeClass('d-none');
                            $('#confirmImportBtn').prop('disabled', false);
                        } else {
                            alert(response.message || 'حدث خطأ أثناء المعاينة');
                        }
                    },
                    error: function (xhr) {
                        var errorMsg = 'حدث خطأ أثناء المعاينة';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                    },
                    complete: function () {
                        dropZone.removeClass('loading');
                        dropMessage.text('اسحب ملف CSV أو Excel هنا أو انقر للاختيار');
                        // لا نقوم بإعادة تعيين fileInput هنا حتى يمكن إعادة رفع نفس الملف
                    }
                });
            }

            /**
             * بناء قوائم المطابقة: لكل حقل من available_fields، قائمة منسدلة بأسماء الأعمدة (headers)
             */
            function renderMapping(availableFields, headers) {
                var wrapper = $('#mappingFields');
                wrapper.empty();

                if (!availableFields || Object.keys(availableFields).length === 0) {
                    wrapper.append('<div class="col-12 text-danger">حدث خطأ في تحميل الحقول، تأكد من صحة الملف المرفوع. (' + JSON.stringify(availableFields) + ')</div>');
                    return;
                }

                try {
                    var fieldsHtml = '';
                    $.each(availableFields, function (field, label) {
                        var selectOptions = '<option value="">-- تجاهل --</option>';
                        var isMatched = false;

                        if (headers && Array.isArray(headers)) {
                            $.each(headers, function (index, headerName) {
                                var headerLabel = (headerName !== null && headerName !== undefined ? String(headerName).trim() : '') || 'عمود ' + (index + 1);
                                var selected = '';
                                
                                if (!isMatched && (headerLabel === label || headerLabel === field)) {
                                    selected = 'selected';
                                    isMatched = true;
                                }
                                
                                selectOptions += '<option value="' + index + '" ' + selected + '>' + headerLabel + '</option>';
                            });
                        }

                        fieldsHtml += '<div class="col-md-4 col-lg-3">' +
                            '<label class="form-label small fw-bold">' + label + '</label>' +
                            '<select class="form-select form-select-sm mapping-select" data-field="' + field + '">' +
                                selectOptions +
                            '</select>' +
                        '</div>';
                    });
                    
                    wrapper.append(fieldsHtml);
                } catch (err) {
                    alert('حدث خطأ أثناء بناء الحقول: ' + err.message);
                }
            }

            /**
             * عرض الجدول المعاينة: رؤوس الأعمدة وأول 5 صفوف
             */
            function renderPreviewTable(headers, previewRows) {
                var thead = $('#previewHeaders');
                var tbody = $('#previewRows');
                thead.empty();
                tbody.empty();

                // إضافة رؤوس الأعمدة
                var headerRow = '<th>#</th>';
                $.each(headers, function (index, header) {
                    var label = (header !== null && header !== undefined ? String(header).trim() : '') || 'عمود ' + (index + 1);
                    headerRow += `<th>${label}</th>`;
                });
                thead.html(headerRow);

                // إضافة الصفوف (أول 5 صفوف)
                if (previewRows.length === 0) {
                    tbody.append('<tr><td colspan="' + (headers.length + 1) + '" class="text-center text-muted">لا توجد بيانات في الملف</td></tr>');
                    $('#confirmImportBtn').prop('disabled', true);
                    return;
                }

                $.each(previewRows, function (rowIndex, row) {
                    var rowHtml = `<td>${rowIndex + 1}</td>`;
                    $.each(row, function (colIndex, value) {
                        var display = (value !== null && value !== undefined) ? value : '';
                        rowHtml += `<td>${display}</td>`;
                    });
                    tbody.append(`<tr>${rowHtml}</tr>`);
                });
            }

            /**
             * معالجة نموذج التأكيد: بناء mapping بالشكل المطلوب (column_index => field_name)
             */
            $('#processForm').submit(function (e) {
                e.preventDefault();
                var form = $(this);
                var btn = $('#confirmImportBtn');
                var originalText = btn.html();

                // جمع المطابقة: لكل حقل اخترنا عموداً له
                var mapping = {};
                $('.mapping-select').each(function () {
                    var field = $(this).data('field');
                    var columnIndex = $(this).val();
                    if (columnIndex !== '') {
                        mapping[columnIndex] = field; // مفتاح = رقم العمود، قيمة = اسم الحقل
                    }
                });

                // التأكد من وجود مطابقة واحدة على الأقل
                if (Object.keys(mapping).length === 0) {
                    alert('يرجى مطابقة حقل واحد على الأقل مع عمود من Excel');
                    return;
                }

                // إضافة mapping إلى النموذج كـ hidden inputs
                // نرسلها كمصفوفة بحيث تكون name="mapping[0]" = field_name
                form.find('input[name^="mapping["]').remove(); // إزالة القديم
                $.each(mapping, function (columnIndex, fieldName) {
                    $('<input>', {
                        type: 'hidden',
                        name: 'mapping[' + columnIndex + ']',
                        value: fieldName
                    }).appendTo(form);
                });

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الاستيراد...');

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function (response) {
                        if (response.success) {
                            window.location.href = '{{ route("chain_plans.index") }}';
                        } else {
                            alert(response.message || 'حدث خطأ أثناء الاستيراد');
                            btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function (xhr) {
                        var errorMsg = 'حدث خطأ أثناء الاستيراد';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>
@endpush