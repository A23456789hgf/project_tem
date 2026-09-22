{{-- Execution Record Approval Section --}}

{{-- Approval Status Badge --}}
@php
    $approvalStatusColors = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
    ];
    $approvalStatusLabels = [
        'pending' => 'قيد المراجعة',
        'approved' => '✓ موافق عليه',
        'rejected' => '✗ مرفوض',
    ];
@endphp

<div class="mt-3 pt-3 border-top">
    <div class="row">
        <div class="col-md-4">
            <h6 class="text-primary mb-3">
                <i class="fas fa-check-circle me-1"></i>حالة الموافقة
            </h6>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-{{ $approvalStatusColors[$execution->approval_status] ?? 'secondary' }} fs-6">
                    {{ $approvalStatusLabels[$execution->approval_status] ?? $execution->approval_status }}
                </span>
            </div>
            
            @if($execution->approval_status === 'approved' && $execution->approved_by)
                <div class="mt-2 small">
                    <strong>وافق عليه:</strong> {{ $execution->approvedBy->name ?? 'غير متوفر' }}
                    @if($execution->approved_at)
                        <br><strong>في:</strong> {{ $execution->approved_at->format('Y-m-d H:i') }}
                    @endif
                </div>
            @elseif($execution->approval_status === 'rejected' && $execution->rejected_by)
                <div class="mt-2 small">
                    <strong>رفضه:</strong> {{ $execution->rejectedBy->name ?? 'غير متوفر' }}
                    @if($execution->rejected_at)
                        <br><strong>في:</strong> {{ $execution->rejected_at->format('Y-m-d H:i') }}
                    @endif
                </div>
            @endif
        </div>
        
        <div class="col-md-8">
            @php
                // Recalculate context-specific quality for approval view
                $parent = $executionType === 'preliminary' ? $execution->procedure : $execution->action;
                $plannedAmount = $parent ? $parent->costs->sum('total') : 0;
                $actualAmount = $execution->amount_spent;
                
                $finScore = 'match'; $finLabel = 'مطابق'; $finColor = 'primary';
                if ($plannedAmount > 0) {
                    if ($actualAmount > $plannedAmount) { $finScore = 'over'; $finLabel = 'تجاوز'; $finColor = 'danger'; }
                    elseif ($actualAmount < $plannedAmount) { $finScore = 'under'; $finLabel = 'توفير'; $finColor = 'success'; }
                }

                $plannedEnd = $executionType === 'preliminary' ? ($parent->end_date ?? null) : ($parent->end_date_gregorian ?? null);
                $actualEnd = $execution->actual_finish_date_gregorian;
                $timeScore = 'on_time'; $timeLabel = 'في الوقت'; $timeColor = 'primary';
                if ($plannedEnd && $actualEnd) {
                    $pEnd = \Carbon\Carbon::parse($plannedEnd);
                    $aEnd = \Carbon\Carbon::parse($actualEnd);
                    if ($aEnd->gt($pEnd)) { $timeScore = 'late'; $timeLabel = 'متأخر'; $timeColor = 'danger'; }
                    elseif ($aEnd->lt($pEnd)) { $timeScore = 'ahead'; $timeLabel = 'مبكر'; $timeColor = 'success'; }
                }
            @endphp

            @if($execution->approval_status === 'pending')
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-primary mb-0">
                        <i class="fas fa-tasks me-1"></i>إجراءات الموافقة
                    </h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-{{ $timeColor }} small" title="جودة الوقت">
                            <i class="fas fa-clock me-1"></i>{{ $timeLabel }}
                        </span>
                        <span class="badge bg-{{ $finColor }} small" title="جودة التمويل">
                            <i class="fas fa-dollar-sign me-1"></i>{{ $finLabel }}
                        </span>
                    </div>
                </div>

                @if(auth()->user()->hasAnyPermission(['execution.approve', 'execution.reject']))
                    <div class="btn-group btn-group-sm" role="group">
                        @can('execution.approve')
                        <button type="button" 
                                class="btn btn-sm btn-success approve-execution-btn"
                                data-execution-id="{{ $execution->id }}"
                                data-execution-type="{{ $executionType ?? 'preliminary' }}"
                                data-project-id="{{ $project->id }}"
                                title="الموافقة على هذا السجل">
                            <i class="fas fa-check me-1"></i>موافقة
                        </button>
                        @endcan

                        @can('execution.reject')
                        <button type="button" 
                                class="btn btn-sm btn-danger reject-execution-btn"
                                data-execution-id="{{ $execution->id }}"
                                data-execution-type="{{ $executionType ?? 'preliminary' }}"
                                data-project-id="{{ $project->id }}"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectExecutionModal{{ $execution->id }}"
                                title="رفض هذا السجل">
                            <i class="fas fa-times me-1"></i>رفض
                        </button>
                        @endcan
                    </div>
                @else
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="fas fa-lock me-1"></i> بانتظار مراجعة المخولين (مدير المشروع)
                    </div>
                @endif
            @elseif($execution->approval_status === 'rejected')
                <h6 class="text-primary mb-3">
                    <i class="fas fa-exclamation-triangle me-1"></i>سبب الرفض
                </h6>
                <div class="alert alert-danger small mb-0">
                    <strong>السبب:</strong> <br>
                    {{ $execution->rejection_reason }}
                </div>
            @else
                <h6 class="text-primary mb-3">
                    <i class="fas fa-thumbs-up me-1"></i>تم الموافقة
                </h6>
                <p class="text-success mb-0">
                    <i class="fas fa-check-circle me-1"></i>تمت الموافقة على هذا السجل بنجاح
                </p>
            @endif
        </div>
    </div>
