<!-- Executive Activities Section -->
<div class="mb-5">
    @if($project->executiveActivities && $project->executiveActivities->count() > 0)
        @foreach($project->executiveActivities as $activity)
            <div class="mb-5">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h4 class="text-success mb-0 fw-bold">{{ $activity->name }}</h4>
                    <span class="badge bg-light text-success ms-3 border">{{ $activity->weight }}%</span>
                </div>

                <div class="row g-4">
                    @foreach($activity->actions as $action)
                        @php
                            $executions = $action->executions;
                            $totalCompletionPercentage = $executions->sum('completion_percentage') ?? 0;
                        @endphp
                        <div class="col-12">
                            <div class="card border-0 shadow-sm execution-card" id="action-card-{{ $action->id }}" data-planned-end-date="{{ $action->end_date_gregorian ? $action->end_date_gregorian->format('Y-m-d') : '' }}">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3 text-success">
                                            <i class="fas fa-tasks fa-lg"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 fw-bold text-dark">{{ $action->action }}</h5>
                                            <div class="small text-muted">
                                                <span class="me-3"><i class="fas fa-weight me-1"></i>الوزن: {{ $action->weight }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-end d-none d-md-block">
                                            <div class="small text-muted mb-1">نسبة الإنجاز</div>
                                            <div class="d-flex align-items-center">
                                                <div class="progress" style="width: 100px; height: 8px;">
                                                    <div class="progress-bar bg-{{ $totalCompletionPercentage >= 100 ? 'success' : 'primary' }}" 
                                                         role="progressbar" 
                                                         style="width: {{ min($totalCompletionPercentage, 100) }}%" 
                                                         aria-valuenow="{{ $totalCompletionPercentage }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                                <span class="ms-2 fw-bold small">{{ number_format(min($totalCompletionPercentage, 100), 1) }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-8">
                                            <h6 class="text-muted text-uppercase small fw-bold mb-3">معلومات الإجراء</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless bg-light rounded">
                                                    <thead>
                                                        <tr class="text-muted border-bottom">
                                                            <th class="ps-3">الإجراء</th>
                                                            <th>الوزن</th>
                                                            <th class="text-end pe-3">الإنجاز</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="ps-3">{{ $action->action ?? '-' }}</td>
                                                            <td>{{ $action->weight }}%</td>
                                                            <td class="text-end pe-3 fw-bold">{{ number_format($totalCompletionPercentage, 1) }}%</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-4 border-start border-light">
                                            <h6 class="text-muted text-uppercase small fw-bold mb-3">إدارة التنفيذ</h6>
                                            <div class="mt-3">
                                                <button class="btn btn-outline-success btn-sm w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#execution-history-{{ $action->id }}">
                                                    <i class="fas fa-history me-1"></i> عرض السجل / إضافة تنفيذ
                                                </button>
                                                <div class="d-flex gap-2" id="exec-justification-icons-{{ $action->id }}">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger financial-overage-icon"
                                                            data-action-id="{{ $action->id }}"
                                                            title="تم تجاوز الميزانية - إضافة تبرير مالي"
                                                            style="display: none; flex: 1;">
                                                        <i class="fas fa-exclamation-circle me-1"></i>تجاوز مالي
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-warning timeline-overage-icon"
                                                            data-action-id="{{ $action->id }}"
                                                            title="تم تجاوز الموعد المخطط - إضافة تبرير تقني"
                                                            style="display: none; flex: 1;">
                                                        <i class="fas fa-hourglass-end me-1"></i>تجاوز وقتي
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Collapsible Section for History & Form -->
                                    <div class="collapse" id="execution-history-{{ $action->id }}">
                                        <div class="bg-light rounded p-3 mb-3">
                                            <!-- Action-Level Justification Buttons -->
                                            <div class="mb-3 pb-3 border-bottom d-flex gap-2 flex-wrap">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-info add-financial-justification-action"
                                                        data-action-id="{{ $action->id }}"
                                                        title="إضافة تبرير الزيادة في الميزانية للإجراء الكامل">
                                                    <i class="fas fa-file-invoice-dollar me-1"></i>تبرير مالي للإجراء
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary add-technical-justification-action"
                                                        data-action-id="{{ $action->id }}"
                                                        title="إضافة تبرير تقني للإجراء الكامل">
                                                    <i class="fas fa-file-pdf me-1"></i>تبرير تقني للإجراء
                                                </button>
                                            </div>

                                            <ul class="nav nav-pills mb-3" id="pills-tab-{{ $action->id }}" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active btn-sm" id="pills-home-tab-{{ $action->id }}" data-bs-toggle="pill" data-bs-target="#pills-home-{{ $action->id }}" type="button" role="tab">
                                                        <i class="fas fa-list me-1"></i> سجل العمليات
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link btn-sm" id="pills-profile-tab-{{ $action->id }}" data-bs-toggle="pill" data-bs-target="#pills-profile-{{ $action->id }}" type="button" role="tab">
                                                        <i class="fas fa-plus-circle me-1"></i> إضافة جديد
                                                    </button>
                                                </li>
                                            </ul>
                                            <div class="tab-content" id="pills-tabContent-{{ $action->id }}">
                                                <!-- Operations Log Tab -->
                                                <div class="tab-pane fade show active" id="pills-home-{{ $action->id }}" role="tabpanel">
                                                    @include('projects.partials.execution-executive-display', ['allExecutions' => $executions, 'action' => $action])
                                                </div>
                                                
                                                <!-- Add New Tab -->
                                                <div class="tab-pane fade" id="pills-profile-{{ $action->id }}" role="tabpanel">
                                                    @can('execution.edit')
                                                    <form action="{{ route('projects.execution.store', $project) }}" 
                                                          method="POST" 
                                                          enctype="multipart/form-data"
                                                          class="execution-form"
                                                          data-action-id="{{ $action->id }}">
                                                        @csrf
                                                        <input type="hidden" name="executive_activity_action_id" value="{{ $action->id }}">
                                                        
                                                        @include('projects.partials.execution-executive-form', ['action' => $action, 'allExecutions' => $executions])
                                                        
                                                        <div class="text-end mt-3">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#execution-history-{{ $action->id }}">إلغاء</button>
                                                            <button type="submit" class="btn btn-success btn-sm save-execution-btn">
                                                                <i class="fas fa-save me-1"></i> حفظ البيانات
                                                            </button>
                                                        </div>
                                                    </form>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="text-center py-5 text-muted">
            <i class="fas fa-briefcase fa-3x mb-3 opacity-50"></i>
            <p>لا يوجد أنشطة تنفيذية لهذا المشروع.</p>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Form Submission via AJAX
    document.querySelectorAll('.save-execution-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const formData = new FormData(form);
            const originalBtnText = this.innerHTML;

            // Basic Validation
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    form.prepend(alertDiv);
                    
                    // Reset form
                    form.reset();
                    
                    setTimeout(() => {
                        window.location.reload(); 
                    }, 1000);
                } else {
                    throw new Error(data.message || 'حدث خطأ غير متوقع');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                alertDiv.innerHTML = `
                    <i class="fas fa-exclamation-circle me-2"></i> ${error.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                form.prepend(alertDiv);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = originalBtnText;
            });
        });
    });

    // Check for budget and timeline overages and show/hide icons
    function checkExecutiveActionOverages() {
        document.querySelectorAll('[id^="exec-justification-icons-"]').forEach(container => {
            const actionId = container.id.replace('exec-justification-icons-', '');
            const card = document.getElementById('action-card-' + actionId);
            
            if (!card) return;
            
            // Get execution table for this action
            const tableId = 'execution_tbody_' + actionId;
            const tbody = document.getElementById(tableId);
            
            if (!tbody) return;
            
            // Get all executions for this action
            const rows = tbody.querySelectorAll('tr[id^="exec-row-"]');
            let totalActualAmount = 0;
            let totalSpent = 0;
            let hasTimelineOverage = false;
            
            rows.forEach(row => {
                const actualAmountInput = row.querySelector('.actual-amount');
                const spentInput = row.querySelector('.amount-spent');
                const finishDateInput = row.querySelector('.actual-finish-gregorian');
                
                if (actualAmountInput) totalActualAmount += parseFloat(actualAmountInput.value) || 0;
                if (spentInput) totalSpent += parseFloat(spentInput.value) || 0;
            });
            
            // Get planned end date and check if any execution is after it
            const plannedEndDate = card.dataset.plannedEndDate;
            if (plannedEndDate) {
                rows.forEach(row => {
                    const finishDateInput = row.querySelector('.actual-finish-gregorian');
                    if (finishDateInput && finishDateInput.value && finishDateInput.value > plannedEndDate) {
                        hasTimelineOverage = true;
                    }
                });
            }
            
            // Show/hide icons based on conditions
            const financialIcon = container.querySelector('.financial-overage-icon');
            const timelineIcon = container.querySelector('.timeline-overage-icon');
            
            if (financialIcon) {
                financialIcon.style.display = totalSpent > totalActualAmount ? 'block' : 'none';
            }
            
            if (timelineIcon) {
                timelineIcon.style.display = hasTimelineOverage ? 'block' : 'none';
            }
        });
    }
    
    // Check overages on page load
    checkExecutiveActionOverages();
    
    // Recheck overages after form submission
    document.addEventListener('execution-data-updated', checkExecutiveActionOverages);
    
    // Handle action-level justification buttons
    document.querySelectorAll('.add-financial-justification-action').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const actionId = this.dataset.actionId;
            console.log('Financial justification for entire action:', actionId);
            flasher.info('سيتم إضافة التبرير المالي للإجراء الكامل');
        });
    });

    document.querySelectorAll('.add-technical-justification-action').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const actionId = this.dataset.actionId;
            console.log('Technical justification for entire action:', actionId);
            flasher.info('سيتم إضافة التبرير التقني للإجراء الكامل');
        });
    });

    // Handle overage icon clicks
    document.querySelectorAll('.financial-overage-icon').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const actionId = this.dataset.actionId;
            console.log('Financial overage justification for action:', actionId);
            flasher.warning('يجب تقديم تبرير مالي لأن الإنفاق يتجاوز المبلغ المخطط');
        });
    });

    document.querySelectorAll('.timeline-overage-icon').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const actionId = this.dataset.actionId;
            console.log('Timeline overage justification for action:', actionId);
            flasher.warning('يجب تقديم تبرير تقني لأن التنفيذ تجاوز الموعد المخطط');
        });
    });
});
</script>

<style>
.execution-card {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.execution-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(40, 167, 69, 0.2) !important;
}

.execution-card .card-header {
    border-bottom: none;
    position: relative;
}

.execution-card .card-body {
    background: #f8f9fa;
}

.execution-card .card-footer {
    background: white;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}
</style>
