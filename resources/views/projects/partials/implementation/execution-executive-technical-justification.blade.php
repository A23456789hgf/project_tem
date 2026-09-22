<!-- Executive Action-Level Technical Justification Section -->
<div class="technical-justification-section" id="technical-justification-section-{{ $action->id }}" data-has-justifications="{{ ($action->technicalJustifications && $action->technicalJustifications->count() > 0) ? 'true' : 'false' }}">
    <div class="alert alert-info border-2 border-info rounded mt-3 mb-3">
        <div class="d-flex align-items-center mb-2">
            <i class="fas fa-hourglass-end fs-5 text-info me-2"></i>
            <strong>تنبيه: تجاوز الجدول الزمني</strong>
        </div>
        <p class="mb-2 text-muted">
            التنفيذ الفعلي تجاوز الموعد المخطط. يرجى تقديم تبرير تقني شامل يوضح أسباب التأخير.
        </p>
    </div>

    @php
        $technicalJustifications = $action->technicalJustifications ?? collect([]);
        $latestJustification = $technicalJustifications->first();
        $plannedEndDate = $action->end_date_gregorian ? $action->end_date_gregorian->format('Y-m-d') : null;
        
        // Recalculate overage for Blade logic
        $latestExecution = $action->executions?->sortByDesc('actual_finish_date_gregorian')->first();
        $isOverTime = $latestExecution && $action->end_date_gregorian && $latestExecution->actual_finish_date_gregorian > $action->end_date_gregorian;
    @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0">
                <i class="fas fa-file-contract me-2 text-info"></i>التبرير التقني للإجراء
            </h6>
        </div>
        <div class="card-body">
            @if($technicalJustifications->count() === 0)
                @if($isOverTime)
                    <!-- Form Mode (First Justification) -->
                    @can('projects.execution.storeExecutiveTechnicalJustification')
                    <form action="{{ route('projects.execution.storeExecutiveTechnicalJustification', $project) }}" 
                    @endcan
                          method="POST" 
                          enctype="multipart/form-data"
                          class="executive-technical-justification-form"
                          data-action-id="{{ $action->id }}">
                        @csrf
                        <input type="hidden" name="executive_activity_action_id" value="{{ $action->id }}">
                        <input type="hidden" name="planned_end_date" value="{{ $plannedEndDate }}">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">تاريخ البداية المخطط</label>
                                <input type="text" class="form-control bg-light" name="planned_start_date_display" id="exec-planned-start-date-{{ $action->id }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">تاريخ النهاية المخطط</label>
                                <input type="text" class="form-control bg-light" name="planned_end_date_display" id="exec-planned-end-date-{{ $action->id }}" value="{{ $plannedEndDate }}" readonly>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">تاريخ البداية الفعلي</label>
                                <input type="text" class="form-control bg-light" name="actual_start_date_display" id="exec-actual-start-date-{{ $action->id }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">تاريخ النهاية الفعلي *</label>
                                <input type="date" class="form-control check-date-overage" name="actual_end_date" id="exec-actual-end-date-{{ $action->id }}" data-planned-date="{{ $plannedEndDate }}" data-target-container="justification-fields-{{ $action->id }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-danger">مدة التأخير (أيام)</label>
                            <input type="text" class="form-control bg-danger bg-opacity-10 text-danger fw-bold" name="delay_days_display" id="exec-delay-days-{{ $action->id }}" readonly>
                        </div>

                        <div id="justification-fields-{{ $action->id }}" style="display: none;">
                            <div class="mt-3">
                                <label class="form-label fw-bold">التبرير التقني</label>
                                <textarea class="form-control" name="justification" rows="5" placeholder="يرجى إدخال تبرير شامل..."></textarea>
                                <small class="text-muted d-block mt-1">أسباب التأخير والعوامل التقنية المؤثرة</small>
                            </div>

                            <div class="mt-3">
                                <label class="form-label fw-bold">المستندات المرفقة</label>
                                <input type="file" class="form-control executive-technical-attachments" 
                                       name="executive_technical_attachments[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png" multiple>
                                <div class="mt-2" id="technical-file-list-{{ $action->id }}"></div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save me-1"></i> حفظ
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('technical-justification-section-{{ $action->id }}').style.display='none'">
                                <i class="fas fa-times me-1"></i> إلغاء
                            </button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-success border-0 bg-success bg-opacity-10 mb-0">
                        <i class="fas fa-check-circle me-2"></i>التنفيذ حالياً ضمن الجدول الزمني المخطط له.
                    </div>
                @endif
            @endif
            
            <!-- Technical Justifications Log -->
            @if($technicalJustifications->count() > 0)
                <hr class="my-3">
                
                <h6 class="border-bottom pb-2 mb-3">
                    <i class="fas fa-history me-2"></i>سجل التبريرات التقنية
                </h6>
                
                @foreach($technicalJustifications as $justification)
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-4">
                            <!-- Header Row -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-calendar text-secondary me-1"></i>تاريخ الإضافة
                                        </small>
                                        <div class="fw-semibold small">{{ $justification->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">
                                            <i class="fas fa-user text-secondary me-1"></i>أضاف بواسطة
                                        </small>
                                        <div class="fw-semibold small">{{ $justification->createdBy?->name ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block mb-2">الحالة</small>
                                    <span class="badge px-3 py-2 bg-{{ $justification->approval_status === 'approved' ? 'success' : ($justification->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                                        @if($justification->approval_status === 'approved')
                                            <i class="fas fa-check me-1"></i>موافق عليه
                                        @elseif($justification->approval_status === 'rejected')
                                            <i class="fas fa-times me-1"></i>مرفوض
                                        @else
                                            <i class="fas fa-hourglass-half me-1"></i>قيد الانتظار
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <hr class="my-2">

                            <!-- Justification Text -->
                            <div class="mb-3">
                                <strong class="d-block text-dark mb-2 small">التبرير التقني:</strong>
                                <p class="text-muted lh-lg small">{{ $justification->explanation }}</p>
                            </div>

                            <!-- Attachments -->
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong class="d-block text-dark small">
                                        <i class="fas fa-file me-1 text-info"></i>المرفقات
                                    </strong>
                                    @if($justification->approval_status !== 'approved')
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#addAttachments-exec-{{ $justification->id }}">
                                            <i class="fas fa-plus me-1"></i>إضافة مرفقات
                                        </button>
                                    @endif
                                </div>
                                
                                @if($justification->attachments && count($justification->attachments) > 0)
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        @foreach($justification->attachments as $attachment)
                                            <a href="{{ asset('storage/' . $attachment) }}" 
                                               class="btn btn-sm btn-outline-info text-decoration-none" 
                                               target="_blank">
                                                <i class="fas fa-download me-1"></i>{{ basename($attachment) }}
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small mb-3">لا توجد مرفقات</p>
                                @endif
                                
                                <!-- Add More Attachments Form -->
                                @if($justification->approval_status !== 'approved')
                                    <div class="collapse" id="addAttachments-exec-{{ $justification->id }}">
                                        @can('projects.execution.storeExecutiveTechnicalJustification')
                                        <form action="{{ route('projects.execution.storeExecutiveTechnicalJustification', ['project' => $project]) }}" 
                                              method="POST" 
                                              enctype="multipart/form-data"
                                              class="add-attachments-form">
                                            @csrf
                                            <input type="hidden" name="executive_activity_action_id" value="{{ $action->id }}">
                                            <input type="hidden" name="planned_end_date" value="{{ $action->end_date_gregorian?->format('Y-m-d') }}">
                                            <input type="hidden" name="actual_end_date" value="{{ $justification->updated_at?->format('Y-m-d') }}">
                                            <input type="hidden" name="justification" value="{{ $justification->explanation }}">
                                            
                                            <div class="mt-2 p-3 bg-light rounded border">
                                                <label class="form-label small fw-bold">اختر الملفات الإضافية</label>
                                                <input type="file" class="form-control" 
                                                       name="executive_technical_attachments[]" 
                                                       multiple 
                                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                                <small class="text-muted d-block mt-2">PDF، Word، Excel، الصور (الحد الأقصى 10 MB لكل ملف)</small>
                                                <div class="mt-2 d-flex gap-2">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-upload me-1"></i>إضافة
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-secondary" 
                                                            data-bs-toggle="collapse" 
                                                            data-bs-target="#addAttachments-exec-{{ $justification->id }}">
                                                        <i class="fas fa-times me-1"></i>إلغاء
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        @endcan
                                    </div>
                                @endif
                            </div>

                            <!-- Reviewer Info -->
                            @if($justification->reviewed_by)
                                <hr class="my-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block mb-1">مراجعة بواسطة:</small>
                                        <p class="small fw-semibold mb-2">{{ $justification->reviewedBy?->name ?? '-' }}</p>
                                        
                                        <small class="text-muted d-block mb-1">تاريخ المراجعة:</small>
                                        <p class="small fw-semibold">{{ $justification->reviewed_at?->format('d/m/Y H:i') ?? '-' }}</p>
                                    </div>
                                    @if($justification->reviewer_notes)
                                        <div class="col-md-6">
                                            <small class="text-muted d-block mb-1">ملاحظات المراجع:</small>
                                            <p class="small text-muted lh-lg">{{ $justification->reviewer_notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-plus me-2"></i>تبريرات إضافية
                    </h6>
                    @if($isOverTime)
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addNewExecutiveJustification-{{ $action->id }}">
                            <i class="fas fa-plus me-1"></i>إضافة تبرير جديد
                        </button>
                    @endif
                </div>
                
                @if($isOverTime)
                    <div class="collapse mt-3" id="addNewExecutiveJustification-{{ $action->id }}">
                        @can('projects.execution.storeExecutiveTechnicalJustification')
                        <form action="{{ route('projects.execution.storeExecutiveTechnicalJustification', $project) }}" 
                        @endcan
                              method="POST" 
                              enctype="multipart/form-data"
                              class="executive-technical-justification-form"
                              data-action-id="{{ $action->id }}">
                            @csrf
                            <input type="hidden" name="executive_activity_action_id" value="{{ $action->id }}">
                            <input type="hidden" name="planned_end_date" value="{{ $plannedEndDate }}">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">التاريخ المخطط</label>
                                    <div class="p-3 bg-light rounded border">
                                        <strong class="fs-5">{{ $plannedEndDate ?? '-' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">التاريخ الفعلي *</label>
                                    <input type="date" class="form-control check-date-overage" name="actual_end_date" data-planned-date="{{ $plannedEndDate }}" data-target-container="justification-fields-new-{{ $action->id }}">
                                </div>
                            </div>

                            <div id="justification-fields-new-{{ $action->id }}" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">التبرير التقني</label>
                                    <textarea class="form-control" name="justification" rows="4" placeholder="يرجى إدخال تبرير شامل..."></textarea>
                                    <small class="text-muted d-block mt-1">أسباب التأخير والعوامل التقنية المؤثرة</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">المستندات المرفقة</label>
                                    <input type="file" class="form-control executive-technical-attachments" 
                                           name="executive_technical_attachments[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png" multiple>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save me-1"></i> حفظ
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#addNewExecutiveJustification-{{ $action->id }}">
                                    <i class="fas fa-times me-1"></i> إلغاء
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="alert alert-success border-0 bg-success bg-opacity-10 mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>الجدول الزمني حالياً ضمن الحدود المخطط لها. يمكنك مراجعة السجل أعلاه.
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
