@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            {{-- Header --}}
            <div class="card mb-4" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; border: none; border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="mb-1 fw-bold"><i class="fas fa-dollar-sign me-2"></i>المراجعة المالية للمشروع</h3>
                            <p class="mb-0 text-white-50">مراجعة البيانات المالية، التكاليف، وإمكانية إضافة مستندات وملاحظات المراجعة</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-white text-dark shadow-sm fs-6 px-3 py-2">
                                <i class="fas fa-barcode me-1 text-info"></i>
                                {{ $project->form_number ?? 'بدون رقم' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Project Basic Details Card --}}
            <div class="card mb-4 border-0 shadow-sm rounded-3">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-info-circle me-2"></i>معلومات المشروع الأساسية</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <span class="text-muted d-block small">اسم المشروع:</span>
                            <strong class="fs-6 text-dark">{{ $project->project_name }}</strong>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <span class="text-muted d-block small">البرنامج:</span>
                            <span class="fw-semibold">{{ optional($project->program)->name ?? 'ـ' }}</span>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <span class="text-muted d-block small">المجال / الفرعي:</span>
                            <span class="fw-semibold">{{ optional($project->domain)->name ?? 'ـ' }} / {{ optional($project->subdomain)->name ?? 'ـ' }}</span>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <span class="text-muted d-block small">المرحلة الحالية:</span>
                            <span class="badge bg-primary px-2 py-1">{{ $project->currentApprovalStage->name_ar ?? 'المراجعة المالية والفنية' }}</span>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <span class="text-muted d-block small">حالة المشروع:</span>
                            <span class="badge bg-info px-2 py-1">{{ $project->status }}</span>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <span class="text-muted d-block small">التكلفة الإجمالية للمشروع:</span>
                            <span class="fw-bold text-success fs-6">{{ number_format($project->total_cost ?? $project->cost?->total_cost ?? 0, 2) }} ريال</span>
                        </div>
                        @if($project->supervisingAuthorities->count() > 0)
                        <div class="col-md-6">
                            <span class="text-muted d-block small">الجهات الإشرافية:</span>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($project->supervisingAuthorities as $auth)
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">{{ $auth->name_ar }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if($project->implementingEntities->count() > 0)
                        <div class="col-md-6">
                            <span class="text-muted d-block small">الجهات المنفذة:</span>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($project->implementingEntities as $entity)
                                    <span class="badge bg-primary-subtle text-primary border px-2 py-1">{{ $entity->name_ar }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Technical Review Summary (Read-only Preview) --}}
            @if(isset($approval) && $approval)
            <div class="card mb-4 border-start border-4 border-purple shadow-sm" style="border-left-color: #6f42c1 !important;">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-dark fw-bold">
                        <i class="fas fa-tools me-2 text-purple" style="color: #6f42c1;"></i>نتائج وتوصيات المراجعة الفنية
                    </h5>
                    <div>
                        @if($approval->technical_review_status === 'approved')
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>تمت المراجعة الفنية (موافقة)</span>
                        @elseif($approval->technical_review_status === 'need_action')
                            <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle me-1"></i>مطلوب إجراء فني</span>
                        @elseif($approval->technical_review_status === 'rejected')
                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>مرفوضة فنيًا</span>
                        @else
                            <span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>بانتظار الاعتماد الفني</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-user-check me-1 text-muted"></i>المراجع الفني:</strong> 
                            {{ $approval->technicalReviewer->name ?? 'غير محدد بعد' }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-calendar-alt me-1 text-muted"></i>تاريخ المراجعة الفنية:</strong> 
                            {{ $approval->technical_reviewed_at ? $approval->technical_reviewed_at->format('Y-m-d H:i') : 'ـ' }}
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-comment-dots me-1 text-muted"></i>ملاحظات المراجع الفني:</strong>
                        <div class="p-3 bg-light rounded mt-1 border text-dark" style="min-height: 50px;">
                            {{ $approval->technical_notes ?: 'لا توجد ملاحظات فنية مضافة حتى الآن.' }}
                        </div>
                    </div>

                    @if($approval->technical_attachment)
                    <div>
                        <strong><i class="fas fa-paperclip me-1 text-muted"></i>مرفق المراجعة الفنية:</strong>
                        <a href="{{ Storage::url($approval->technical_attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                            <i class="fas fa-download me-1"></i>عرض المرفق الفني
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Main Form for Financial Review & Cost Editing & File Upload --}}
            @can('reviews.financial')
            <form method="POST" action="{{ route('projects.review.financial.submit', $project) }}" enctype="multipart/form-data">
            @endcan
                @csrf

                {{-- Policy Notice Banner --}}
                <div class="alert border-0 p-3 rounded-3 mb-4 shadow-sm" style="background-color: #e3f7fa; border-right: 4px solid #17a2b8 !important;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fs-4 me-3 text-info"></i>
                        <div>
                            <strong class="d-block text-dark">ضوابط المراجعة المالية:</strong>
                            <span class="text-muted small">
                                يُسمح لك بتعديل التكاليف المالية المرتبطة بالأنشطة والإجراءات التمهيدية والتنفيذية (الكميات وأسعار الوحدات). 
                                <span class="fw-bold text-danger"><i class="fas fa-lock me-1"></i>البيانات الفنية ومحتوى الأنشطة والإجراءات مخصصة للعرض فقط ولا يُسمح بتعديلها في المراجعة المالية.</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Preliminary Activities & Costs Section --}}
                @if($project->preliminaryActivities->count() > 0)
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>الأنشطة والإجراءات التمهيدية والتكاليف المالـية</h5>
                            <span class="badge bg-white text-dark small"><i class="fas fa-lock me-1 text-secondary"></i>البيانات الفنية للعرض فقط</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach($project->preliminaryActivities as $activity)
                            <div class="mb-4 p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                    <h6 class="fw-bold text-info mb-0">
                                        <i class="fas fa-tasks me-1"></i> النشاط التمهيدي: {{ $activity->name ?? $activity->activity }}
                                    </h6>
                                </div>
                                @include('projects.review.partials.item-notes', ['model' => $activity])
                                @if($activity->procedures->count() > 0)
                                    @foreach($activity->procedures as $procedure)
                                        <div class="ms-3 mb-3 p-3 bg-white border rounded">
                                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                                <strong class="text-dark"><i class="fas fa-angle-left me-1 text-info"></i> الإجراء: {{ $procedure->procedure }}</strong>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-secondary-subtle text-secondary border">
                                                        {{ $procedure->start_date }} إلى {{ $procedure->end_date }} ({{ $procedure->duration_days }} يوم)
                                                    </span>
                                                </div>
                                            </div>
                                            @include('projects.review.partials.item-notes', ['model' => $procedure])
                                            @if($procedure->costs->count() > 0)
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-bordered table-hover table-sm mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>البند المالي</th>
                                                                <th style="width: 130px;">الكمية</th>
                                                                <th style="width: 150px;">سعر الوحدة</th>
                                                                <th>التكلفة الإجمالية (ريال)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($procedure->costs as $cost)
                                                            <tr>
                                                                <td>{{ $cost->financialItem->name ?? 'بند غير محدد' }}</td>
                                                                <td>
                                                                    <input type="number" 
                                                                           name="preliminary_costs[{{ $cost->id }}][quantity]" 
                                                                           class="form-control form-control-sm cost-quantity" 
                                                                           value="{{ $cost->quantity }}"
                                                                           min="0"
                                                                           data-cost-id="{{ $cost->id }}">
                                                                </td>
                                                                <td>
                                                                    <input type="number" 
                                                                           name="preliminary_costs[{{ $cost->id }}][amount]" 
                                                                           class="form-control form-control-sm cost-unit-price" 
                                                                           value="{{ $cost->unit_price }}"
                                                                           min="0"
                                                                           step="0.01"
                                                                           data-cost-id="{{ $cost->id }}">
                                                                </td>
                                                                <td class="total-cost-{{ $cost->id }} fw-bold text-success">
                                                                    {{ number_format($cost->total_cost, 2) }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="4" class="p-2 border-0 bg-light">
                                                                    @include('projects.review.partials.item-notes', ['model' => $cost])
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>لا توجد تكاليف مالية مسجلة لهذا الإجراء.</p>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted small ms-3 mb-0">لا توجد إجراءات مسجلة لهذا النشاط.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Executive Activities & Costs Section --}}
                @if($project->executiveActivities->count() > 0)
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: white;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-running me-2"></i>الأنشطة والإجراءات التنفيذية والتكاليف المالـية</h5>
                            <span class="badge bg-white text-dark small"><i class="fas fa-edit me-1 text-info"></i>تعديل التكاليف</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach($project->executiveActivities as $activity)
                            <div class="mb-4 p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                    <h6 class="fw-bold style-purple mb-0" style="color: #6f42c1;">
                                        <i class="fas fa-running me-1"></i> النشاط التنفيذي: {{ $activity->activity_name ?? $activity->name }}
                                    </h6>
                                </div>
                                @include('projects.review.partials.item-notes', ['model' => $activity])
                                @if($activity->actions->count() > 0)
                                    @foreach($activity->actions as $action)
                                        <div class="ms-3 mb-3 p-3 bg-white border rounded">
                                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                                <strong class="text-dark"><i class="fas fa-angle-left me-1" style="color: #6f42c1;"></i> الإجراء التنفيذي: {{ $action->action_name }}</strong>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-secondary-subtle text-secondary border">
                                                        {{ $action->start_date }} إلى {{ $action->end_date }}
                                                    </span>
                                                </div>
                                            </div>
                                            @include('projects.review.partials.item-notes', ['model' => $action])
                                            @if($action->costs->count() > 0)
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-bordered table-hover table-sm mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>البند المالي</th>
                                                                <th style="width: 130px;">الكمية</th>
                                                                <th style="width: 150px;">سعر الوحدة</th>
                                                                <th>التكلفة الإجمالية (ريال)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($action->costs as $cost)
                                                            <tr>
                                                                <td>{{ $cost->financialItem->name ?? 'بند غير محدد' }}</td>
                                                                <td>
                                                                    <input type="number" 
                                                                           name="executive_costs[{{ $cost->id }}][quantity]" 
                                                                           class="form-control form-control-sm cost-quantity" 
                                                                           value="{{ $cost->quantity }}"
                                                                           min="0"
                                                                           data-cost-id="{{ $cost->id }}">
                                                                </td>
                                                                <td>
                                                                    <input type="number" 
                                                                           name="executive_costs[{{ $cost->id }}][amount]" 
                                                                           class="form-control form-control-sm cost-unit-price" 
                                                                           value="{{ $cost->unit_price }}"
                                                                           min="0"
                                                                           step="0.01"
                                                                           data-cost-id="{{ $cost->id }}">
                                                                </td>
                                                                <td class="total-cost-{{ $cost->id }} fw-bold text-success">
                                                                    {{ number_format($cost->total_cost, 2) }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="4" class="p-2 border-0 bg-light">
                                                                    @include('projects.review.partials.item-notes', ['model' => $cost])
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>لا توجد تكاليف مالية مسجلة لهذا الإجراء التنفيذي.</p>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted small ms-3 mb-0">لا توجد إجراءات مسجلة لهذا النشاط.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Review Decision, Document Upload & Notes Card --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-file-signature me-2 text-info"></i>قرار المراجعة المالية وإرفاق المستندات</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-check-double me-1 text-primary"></i>قرار المراجعة المالية:</label>
                                <select name="status" class="form-select form-select-lg">
                                    <option value="approved" {{ (isset($approval) && $approval->financial_review_status === 'approved') ? 'selected' : '' }}>
                                        موافقة على المراجعة المالية (معتمدة)
                                    </option>
                                    <option value="need_action" {{ (isset($approval) && $approval->financial_review_status === 'need_action') ? 'selected' : '' }}>
                                        مطلوب تعديل / إجراء مالي (إرجاع للمراجعة)
                                    </option>
                                    <option value="rejected" {{ (isset($approval) && $approval->financial_review_status === 'rejected') ? 'selected' : '' }}>
                                        رفض التكاليف المحددة
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-paperclip me-1 text-primary"></i>إرفاق مستند / تقرير المراجعة المالية:</label>
                                <input type="file" name="attachment" class="form-control form-control-lg" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx,.csv">
                                <small class="text-muted d-block mt-1">يمكنك إرفاق ملف تقرير المراجعة المالية بصيغ (PDF, Word, Excel, Images - حد أقصى 20MB)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-comment-dots me-1 text-primary"></i>ملاحظات وتوصيات المراجعة المالية:</label>
                            <textarea name="review_notes" 
                                      class="form-control" 
                                      rows="4" 
                                      placeholder="أدخل ملاحظاتك وتفاصيل الاعتماد أو التعديلات المالية المطلوب إجراؤها...">{{ $approval->financial_notes ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Form Submission Actions --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary btn-lg px-4">
                                <i class="fas fa-arrow-right me-1"></i>الرجوع للمشروع
                            </a>
                            <button type="submit" class="btn btn-info btn-lg text-white px-5 shadow-sm fw-bold">
                                <i class="fas fa-save me-2"></i>حفظ واعتماد المراجعة المالية
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Audit Trail Component (سجل المراجعات المالية والفنية لكل المراحل) --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @include('projects.partials.activity-history-display')
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate total cost when quantity or unit price changes
    document.querySelectorAll('.cost-quantity, .cost-unit-price').forEach(input => {
        input.addEventListener('input', function() {
            const costId = this.dataset.costId;
            const quantityInput = document.querySelector(`.cost-quantity[data-cost-id="${costId}"]`);
            const unitPriceInput = document.querySelector(`.cost-unit-price[data-cost-id="${costId}"]`);
            const totalCostElement = document.querySelector(`.total-cost-${costId}`);
            
            if (quantityInput && unitPriceInput && totalCostElement) {
                const quantity = parseFloat(quantityInput.value) || 0;
                const unitPrice = parseFloat(unitPriceInput.value) || 0;
                const totalCost = quantity * unitPrice;
                totalCostElement.textContent = totalCost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }
        });
    });
});
</script>
@endsection