</div>

{{-- Rejection Reason Modal --}}
@if($execution->approval_status === 'pending')
    <div class="modal fade" id="rejectExecutionModal{{ $execution->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">رفض سجل التنفيذ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                @can('projects.execution.')
                <form action="{{ route('projects.execution.' . ($executionType ?? 'preliminary') . '.reject', [$project, $execution]) }}" 
                      method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rejection_reason_{{ $execution->id }}" class="form-label">
                                <strong>سبب الرفض</strong> 
                            </label>
                            <textarea class="form-control" 
                                      id="rejection_reason_{{ $execution->id }}" 
                                      name="rejection_reason" 
                                      rows="4"
                                      placeholder="أدخل السبب التفصيلي للرفض (لا يقل عن 10 أحرف)..."
                                      minlength="10"
                                      maxlength="1000"></textarea>
                            <small class="form-text text-muted d-block mt-2">
                                يجب إدخال سبب واضح وتفصيلي لرفض السجل ليتمكن المستخدم من معرفة نقاط الضعف والتحسين.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>رفض السجل
                        </button>
                    </div>
                </form>
                @endcan
            </div>
        </div>
    </div>
@endif

<style>
    .approval-section {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        border-right: 4px solid #4f46e5;
    }
    
    .approval-section.approved {
        border-right-color: #22c55e;
        background-color: #f0fdf4;
    }
    
    .approval-section.rejected {
        border-right-color: #ef4444;
        background-color: #fef2f2;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Approve button functionality
    document.querySelectorAll('.approve-execution-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            Swal.fire({
                title: 'تأكيد العملية',
                text: 'هل تريد بالفعل الموافقة على هذا السجل؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم',
                cancelButtonText: 'لا',
                reverseButtons: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    const executionId = this.dataset.executionId;
                    const executionType = this.dataset.executionType;
                    const projectId = this.dataset.projectId;
                    
                    const url = `/projects/${projectId}/execution/${executionType}/${executionId}/approve`;
                    
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            flasher.success(data.message || 'تمت الموافقة بنجاح');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            flasher.error(data.message || 'حدث خطأ أثناء الموافقة');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        flasher.error('حدث خطأ أثناء معالجة الطلب');
                    });
                }
            });
        });
    });
});
</script>
