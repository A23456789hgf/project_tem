<!-- Procedure-Level Technical Justification Section -->
<div class="technical-justification-section" id="technical-justification-section-{{ $procedure->id }}" style="display: none;">
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
        $technicalJustification = $procedure->technicalJustification ?? null;
    @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0">
                <i class="fas fa-file-contract me-2 text-info"></i>التبرير التقني للإجراء
            </h6>
        </div>
        <div class="card-body">
            @if($technicalJustification)
                <!-- Display Mode -->
                <div class="mb-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">التاريخ المخطط</label>
                        <div class="p-2 bg-light rounded">
                            <strong>{{ $plannedEndDate ? \Carbon\Carbon::parse($plannedEndDate)->format('Y-m-d') : '-' }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">التاريخ الفعلي</label>
                        <div class="p-2 bg-light rounded">
                            <strong class="text-danger">{{ $technicalJustification->actual_end_date ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">مدة التأخير</label>
                        <div class="p-2 bg-warning bg-opacity-10 rounded border border-warning border-opacity-50">
                            <strong class="text-warning">{{ $technicalJustification->delay_days ?? 0 }} يوم</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">التبرير</label>
                        <div class="p-3 bg-light rounded">
                            <p>{{ $technicalJustification->justification }}</p>
                        </div>
                    </div>
                    @if($technicalJustification->attachments && count($technicalJustification->attachments) > 0)
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">المستندات المرفقة</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($technicalJustification->attachments as $attachment)
                                    <button type="button" class="btn btn-sm btn-outline-info view-doc"
                                            data-doc-path="{{ $attachment }}"
                                            data-doc-name="{{ basename($attachment) }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#fileViewerModal">
                                        <i class="fas fa-file me-1"></i> {{ basename($attachment) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="mt-3 pt-3 border-top">
                        <div class="row g-2">
                            <div class="col-auto">
                                <span class="badge bg-{{ $technicalJustification->approval_status === 'approved' ? 'success' : ($technicalJustification->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ $technicalJustification->approval_status === 'approved' ? 'موافق عليه' : ($technicalJustification->approval_status === 'rejected' ? 'مرفوض' : 'قيد الانتظار') }}
                                </span>
                            </div>
                            @if($technicalJustification->reviewer_notes)
                                <div class="col">
                                    <small class="text-muted">ملاحظات المراجع: {{ $technicalJustification->reviewer_notes }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <!-- Form Mode -->
                @can('projects.execution.storeProcedureTechnicalJustification')
                <form action="{{ route('projects.execution.storeProcedureTechnicalJustification', $project) }}" 
                @endcan
                      method="POST" 
                      enctype="multipart/form-data"
                      class="procedure-technical-justification-form"
                      data-procedure-id="{{ $procedure->id }}">
                    @csrf
                    <input type="hidden" name="preliminary_procedure_id" value="{{ $procedure->id }}">
                    <input type="hidden" name="planned_end_date" value="{{ $plannedEndDate }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">التاريخ المخطط</label>
                            <div class="p-3 bg-light rounded border">
                                <strong class="fs-5">{{ $plannedEndDate ? \Carbon\Carbon::parse($plannedEndDate)->format('Y-m-d') : '-' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">التاريخ الفعلي *</label>
                            <input type="date" class="form-control" name="actual_end_date">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-bold">التبرير التقني</label>
                        <textarea class="form-control" name="justification" rows="5" placeholder="يرجى إدخال تبرير شامل..."></textarea>
                        <small class="text-muted d-block mt-1">أسباب التأخير والعوامل التقنية المؤثرة</small>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-bold">المستندات المرفقة</label>
                        <input type="file" class="form-control procedure-technical-attachments" 
                               name="procedure_technical_attachments[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png" multiple>
                        <div class="mt-2" id="technical-file-list-{{ $procedure->id }}"></div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i> حفظ
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('technical-justification-section-{{ $procedure->id }}').style.display='none'">
                            <i class="fas fa-times me-1"></i> إلغاء
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
