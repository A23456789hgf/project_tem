<!-- Financial Justification Section -->
<div class="card border-0 bg-white shadow-lg mb-4" style="border-radius: 12px; overflow: hidden;">
    @php
        $difference = $totalSpent - $totalPlanned;
        $hasOverage = $difference > 0;
    @endphp

    <!-- Header with Gradient -->
    <div class="bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 20px; color: white;">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1" style="font-weight: 700;">
                    <i class="fas fa-money-bill-wave me-2"></i>التبريرات المالية
                </h5>
                <p class="mb-0 small" style="opacity: 0.9;">ملخص الميزانية والتكاليف الفعلية</p>
            </div>
            <i class="fas fa-chart-pie fa-3x" style="opacity: 0.2;"></i>
        </div>
    </div>

    <div class="card-body p-4">
        <!-- Financial Summary Cards -->
        <div class="row mb-5 g-3">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #667eea; background: linear-gradient(135deg, #f5f7ff 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: #667eea; letter-spacing: 0.5px;">المبلغ المخطط</span>
                            <div class="bg-primary bg-opacity-10 rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-coins" style="color: #667eea; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div style="font-size: 1.8rem; font-weight: 700; color: #667eea;">{{ number_format($totalPlanned, 2) }}</div>
                        <small class="text-muted d-block mt-2">﷼ (ريال سعودي)</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f093fb; background: linear-gradient(135deg, #fff5f7 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: #f093fb; letter-spacing: 0.5px;">المنصرف الفعلي</span>
                            <div class="bg-danger bg-opacity-10 rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-money-bill-wave" style="color: #f093fb; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div style="font-size: 1.8rem; font-weight: 700; color: #f093fb;">{{ number_format($totalSpent, 2) }}</div>
                        <small class="text-muted d-block mt-2">﷼ (ريال سعودي)</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid {{ $hasOverage ? '#dc3545' : '#28a745' }}; background: linear-gradient(135deg, {{ $hasOverage ? '#fff5f5' : '#f5fff5' }} 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: {{ $hasOverage ? '#dc3545' : '#28a745' }}; letter-spacing: 0.5px;">{{ $hasOverage ? 'التجاوز' : 'الباقي' }}</span>
                            <div class="rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: {{ $hasOverage ? '#fce4e6' : '#e8f5e9' }};">
                                <i class="fas {{ $hasOverage ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}" style="color: {{ $hasOverage ? '#dc3545' : '#28a745' }}; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div style="font-size: 1.8rem; font-weight: 700; color: {{ $hasOverage ? '#dc3545' : '#28a745' }};">{{ number_format(abs($difference), 2) }}</div>
                        <small class="text-muted d-block mt-2">﷼ (ريال سعودي)</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107; background: linear-gradient(135deg, #fffef5 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: #ffc107; letter-spacing: 0.5px;">نسبة الإنفاق</span>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-percentage" style="color: #ffc107; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div style="font-size: 1.8rem; font-weight: 700; color: #ffc107;">{{ number_format(($totalSpent / max($totalPlanned, 1)) * 100, 1) }}%</div>
                        <small class="text-muted d-block mt-2">من إجمالي الميزانية</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert for Overage -->
        @if($hasOverage)
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border: none; border-left: 5px solid #dc3545; background: linear-gradient(135deg, #fce4e6 0%, #ffffff 100%); padding: 20px;">
                <div class="d-flex align-items-start">
                    <div class="me-3" style="font-size: 2rem; color: #dc3545;">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-2" style="color: #dc3545; font-weight: 700;">
                            <i class="fas fa-exclamation-circle me-2"></i>تنبيه: تجاوز الميزانية
                        </h6>
                        <p class="mb-0 text-dark" style="line-height: 1.6;">
                            تم تجاوز الميزانية المخططة بمبلغ <strong style="color: #dc3545; font-size: 1.1rem;">{{ number_format($difference, 2) }} ﷼</strong> 
                            <br><small class="text-muted">يرجى تقديم تبرير مالي مفصل وشامل للأسباب والعوامل التي أدت إلى هذا التجاوز.</small>
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="flex-shrink: 0;"></button>
                </div>
            </div>
        @endif

        <hr class="my-4" style="border-top: 2px dashed #e0e0e0;">

    <!-- Form Section -->
    @if($hasOverage)
        @can('projects.execution.storeProcedureBudgetJustification')
        <form action="{{ route('projects.execution.storeProcedureBudgetJustification', ['project' => $project]) }}" 
        @endcan
              method="POST" 
              enctype="multipart/form-data"
              class="budget-justification-form">
            @csrf
            <input type="hidden" name="preliminary_procedure_id" value="{{ $procedure->id }}">
            <input type="hidden" name="planned_total" value="{{ $totalPlanned }}">
            <input type="hidden" name="actual_total" value="{{ $totalSpent }}">

            <h6 class="border-bottom pb-2 mb-4">
                <i class="fas fa-file-alt me-2"></i>تقديم التبرير المالي
            </h6>

            <!-- Justification Field -->
            <div class="mb-4">
                <label for="justification-{{ $procedure->id }}" class="form-label fw-semibold">
                    التبرير المالي 
                </label>
                <textarea class="form-control form-control-lg" 
                          id="justification-{{ $procedure->id }}" 
                          name="justification" 
                          rows="5" 
                          placeholder="اشرح الأسباب والعوامل التي أدت إلى الفرق بين الميزانية المخطط لها والمنصرف الفعلي..." style="resize: vertical;"></textarea>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle me-1"></i>قدم شرحاً مفصلاً وواضحاً للأسباب الاقتصادية والإدارية
                </small>
                @error('justification')
                    <div class="alert alert-danger alert-sm mt-2 small">{{ $message }}</div>
                @enderror
            </div>

            <!-- Attachments Field -->
            <div class="mb-4">
                <label for="attachments-{{ $procedure->id }}" class="form-label fw-semibold">
                    <i class="fas fa-paperclip me-2"></i>المرفقات الداعمة
                </label>
                <input type="file" 
                       class="form-control d-none" 
                       id="attachments-{{ $procedure->id }}" 
                       name="attachments[]" 
                       multiple 
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                
                <div class="card border-dashed border-2 border-secondary bg-light p-4 cursor-pointer" 
                     onclick="document.getElementById('attachments-{{ $procedure->id }}').click();"
                     style="cursor: pointer; transition: all 0.3s ease;">
                    <div class="text-center">
                        <i class="fas fa-cloud-upload-alt fa-2x text-secondary mb-2"></i>
                        <p class="mb-1 text-dark fw-semibold">اضغط أو اسحب الملفات هنا</p>
                        <small class="text-muted">PDF، Word، Excel، الصور (اختياري)</small>
                    </div>
                </div>
                
                <!-- File Preview -->
                <div id="files-preview-{{ $procedure->id }}" class="mt-3"></div>
                @error('attachments')
                    <div class="alert alert-danger alert-sm mt-2 small">{{ $message }}</div>
                @enderror
            </div>

            <!-- Display Existing Justifications -->
            @if($procedure->procedureBudgetJustification)
                <hr class="my-4">
                
                <h6 class="border-bottom pb-2 mb-4">
                    <i class="fas fa-history me-2"></i>سجل التبريرات المالية
                </h6>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-calendar text-secondary me-1"></i>تاريخ الإضافة
                                    </small>
                                    <div class="fw-semibold">{{ $procedure->procedureBudgetJustification->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-user text-secondary me-1"></i>أضاف بواسطة
                                    </small>
                                    <div class="fw-semibold">{{ $procedure->procedureBudgetJustification->createdBy?->name ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block mb-2">الحالة</small>
                                <span class="badge px-3 py-2 bg-{{ $procedure->procedureBudgetJustification->approval_status === 'approved' ? 'success' : ($procedure->procedureBudgetJustification->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                                    @if($procedure->procedureBudgetJustification->approval_status === 'approved')
                                        <i class="fas fa-check me-1"></i>موافق عليه
                                    @elseif($procedure->procedureBudgetJustification->approval_status === 'rejected')
                                        <i class="fas fa-times me-1"></i>مرفوض
                                    @else
                                        <i class="fas fa-hourglass-half me-1"></i>قيد الانتظار
                                    @endif
                                </span>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <strong class="d-block text-dark mb-2">التبرير المالي:</strong>
                            <p class="text-muted lh-lg">{{ $procedure->procedureBudgetJustification->justification }}</p>
                        </div>

                        @if($procedure->procedureBudgetJustification->attachments && count($procedure->procedureBudgetJustification->attachments) > 0)
                            <div class="mt-3 pt-3 border-top">
                                <strong class="d-block text-dark mb-2">
                                    <i class="fas fa-file me-1 text-info"></i>المرفقات
                                </strong>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($procedure->procedureBudgetJustification->attachments as $attachment)
                                        <a href="{{ asset('storage/' . $attachment) }}" 
                                           class="btn btn-sm btn-outline-info text-decoration-none" 
                                           target="_blank">
                                            <i class="fas fa-download me-1"></i>{{ basename($attachment) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($procedure->procedureBudgetJustification->reviewed_by)
                            <hr class="my-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted d-block mb-1">مراجعة بواسطة:</small>
                                    <p class="small fw-semibold mb-2">{{ $procedure->procedureBudgetJustification->reviewedBy?->name ?? '-' }}</p>
                                    
                                    <small class="text-muted d-block mb-1">تاريخ المراجعة:</small>
                                    <p class="small fw-semibold">{{ $procedure->procedureBudgetJustification->reviewed_at?->format('d/m/Y H:i') ?? '-' }}</p>
                                </div>
                                @if($procedure->procedureBudgetJustification->reviewer_notes)
                                    <div class="col-md-6">
                                        <small class="text-muted d-block mb-1">ملاحظات المراجع:</small>
                                        <p class="small text-muted lh-lg">{{ $procedure->procedureBudgetJustification->reviewer_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Submit Buttons -->
            <div class="d-flex gap-3 justify-content-end mt-5 pt-3 border-top">
                <button type="button" 
                        class="btn btn-light border" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#financialJustificationCollapse-{{ $procedure->id }}">
                    <i class="fas fa-times me-2"></i>إلغاء
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check me-2"></i>حفظ التبرير المالي
                </button>
            </div>
        </form>
    @else
        <div class="alert alert-success border-0 bg-success bg-opacity-10 mb-4 p-4 rounded-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fs-3 me-3"></i>
                <div>
                    <h6 class="mb-1 fw-bold">الميزانية مكتملة وضمن الحدود</h6>
                    <p class="mb-0 small">المنصرف الفعلي لا يتجاوز المبلغ المخطط له. لا يتطلب الأمر تقديم تبرير مالي حالياً.</p>
                </div>
            </div>
        </div>

        @if($procedure->procedureBudgetJustification)
            <h6 class="border-bottom pb-2 mb-4">
                <i class="fas fa-history me-2"></i>سجل التبريرات المالية
            </h6>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">
                                    <i class="fas fa-calendar text-secondary me-1"></i>تاريخ الإضافة
                                </small>
                                <div class="fw-semibold">{{ $procedure->procedureBudgetJustification->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">
                                    <i class="fas fa-user text-secondary me-1"></i>أضاف بواسطة
                                </small>
                                <div class="fw-semibold">{{ $procedure->procedureBudgetJustification->createdBy?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-2">الحالة</small>
                            <span class="badge px-3 py-2 bg-{{ $procedure->procedureBudgetJustification->approval_status === 'approved' ? 'success' : ($procedure->procedureBudgetJustification->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                                @if($procedure->procedureBudgetJustification->approval_status === 'approved')
                                    <i class="fas fa-check me-1"></i>موافق عليه
                                @elseif($procedure->procedureBudgetJustification->approval_status === 'rejected')
                                    <i class="fas fa-times me-1"></i>مرفوض
                                @else
                                    <i class="fas fa-hourglass-half me-1"></i>قيد الانتظار
                                @endif
                            </span>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <strong class="d-block text-dark mb-2">التبرير المالي:</strong>
                        <p class="text-muted lh-lg">{{ $procedure->procedureBudgetJustification->justification }}</p>
                    </div>

                    @if($procedure->procedureBudgetJustification->attachments && count($procedure->procedureBudgetJustification->attachments) > 0)
                        <div class="mt-3 pt-3 border-top">
                            <strong class="d-block text-dark mb-2">
                                <i class="fas fa-file me-1 text-info"></i>المرفقات
                            </strong>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($procedure->procedureBudgetJustification->attachments as $attachment)
                                    <a href="{{ asset('storage/' . $attachment) }}" 
                                       class="btn btn-sm btn-outline-info text-decoration-none" 
                                       target="_blank">
                                        <i class="fas fa-download me-1"></i>{{ basename($attachment) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        <div class="d-flex justify-content-end">
            <button type="button" 
                    class="btn btn-secondary btn-sm" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#financialJustificationCollapse-{{ $procedure->id }}">
                <i class="fas fa-times me-1"></i>إغلاق
            </button>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('attachments-{{ $procedure->id }}');
    const previewContainer = document.getElementById('files-preview-{{ $procedure->id }}');
    const form = document.querySelector('.budget-justification-form');

    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            const files = e.target.files;

            if (files.length > 0) {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'mt-2';

                Array.from(files).forEach((file, index) => {
                    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-info text-white me-2 mb-2 py-2 px-3';
                    badge.innerHTML = `<i class="fas fa-file me-1"></i>${file.name} (${sizeMB} MB)`;
                    previewDiv.appendChild(badge);
                });

                previewContainer.appendChild(previewDiv);
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const justification = document.getElementById('justification-{{ $procedure->id }}').value;
            const attachmentsInput = document.getElementById('attachments-{{ $procedure->id }}');
            
            if (!justification.trim()) {
                alert('يرجى إدخال التبرير المالي');
                return;
            }
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.createElement('div');
                    notification.className = 'alert alert-success alert-dismissible fade show';
                    notification.setAttribute('role', 'alert');
                    notification.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>تم إضافة التبرير بنجاح!</strong>
                        <div class="mt-2">
                            <p class="mb-2"><strong>التفاصيل:</strong></p>
                            <ul class="mb-2">
                                <li>التبرير: تم حفظ التبرير المالي بنجاح</li>
                                ${attachmentsInput.files.length > 0 ? `<li>المرفقات: تم إضافة ${attachmentsInput.files.length} ملف(ات)</li>` : ''}
                            </ul>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    
                    form.parentElement.insertBefore(notification, form);
                    form.reset();
                    previewContainer.innerHTML = '';
                    
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('حدث خطأ: ' + (data.message || 'فشل حفظ التبرير'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء حفظ التبرير');
            });
        });
    }
});
</script>
