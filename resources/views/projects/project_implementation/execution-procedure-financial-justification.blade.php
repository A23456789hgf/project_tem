<!-- Procedure-Level Financial Justification Section -->
<div class="financial-justification-section" id="financial-justification-section-{{ $procedure->id }}" style="display: none;">
    <div class="alert alert-warning border-2 border-warning rounded mt-3 mb-3">
        <div class="d-flex align-items-center mb-2">
            <i class="fas fa-exclamation-triangle fs-5 text-warning me-2"></i>
            <strong>تنبيه: تجاوز الميزانية</strong>
        </div>
        <p class="mb-2 text-muted">
            المبلغ الفعلي المنفق يتجاوز المبلغ المخطط. يرجى تقديم تبرير مالي شامل يوضح أسباب التجاوز.
        </p>
    </div>

    @php
        $budgetJustification = $procedure->budgetJustification;
    @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0">
                <i class="fas fa-file-invoice-dollar me-2 text-info"></i>التبرير المالي للإجراء
            </h6>
        </div>
        <div class="card-body">
            @if($budgetJustification)
                <!-- Display Mode -->
                <div class="mb-3">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">المبلغ المخطط</label>
                            <div class="p-2 bg-light rounded">
                                <strong>{{ number_format($budgetJustification->planned_total, 2) }} ﷼</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">المبلغ الفعلي</label>
                            <div class="p-2 bg-light rounded">
                                <strong class="text-danger">{{ number_format($budgetJustification->actual_total, 2) }} ﷼</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">مبلغ التجاوز</label>
                        <div class="p-2 bg-danger bg-opacity-10 rounded border border-danger border-opacity-50">
                            <strong class="text-danger">{{ number_format($budgetJustification->overage_amount, 2) }} ﷼</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">التبرير</label>
                        <div class="p-3 bg-light rounded">
                            <p>{{ $budgetJustification->justification }}</p>
                        </div>
                    </div>
                    @if($budgetJustification->attachments && count($budgetJustification->attachments) > 0)
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">المستندات المرفقة</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($budgetJustification->attachments as $attachment)
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
                                <span class="badge bg-{{ $budgetJustification->approval_status === 'approved' ? 'success' : ($budgetJustification->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ $budgetJustification->approval_status === 'approved' ? 'موافق عليه' : ($budgetJustification->approval_status === 'rejected' ? 'مرفوض' : 'قيد الانتظار') }}
                                </span>
                            </div>
                            @if($budgetJustification->reviewer_notes)
                                <div class="col">
                                    <small class="text-muted">ملاحظات المراجع: {{ $budgetJustification->reviewer_notes }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <!-- Form Mode -->
                @can('projects.execution.storeProcedureBudgetJustification')
                <form action="{{ route('projects.execution.storeProcedureBudgetJustification', $project) }}" 
                @endcan
                      method="POST" 
                      enctype="multipart/form-data"
                      class="procedure-financial-justification-form"
                      data-procedure-id="{{ $procedure->id }}">
                    @csrf
                    <input type="hidden" name="preliminary_procedure_id" value="{{ $procedure->id }}">
                    <input type="hidden" name="planned_total" value="{{ $totalPlanned }}">
                    <input type="hidden" name="actual_total" value="{{ $totalSpent }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">المبلغ المخطط</label>
                            <div class="p-3 bg-light rounded border">
                                <strong class="fs-5">{{ number_format($totalPlanned, 2) }} ﷼</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">المبلغ الفعلي</label>
                            <div class="p-3 bg-danger bg-opacity-10 rounded border border-danger">
                                <strong class="fs-5 text-danger">{{ number_format($totalSpent, 2) }} ﷼</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-bold">التبرير المالي</label>
                        <textarea class="form-control" name="justification" rows="5" placeholder="يرجى إدخال تبرير شامل..."></textarea>
                        <small class="text-muted d-block mt-1">أسباب التجاوز والمبررات الاقتصادية</small>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-bold">المستندات المرفقة</label>
                        <input type="file" class="form-control procedure-financial-attachments" 
                               name="procedure_financial_attachments[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png" multiple>
                        <div class="mt-2" id="financial-file-list-{{ $procedure->id }}"></div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i> حفظ
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('financial-justification-section-{{ $procedure->id }}').style.display='none'">
                            <i class="fas fa-times me-1"></i> إلغاء
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
