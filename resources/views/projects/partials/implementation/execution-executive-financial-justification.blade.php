<!-- Financial Justification Section (Executive) -->
<div class="card border-0 bg-white shadow-lg mb-4" style="border-radius: 12px; overflow: hidden;">
    @php
        $difference = $totalSpent - $totalPlanned;
        $hasOverage = $difference > 0;
    @endphp

    <!-- Header with Gradient (Matching Preliminary) -->
    <div class="bg-gradient" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 30px 20px; color: white;">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1" style="font-weight: 700;">
                    <i class="fas fa-money-bill-wave me-2"></i>التبريرات المالية (الأنشطة التنفيذية)
                </h5>
                <p class="mb-0 small" style="opacity: 0.9;">ملخص الميزانية والتكاليف الفعلية للإجراء: {{ $action->action }}</p>
            </div>
            <i class="fas fa-chart-line fa-3x" style="opacity: 0.2;"></i>
        </div>
    </div>

    <div class="card-body p-4">
        <!-- Financial Summary Cards -->
        <div class="row mb-5 g-3">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #11998e; background: linear-gradient(135deg, #f5fffb 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: #11998e; letter-spacing: 0.5px;">المبلغ المخطط</span>
                            <div class="bg-success bg-opacity-10 rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-coins" style="color: #11998e; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div style="font-size: 1.8rem; font-weight: 700; color: #11998e;">{{ number_format($totalPlanned, 2) }}</div>
                        <small class="text-muted d-block mt-2">﷼ (ريال سعودي)</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #38ef7d; background: linear-gradient(135deg, #f5fff8 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: #38ef7d; letter-spacing: 0.5px;">المنصرف الفعلي</span>
                            <div class="bg-success bg-opacity-10 rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-money-bill-wave" style="color: #38ef7d; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div style="font-size: 1.8rem; font-weight: 700; color: #38ef7d;">{{ number_format($totalSpent, 2) }}</div>
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
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffbc00; background: linear-gradient(135deg, #fffcf5 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: #ffbc00; letter-spacing: 0.5px;">نسبة الإنفاق</span>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-percentage" style="color: #ffbc00; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div style="font-size: 1.8rem; font-weight: 700; color: #ffbc00;">{{ number_format(($totalSpent / max($totalPlanned, 1)) * 100, 1) }}%</div>
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
                            <i class="fas fa-exclamation-circle me-2"></i>تنبيه: تجاوز الميزانية المخططة
                        </h6>
                        <p class="mb-0 text-dark" style="line-height: 1.6;">
                            تم تجاوز الموازنة المرصودة لهذا الإجراء بمبلغ <strong style="color: #dc3545; font-size: 1.1rem;">{{ number_format($difference, 2) }} ﷼</strong> 
                            <br><small class="text-muted">نظام الرقابة المالية يتطلب تقديم تبرير مالي معتمد يوضح أسباب هذا التجاوز للمراجعة.</small>
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="flex-shrink: 0;"></button>
                </div>
            </div>
        @endif

        <hr class="my-4" style="border-top: 2px dashed #e0e0e0;">

        <!-- Form Section -->
        @if($hasOverage)
            @can('projects.execution.storeExecutiveBudgetJustification')
            <form action="{{ route('projects.execution.storeExecutiveBudgetJustification', ['project' => $project]) }}" 
            @endcan
                  method="POST" 
                  enctype="multipart/form-data"
                  class="budget-justification-form-exec"
                  id="budget-justification-form-exec-{{ $action->id }}">
                @csrf
                <input type="hidden" name="executive_activity_action_id" value="{{ $action->id }}">
                <input type="hidden" name="planned_amount" value="{{ $totalPlanned }}">
                <input type="hidden" name="actual_amount" value="{{ $totalSpent }}">

                <h6 class="border-bottom pb-2 mb-4 fw-bold">
                    <i class="fas fa-file-signature me-2 text-primary"></i>تقديم التبرير المالي للإجراء
                </h6>

                <!-- Justification Field -->
                <div class="mb-4">
                    <label for="justification-exec-{{ $action->id }}" class="form-label fw-semibold">
                        نص التبرير المالي 
                    </label>
                    <textarea class="form-control form-control-lg border-2" 
                              id="justification-exec-{{ $action->id }}" 
                              name="justification" 
                              rows="5" 
                              placeholder="يرجى كتابة أسباب الزيادة بالتفصيل..." style="resize: vertical;"></textarea>
                    <div class="form-text text-muted mt-2">
                        <i class="fas fa-info-circle me-1"></i>يجب أن يتضمن التبرير الأسباب التشغيلية أو التغيرات في الأسعار أو الظروف الطارئة.
                    </div>
                </div>

                <!-- Attachments Field -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-folder-open me-2 text-warning"></i>المرفقات والوثائق الداعمة
                    </label>
                    <input type="file" 
                           class="form-control d-none executive-financial-attachment-input" 
                           id="attachments-exec-{{ $action->id }}" 
                           name="executive_financial_attachments[]" 
                           multiple 
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                           data-preview-id="files-preview-exec-{{ $action->id }}">
                    
                    <div class="card border-dashed border-2 border-primary bg-light p-4 text-center cursor-pointer hover-shadow" 
                         onclick="document.getElementById('attachments-exec-{{ $action->id }}').click();"
                         style="cursor: pointer; border-style: dashed !important;">
                        <div>
                            <i class="fas fa-upload fa-2xl text-primary mb-3"></i>
                            <h6 class="mb-1 text-dark fw-bold">اختر الملفات أو قم بسحبها هنا</h6>
                            <p class="mb-0 small text-muted">يمكنك رفع الفواتير، عروض الأسعار، أو أي مستندات رسمية</p>
                        </div>
                    </div>
                    
                    <!-- File Preview -->
                    <div id="files-preview-exec-{{ $action->id }}" class="mt-3 d-flex flex-wrap gap-2"></div>
                </div>

                <!-- Display Existing Justifications -->
                @if($action->budgetJustification)
                    <div class="card border-0 shadow-sm bg-light mt-5">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                            <i class="fas fa-history text-primary me-2"></i>
                            <h6 class="mb-0 fw-bold">آخر تبرير مالي مسجل</h6>
                            <span class="ms-auto badge bg-{{ $action->budgetJustification->approval_status === 'approved' ? 'success' : ($action->budgetJustification->approval_status === 'rejected' ? 'danger' : 'warning') }} px-3 py-2">
                                @if($action->budgetJustification->approval_status === 'approved')
                                    مقبول
                                @elseif($action->budgetJustification->approval_status === 'rejected')
                                    مرفوض
                                @else
                                    قيد المراجعة
                                @endif
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <p class="text-muted lh-lg mb-0" style="white-space: pre-wrap;">{{ $action->budgetJustification->justification }}</p>
                            </div>

                            @if($action->budgetJustification->attachments && count($action->budgetJustification->attachments) > 0)
                                <div class="attachments-list p-3 bg-white rounded border">
                                    <h6 class="small fw-bold text-dark mb-3">المرفقات المسجلة:</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($action->budgetJustification->attachments as $attachment)
                                            <a href="{{ asset('storage/' . $attachment) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               target="_blank">
                                                <i class="fas fa-file-download me-1"></i>{{ basename($attachment) }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                                <div class="small">
                                    <span class="text-muted">بواسطة:</span> <span class="fw-bold">{{ $action->budgetJustification->createdBy?->name ?? 'نظام' }}</span>
                                    <span class="text-muted ms-3">تاريخ:</span> <span class="fw-bold">{{ $action->budgetJustification->created_at?->format('Y-m-d H:i') }}</span>
                                </div>
                                <i class="fas fa-quote-right fa-2x text-light"></i>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Submit Buttons -->
                <div class="d-flex gap-2 justify-content-end mt-5 pt-3 border-top">
                    <button type="button" 
                            class="btn btn-outline-secondary px-4" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#financialJustificationCollapse-{{ $action->id }}">
                        <i class="fas fa-times me-2"></i>إغلاق
                    </button>
                    <button type="submit" class="btn btn-success px-5 save-budget-justification-exec-btn">
                        <i class="fas fa-save me-2"></i>حفظ وإرسال التبرير
                    </button>
                </div>
            </form>
        @else
            <!-- No Overage State -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="fas fa-check-double fa-3x text-success"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark">الميزانية مكتملة وبحالة جيدة</h5>
                <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
                    لا يوجد أي تجاوز مالي لهذا الإجراء حالياً. إجمالي المنصرف ({{ number_format($totalSpent, 2) }} ﷼) ضمن حدود الميزانية المخططة ({{ number_format($totalPlanned, 2) }} ﷼).
                </p>
                
                @if($action->budgetJustification)
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-sm btn-outline-primary px-4" type="button" data-bs-toggle="collapse" data-bs-target="#history-justification-{{ $action->id }}">
                             عرض التبريرات السابقة <i class="fas fa-chevron-down ms-1 small"></i>
                        </button>
                    </div>
                    
                    <div class="collapse mt-4 text-start" id="history-justification-{{ $action->id }}">
                         <div class="card border-0 shadow-sm bg-light">
                             <div class="card-body">
                                 <h6 class="fw-bold mb-3 small">تاريخ التبريرات:</h6>
                                 <p class="text-muted small mb-3">{{ $action->budgetJustification->justification }}</p>
                                 <div class="small text-muted">تم التسجيل بواسطة {{ $action->budgetJustification->createdBy?->name }} في {{ $action->budgetJustification->created_at?->format('Y-m-d') }}</div>
                             </div>
                         </div>
                    </div>
                @endif
                
                <div class="mt-4 pt-4 border-top">
                    <button type="button" 
                            class="btn btn-secondary px-4" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#financialJustificationCollapse-{{ $action->id }}">
                        إغلاق النافذة
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
(function() {
    const actionId = "{{ $action->id }}";
    const form = document.getElementById('budget-justification-form-exec-' + actionId);
    const fileInput = document.getElementById('attachments-exec-' + actionId);
    const previewContainer = document.getElementById('files-preview-exec-' + actionId);

    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            Array.from(e.target.files).forEach(file => {
                const size = (file.size / 1024 / 1024).toFixed(2);
                const badge = document.createElement('div');
                badge.className = 'badge bg-white text-dark border shadow-sm p-2 d-flex align-items-center';
                badge.innerHTML = `
                    <i class="fas fa-file-pdf text-danger me-2"></i>
                    <div class="text-start">
                        <div class="fw-bold small text-truncate" style="max-width: 150px;">${file.name}</div>
                        <div class="x-small text-muted" style="font-size: 10px;">${size} MB</div>
                    </div>
                `;
                previewContainer.appendChild(badge);
            });
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = form.querySelector('.save-budget-justification-exec-btn');
            const originalHtml = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري الحفظ...';
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    flasher.success(data.message || 'تم حفظ التبرير بنجاح');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    flasher.error(data.message || 'حدث خطأ أثناء حفظ التبرير');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                flasher.error('فشل الاتصال بالخادم');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });
    }
})();
</script>