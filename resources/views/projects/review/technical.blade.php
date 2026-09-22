@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            {{-- Header --}}
            <div class="card mb-4" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: white; border: none; border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="mb-1 fw-bold"><i class="fas fa-tools me-2"></i>المراجعة الفنية للمشروع</h3>
                            <p class="mb-0 text-white-50">مراجعة الأنشطة، الإجراءات، الجهات المنفذة، وإرفاق مستندات وملاحظات الاعتماد الفني</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-white text-dark shadow-sm fs-6 px-3 py-2">
                                <i class="fas fa-barcode me-1 text-primary"></i>
                                {{ $project->form_number ?? 'بدون رقم' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Project Basic Details Card --}}
            <div class="card mb-4 border-0 shadow-sm rounded-3">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0 fw-bold style-purple" style="color: #6f42c1;"><i class="fas fa-info-circle me-2"></i>معلومات المشروع الأساسية</h5>
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
                            <span class="text-muted d-block small">إجمالي تكلفة المشروع:</span>
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

            {{-- Financial Review Summary Card --}}
            @if(isset($approval) && $approval)
            <div class="card mb-4 border-start border-4 border-info shadow-sm" style="border-left-color: #17a2b8 !important;">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-dark fw-bold">
                        <i class="fas fa-dollar-sign me-2 text-info"></i>نتائج وتعديلات المراجعة المالية
                    </h5>
                    <div>
                        @if($approval->financial_review_status === 'approved')
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>تمت المراجعة المالية (موافقة)</span>
                        @elseif($approval->financial_review_status === 'need_action')
                            <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle me-1"></i>مطلوب إجراء مالي</span>
                        @elseif($approval->financial_review_status === 'rejected')
                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>مرفوضة مالياً</span>
                        @else
                            <span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>بانتظار الاعتماد المالي</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-user-check me-1 text-muted"></i>المراجع المالي:</strong> 
                            {{ $approval->financialReviewer->name ?? 'غير محدد بعد' }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-calendar-alt me-1 text-muted"></i>تاريخ المراجعة المالية:</strong> 
                            {{ $approval->financial_reviewed_at ? $approval->financial_reviewed_at->format('Y-m-d H:i') : 'ـ' }}
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-comment-dots me-1 text-muted"></i>ملاحظات المراجع المالي:</strong>
                        <div class="p-3 bg-light rounded mt-1 border text-dark" style="min-height: 50px;">
                            {{ $approval->financial_notes ?: 'لا توجد ملاحظات مالية مضافة حتى الآن.' }}
                        </div>
                    </div>

                    @if($approval->financial_attachment)
                    <div>
                        <strong><i class="fas fa-paperclip me-1 text-muted"></i>مرفق المراجعة المالية:</strong>
                        <a href="{{ Storage::url($approval->financial_attachment) }}" target="_blank" class="btn btn-sm btn-outline-info ms-2">
                            <i class="fas fa-download me-1"></i>عرض المرفق المالي
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Main Technical Review Form --}}
            @can('reviews.technical')
            <form method="POST" action="{{ route('projects.review.technical.submit', $project) }}" enctype="multipart/form-data">
            @endcan
                @csrf

                {{-- Policy Notice Banner --}}
                <div class="alert border-0 p-3 rounded-3 mb-4 shadow-sm" style="background-color: #f3ebff; border-right: 4px solid #6f42c1 !important;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fs-4 me-3 text-purple" style="color: #6f42c1;"></i>
                        <div>
                            <strong class="d-block text-dark">ضوابط المراجعة الفنية:</strong>
                            <span class="text-muted small">
                                يُسمح لك بتعديل مسميات ومواعيد الأنشطة والإجراءات التمهيدية والتنفيذية. 
                                <span class="fw-bold text-danger"><i class="fas fa-lock me-1"></i>الجانب المالي والتكاليف مخصص للعرض فقط ولا يُسمح بتعديله في المراجعة الفنية.</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Preliminary Activities & Procedures (Editable Technical Details) --}}
                @if($project->preliminaryActivities->count() > 0)
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header style-purple text-white" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>الأنشطة والإجراءات التمهيدية (قابلة للتعديل الفني)</h5>
                            <span class="badge bg-white text-dark small"><i class="fas fa-edit me-1 text-primary"></i>تعديل فني</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach($project->preliminaryActivities as $activity)
                            <div class="mb-4 p-3 bg-light rounded border">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-9">
                                        <label class="form-label fw-bold text-primary small">
                                            <i class="fas fa-tasks me-1"></i> بيان / اسم النشاط التمهيدي:
                                        </label>
                                        <input type="text" 
                                               name="preliminary_activities[{{ $activity->id }}][name]" 
                                               class="form-control fw-semibold" 
                                               value="{{ $activity->name ?? $activity->activity }}" 
                                               required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-primary small">
                                            <i class="fas fa-percentage me-1"></i> الوزن النسبي (%):
                                        </label>
                                        <input type="number" step="0.01" min="0" max="100"
                                               name="preliminary_activities[{{ $activity->id }}][weight]" 
                                               class="form-control fw-semibold" 
                                               value="{{ $activity->weight ?? 0 }}">
                                    </div>
                                </div>

                                @if($activity->procedures->count() > 0)
                                    <div class="ms-md-3">
                                        <label class="form-label fw-bold text-dark small mb-2">إجراءات النشاط التمهيدي والمواعيد والأوزان:</label>
                                        @foreach($activity->procedures as $procedure)
                                            <div class="p-3 bg-white mb-3 rounded border shadow-xs">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">اسم الإجراء:</label>
                                                        <input type="text" 
                                                               name="preliminary_procedures[{{ $procedure->id }}][procedure_name]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $procedure->procedure_name ?? $procedure->procedure }}" 
                                                               required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small text-muted mb-1">الوزن (%):</label>
                                                        <input type="number" step="0.01" min="0" max="100"
                                                               name="preliminary_procedures[{{ $procedure->id }}][weight]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $procedure->weight ?? 0 }}">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small text-muted mb-1">تاريخ البداية:</label>
                                                        <input type="date" 
                                                               name="preliminary_procedures[{{ $procedure->id }}][start_date]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $procedure->start_date ? \Carbon\Carbon::parse($procedure->start_date)->format('Y-m-d') : '' }}">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small text-muted mb-1">تاريخ النهاية:</label>
                                                        <input type="date" 
                                                               name="preliminary_procedures[{{ $procedure->id }}][end_date]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $procedure->end_date ? \Carbon\Carbon::parse($procedure->end_date)->format('Y-m-d') : '' }}">
                                                    </div>
                                                </div>

                                                {{-- Read-Only Costs Display --}}
                                                @if($procedure->costs->count() > 0)
                                                    <div class="mt-2 pt-2 border-top">
                                                        <span class="text-muted small me-2"><i class="fas fa-lock me-1 text-secondary"></i>التكاليف المالية (للعرض فقط):</span>
                                                        @foreach($procedure->costs as $cost)
                                                            <span class="badge bg-secondary-subtle text-secondary border me-1">
                                                                {{ $cost->financialItem->name ?? 'بند' }}: {{ number_format($cost->total_cost ?? $cost->total, 2) }} ريال
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small ms-3 mb-0">لا توجد إجراءات مسجلة لهذا النشاط التمهيدي.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Executive Activities & Actions (Editable Technical Details) --}}
                @if($project->executiveActivities->count() > 0)
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header style-purple text-white" style="background: linear-gradient(135deg, #5a32a3 0%, #3c1e75 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-running me-2"></i>الأنشطة والإجراءات التنفيذية (قابلة للتعديل الفني)</h5>
                            <span class="badge bg-white text-dark small"><i class="fas fa-edit me-1 text-primary"></i>تعديل فني</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach($project->executiveActivities as $activity)
                            <div class="mb-4 p-3 bg-light rounded border">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-9">
                                        <label class="form-label fw-bold small" style="color: #6f42c1;">
                                            <i class="fas fa-running me-1"></i> بيان / اسم النشاط التنفيذي:
                                        </label>
                                        <input type="text" 
                                               name="executive_activities[{{ $activity->id }}][activity_name]" 
                                               class="form-control fw-semibold" 
                                               value="{{ $activity->activity_name ?? $activity->name }}" 
                                               required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small" style="color: #6f42c1;">
                                            <i class="fas fa-percentage me-1"></i> الوزن النسبي (%):
                                        </label>
                                        <input type="number" step="0.01" min="0" max="100"
                                               name="executive_activities[{{ $activity->id }}][weight]" 
                                               class="form-control fw-semibold" 
                                               value="{{ $activity->weight ?? 0 }}">
                                    </div>
                                </div>

                                @if($activity->actions->count() > 0)
                                    <div class="ms-md-3">
                                        <label class="form-label fw-bold text-dark small mb-2">إجراءات النشاط التنفيذي والمواعيد والأوزان:</label>
                                        @foreach($activity->actions as $action)
                                            <div class="p-3 bg-white mb-3 rounded border shadow-xs">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-md-5">
                                                        <label class="form-label small text-muted mb-1">اسم الإجراء التنفيذي:</label>
                                                        <input type="text" 
                                                               name="executive_actions[{{ $action->id }}][action_name]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $action->action_name ?? $action->action }}" 
                                                               required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small text-muted mb-1">الوزن (%):</label>
                                                        <input type="number" step="0.01" min="0" max="100"
                                                               name="executive_actions[{{ $action->id }}][weight]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $action->weight ?? 0 }}">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small text-muted mb-1">تاريخ البداية:</label>
                                                        <input type="date" 
                                                               name="executive_actions[{{ $action->id }}][start_date]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $action->start_date ? \Carbon\Carbon::parse($action->start_date)->format('Y-m-d') : '' }}">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small text-muted mb-1">تاريخ النهاية:</label>
                                                        <input type="date" 
                                                               name="executive_actions[{{ $action->id }}][end_date]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $action->end_date ? \Carbon\Carbon::parse($action->end_date)->format('Y-m-d') : '' }}">
                                                    </div>
                                                </div>

                                                @if($action->assignedEntities->count() > 0)
                                                    <div class="mt-2">
                                                        <span class="text-muted small me-1">الجهات المكلفة:</span>
                                                        @foreach($action->assignedEntities as $assigned)
                                                            <span class="badge bg-info-subtle text-info border px-2 py-1 me-1">{{ $assigned->name_ar }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                {{-- Read-Only Costs Display --}}
                                                @if($action->costs->count() > 0)
                                                    <div class="mt-2 pt-2 border-top">
                                                        <span class="text-muted small me-2"><i class="fas fa-lock me-1 text-secondary"></i>التكاليف المالية (للعرض فقط):</span>
                                                        @foreach($action->costs as $cost)
                                                            <span class="badge bg-secondary-subtle text-secondary border me-1">
                                                                {{ $cost->financialItem->name ?? 'بند' }}: {{ number_format($cost->total_cost ?? $cost->total, 2) }} ريال
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small ms-3 mb-0">لا توجد إجراءات مسجلة لهذا النشاط التنفيذي.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Review Decision, Attachment & Notes Card --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-file-signature me-2 text-purple" style="color: #6f42c1;"></i>قرار المراجعة الفنية وإرفاق المستندات</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-check-double me-1 text-purple" style="color: #6f42c1;"></i>قرار الاعتماد الفني:</label>
                                <select name="status" class="form-select form-select-lg">
                                    <option value="approved" {{ (isset($approval) && $approval->technical_review_status === 'approved') ? 'selected' : '' }}>
                                        موافقة واعتماد المراجعة الفنية
                                    </option>
                                    <option value="need_action" {{ (isset($approval) && $approval->technical_review_status === 'need_action') ? 'selected' : '' }}>
                                        مطلوب تعديل / إجراء فني (إرجاع للمراجعة)
                                    </option>
                                    <option value="rejected" {{ (isset($approval) && $approval->technical_review_status === 'rejected') ? 'selected' : '' }}>
                                        رفض الأنشطة والإجراءات الفنية
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="fas fa-paperclip me-1 text-purple" style="color: #6f42c1;"></i>إرفاق مستند / تقرير المراجعة الفنية:</label>
                                <input type="file" name="attachment" class="form-control form-control-lg" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx,.csv">
                                <small class="text-muted d-block mt-1">يمكنك إرفاق تقرير المراجعة الفنية بصيغ (PDF, Word, Excel, Images - حد أقصى 20MB)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-comment-dots me-1 text-purple" style="color: #6f42c1;"></i>ملاحظات وتوصيات المراجعة الفنية:</label>
                            <textarea name="review_notes" 
                                      class="form-control" 
                                      rows="4" 
                                      placeholder="أدخل ملاحظاتك الفنية وتفاصيل التوصيات أو التعديلات الفنية المطلوبة...">{{ $approval->technical_notes ?? '' }}</textarea>
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
                            <button type="submit" class="btn text-white btn-lg px-5 shadow-sm fw-bold" style="background-color: #6f42c1;">
                                <i class="fas fa-save me-2"></i>حفظ واعتماد المراجعة الفنية
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
@endsection
