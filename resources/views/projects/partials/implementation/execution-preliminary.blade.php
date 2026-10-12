<!-- Preliminary Activities Section -->
<div class="mb-5">
    @if($project->preliminaryActivities && $project->preliminaryActivities->count() > 0)
        @foreach($project->preliminaryActivities as $activity)
            <div class="mb-5">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h4 class="text-primary mb-0 fw-bold">{{ $activity->name }}</h4>
                    <span class="badge bg-light text-primary ms-3 border">{{ $activity->weight }}%</span>
                    @can('execution.assign')
                        <button type="button" class="btn btn-sm btn-outline-primary ms-3 btn-assign" 
                                data-assignable-type="preliminary_activity" 
                                data-assignable-id="{{ $activity->id }}"
                                data-assignable-name="{{ $activity->name }}"
                                title="تكليف مستخدمين">
                            <i class="fas fa-user-plus me-1"></i>تكليف
                        </button>
                    @endcan
                    <div class="assigned-users-list ms-3 d-flex align-items-center gap-1 flex-wrap" id="assigned-users-preliminary_activity-{{ $activity->id }}">
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
                    @foreach($activity->procedures as $procedure)
                        @php
                            $totalPlanned = $procedure->costs->sum('total');
                            $executions = $procedure->executions; // Assuming relationship exists
                            
                            // Aggregate data from all execution records
                            $completionPct = $executions->sum('completion_percentage');
                            $totalActualAmount = $executions->sum('actual_amount');
                            $totalSpent = $executions->sum('amount_spent');
                            
                            // Ensure completion doesn't exceed 100 for display if logic allows overage
                            $displayPct = min($completionPct, 100);
                            
                            $lastExecution = $executions->sortByDesc('created_at')->first();
                            $status = $lastExecution?->status ?? 'not_started';
                            $statusColors = [
                                'not_started' => 'secondary',
                                'in_progress' => 'primary',
                                'delayed' => 'danger',
                                'stalled' => 'warning',
                                'completed' => 'success'
                            ];
                        @endphp
                        <div class="col-12">
                            <div class="card border-0 shadow-sm h-100 execution-card" id="procedure-card-{{ $procedure->id }}" data-planned-start-date="{{ $activity->start_date_gregorian ? $activity->start_date_gregorian->format('Y-m-d') : '' }}" data-planned-end-date="{{ $activity->end_date_gregorian ? $activity->end_date_gregorian->format('Y-m-d') : '' }}" data-planned-amount="{{ $totalPlanned }}">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3 text-secondary">
                                            <i class="fas fa-clipboard-list fa-lg"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 fw-bold text-dark">{{ $procedure->procedure_name }}</h5>
                                            <div class="small text-muted d-flex align-items-center gap-3 flex-wrap">
                                                <span><i class="fas fa-coins me-1"></i>المخطط: {{ number_format($totalPlanned, 2) }} ﷼</span>
                                                <span><i class="fas fa-weight-hanging me-1"></i>الوزن: {{ $procedure->weight }}%</span>
                                                @can('execution.assign')
                                                    <button type="button" class="btn btn-link btn-sm text-primary p-0 btn-assign" style="text-decoration: none;"
                                                            data-assignable-type="preliminary_procedure" 
                                                            data-assignable-id="{{ $procedure->id }}"
                                                            data-assignable-name="{{ $procedure->procedure_name }}"
                                                            title="تكليف مستخدمين">
                                                        <i class="fas fa-user-plus me-1"></i>تكليف
                                                    </button>
                                                @endcan
                                                <div class="assigned-users-list d-flex align-items-center gap-1 flex-wrap" id="assigned-users-preliminary_procedure-{{ $procedure->id }}">
                                                    @foreach($procedure->assignments as $assignment)
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
                                                    <div class="progress-bar bg-{{ $statusColors[$status] ?? 'primary' }}" 
                                                         role="progressbar" 
                                                         style="width: {{ $displayPct }}%; box-shadow: 0 0 10px rgba(var(--bs-{{ $statusColors[$status] ?? 'primary' }}-rgb), 0.5);" 
                                                         aria-valuenow="{{ $displayPct }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                                <span class="ms-2 fw-bold small text-{{ $statusColors[$status] ?? 'primary' }}">{{ number_format($completionPct, 1) }}%</span>
                                            </div>
                                        </div>
                                        <div class="vr mx-2 d-none d-md-block"></div>
                                        <span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }} px-3 py-2 rounded-pill">
                                            {{ __('execution.status.' . $status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-8">
                                            <h6 class="text-muted text-uppercase small fw-bold mb-3">تفاصيل التكاليف</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless bg-light rounded">
                                                    <thead>
                                                        <tr class="text-muted border-bottom">
                                                            <th class="ps-3">البند</th>
                                                            <th>الكمية</th>
                                                            <th class="text-end pe-3">القيمة</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($procedure->costs as $cost)
                                                            <tr>
                                                                <td class="ps-3">{{ $cost->financialItem->name ?? '-' }}</td>
                                                                <td>{{ $cost->quantity }}</td>
                                                                <td class="text-end pe-3 fw-bold">{{ number_format($cost->total, 2) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="3" class="text-center text-muted small py-2">لا توجد تكاليف مسجلة</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-4 border-start border-light">
                                            <h6 class="text-muted text-uppercase small fw-bold mb-3">ملخص التنفيذ</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">تاريخ البداية:</span>
                                                <span class="fw-bold text-info">{{ $procedure->start_date ? \Carbon\Carbon::parse($procedure->start_date)->format('Y-m-d') : '-' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">تاريخ النهاية:</span>
                                                <span class="fw-bold text-info">{{ $procedure->end_date ? \Carbon\Carbon::parse($procedure->end_date)->format('Y-m-d') : '-' }}</span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">المبلغ المخطط (الميزانية):</span>
                                                <span class="fw-bold text-primary">{{ number_format($totalPlanned, 2) }} ﷼</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">إجمالي المبلغ المخصص (التعميد):</span>
                                                <span class="fw-bold text-info">{{ number_format($totalActualAmount, 2) }} ﷼</span>
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
                                            @if(($totalPlanned - $totalSpent) < 0 || ($procedure->budgetJustification))
                                                <!-- Financial Overage Button -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger financial-overage-icon w-100 mt-2"
                                                        data-procedure-id="{{ $procedure->id }}"
                                                        data-has-justification="{{ $procedure->budgetJustification ? 'true' : 'false' }}"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#financialJustificationCollapse-{{ $procedure->id }}"
                                                        title="تم تجاوز الميزانية - إضافة تبرير مالي">
                                                    <i class="fas fa-exclamation-circle me-1"></i>
                                                    {{ ($totalPlanned - $totalSpent) < 0 ? 'تجاوز مالي' : 'عرض التبرير المالي' }}
                                                </button>

                                                <!-- Financial Justification Partial -->
                                                <div class="collapse mt-3" id="financialJustificationCollapse-{{ $procedure->id }}">
                                                    @include('projects.partials.implementation.execution-procedure-financial-justification', [
                                                        'procedure' => $procedure, 
                                                        'project' => $project,
                                                        'totalPlanned' => $totalPlanned,
                                                        'totalSpent' => $totalSpent
                                                    ])
                                                </div>
                                            @endif

                                            @php
                                                $hasTimelineOverageBlade = $executions->contains(function($exec) use ($procedure) {
                                                    return $exec->actual_finish_date_gregorian && $procedure->end_date && 
                                                           $exec->actual_finish_date_gregorian->gt($procedure->end_date);
                                                }) || ($procedure->technicalJustifications && $procedure->technicalJustifications->count() > 0);
                                            @endphp

                                            @if($hasTimelineOverageBlade)
                                                <!-- Technical Justification Button -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-warning timeline-overage-icon w-100 mt-2"
                                                        id="tech-justification-btn-{{ $procedure->id }}"
                                                        data-procedure-id="{{ $procedure->id }}"
                                                        data-has-technical-justification="{{ ($procedure->technicalJustifications && $procedure->technicalJustifications->count() > 0) ? 'true' : 'false' }}"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#technicalJustificationCollapse-{{ $procedure->id }}"
                                                        title="{{ ($procedure->technicalJustifications && $procedure->technicalJustifications->count() > 0) ? 'عرض التبرير التقني' : 'إضافة تبرير تقني' }}">
                                                    <i class="fas fa-hourglass-end me-1"></i>
                                                    {{ ($procedure->technicalJustifications && $procedure->technicalJustifications->count() > 0) ? 'عرض التبرير التقني' : 'تبرير تقني' }}
                                                </button>

                                                <!-- Technical Justification Partial -->
                                                <div class="collapse mt-3" id="technicalJustificationCollapse-{{ $procedure->id }}">
                                                    @include('projects.partials.implementation.execution-procedure-technical-justification', [
                                                        'procedure' => $procedure, 
                                                        'project' => $project
                                                    ])
                                                </div>
                                            @endif

                                            <div class="mt-3">
                                                <button class="btn btn-outline-primary btn-sm w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#execution-history-{{ $procedure->id }}">
                                                    <i class="fas fa-history me-1"></i> عرض السجل / إضافة تنفيذ
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Collapsible Section for History & Form -->
                                    <div class="collapse" id="execution-history-{{ $procedure->id }}">
                                        <div class="bg-light rounded p-3 mb-3">
                                            <ul class="nav nav-pills mb-3" id="pills-tab-{{ $procedure->id }}" role="tablist">
                                                @can('execution.log.view')
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active btn-sm" id="pills-home-tab-{{ $procedure->id }}" data-bs-toggle="pill" data-bs-target="#pills-home-{{ $procedure->id }}" type="button" role="tab">
                                                        <i class="fas fa-list me-1"></i> سجل العمليات
                                                    </button>
                                                </li>
                                                @endcan
                                                @can('execution.add')
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link {{ !auth()->user()->hasPermission('execution.log.view') ? 'active' : '' }} btn-sm" id="pills-profile-tab-{{ $procedure->id }}" data-bs-toggle="pill" data-bs-target="#pills-profile-{{ $procedure->id }}" type="button" role="tab">
                                                        <i class="fas fa-plus-circle me-1"></i> إضافة جديد
                                                    </button>
                                                </li>
                                                @endcan
                                            </ul>
                                            <div class="tab-content" id="pills-tabContent-{{ $procedure->id }}">
                                                <!-- Operations Log Tab -->
                                                @can('execution.log.view')
                                                <div class="tab-pane fade show active" id="pills-home-{{ $procedure->id }}" role="tabpanel">
                                                    @include('projects.partials.implementation.execution-preliminary-display', ['allExecutions' => $executions, 'procedure' => $procedure])
                                                </div>
                                                @endcan
                                                
                                                <!-- Add New Tab -->
                                                @can('execution.add')
                                                <div class="tab-pane fade {{ !auth()->user()->hasPermission('execution.log.view') ? 'show active' : '' }}" id="pills-profile-{{ $procedure->id }}" role="tabpanel">
                                                    <form action="{{ route('projects.execution.store', $project) }}" 
                                                          method="POST" 
                                                          enctype="multipart/form-data"
                                                          class="execution-form"
                                                          data-procedure-id="{{ $procedure->id }}">
                                                        @csrf
                                                        <input type="hidden" name="preliminary_procedure_id" value="{{ $procedure->id }}">
                                                        
                                                        @include('projects.partials.implementation.execution-preliminary-form', ['allExecutions' => $executions, 'procedure' => $procedure])
                                                        
                                                        <div class="text-end mt-3">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#execution-history-{{ $procedure->id }}">إلغاء</button>
                                                            <button type="submit" class="btn btn-primary btn-sm save-execution-btn">
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
            <i class="fas fa-clipboard-check fa-3x mb-3 opacity-50"></i>
            <p>لا يوجد أنشطة تمهيدية لهذا المشروع.</p>
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
            
            const procedureId = container.dataset.procedureId || container.closest('.execution-card')?.id.replace('procedure-card-', '');
            const budgetWarning = document.getElementById('budget-warning-' + procedureId);

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
                if (procedureId && !container.id.startsWith('exec-row-')) {
                    const summarySpentEl = document.getElementById('summary-spent-' + procedureId);
                    const summaryRemainingEl = document.getElementById('summary-remaining-' + procedureId);
                    const summaryRemainingBar = document.getElementById('summary-remaining-bar-' + procedureId);
                    const summaryBudgetEl = document.getElementById('summary-budget-' + procedureId);

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
                if (procedureId && !container.id.startsWith('exec-row-')) {
                    const summaryCompletionEl = document.getElementById('summary-completion-' + procedureId);
                    const summaryCompletionBar = document.getElementById('summary-completion-bar-' + procedureId);

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

    // Check for budget overages and show/hide justification sections
    function checkBudgetOverages() {
        @foreach($project->preliminaryActivities as $activity)
            @foreach($activity->procedures as $procedure)
                @php
                    $totalPlanned = $procedure->costs->sum('total');
                    $executions = $procedure->executions;
                    $totalActual = $executions->sum('amount_spent');
                @endphp
                
                const procedureId = {{ $procedure->id }};
                const planned = {{ $totalPlanned }};
                const actual = {{ $totalActual }};
                const section = document.getElementById('budget-justification-section-' + procedureId);
                
                if (section) {
                    if (actual > planned) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                }
            @endforeach
        @endforeach
    }
    
    // We are removing the call to checkBudgetOverages for preliminary because we have a more comprehensive function below.
    // checkBudgetOverages();

    // File input change handler for financial justification
    document.querySelectorAll('.procedure-financial-attachments').forEach(input => {
        input.addEventListener('change', function() {
            const procedureId = this.closest('form').dataset.procedureId;
            const fileList = document.getElementById('financial-file-list-' + procedureId);
            displayFileList(this, fileList);
        });
    });

    // File input change handler for technical justification
    document.querySelectorAll('.procedure-technical-attachments').forEach(input => {
        input.addEventListener('change', function() {
            const procedureId = this.closest('form').dataset.procedureId;
            const fileList = document.getElementById('technical-file-list-' + procedureId);
            displayFileList(this, fileList);
        });
    });

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

    // Handle Form Submission via AJAX
    document.querySelectorAll('.save-execution-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const procedureId = form.dataset.procedureId;
            const formData = new FormData(form);
            const originalBtnText = this.innerHTML;

            // Basic Validation
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // --- Overage Checks (Budget & Timeframe) ---
            const amountSpent = parseFloat(formData.get('amount_spent')) || 0;
            const summarySpentEl = document.getElementById('summary-spent-' + procedureId);
            const summaryBudgetEl = document.getElementById('summary-budget-' + procedureId);
            
            const baseSpent = summarySpentEl ? (parseFloat(summarySpentEl.dataset.baseValue) || 0) : 0;
            const plannedAmount = summaryBudgetEl ? (parseFloat(summaryBudgetEl.dataset.baseValue) || 0) : 0;
            const newTotalSpent = baseSpent + amountSpent;

            const actualEndDateStr = formData.get('actual_finish_date_gregorian');
            const procedureCard = document.getElementById('procedure-card-' + procedureId);
            const plannedEndDateStr = procedureCard ? procedureCard.dataset.plannedEndDate : null;

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
                    justificationData.set('execution_type', 'preliminary');
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
                    const actualEndDate = new Date(formData.get('actual_finish_date_gregorian'));
                    const amountSpent = parseFloat(formData.get('amount_spent')) || 0;
                    
                    const procedureCard = document.getElementById('procedure-card-' + procedureId);
                    const plannedEndDate = procedureCard ? new Date(procedureCard.dataset.plannedEndDate) : null;
                    const plannedEndDateStr = procedureCard ? procedureCard.dataset.plannedEndDate : null;
                    
                    const summarySpentEl = document.getElementById('summary-spent-' + procedureId);
                    const summaryBudgetEl = document.getElementById('summary-budget-' + procedureId);
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
                                const delayBtn = document.querySelector(`[data-procedure-id="${procedureId}"].timeline-overage-icon`);
                                if (delayBtn) delayBtn.click();
                            }
                            
                            if (isBudgetExceeded) {
                                flasher.warning('يرجى إضافة تبرير مالي لتجاوز الميزانية');
                                const financeBtn = document.querySelector(`[data-procedure-id="${procedureId}"].financial-overage-icon`);
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

    // Handle Procedure Financial Justification Form Submission
    document.querySelectorAll('.procedure-financial-justification-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const procedureId = this.dataset.procedureId;
            const originalBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = originalBtn.innerHTML;

            originalBtn.disabled = true;
            originalBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';

            fetch(this.action, {
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

    // Handle Procedure Technical Justification Form Submission
    document.querySelectorAll('.procedure-technical-justification-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const procedureId = this.dataset.procedureId;
            const originalBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = originalBtn.innerHTML;

            originalBtn.disabled = true;
            originalBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';

            fetch(this.action, {
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
                    flasher.success('تم حفظ التبرير التقني بنجاح');
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

    // Check for budget and timeline overages for preliminary activities
    function checkPreliminaryProcedureOverages() {
        document.querySelectorAll('[id^="prel-justification-icons-"]').forEach(container => {
            const procedureId = container.id.replace('prel-justification-icons-', '');
            const card = document.getElementById('procedure-card-' + procedureId);
            
            if (!card) return;
            
            const tableId = 'execution_tbody_' + procedureId;
            const tbody = document.getElementById(tableId);
            
            let totalActualAmount = 0;
            let totalSpent = 0;
            let hasTimelineOverage = false;
            let rowsFound = false;

            if (tbody) {
                const rows = tbody.querySelectorAll('tr[id^="exec-row-"]');
                if (rows && rows.length > 0) {
                    rowsFound = true;
                    rows.forEach(row => {
                        const actualAmountInput = row.querySelector('.actual-amount');
                        const spentInput = row.querySelector('.amount-spent');
                        if (actualAmountInput) totalActualAmount += parseFloat(actualAmountInput.value) || 0;
                        if (spentInput) totalSpent += parseFloat(spentInput.value) || 0;
                    });
                }
            }
            
            const plannedEndDate = card.querySelector('[data-planned-end-date]')?.dataset.plannedEndDate;
            const plannedAmount = parseFloat(card.dataset.plannedAmount) || 0;
            
            // Check Timeline Overage
            if (plannedEndDate) {
                if (rowsFound) {
                    tbody.querySelectorAll('tr[id^="exec-row-"]').forEach(row => {
                        const finishDateInput = row.querySelector('.actual-finish-gregorian');
                        if (finishDateInput && finishDateInput.value && finishDateInput.value > plannedEndDate) {
                            hasTimelineOverage = true;
                        }
                    });
                }

                // Check if today is past planned end date and not completed
                const today = new Date().toISOString().split('T')[0];
                const progressBar = card.querySelector('.progress-bar');
                const completionPercentage = progressBar ? (parseFloat(progressBar.getAttribute('aria-valuenow')) || 0) : 0;
                
                if (today > plannedEndDate && completionPercentage < 100) {
                    hasTimelineOverage = true;
                }
            }
            
            const financialIcon = container.querySelector('.financial-overage-icon');
            const timelineIcon = container.querySelector('.timeline-overage-icon');
            
            if (financialIcon) {
                const hasJustification = financialIcon.dataset.hasJustification === 'true';
                
                // If rows found, we use calculated overage. 
                // If NO rows found, we assume 0 spent.
                // However, we must be careful not to hide it if server showed it (via hasJustification).
                
                const isFinancialOverage = totalSpent > plannedAmount;
                financialIcon.style.display = (isFinancialOverage || hasJustification) ? 'block' : 'none';
            }
            
            if (timelineIcon) {
                timelineIcon.style.display = hasTimelineOverage ? 'block' : 'none';
            }

            // Sync technical section visibility with overage OR existing justifications
            const technicalSection = document.getElementById('technical-justification-section-' + procedureId);
            if (technicalSection) {
                const hasJustifications = technicalSection.dataset.hasJustifications === 'true';
                if (hasTimelineOverage || hasJustifications) {
                    technicalSection.style.display = 'block';
                } else {
                    technicalSection.style.display = 'none';
                }
            }
        });
    }
    
    // Check overages on page load
    checkPreliminaryProcedureOverages();
    
    // Handle financial overage icon clicks for preliminary activities (Event Delegation with Toggle) - REMOVED as redundant with BS collapse
    // document.addEventListener('click', function(e) { ... });

    document.addEventListener('click', function(e) {
        const timelineBtn = e.target.closest('.timeline-overage-icon');
        if (timelineBtn && timelineBtn.closest('[id^="prel-justification-icons-"]')) {
            e.preventDefault();
            const procedureId = timelineBtn.dataset.procedureId;
            const technicalSection = document.getElementById('technical-justification-section-' + procedureId);
            if (technicalSection) {
                if (technicalSection.style.display === 'none' || technicalSection.style.display === '') {
                    technicalSection.style.display = 'block';
                    technicalSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    technicalSection.style.display = 'none';
                }
            }
            return;
        }
    });

    // Event listener for showing technical justification to populate fields
    document.querySelectorAll('[id^="technicalJustificationCollapse-"]').forEach(el => {
        el.addEventListener('show.bs.collapse', function () {
            const procedureId = this.id.replace('technicalJustificationCollapse-', '');
            const card = document.getElementById('procedure-card-' + procedureId);
            if (!card) return;

            const plannedStartDateStr = card.dataset.plannedStartDate;
            const plannedEndDateStr = card.dataset.plannedEndDate;
            
            // Find the execution with the latest actual end date
            const tbody = document.getElementById('execution_tbody_' + procedureId);
            let maxEndDate = null;
            let targetRow = null;

            if (tbody) {
                const rows = tbody.querySelectorAll('tr[id^="exec-row-"]');
                rows.forEach(row => {
                    const actualEndDateStr = row.dataset.actualEndDate;
                    if (actualEndDateStr) {
                        const actualEndDate = new Date(actualEndDateStr);
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
                document.getElementById('planned-start-date-' + procedureId).value = plannedStartDateStr;
                document.getElementById('planned-start-date-hidden-' + procedureId).value = plannedStartDateStr;
                
                document.getElementById('planned-end-date-' + procedureId).value = plannedEndDateStr;
                
                const actualStartDateStr = targetRow.dataset.actualStartDate;
                document.getElementById('actual-start-date-' + procedureId).value = actualStartDateStr;
                document.getElementById('actual-start-date-hidden-' + procedureId).value = actualStartDateStr;

                const actualEndDateStr = targetRow.dataset.actualEndDate;
                document.getElementById('actual-end-date-' + procedureId).value = actualEndDateStr;
                
                // Calculate Delay
                const diffTime = Math.abs(maxEndDate - plannedEndDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                // Check if it is actually a delay (actual > planned)
                const isDelay = maxEndDate > plannedEndDate;
                
                const delayDaysVal = isDelay ? diffDays : 0;
                
                document.getElementById('delay-days-' + procedureId).value = delayDaysVal;
                document.getElementById('delay-days-hidden-' + procedureId).value = delayDaysVal;
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
                            checkPreliminaryProcedureOverages();
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
});
</script>

            <style>
            /* Execution Card Styles */
            .execution-card {
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            }
            
            .execution-card:hover {
                transform: translateY(-8px) scale(1.02);
                box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2) !important;
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
            
            /* Progress Bar Animations */
            .progress-bar-animated {
                animation: progress-bar-stripes 1.5s linear infinite;
            }
            
            @keyframes progress-bar-stripes {
                0% {
                    background-position: 1rem 0;
                }
                100% {
                    background-position: 0 0;
                }
            }
            
            /* Financial Cards */
            .financial-card {
                transition: all 0.3s ease;
            }
            
            .financial-card:hover {
                transform: scale(1.05);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            }
            
            /* Timeline Items */
            .timeline-item {
                transition: all 0.3s ease;
            }
            
            .timeline-item:hover {
                transform: translateX(-4px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
            
            /* Button Enhancements */
            .execution-card .btn {
                transition: all 0.3s ease;
                font-weight: 500;
            }
            
            .execution-card .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            
            .execution-card .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
            }
            
            .execution-card .btn-primary:hover {
                background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            }
            
            /* Summary Statistics Cards */
            .summary-stat-card {
                transition: all 0.3s ease;
                border-radius: 12px;
                overflow: hidden;
            }
            
            .summary-stat-card:hover {
                transform: translateY(-4px) scale(1.03);
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
            }
            
            /* Responsive Adjustments */
            @media (max-width: 768px) {
                .execution-card:hover {
                    transform: translateY(-4px) scale(1.01);
                }
                
                .financial-card:hover {
                    transform: scale(1.02);
                }
            }
            
            /* Badge Enhancements */
            .badge {
                font-weight: 600;
                letter-spacing: 0.3px;
            }
            
            /* Card Title Styles */
            .card-title {
                font-weight: 700;
                letter-spacing: -0.5px;
            }
            
            /* Smooth Scrolling for Cards Container */
            .row.g-4 {
                scroll-behavior: smooth;
            }
            
            /* Loading Animation */
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .execution-row {
                animation: slideInUp 0.5s ease-out;
                animation-fill-mode: both;
            }
            
            .execution-row:nth-child(1) { animation-delay: 0.1s; }
            .execution-row:nth-child(2) { animation-delay: 0.2s; }
            .execution-row:nth-child(3) { animation-delay: 0.3s; }
            .execution-row:nth-child(4) { animation-delay: 0.4s; }
            .execution-row:nth-child(5) { animation-delay: 0.5s; }
            .execution-row:nth-child(6) { animation-delay: 0.6s; }
            
            /* Icon Animations */
            .execution-card i {
                transition: all 0.3s ease;
            }
            
            .execution-card:hover i {
                transform: scale(1.1);
            }
            
            /* Text Enhancements */
            .text-break {
                word-wrap: break-word;
                overflow-wrap: break-word;
                hyphens: auto;
            }
            
            /* Additional Button Enhancements */
            .btn {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-weight: 600;
            }
            
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
            }
            
            .btn-primary:hover {
                background: linear-gradient(135deg, #5568d3 0%, #653a8b 100%);
            }
            
            /* Form Control Enhancements */
            .form-control:focus,
            .form-select:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }
            
            /* Stat Card Hover Effects */
            .stat-card {
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .stat-card:hover {
                transform: translateY(-8px) scale(1.02);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2) !important;
            }
            
            /* Timeline Item Enhancements */
            .timeline-item {
                transition: all 0.3s ease;
                cursor: default;
            }
            
            .timeline-item:hover {
                transform: scale(1.03);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            }
            
            /* Financial Box Enhancements */
            .financial-box {
                transition: all 0.3s ease;
            }
            
            .financial-box:hover {
                transform: scale(1.05);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            }
            
            /* Responsive Enhancements */
            @media (max-width: 992px) {
                .execution-card .card-header h1 {
                    font-size: 2.8rem !important;
                }
                
                .stat-card .card-body h2 {
                    font-size: 2rem !important;
                }
            }
            
            @media (max-width: 768px) {
                .execution-card {
                    margin-bottom: 1.5rem;
                }
                
                .execution-card .card-header {
                    padding: 1.5rem !important;
                }
                
                .execution-card .card-header h1 {
                    font-size: 2.5rem !important;
                }
                
                .timeline-item,
                .financial-box {
                    margin-bottom: 1rem;
                }
            }
            
            /* Shadow Utilities */
            .shadow-sm {
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08) !important;
            }
            
            .shadow {
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
            }
            
            /* Gradient Text Effect */
            .gradient-text {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            /* Form Control Enhancements */
            .execution-form .form-control:focus,
            .execution-form .form-select:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }
            
            /* Attachment Button */
            .attachment-btn:hover {
                background-color: #e9ecef;
                transform: translateY(-2px);
                transition: all 0.2s ease;
            }
            
            /* Delete Button */
            .delete-row-btn:hover,
            .delete-execution-row:hover {
                background-color: #dc3545;
                color: white;
                transform: scale(1.05);
                transition: all 0.2s ease;
            }
            
            /* Disabled State */
            .save-all-btn:disabled,
            .save-execution-btn:disabled {
                cursor: not-allowed;
                opacity: 0.6;
            }
            
            /* Alert Enhancements */
            .alert {
                border-radius: 8px;
                border: none;
            }
            
            /* Table Enhancements */
            .table th {
                font-weight: 700;
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .table td {
                font-size: 0.9rem;
                vertical-align: middle;
            }
            
            /* Progress Bar Styling */
            .progress {
                border-radius: 10px;
                overflow: hidden;
            }
            
            .progress-bar {
                font-size: 0.75rem;
                font-weight: 600;
                transition: width 0.6s ease;
            }
            
            /* Border Utilities */
            .border-success {
                border-width: 2px !important;
            }
            
            /* Opacity Utilities */
            .opacity-10 { opacity: 0.1; }
            .opacity-25 { opacity: 0.25; }
            .opacity-50 { opacity: 0.5; }
            .opacity-75 { opacity: 0.75; }
            
            /* Stage Number Circle */
            .stage-number .rounded-circle {
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                transition: all 0.3s ease;
            }
            
            .execution-card:hover .stage-number .rounded-circle {
                transform: rotate(360deg) scale(1.1);
            }
            
            /* Enhanced Table Styling */
            .execution-data-table {
                border-collapse: separate;
                border-spacing: 0;
            }
            
            .execution-data-table thead th {
                font-weight: 600;
                letter-spacing: 0.3px;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            }
            
            .execution-table-row {
                transition: all 0.3s ease;
                background: white;
            }
            
            .execution-table-row:hover {
                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
                transform: scale(1.01);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }
            
            .execution-table-row td {
                vertical-align: middle;
                padding: 0.75rem;
            }
            
            /* Staggered Animation for Table Rows */
            .execution-table-row {
                animation: slideInRight 0.5s ease-out;
                animation-fill-mode: both;
            }
            
            .execution-table-row:nth-child(1) { animation-delay: 0.05s; }
            .execution-table-row:nth-child(2) { animation-delay: 0.1s; }
            .execution-table-row:nth-child(3) { animation-delay: 0.15s; }
            .execution-table-row:nth-child(4) { animation-delay: 0.2s; }
            .execution-table-row:nth-child(5) { animation-delay: 0.25s; }
            .execution-table-row:nth-child(6) { animation-delay: 0.3s; }
            
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            /* Table Responsive Container */
            .table-responsive {
                border: 1px solid #e9ecef;
            }
            
            /* Button Group Enhancements */
            .execution-table-row .btn-group .btn {
                transition: all 0.3s ease;
            }
            
            .execution-table-row .btn-group .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            }
            
            /* Enhanced Horizontal Scrolling */
            .table-responsive {
                scrollbar-width: thin;
                scrollbar-color: #667eea #e9ecef;
                scroll-behavior: smooth;
            }
            
            .table-responsive::-webkit-scrollbar {
                height: 12px;
            }
            
            .table-responsive::-webkit-scrollbar-track {
                background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 10px;
            }
            
            .table-responsive::-webkit-scrollbar-thumb {
                background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
                border-radius: 10px;
                border: 2px solid #f8f9fa;
            }
            
            .table-responsive::-webkit-scrollbar-thumb:hover {
                background: linear-gradient(90deg, #764ba2 0%, #667eea 100%);
            }
            
            /* Scroll Indicator Animation */
            .scroll-indicator {
                animation: fadeIn 1s ease-in-out;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            /* Table minimum width to ensure horizontal scroll */
            .execution-data-table {
                min-width: 1400px;
                width: 100%;
            }
            </style>

<!-- Partial view for execution row -->
@if(!isset($includePartial))
@push('partials')
<div id="execution-row-template" style="display: none;">
    <tr class="execution-row">
        <td>__INDEX__</td>
        <td>
            <input type="date" class="form-control form-control-sm actual-start-gregorian" 
                   value="__START_GREGORIAN__" 
                   data-hijri-field="hijri_start___ROW_ID__">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm bg-light hijri-start" 
                   id="hijri_start___ROW_ID__" 
                   value="__START_HIJRI__" 
                   readonly>
        </td>
        <td>
            <input type="date" class="form-control form-control-sm actual-finish-gregorian" 
                   value="__FINISH_GREGORIAN__" 
                   data-hijri-field="hijri_finish___ROW_ID__">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm bg-light hijri-finish" 
                   id="hijri_finish___ROW_ID__" 
                   value="__FINISH_HIJRI__" 
                   readonly>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm actual-amount" 
                   step="0.01" 
                   value="__ACTUAL_AMOUNT__">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm amount-spent" 
                   step="0.01" 
                   value="__AMOUNT_SPENT__">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm bg-light remaining-amount" 
                   value="__REMAINING_AMOUNT__" 
                   readonly>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm completion-pct" 
                   step="0.01" min="0" max="100" 
                   value="__COMPLETION_PCT__">
        </td>
        <td>
            <select class="form-select form-select-sm exec-status">
                <option value="not_started">لم يبدأ</option>
                <option value="in_progress">قيد التنفيذ</option>
                <option value="delayed">متأخر</option>
                <option value="stalled">متعثر</option>
            </select>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-info attachment-btn w-100">
                <i class="fas fa-paperclip"></i>
            </button>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger delete-row-btn w-100">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
</div>
@endpush
@endif

<!-- Include Delay Justification Modal -->
@include('projects.partials.implementation.execution-delay-justification-modal')

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkDelayedProcedures();
    
    // Attach click handlers to delay justification buttons
    document.querySelectorAll('[data-procedure-id].timeline-overage-icon').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const procedureId = this.dataset.procedureId;
            const procedureCard = document.getElementById('procedure-card-' + procedureId);
            
            if (!procedureCard) return;
            
            const plannedEndDate = procedureCard.dataset.plannedEndDate;
            
            // Get the execution table row to find the actual dates
            const executionRow = document.querySelector(`tr[data-procedure-id="${procedureId}"][data-actual-end-date]`);
            
            if (!executionRow) {
                flasher.warning('لم يتم العثور على تاريخ فعلي. يرجى إضافة تسجيل تنفيذ أولا.');
                return;
            }
            
            const actualStartDate = executionRow.dataset.actualStartDate || '';
            const actualEndDate = executionRow.dataset.actualEndDate || '';
            const plannedStartDate = procedureCard.dataset.plannedStartDate || new Date().toISOString().split('T')[0];
            
            if (!actualEndDate) {
                flasher.warning('لم يتم العثور على تاريخ انتهاء فعلي. يرجى إضافة تاريخ الانتهاء الفعلي أولا.');
                return;
            }
            
            openDelayJustificationModal(
                'preliminary',
                procedureId,
                plannedStartDate,
                plannedEndDate,
                actualStartDate,
                actualEndDate
            );
        });
    });
});

function checkDelayedProcedures() {
    document.querySelectorAll('[id^="procedure-card-"]').forEach(card => {
        const procedureId = card.id.replace('procedure-card-', '');
        const plannedEndDate = card.dataset.plannedEndDate;
        
        if (!plannedEndDate) return;
        
        // Get the latest execution row for this procedure
        const executionRow = document.querySelector(`tr[data-procedure-id="${procedureId}"][data-actual-end-date]`);
        
        if (!executionRow) return;
        
        const actualEndDate = executionRow.dataset.actualEndDate;
        
        if (!actualEndDate) return;
        
        // Compare dates
        const planned = new Date(plannedEndDate);
        const actual = new Date(actualEndDate);
        
        if (actual > planned) {
            // Show the delay button
            const delayBtn = document.getElementById(`tech-justification-btn-${procedureId}`);
            if (delayBtn) {
                delayBtn.style.display = 'block';
            }
        }
    });
}

// Re-check delays when new execution is added
function reloadDelayCheck() {
    setTimeout(checkDelayedProcedures, 500);
}
</script>