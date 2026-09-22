<div class="mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="fas fa-file-upload me-2"></i>@if($project->status === 'draft' || (isset($allowUpload) && $allowUpload))رفع وثائق المشروع @else وثائق المشروع @endif
            </h6>
        </div>
        <div class="card-body p-0">
            {{-- جدول الوثائق --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="documentsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="30%" class="text-center">الوثيقة المطلوبة</th>
                            <th width="70%" class="text-center">المرفقات</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- وثيقة المشروع --}}
                        <tr>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-contract text-primary fs-4 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">وثيقة المشروع</h6>
                                        <small class="text-muted">
                                            PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX<br>
                                            الحد الأقصى: 20MB
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{-- المرفقات الحالية --}}
                                <div id="projectDocumentFiles" class="mb-3">
                                    @foreach($project->documents->where('document_type', 'project_document') as $doc)
                                    <div class="file-item d-flex justify-content-between align-items-center p-2 border rounded mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-pdf text-danger me-2"></i>
                                            <span>{{ $doc->file_name }}</span>
                                            <small class="text-muted ms-2">({{ $doc->formatted_file_size }})</small>
                                        </div>
                                        <div class="file-actions">
                                            <a href="{{ route('projects.documents.show', [$project->id, $doc->id]) }}" 
                                               class="btn btn-sm btn-outline-primary me-1" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('projects.documents.download', [$project->id, $doc->id]) }}" 
                                               class="btn btn-sm btn-outline-success me-1">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @if($project->status === 'draft' || (isset($allowUpload) && $allowUpload))
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteDocument({{ $doc->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                @if($project->status === 'draft' || (isset($allowUpload) && $allowUpload))
                                {{-- نموذج الرفع --}}
                                <form class="auto-upload-form" data-type="project_document">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-8">
                                            <input type="file" 
                                                   class="form-control form-control-sm auto-upload" 
                                                   name="document" 
                                                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx"
                                                   data-type="project_document">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-primary btn-sm w-100 upload-btn" disabled>
                                                <i class="fas fa-upload me-1"></i>رفع
                                            </button>
                                        </div>
                                    </div>
                                    <div class="upload-status mt-2" style="display: none;">
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small class="status-text text-muted"></small>
                                    </div>
                                </form>
                                @endif
                            </td>
                        </tr>

                        {{-- بطاقة المشروع --}}
                        <tr>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-address-card text-success fs-4 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">بطاقة المشروع</h6>
                                        <small class="text-muted">
                                            PDF, DOC, DOCX, JPG, JPEG, PNG<br>
                                            الحد الأقصى: 20MB
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{-- المرفقات الحالية --}}
                                <div id="projectCardFiles" class="mb-3">
                                    @foreach($project->documents->where('document_type', 'project_card') as $doc)
                                    <div class="file-item d-flex justify-content-between align-items-center p-2 border rounded mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas {{ $doc->file_icon }} me-2"></i>
                                            <span>{{ $doc->file_name }}</span>
                                            <small class="text-muted ms-2">({{ $doc->formatted_file_size }})</small>
                                        </div>
                                        <div class="file-actions">
                                            <a href="{{ route('projects.documents.show', [$project->id, $doc->id]) }}" 
                                               class="btn btn-sm btn-outline-primary me-1" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('projects.documents.download', [$project->id, $doc->id]) }}" 
                                               class="btn btn-sm btn-outline-success me-1">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @if($project->status === 'draft' || (isset($allowUpload) && $allowUpload))
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteDocument({{ $doc->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                @if($project->status === 'draft' || (isset($allowUpload) && $allowUpload))
                                {{-- نموذج الرفع --}}
                                <form class="auto-upload-form" data-type="project_card">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-8">
                                            <input type="file" 
                                                   class="form-control form-control-sm auto-upload" 
                                                   name="document" 
                                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                   data-type="project_card">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-success btn-sm w-100 upload-btn" disabled>
                                                <i class="fas fa-upload me-1"></i>رفع
                                            </button>
                                        </div>
                                    </div>
                                    <div class="upload-status mt-2" style="display: none;">
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small class="status-text text-muted"></small>
                                    </div>
                                </form>
                                @endif
                            </td>
                        </tr>

                        {{-- طلب الموافقة --}}
                        <tr>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clipboard-check text-warning fs-4 me-3"></i>
                                    <div>
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="mb-0 me-2">طلب الموافقة</h6>
                                            @can('correspondence.create')
                                            <a href="{{ route('correspondence.create', ['subject' => 'طلب موافقة على مشروع: ' . ($project->project_name ?? $project->name) . ' (' . ($project->form_number ?? $project->project_number) . ')', 'project_id' => $project->id]) }}" 
                                               class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.8rem;"
                                               title="إنشاء مراسلة لطلب الموافقة" target="_blank">
                                                <i class="fas fa-envelope me-1"></i>إنشاء مراسلة
                                            </a>
                                            @endcan
                                        </div>
                                        <small class="text-muted">
                                            PDF, DOC, DOCX<br>
                                            الحد الأقصى: 20MB
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{-- المرفقات الحالية --}}
                                <div id="approvalRequestFiles" class="mb-3">
                                    @foreach($project->documents->where('document_type', 'approval_request') as $doc)
                                    <div class="file-item d-flex justify-content-between align-items-center p-2 border rounded mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas {{ $doc->file_icon }} me-2"></i>
                                            <span>{{ $doc->file_name }}</span>
                                            <small class="text-muted ms-2">({{ $doc->formatted_file_size }})</small>
                                        </div>
                                        <div class="file-actions">
                                            <a href="{{ route('projects.documents.show', [$project->id, $doc->id]) }}" 
                                               class="btn btn-sm btn-outline-primary me-1" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('projects.documents.download', [$project->id, $doc->id]) }}" 
                                               class="btn btn-sm btn-outline-success me-1">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @if($project->status === 'draft' || (isset($allowUpload) && $allowUpload))
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteDocument({{ $doc->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                @if($project->status === 'draft' || (isset($allowUpload) && $allowUpload))
                                {{-- نموذج الرفع --}}
                                <form class="auto-upload-form" data-type="approval_request">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-8">
                                            <input type="file" 
                                                   class="form-control form-control-sm auto-upload" 
                                                   name="document" 
                                                   accept=".pdf,.doc,.docx"
                                                   data-type="approval_request">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-warning btn-sm w-100 upload-btn" disabled>
                                                <i class="fas fa-upload me-1"></i>رفع
                                            </button>
                                        </div>
                                    </div>
                                    <div class="upload-status mt-2" style="display: none;">
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small class="status-text text-muted"></small>
                                    </div>
                                </form>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- منطقة الرسائل --}}
<div id="uploadMessages" class="mt-3"></div>

{{-- Submit for Approval Section --}}
@if(in_array($project->status, ['draft', 'completed_draft']) && $project->isDraftComplete() && (!isset($allowUpload) || !$allowUpload))
<div class="mt-4">
    <div class="card border-primary">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="fas fa-paper-plane me-2"></i>إرسال المشروع للموافقة
            </h6>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                <i class="fas fa-info-circle me-2"></i>بعد الانتهاء من رفع جميع الوثائق المطلوبة، يمكنك إرسال المشروع للموافقة.
            </p>
            @can('projects.finalize')
            <form action="{{ route('projects.finalize', $project->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg w-100" onclick="return confirmAction(this, 'هل أنت متأكد من إرسال المشروع للموافقة؟')">
                    <i class="fas fa-check-circle me-2"></i>إرسال للموافقة
                </button>
            </form>
            @endcan
        </div>
    </div>
</div>
@endif

{{-- السكربت لرفع الملفات تلقائياً --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // إعدادات الرفع
    const uploadSettings = {
        maxFileSize: 20 * 1024 * 1024, // 20MB
        allowedTypes: {
            'project_document': ['.pdf', '.doc', '.docx', '.ppt', '.pptx', '.xls', '.xlsx'],
            'project_card': ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png'],
            'approval_request': ['.pdf', '.doc', '.docx']
        }
    };

    // تفعيل أزرار الرفع عند اختيار ملف
    document.querySelectorAll('.auto-upload').forEach(input => {
        input.addEventListener('change', function() {
            const uploadBtn = this.closest('.row').querySelector('.upload-btn');
            uploadBtn.disabled = !this.files.length;
        });
    });

    // أحداث أزرار الرفع
    document.querySelectorAll('.upload-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = this.closest('.auto-upload-form');
            const input = form.querySelector('.auto-upload');
            const documentType = form.dataset.type;
            
            if (input.files.length > 0) {
                const file = input.files[0];
                if (validateFile(file, documentType)) {
                    uploadFile(file, documentType, form);
                }
            }
        });
    });

    // التحقق من صحة الملف
    function validateFile(file, documentType) {
        const maxSize = uploadSettings.maxFileSize;
        const allowedExtensions = uploadSettings.allowedTypes[documentType];
        
        if (file.size > maxSize) {
            showMessage('حجم الملف كبير جداً. الحد الأقصى 20MB', 'danger');
            return false;
        }

        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        if (!allowedExtensions.includes(fileExtension)) {
            showMessage(`نوع الملف غير مسموح. الأنواع المسموحة: ${allowedExtensions.join(', ')}`, 'danger');
            return false;
        }

        return true;
    }

    // رفع الملف
    function uploadFile(file, documentType, formElement) {
        const formData = new FormData();
        formData.append('document', file);
        formData.append('document_type', documentType);
        formData.append('_token', '{{ csrf_token() }}');

        // عرض حالة التحميل
        const statusContainer = formElement.querySelector('.upload-status');
        const progressBar = statusContainer.querySelector('.progress-bar');
        const statusText = statusContainer.querySelector('.status-text');
        const uploadBtn = formElement.querySelector('.upload-btn');
        
        statusContainer.style.display = 'block';
        progressBar.style.width = '0%';
        statusText.textContent = 'جاري الرفع...';
        uploadBtn.disabled = true;

        // إرسال طلب AJAX
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                statusText.textContent = `جاري الرفع... ${Math.round(percentComplete)}%`;
            }
        });

        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    progressBar.style.width = '100%';
                    progressBar.classList.remove('progress-bar-animated');
                    statusText.textContent = 'تم الرفع بنجاح';
                    
                    showMessage(`تم رفع الملف "${file.name}" بنجاح`, 'success');
                    
                    // تحديث قائمة الملفات للمستند
                    updateDocumentList(documentType, response.document);

                    @if(isset($allowUpload) && $allowUpload)
                    // إعادة تحميل الصفحة لتحديث حالة التحذيرات وزر الموافقة
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                    @endif
                    
                    // إعادة تعيين الحقول
                    setTimeout(() => {
                        formElement.reset();
                        statusContainer.style.display = 'none';
                        progressBar.classList.add('progress-bar-animated');
                        uploadBtn.disabled = true;
                    }, 2000);
                    
                } else {
                    showMessage(`فشل في رفع الملف: ${response.message}`, 'danger');
                    resetUploadStatus(statusContainer, uploadBtn);
                }
            } else {
                showMessage('حدث خطأ أثناء الرفع', 'danger');
                resetUploadStatus(statusContainer, uploadBtn);
            }
        });

        xhr.addEventListener('error', function() {
            showMessage('فشل في الاتصال بالخادم', 'danger');
            resetUploadStatus(statusContainer, uploadBtn);
        });

        xhr.open('POST', '{{ route('projects.documents.upload', $project->id) }}');
        xhr.send(formData);
    }

    // تحديث قائمة الملفات للمستند
    function updateDocumentList(documentType, newDocument) {
        const containerId = documentType + 'Files';
        const container = document.getElementById(containerId);
        
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item d-flex justify-content-between align-items-center p-2 border rounded mb-2';
        fileItem.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${getFileIcon(newDocument.mime_type)} me-2"></i>
                <span>${newDocument.file_name}</span>
                <small class="text-muted ms-2">(${formatFileSize(newDocument.file_size)})</small>
            </div>
            <div class="file-actions">
                <a href="${newDocument.preview_url}" class="btn btn-sm btn-outline-primary me-1" target="_blank">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="${newDocument.download_url}" class="btn btn-sm btn-outline-success me-1">
                    <i class="fas fa-download"></i>
                </a>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(${newDocument.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        container.prepend(fileItem);
    }

    // دوال مساعدة
    function getFileIcon(mimeType) {
        if (mimeType.includes('pdf')) return 'fa-file-pdf text-danger';
        if (mimeType.includes('word')) return 'fa-file-word text-primary';
        if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'fa-file-excel text-success';
        if (mimeType.includes('image')) return 'fa-file-image text-info';
        return 'fa-file text-secondary';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showMessage(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.getElementById('uploadMessages').appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    function resetUploadStatus(statusContainer, uploadBtn) {
        const progressBar = statusContainer.querySelector('.progress-bar');
        const statusText = statusContainer.querySelector('.status-text');
        
        setTimeout(() => {
            statusContainer.style.display = 'none';
            progressBar.style.width = '0%';
            statusText.textContent = '';
            if (uploadBtn) {
                uploadBtn.disabled = false;
            }
        }, 3000);
    }
});

// دالة حذف المستند
function deleteDocument(documentId) {
    Swal.fire({
        title: 'تأكيد العملية',
        text: 'هل أنت متأكد من حذف هذا المستند؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم',
        cancelButtonText: 'لا',
        reverseButtons: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/projects/{{ $project->id }}/documents/${documentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('تم حذف المستند بنجاح', 'success');
                    // إعادة تحميل الصفحة لتحديث البيانات
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                showMessage('فشل في حذف المستند', 'danger');
            });
        }
    });
}

function showMessage(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.getElementById('uploadMessages').appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>

<style>
.documents-table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.file-item {
    background-color: #fafafa;
    transition: all 0.3s ease;
}

.file-item:hover {
    background-color: #f0f0f0;
}

.file-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.upload-status {
    transition: all 0.3s ease;
}

.progress {
    border-radius: 3px;
}

.auto-upload-form .form-control {
    font-size: 0.875rem;
}

.upload-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

{{-- Show upload scripts only in draft status --}}
@if($project->status === 'draft' || (isset($allowUpload) && $allowUpload))
<script>
// Upload script is initialized via document.addEventListener('DOMContentLoaded') above
</script>
@endif