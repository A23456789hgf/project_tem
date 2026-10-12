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
                    @can('execution.assign')
                        <button type="button" class="btn btn-sm btn-outline-success ms-3 btn-assign" 
                                data-assignable-type="executive_activity" 
                                data-assignable-id="{{ $activity->id }}"
                                data-assignable-name="{{ $activity->name }}"
                                title="تكليف مستخدمين">
                            <i class="fas fa-user-plus me-1"></i>تكليف
                        </button>
                    @endcan
                    <div class="assigned-users-list ms-3 d-flex align-items-center gap-1 flex-wrap" id="assigned-users-executive_activity-{{ $activity->id }}">
                        @foreach($activity->assignments as $assignment)
                            <span class="badge bg-soft-info text-info border d-inline-flex align-items-center gap-1" title="مكلف: {{ $assignment->assignedTo->name }}" id="assignment-badge-{{ $assignment->id }}">
                                <i class="fas fa-user-circle"></i>
                                {{ $assignment->assignedTo->name }}
                                @can('execution.assign')
                                    <a href="javascript:void(0)" class="text-danger ms-1 remove-assignment" data-id="{{ $assignment->id }}" style="font-size: 0.8em; text-decoration: none;">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                @endcan
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="row g-4">
                    @foreach($activity->actions as $action)
                        @php
                            $executions = $action->executions;
                            
                            // Aggregate data from all execution records
                            $totalCompletionPercentage = $executions->sum('completion_percentage');
                            $totalPlanned = $action->costs->sum('total');
                            $totalActualAmount = $executions->sum('actual_amount');
                            $totalSpent = $executions->sum('amount_spent');
                        @endphp
                        <div class="col-12">
                            <div class="card border-0 shadow-sm execution-card" id="action-card-{{ $action->id }}" data-planned-start-date="{{ $action->start_date_gregorian ? $action->start_date_gregorian->format('Y-m-d') : '' }}" data-planned-end-date="{{ $action->end_date_gregorian ? $action->end_date_gregorian->format('Y-m-d') : '' }}" data-planned-amount="{{ $totalPlanned }}">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3 text-success">
                                            <i class="fas fa-tasks fa-lg"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 fw-bold text-dark">{{ $action->action }}</h5>
                                            <div class="small text-muted d-flex align-items-center gap-3 flex-wrap">
                                                <span><i class="fas fa-weight me-1"></i>الوزن: {{ $action->weight }}%</span>
                                                @can('execution.assign')
                                                    <button type="button" class="btn btn-link btn-sm text-primary p-0 btn-assign" style="text-decoration: none;"
                                                            data-assignable-type="executive_action" 
                                                            data-assignable-id="{{ $action->id }}"
                                                            data-assignable-name="{{ $action->action }}"
                                                            title="تكليف مستخدمين">
                                                        <i class="fas fa-user-plus me-1"></i>تكليف
                                                    </button>
                                                @endcan
                                                <div class="assigned-users-list d-flex align-items-center gap-1 flex-wrap" id="assigned-users-executive_action-{{ $action->id }}">
                                                    @foreach($action->assignments as $assignment)
                                                        <span class="badge bg-soft-info text-info border d-inline-flex align-items-center gap-1" title="مكلف: {{ $assignment->assignedTo->name }}" id="assignment-badge-{{ $assignment->id }}">
                                                            <i class="fas fa-user-circle"></i>
                                                            {{ $assignment->assignedTo->name }}
                                                            @can('execution.assign')
                                                                <a href="javascript:void(0)" class="text-danger ms-1 remove-assignment" data-id="{{ $assignment->id }}" style="font-size: 0.8em; text-decoration: none;">
                                                                    <i class="fas fa-times-circle"></i>
                                                                </a>
                                                            @endcan
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-end d-none d-md-block">
                                            <div class="small text-muted mb-1">إجمالي الإنجاز التراكمي</div>
                                            <div class="d-flex align-items-center">
                                                <div class="progress" style="width: 120px; height: 10px; border-radius: 5px; background-color: rgba(0,0,0,0.05);">
                                                    <div class="progress-bar bg-{{ $totalCompletionPercentage >= 100 ? 'success' : 'primary' }}" 
                                                         role="progressbar" 
                                                         style="width: {{ min($totalCompletionPercentage, 100) }}%; box-shadow: 0 0 10px rgba(var(--bs-{{ $totalCompletionPercentage >= 100 ? 'success' : 'primary' }}-rgb), 0.5);" 
                                                         aria-valuenow="{{ $totalCompletionPercentage }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                                <span class="ms-2 fw-bold small text-{{ $totalCompletionPercentage >= 100 ? 'success' : 'primary' }}">{{ number_format($totalCompletionPercentage, 1) }}%</span>
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
                                            
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">تاريخ البداية:</span>
                                                <span class="fw-bold text-info">{{ $action->start_date ? \Carbon\Carbon::parse($action->start_date)->format('Y-m-d') : '-' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">تاريخ النهاية:</span>
                                                <span class="fw-bold text-info">{{ $action->end_date ? \Carbon\Carbon::parse($action->end_date)->format('Y-m-d') : '-' }}</span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">المبلغ المخطط (الميزانية):</span>
                                                <span class="fw-bold text-success">{{ number_format($totalPlanned, 2) }} ﷼</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">إجمالي المبلغ المخصص (التعميد):</span>
                                                <span class="fw-bold text-primary">{{ number_format($totalActualAmount, 2) }} ﷼</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">المنصرف الفعلي (المدفوع):</span>
                                                <span class="fw-bold text-dark">{{ number_format($totalSpent, 2) }} ﷼</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">المتبقي من الميزانية:</span>
                                                <span class="fw-bold {{ ($totalPlanned - $totalSpent) < 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ number_format($totalPlanned - $totalSpent, 2) }} ﷼
                                                </span>
                                            </div>
                                            @if(($totalPlanned - $totalSpent) < 0 || ($action->budgetJustification))
                                                <!-- Financial Overage Button -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger financial-overage-icon w-100 mt-2"
                                                        data-action-id="{{ $action->id }}"
                                                        data-has-justification="{{ $action->budgetJustification ? 'true' : 'false' }}"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#financialJustificationCollapse-{{ $action->id }}"
                                                        title="تم تجاوز الميزانية - إضافة تبرير مالي">
                                                    <i class="fas fa-exclamation-circle me-1"></i>
                                                    {{ ($totalPlanned - $totalSpent) < 0 ? 'تجاوز مالي' : 'عرض التبرير المالي' }}
                                                </button>

                                                <!-- Financial Justification Partial -->
                                                <div class="collapse mt-3" id="financialJustificationCollapse-{{ $action->id }}">
                                                    @include('projects.partials.implementation.execution-executive-financial-justification', [
                                                        'action' => $action, 
                                                        'project' => $project,
                                                        'totalPlanned' => $totalPlanned,
                                                        'totalSpent' => $totalSpent
                                                    ])
                                                </div>
                                            @endif

                                            @php
                                                $hasTimelineOverageBlade = $executions->contains(function($exec) use ($action) {
                                                    return $exec->actual_finish_date_gregorian && $action->end_date_gregorian && 
                                                           $exec->actual_finish_date_gregorian->gt($action->end_date_gregorian);
                                                }) || ($action->technicalJustifications && $action->technicalJustifications->count() > 0);
                                            @endphp

                                            @if($hasTimelineOverageBlade)
                                                <!-- Technical Justification Button -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-warning timeline-overage-icon w-100 mt-2"
                                                        id="tech-justification-btn-{{ $action->id }}"
                                                        data-action-id="{{ $action->id }}"
                                                        data-has-technical-justification="{{ ($action->technicalJustifications && $action->technicalJustifications->count() > 0) ? 'true' : 'false' }}"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#technicalJustificationCollapse-{{ $action->id }}"
                                                        title="{{ ($action->technicalJustifications && $action->technicalJustifications->count() > 0) ? 'عرض التبرير التقني' : 'إضافة تبرير تقني' }}">
                                                    <i class="fas fa-hourglass-end me-1"></i>
                                                    {{ ($action->technicalJustifications && $action->technicalJustifications->count() > 0) ? 'عرض التبرير التقني' : 'تبرير تقني' }}
                                                </button>

                                                <!-- Technical Justification Partial -->
                                                <div class="collapse mt-3" id="technicalJustificationCollapse-{{ $action->id }}">
                                                    @include('projects.partials.implementation.execution-executive-technical-justification', [
                                                        'action' => $action, 
                                                        'project' => $project
                                                    ])
                                                </div>
                                            @endif

                                            <div class="mt-3">
                                                <button class="btn btn-outline-success btn-sm w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#execution-history-{{ $action->id }}">
                                                    <i class="fas fa-history me-1"></i> عرض السجل / إضافة تنفيذ
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Collapsible Section for History & Form -->
                                    <div class="collapse" id="execution-history-{{ $action->id }}">
                                        <div class="bg-light rounded p-3 mb-3">
                                            <!-- Action-Level Justification Buttons -->
                                            @can('execution.edit')
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
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#technicalJustificationCollapse-{{ $action->id }}"
                                                        title="إضافة تبرير تقني للإجراء الكامل">
                                                    <i class="fas fa-file-pdf me-1"></i>تبرير تقني للإجراء
                                                </button>
                                            </div>
                                            @endcan

                                            <ul class="nav nav-pills mb-3" id="pills-tab-{{ $action->id }}" role="tablist">
                                                @can('execution.log.view')
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active btn-sm" id="pills-home-tab-{{ $action->id }}" data-bs-toggle="pill" data-bs-target="#pills-home-{{ $action->id }}" type="button" role="tab">
                                                        <i class="fas fa-list me-1"></i> سجل العمليات
                                                    </button>
                                                </li>
                                                @endcan
                                                @can('execution.add')
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link {{ !auth()->user()->hasPermission('execution.log.view') ? 'active' : '' }} btn-sm" id="pills-profile-tab-{{ $action->id }}" data-bs-toggle="pill" data-bs-target="#pills-profile-{{ $action->id }}" type="button" role="tab">
                                                        <i class="fas fa-plus-circle me-1"></i> إضافة جديد
                                                    </button>
                                                </li>
                                                @endcan
                                            </ul>
                                            <div class="tab-content" id="pills-tabContent-{{ $action->id }}">
                                                <!-- Operations Log Tab -->
                                                @can('execution.log.view')
                                                <div class="tab-pane fade show active" id="pills-home-{{ $action->id }}" role="tabpanel">
                                                    @include('projects.partials.implementation.execution-executive-display', ['allExecutions' => $executions, 'action' => $action])
                                                </div>
                                                @endcan
                                                
                                                <!-- Add New Tab -->
                                                @can('execution.add')
                                                <div class="tab-pane fade {{ !auth()->user()->hasPermission('execution.log.view') ? 'show active' : '' }}" id="pills-profile-{{ $action->id }}" role="tabpanel">
                                                    <form action="{{ route('projects.execution.store', $project) }}" 
                                                          method="POST" 
                                                          enctype="multipart/form-data"
                                                          class="execution-form"
                                                          data-action-id="{{ $action->id }}">
                                                        @csrf
                                                        <input type="hidden" name="executive_activity_action_id" value="{{ $action->id }}">
                                                        
                                                        @include('projects.partials.implementation.execution-executive-form', ['action' => $action, 'allExecutions' => $executions])
                                                        
                                                        <div class="text-end mt-3">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#execution-history-{{ $action->id }}">إلغاء</button>
                                                            <button type="submit" class="btn btn-success btn-sm save-execution-btn">
                                                                <i class="fas fa-save me-1"></i> حفظ البيانات
                                                            </button>
                                                        </div>
                                                        @endcan
                                                        
                                                        @if(!auth()->user()->hasPermission('execution.log.view') && !auth()->user()->hasPermission('execution.add'))
                                                            <div class="alert alert-warning mt-3">
                                                                <i class="fas fa-exclamation-triangle me-2"></i> لا تملك الصلاحيات الكافية لعرض أو إضافة سجلات التنفيذ.
                                                            </div>
                                                        @endif
                                                    </form>
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

<!-- Core Logic Scripts -->
<script src="{{ asset('js/hijri-converter.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Centralized Hijri Conversion Delegated Listener
    document.addEventListener('change', function(e) {
        if (e.target.matches('.actual-start-gregorian, .actual-finish-gregorian')) {
            const gregorianInput = e.target;
            const hijriFieldId = gregorianInput.getAttribute('data-hijri-field');
            const hijriField = document.getElementById(hijriFieldId);

            if (gregorianInput.value && hijriField) {
                const hijri = HijriConverter.gregorianToHijri(gregorianInput.value);
                const formatted = HijriConverter.formatHijri(hijri);
                
                if (hijriField.tagName === 'INPUT') {
                    hijriField.value = formatted;
                } else {
                    hijriField.textContent = formatted;
                }
            }
        }
    });

    // 2. Initial Conversion for all visible Gregorian inputs
    function initializeHijriDates() {
        document.querySelectorAll('.actual-start-gregorian, .actual-finish-gregorian').forEach(input => {
            const hijriFieldId = input.getAttribute('data-hijri-field');
            const hijriField = document.getElementById(hijriFieldId);
            if (input.value && hijriField) {
                const hijri = HijriConverter.gregorianToHijri(input.value);
                const formatted = HijriConverter.formatHijri(hijri);
                if (hijriField.tagName === 'INPUT') {
                    hijriField.value = formatted;
                } else {
                    hijriField.textContent = formatted;
                }
            }
        });
    }
    initializeHijriDates();

    // 3. Real-time Calculations for Amounts and Progress
    document.addEventListener('input', function(e) {
        if (e.target.matches('.amount-spent, .actual-amount, .completion-percentage-input')) {
            const container = e.target.closest('.execution-form') || e.target.closest('form') || e.target.closest('tr');
            if (!container) return;

            const actualAmountInput = container.querySelector('.actual-amount');
            const spentInput = container.querySelector('.amount-spent');
            const remainingDisplay = container.querySelector('.remaining-amount, .remaining-amount-display');
            const completionInput = container.querySelector('.completion-percentage-input');
            const progressPreview = container.querySelector('.completion-progress-preview');
            const percentagePreview = container.querySelector('.completion-percentage-preview');
            
            const actionId = container.dataset.actionId || container.closest('.execution-card')?.id.replace('action-card-', '');
            const budgetWarning = document.getElementById('budget-warning-exec-' + actionId);

            if (actualAmountInput && spentInput && remainingDisplay) {
                const actual = parseFloat(actualAmountInput.value) || 0;
                const spent = parseFloat(spentInput.value) || 0;
                const remaining = actual - spent;
                
                if (remainingDisplay.tagName === 'INPUT') {
                    remainingDisplay.value = remaining.toFixed(2);
                } else {
                    remainingDisplay.textContent = remaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }

                // Update Summary Cards (for "Add New" form only)
                if (actionId && !container.id.startsWith('exec-row-')) {
                    const summarySpentEl = document.getElementById('summary-spent-exec-' + actionId);
                    const summaryRemainingEl = document.getElementById('summary-remaining-exec-' + actionId);
                    const summaryRemainingBar = document.getElementById('summary-remaining-bar-exec-' + actionId);
                    const summaryBudgetEl = document.getElementById('summary-budget-exec-' + actionId);

                    if (summarySpentEl && summaryRemainingEl && summaryBudgetEl) {
                        const baseSpent = parseFloat(summarySpentEl.dataset.baseValue) || 0;
                        const budget = parseFloat(summaryBudgetEl.dataset.baseValue) || 0;
                        
                        const newTotalSpent = baseSpent + spent;
                        const newRemaining = Math.max(0, budget - newTotalSpent);
                        
                        summarySpentEl.textContent = newTotalSpent.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        summaryRemainingEl.textContent = newRemaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        if (summaryRemainingBar) {
                            const pct = budget > 0 ? (newRemaining / budget * 100) : 0;
                            summaryRemainingBar.style.width = pct + '%';
                            summaryRemainingBar.setAttribute('aria-valuenow', pct.toFixed(1));
                        }

                        // Toggle alert if exceeding
                        if (budgetWarning) {
                            budgetWarning.style.display = (newTotalSpent > budget + 0.01) ? 'block' : 'none';
                        }
                    }
                }
                
                // Visual feedback for balance
                if (remaining < 0) {
                    remainingDisplay.classList.add('text-danger');
                    remainingDisplay.classList.remove('text-success');
                } else {
                    remainingDisplay.classList.remove('text-danger');
                    remainingDisplay.classList.add('text-success');
                }
            }

            if (completionInput && (progressPreview || percentagePreview)) {
                const val = parseFloat(completionInput.value) || 0;
                const displayVal = Math.min(val, 100);
                
                if (progressPreview) progressPreview.style.width = displayVal + '%';
                if (percentagePreview) percentagePreview.textContent = val + '%';
                
                // Update Summary Completion Card (for "Add New" form only)
                if (actionId && !container.id.startsWith('exec-row-')) {
                    const summaryCompletionEl = document.getElementById('summary-completion-exec-' + actionId);
                    const summaryCompletionBar = document.getElementById('summary-completion-bar-exec-' + actionId);

                    if (summaryCompletionEl) {
                        const baseCompletion = parseFloat(summaryCompletionEl.dataset.baseValue) || 0;
                        const newTotalCompletion = baseCompletion + val;
                        const displayTotal = Math.min(newTotalCompletion, 100);
                        
                        summaryCompletionEl.textContent = displayTotal.toFixed(1);
                        if (summaryCompletionBar) {
                            summaryCompletionBar.style.width = displayTotal + '%';
                            summaryCompletionBar.setAttribute('aria-valuenow', displayTotal.toFixed(1));
                        }
                    }
                }
            }
        }
    });

    // Handle Form Submission via AJAX (Existing Logic)
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

            // --- Overage Checks (Budget & Timeframe) ---
            const actionId = formData.get('executive_activity_action_id');
            const amountSpent = parseFloat(formData.get('amount_spent')) || 0;
            const summarySpentEl = document.getElementById('summary-spent-exec-' + actionId);
            const summaryBudgetEl = document.getElementById('summary-budget-exec-' + actionId);
            
            const baseSpent = summarySpentEl ? (parseFloat(summarySpentEl.dataset.baseValue) || 0) : 0;
            const plannedAmount = summaryBudgetEl ? (parseFloat(summaryBudgetEl.dataset.baseValue) || 0) : 0;
            const newTotalSpent = baseSpent + amountSpent;

            const actualEndDateStr = formData.get('actual_finish_date_gregorian');
            const actionCard = document.getElementById('action-card-' + actionId);
            const plannedEndDateStr = actionCard ? actionCard.dataset.plannedEndDate : null;

            const isBudgetExceeded = plannedAmount > 0 && newTotalSpent > (plannedAmount + 0.01);
            const isTimeExceeded = plannedEndDateStr && actualEndDateStr && new Date(actualEndDateStr) > new Date(plannedEndDateStr);

            if (isBudgetExceeded || isTimeExceeded) {
                let message = '';
                if (isBudgetExceeded && isTimeExceeded) {
                    message = 'لقد تجاوزت المبلغ المالي المسموح به والفترة الزمنية المحددة. هل ترغب في الاستمرار بتقديم تبريرات مالية وتقنية؟';
                } else if (isBudgetExceeded) {
                    message = 'لقد تجاوزت المبلغ المالي المسموح به. هل ترغب في الاستمرار وتقديم تبرير مالي؟';
                } else {
                    message = 'لقد تجاوزت الفترة المحددة للنشاط. هل ترغب في الاستمرار بتقديم تبرير تقني؟';
                }

                Swal.fire({
                    title: 'تنبيه تجاوز الحدود',
                    html: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'نعم، استمر',
                    cancelButtonText: 'إلغاء',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeSubmit();
                    }
                });
            } else {
                executeSubmit();
            }

            const self = this;
            function executeSubmit() {
                self.disabled = true;
                const originalText = self.innerHTML;
                self.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
            .then(response => response.json())
            .then(async data => {
                if (!data.success && data.code === 'insufficient_installment_balance') {
                    const confirmation = await Swal.fire({
                        title: 'Installment balance exceeded',
                        html: `Available: <strong>${Number(data.available_amount).toFixed(2)}</strong><br>Requested: <strong>${Number(data.requested_amount).toFixed(2)}</strong><br>Shortage: <strong>${Number(data.shortage_amount).toFixed(2)}</strong>`,
                        icon: 'warning', showCancelButton: true,
                        confirmButtonText: 'Submit financial justification', cancelButtonText: 'Cancel',
                    });
                    if (!confirmation.isConfirmed) { return; }
                    const source = await Swal.fire({
                        title: 'Financial justification',
                        html: `<input id="workflow-funding-source" class="swal2-input" placeholder="Funding source" value="{{ addslashes($currentInstallment?->fundingSource?->name ?? '') }}"><textarea id="workflow-justification" class="swal2-textarea" placeholder="Explain the amount above the available installment balance"></textarea>`,
                        focusConfirm: false, showCancelButton: true,
                        confirmButtonText: 'Submit for approval', cancelButtonText: 'Back to form',
                        preConfirm: () => {
                            const fundingSource = document.getElementById('workflow-funding-source').value.trim();
                            const justification = document.getElementById('workflow-justification').value.trim();
                            if (!fundingSource || justification.length < 10) { Swal.showValidationMessage('Enter a funding source and a justification of at least 10 characters.'); return false; }
                            return { fundingSource, justification };
                        },
                    });
                    if (!source.isConfirmed) { return; }
                    const justificationData = new FormData(form);
                    justificationData.set('execution_type', 'executive');
                    justificationData.set('funding_source', source.value.fundingSource);
                    justificationData.set('justification', source.value.justification);
                    const justificationResponse = await fetch("{{ route('projects.execution.financial-justification', $project) }}", {
                        method: 'POST', body: justificationData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    const justificationResult = await justificationResponse.json();
                    if (!justificationResponse.ok || !justificationResult.success) {
                        await Swal.fire('Unable to submit', justificationResult.message || 'The request could not be submitted.', 'error');
                        return;
                    }
                    await Swal.fire('Submitted for approval', `Request ${justificationResult.request.number} is now in the Approval Center.`, 'success');
                    return;
                }
                if (data.success) {
                    // Re-calculate overages for the success message/logic
                    const actionId = formData.get('executive_activity_action_id');
                    const actualEndDate = new Date(formData.get('actual_finish_date_gregorian'));
                    const amountSpent = parseFloat(formData.get('amount_spent')) || 0;
                    
                    const actionCard = document.getElementById('action-card-' + actionId);
                    const plannedEndDate = actionCard ? new Date(actionCard.dataset.plannedEndDate) : null;
                    
                    const summarySpentEl = document.getElementById('summary-spent-exec-' + actionId);
                    const summaryBudgetEl = document.getElementById('summary-budget-exec-' + actionId);
                    const baseSpent = summarySpentEl ? (parseFloat(summarySpentEl.dataset.baseValue) || 0) : 0;
                    const plannedAmount = summaryBudgetEl ? (parseFloat(summaryBudgetEl.dataset.baseValue) || 0) : 0;
                    
                    const isTimeExceeded = plannedEndDate && actualEndDate > plannedEndDate;
                    const isBudgetExceeded = plannedAmount > 0 && (baseSpent + amountSpent) > (plannedAmount + 0.01);
                    
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                    let message = data.message;
                    
                    if (isTimeExceeded || isBudgetExceeded) {
                        message += '<br><small>⚠️ تم إضافة التنفيذ مع وجود تجاوز في ';
                        message += (isTimeExceeded && isBudgetExceeded) ? 'الميزانية والمدة الزمنية' : (isTimeExceeded ? 'المدة الزمنية' : 'الميزانية');
                        message += '. يرجى إضافة التبريرات اللازمة.</small>';
                    }
                    
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i> ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    form.prepend(alertDiv);
                    
                    // Reset form
                    form.reset();
                    
                    // Trigger justification prompts
                    if (isTimeExceeded || isBudgetExceeded) {
                        setTimeout(() => {
                            if (isTimeExceeded) {
                                flasher.warning('يرجى إضافة تبرير تقني للتنفيذ خارج الفترة المخططة');
                                const delayBtn = document.querySelector(`[data-action-id="${actionId}"].timeline-overage-icon`);
                                if (delayBtn) delayBtn.click();
                            }
                            
                            if (isBudgetExceeded) {
                                flasher.warning('يرجى إضافة تبرير مالي لتجاوز الميزانية');
                                const financeBtn = document.querySelector(`[data-action-id="${actionId}"].financial-overage-icon`);
                                if (financeBtn) financeBtn.click();
                            }

                            setTimeout(() => {
                                window.location.reload();
                            }, 4000);
                        }, 800);
                    } else {
                        setTimeout(() => {
                            window.location.reload(); 
                        }, 1000);
                    }
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
            const plannedAmount = parseFloat(card.dataset.plannedAmount) || 0;

            if (plannedEndDate) {
                rows.forEach(row => {
                    const finishDateInput = row.querySelector('.actual-finish-gregorian');
                    if (finishDateInput && finishDateInput.value && finishDateInput.value > plannedEndDate) {
                        hasTimelineOverage = true;
                    }
                });

                // Check if today is past planned end date and not completed
                const today = new Date().toISOString().split('T')[0];
                const progressBar = card.querySelector('.progress-bar');
                const completionPercentage = progressBar ? (parseFloat(progressBar.getAttribute('aria-valuenow')) || 0) : 0;
                
                if (today > plannedEndDate && completionPercentage < 100) {
                    hasTimelineOverage = true;
                }
            }
            
            // Show/hide icons based on conditions
            const financialIcon = container.querySelector('.financial-overage-icon');
            const timelineIcon = container.querySelector('.timeline-overage-icon');
            
            const isFinancialOverage = totalSpent > plannedAmount;

            if (financialIcon) {
                const hasExistingFinancial = financialIcon.dataset.hasJustification === 'true';
                financialIcon.style.display = (isFinancialOverage || hasExistingFinancial) ? 'block' : 'none';
            }
            
            // Auto-show technical section if overage OR has existing justifications
            const technicalSection = document.getElementById('technical-justification-section-' + actionId);
            if (technicalSection) {
                const hasJustifications = technicalSection.dataset.hasJustifications === 'true';
                if (hasTimelineOverage || hasJustifications) {
                    technicalSection.style.display = 'block';
                } else {
                    technicalSection.style.display = 'none';
                }
            }
            
            if (timelineIcon) {
                const hasExistingTechnical = timelineIcon.dataset.hasTechnicalJustification === 'true';
                timelineIcon.style.display = (hasTimelineOverage || hasExistingTechnical) ? 'block' : 'none';
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
            const section = document.getElementById('financial-justification-section-' + actionId);
            if(section) {
                section.style.display = 'block';
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });

    document.querySelectorAll('.add-technical-justification-action').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const actionId = this.dataset.actionId;
            const section = document.getElementById('technical-justification-section-' + actionId);
            if(section) {
                section.style.display = 'block';
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });

    // Handle overage icon clicks
    document.querySelectorAll('.financial-overage-icon').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const actionId = this.dataset.actionId;
            const section = document.getElementById('financial-justification-section-' + actionId);
            if(section) {
                section.style.display = 'block';
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });

    document.querySelectorAll('.timeline-overage-icon').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const actionId = this.dataset.actionId;
            const section = document.getElementById('technical-justification-section-' + actionId);
            if(section) {
                section.style.display = 'block';
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

    // Event listener for showing EXECUTIVE technical justification to populate fields
    document.querySelectorAll('[id^="technicalJustificationCollapse-"]').forEach(el => {
        el.addEventListener('show.bs.collapse', function () {
            const actionId = this.id.replace('technicalJustificationCollapse-', '');
            const card = document.getElementById('action-card-' + actionId);
            if (!card) return;

            const plannedStartDateStr = card.dataset.plannedStartDate;
            const plannedEndDateStr = card.dataset.plannedEndDate;
            
            // Find the execution with the latest actual end date
            const tbody = document.getElementById('execution_tbody_' + actionId);
            let maxEndDate = null;
            let targetRow = null;

            if (tbody) {
                const rows = tbody.querySelectorAll('tr[id^="exec-row-"]');
                rows.forEach(row => {
                    const finishDateInput = row.querySelector('.actual-finish-gregorian');
                    if (finishDateInput && finishDateInput.value) {
                        const actualEndDate = new Date(finishDateInput.value);
                        if (!maxEndDate || actualEndDate > maxEndDate) {
                            maxEndDate = actualEndDate;
                            targetRow = row;
                        }
                    }
                });
            }

            if (targetRow && maxEndDate && plannedEndDateStr) {
                const plannedEndDate = new Date(plannedEndDateStr);
                
                // Populate fields
                const execPlannedStart = document.getElementById('exec-planned-start-date-' + actionId);
                if(execPlannedStart) execPlannedStart.value = plannedStartDateStr;
                
                const execActualStart = document.getElementById('exec-actual-start-date-' + actionId);
                const actualStartDateInput = targetRow.querySelector('.actual-start-gregorian');
                if(execActualStart && actualStartDateInput) execActualStart.value = actualStartDateInput.value;

                const execActualEnd = document.getElementById('exec-actual-end-date-' + actionId);
                if(execActualEnd) {
                    execActualEnd.value = maxEndDate.toISOString().split('T')[0];
                    // Trigger change event to show justification text area because of 'check-date-overage' class listener
                    const event = new Event('change');
                    execActualEnd.dispatchEvent(event);
                }
                
                // Calculate Delay
                const diffTime = Math.abs(maxEndDate - plannedEndDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                const isDelay = maxEndDate > plannedEndDate;
                
                const delayDaysVal = isDelay ? diffDays : 0;
                
                const execDelayDays = document.getElementById('exec-delay-days-' + actionId);
                if(execDelayDays) execDelayDays.value = delayDaysVal;
            }
        });
    });

    // Handle Delete Execution
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-execution-row')) {
            const btn = e.target.closest('.delete-execution-row');
            const execId = btn.dataset.execId;
            const execType = btn.dataset.execType; // 'executive' or 'preliminary'
            
            Swal.fire({
                title: 'تأكيد العملية',
                text: 'هل أنت متأكد من حذف هذا السجل؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم',
                cancelButtonText: 'لا',
                reverseButtons: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    const row = document.getElementById('exec-row-' + execId);
                    const detailsRow = document.getElementById('details-row-' + execId);
                    
                    const projectId = "{{ $project->id }}";
                    const url = `/projects/${projectId}/execution/${execId}?type=${execType}`;
                    
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            row.remove();
                            if(detailsRow) detailsRow.remove();
                            flasher.success(data.message);
                            // Update totals/overages
                            checkExecutiveActionOverages();
                        } else {
                            flasher.error(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        flasher.error('حدث خطأ أثناء الحذف');
                    });
                }
            });
        }
    });

    // Handle Edit Execution
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-execution-row')) {
            const btn = e.target.closest('.edit-execution-row');
            const execId = btn.dataset.execId;
            const row = document.getElementById('exec-row-' + execId);
            
            // Toggle Edit Mode
            if (btn.classList.contains('editing')) {
                // Save Mode
                saveExecutionRow(btn, execId, row);
            } else {
                // Enter Edit Mode
                enterEditMode(btn, row);
            }
        }
    });

    function enterEditMode(btn, row) {
        btn.classList.add('editing', 'btn-success');
        btn.classList.remove('btn-outline-warning');
        btn.innerHTML = '<i class="fas fa-save"></i>';
        
        // Enable inputs
        row.querySelectorAll('input, select').forEach(input => {
            if (!input.classList.contains('completion-percentage-field')) { 
                 input.disabled = false;
            }
             // Enable all for now
             input.disabled = false;
        });

        // Re-trigger Hijri conversion for enabled fields
        row.querySelectorAll('.actual-start-gregorian, .actual-finish-gregorian').forEach(input => {
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function saveExecutionRow(btn, execId, row) {
        const projectId = "{{ $project->id }}";
        const url = `/projects/${projectId}/execution/${execId}`;
        const execType = btn.dataset.execType;

        const data = {
            _method: 'PUT',
            type: execType,
            actual_start_date_gregorian: row.querySelector('.actual-start-gregorian').value,
            actual_finish_date_gregorian: row.querySelector('.actual-finish-gregorian').value,
            status: row.querySelector('.exec-status').value,
            actual_amount: row.querySelector('.actual-amount').value,
            amount_spent: row.querySelector('.amount-spent').value,
            completion_percentage: row.querySelector('.completion-percentage-field').value
        };

        fetch(url, {
            method: 'POST', // Method spoofing for PUT
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                flasher.success(data.message);
                // Exit Edit Mode
                btn.classList.remove('editing', 'btn-success');
                btn.classList.add('btn-outline-warning');
                btn.innerHTML = '<i class="fas fa-edit"></i>';
                
                // Disable inputs
                row.querySelectorAll('input, select').forEach(input => input.disabled = true);
                
                // Reload page to update calculations properly
                setTimeout(() => window.location.reload(), 500);
            } else {
                flasher.error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            flasher.error('حدث خطأ أثناء الحفظ');
        });
    }

    // Helper function to display file list
    function displayFileList(input, fileList) {
        if (!fileList) return;
        
        fileList.innerHTML = '';
        
        if (input.files.length > 0) {
            const ul = document.createElement('ul');
            ul.className = 'list-unstyled mt-2';
            
            for (let i = 0; i < input.files.length; i++) {
                const file = input.files[i];
                const li = document.createElement('li');
                li.className = 'mb-2';
                li.innerHTML = `
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-file me-1"></i> ${file.name} (${(file.size / 1024).toFixed(2)} KB)
                    </span>
                `;
                ul.appendChild(li);
            }
            
            fileList.appendChild(ul);
        }
    }

    // File input change handler for executive financial justification
    document.querySelectorAll('.executive-financial-attachments').forEach(input => {
        input.addEventListener('change', function() {
            const actionId = this.closest('form').dataset.actionId;
            const fileList = document.getElementById('financial-file-list-' + actionId);
            displayFileList(this, fileList);
        });
    });

    // Handle Executive Financial Justification Form Submission
    document.querySelectorAll('.executive-financial-justification-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const actionId = this.dataset.actionId;
            const originalBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = originalBtn.innerHTML;

            originalBtn.disabled = true;
            originalBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    flasher.success('تم حفظ التبرير المالي بنجاح');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    flasher.error(data.message || 'فشل حفظ التبرير');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                flasher.error('حدث خطأ أثناء حفظ التبرير');
            })
            .finally(() => {
                originalBtn.disabled = false;
                originalBtn.innerHTML = originalBtnText;
            });
        });
    });

    // Check justification fields visibility
    function checkJustificationFieldsVisibility(input) {
        const plannedDate = input.dataset.plannedDate;
        const actualDate = input.value;
        const targetContainerId = input.dataset.targetContainer;
        const container = document.getElementById(targetContainerId);
        
        if (container && plannedDate && actualDate) {
            if (actualDate > plannedDate) {
                container.style.display = 'block';
                 // Optionally require the fields if visible
                 container.querySelectorAll('textarea, input').forEach(field => {
                    if (field.name === 'justification') field.required = false;
                 });
            } else {
                container.style.display = 'none';
                 // Optionally un-require the fields if hidden so form can submit (if that was allowed)
                 // But here we might want to allow submitting "No Delay" updates if this form supports it?
                 // No, this form is ONLY for adding justification. 
                 // If no delay, there is nothing to submit (button is inside the container or outside?)
                 // In the previous step, I wrapped the BUTTON inside the container too?
                 // Let's check.
                 
                 // Step 35: 
                 // The BUTTONS are AFTER the wrapper in the original code? 
                 // No, I wrapped them? 
                 // Ah, in Step 35, I replaced lines 50-61 with wrapper.
                 // The buttons were lines 63-70. They were OUTSIDE the wrapper.
                 // So if hidden, user can still see "Save" and "Cancel"?
                 // Check line 63 in Step 33.
                 // "div class='mt-3 d-flex gap-2' ... button submit".
                 // That block was NOT part of replacement in Step 35. 
                 // So buttons are VISIBLE.
                 // If hidden, and user clicks Save, it submits empty justification?
                 // But validaton?
                 container.querySelectorAll('textarea, input').forEach(field => {
                    if (field.name === 'justification') field.required = false;
                 });
            }
        }
    }

    // Attach listeners to date inputs
    document.querySelectorAll('.check-date-overage').forEach(input => {
        input.addEventListener('change', function() {
            checkJustificationFieldsVisibility(this);
        });
        
        // Initial check if value exists
        if(input.value) {
            checkJustificationFieldsVisibility(input);
        }
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

<!-- Include Delay Justification Modal -->
@include('projects.partials.implementation.execution-delay-justification-modal')

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkDelayedActions();
    
    // Attach click handlers to delay justification buttons
    document.querySelectorAll('[data-action-id].timeline-overage-icon').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const actionId = this.dataset.actionId;
            const actionCard = document.getElementById('action-card-' + actionId);
            
            if (!actionCard) return;
            
            const plannedEndDate = actionCard.dataset.plannedEndDate;
            
            // Get the execution table row to find the actual dates
            const executionRow = document.querySelector(`tr[data-action-id="${actionId}"][data-actual-end-date]`);
            
            if (!executionRow) {
                flasher.warning('لم يتم العثور على تاريخ فعلي. يرجى إضافة تسجيل تنفيذ أولا.');
                return;
            }
            
            const actualStartDate = executionRow.dataset.actualStartDate || '';
            const actualEndDate = executionRow.dataset.actualEndDate || '';
            const plannedStartDate = actionCard.dataset.plannedStartDate || new Date().toISOString().split('T')[0];
            
            if (!actualEndDate) {
                flasher.warning('لم يتم العثور على تاريخ انتهاء فعلي. يرجى إضافة تاريخ الانتهاء الفعلي أولا.');
                return;
            }
            
            openDelayJustificationModal(
                'executive',
                actionId,
                plannedStartDate,
                plannedEndDate,
                actualStartDate,
                actualEndDate
            );
        });
    });
});

function checkDelayedActions() {
    document.querySelectorAll('[id^="action-card-"]').forEach(card => {
        const actionId = card.id.replace('action-card-', '');
        const plannedEndDate = card.dataset.plannedEndDate;
        
        if (!plannedEndDate) return;
        
        // Get the latest execution row for this action
        const executionRow = document.querySelector(`tr[data-action-id="${actionId}"][data-actual-end-date]`);
        
        if (!executionRow) return;
        
        const actualEndDate = executionRow.dataset.actualEndDate;
        
        if (!actualEndDate) return;
        
        // Compare dates
        const planned = new Date(plannedEndDate);
        const actual = new Date(actualEndDate);
        
        if (actual > planned) {
            // Show the delay button
            const delayBtn = document.getElementById(`tech-justification-btn-${actionId}`);
            if (delayBtn) {
                delayBtn.style.display = 'block';
            }
        }
    });
}

// Re-check delays when new execution is added
function reloadDelayCheck() {
    setTimeout(checkDelayedActions, 500);
}
</script>
