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
                </div>

                <div class="row g-4">
                    @foreach($activity->procedures as $procedure)
                        @php
                            $totalPlanned = $procedure->costs->sum('total');
                            $executions = $procedure->executions; // Assuming relationship exists
                            $totalSpent = $executions->sum('amount_spent');
                            $completionPct = $executions->sum('completion_percentage');
                            // Ensure completion doesn't exceed 100 for display if logic allows overage
                            $displayPct = min($completionPct, 100);
                            
                            $status = $executions->sortByDesc('created_at')->first()->status ?? 'not_started';
                            $statusColors = [
                                'not_started' => 'secondary',
                                'in_progress' => 'primary',
                                'delayed' => 'danger',
                                'stalled' => 'warning',
                                'completed' => 'success'
                            ];
                        @endphp
                        <div class="col-12">
                            <div class="card border-0 shadow-sm h-100 execution-card" id="procedure-card-{{ $procedure->id }}" data-planned-end-date="{{ $activity->end_date_gregorian ? $activity->end_date_gregorian->format('Y-m-d') : '' }}">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3 text-secondary">
                                            <i class="fas fa-clipboard-list fa-lg"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 fw-bold text-dark">{{ $procedure->procedure_name }}</h5>
                                            <div class="small text-muted">
                                                <span class="me-3"><i class="fas fa-coins me-1"></i>المخطط: {{ number_format($totalPlanned, 2) }} ﷼</span>
                                                <span><i class="fas fa-weight-hanging me-1"></i>الوزن: {{ $procedure->weight }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-end d-none d-md-block">
                                            <div class="small text-muted mb-1">نسبة الإنجاز</div>
                                            <div class="d-flex align-items-center">
                                                <div class="progress" style="width: 100px; height: 8px;">
                                                    <div class="progress-bar bg-{{ $statusColors[$status] ?? 'primary' }}" 
                                                         role="progressbar" 
                                                         style="width: {{ $displayPct }}%" 
                                                         aria-valuenow="{{ $displayPct }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                                <span class="ms-2 fw-bold small">{{ number_format($completionPct, 1) }}%</span>
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
                                                <span class="text-muted">المنصرف الفعلي:</span>
                                                <span class="fw-bold text-dark">{{ number_format($totalSpent, 2) }} ﷼</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">المتبقي:</span>
                                                <span class="fw-bold {{ ($totalPlanned - $totalSpent) < 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ number_format($totalPlanned - $totalSpent, 2) }} ﷼
                                                </span>
                                            </div>
                                            <div class="mt-3">
                                                <button class="btn btn-outline-primary btn-sm w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#execution-history-{{ $procedure->id }}">
                                                    <i class="fas fa-history me-1"></i> عرض السجل / إضافة تنفيذ
                                                </button>
                                                <div class="d-flex gap-2" id="prel-justification-icons-{{ $procedure->id }}">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger financial-overage-icon"
                                                            data-procedure-id="{{ $procedure->id }}"
                                                            title="تم تجاوز الميزانية - إضافة تبرير مالي"
                                                            style="display: none; flex: 1;">
                                                        <i class="fas fa-exclamation-circle me-1"></i>تجاوز مالي
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-warning timeline-overage-icon"
                                                            data-procedure-id="{{ $procedure->id }}"
                                                            title="تم تجاوز الموعد المخطط - إضافة تبرير تقني"
                                                            style="display: none; flex: 1;">
                                                        <i class="fas fa-hourglass-end me-1"></i>تجاوز وقتي
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Collapsible Section for History & Form -->
                                    <div class="collapse" id="execution-history-{{ $procedure->id }}">
                                        <div class="bg-light rounded p-3 mb-3">
                                            <ul class="nav nav-pills mb-3" id="pills-tab-{{ $procedure->id }}" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active btn-sm" id="pills-home-tab-{{ $procedure->id }}" data-bs-toggle="pill" data-bs-target="#pills-home-{{ $procedure->id }}" type="button" role="tab">
                                                        <i class="fas fa-list me-1"></i> سجل العمليات
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link btn-sm" id="pills-profile-tab-{{ $procedure->id }}" data-bs-toggle="pill" data-bs-target="#pills-profile-{{ $procedure->id }}" type="button" role="tab">
                                                        <i class="fas fa-plus-circle me-1"></i> إضافة جديد
                                                    </button>
                                                </li>
                                            </ul>
                                            <div class="tab-content" id="pills-tabContent-{{ $procedure->id }}">
                                                <!-- Operations Log Tab -->
                                                <div class="tab-pane fade show active" id="pills-home-{{ $procedure->id }}" role="tabpanel">
                                                    @include('projects.partials.execution-preliminary-display', ['allExecutions' => $executions, 'procedure' => $procedure])
                                                </div>
                                                
                                                <!-- Add New Tab -->
                                                <div class="tab-pane fade" id="pills-profile-{{ $procedure->id }}" role="tabpanel">
                                                    @can('execution.edit')
                                                    <form action="{{ route('projects.execution.store', $project) }}" 
                                                          method="POST" 
                                                          enctype="multipart/form-data"
                                                          class="execution-form"
                                                          data-procedure-id="{{ $procedure->id }}">
                                                        @csrf
                                                        <input type="hidden" name="preliminary_procedure_id" value="{{ $procedure->id }}">
                                                        
                                                        @include('projects.partials.execution-preliminary-form', ['allExecutions' => $executions, 'procedure' => $procedure])
                                                        
                                                        <div class="text-end mt-3">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#execution-history-{{ $procedure->id }}">إلغاء</button>
                                                            <button type="submit" class="btn btn-primary btn-sm save-execution-btn">
                                                                <i class="fas fa-save me-1"></i> حفظ البيانات
                                                            </button>
                                                        </div>
                                                    </form>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Include Separate Justification Files -->
                                    @php
                                        $plannedEndDate = $activity->end_date_gregorian ? $activity->end_date_gregorian->format('Y-m-d') : null;
                                    @endphp
                                    @include('projects.partials.execution-procedure-financial-justification', ['procedure' => $procedure, 'project' => $project, 'totalPlanned' => $totalPlanned, 'totalSpent' => $totalSpent])
                                    @include('projects.partials.execution-procedure-technical-justification', ['procedure' => $procedure, 'project' => $project, 'plannedEndDate' => $plannedEndDate])
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check for budget overages and show/hide justification sections
    function checkBudgetOverages() {
        @foreach($project->preliminaryActivities as $activity)
            @foreach($activity->procedures as $procedure)
                @php
                    $totalPlanned = $procedure->costs->sum('total');
                    $executions = $procedure->executions;
                    $totalActual = $executions->sum('actual_amount');
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
    
    checkProcedureJustificationSections();

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
                    
                    // Reload page to reflect changes and check budget overages again
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
            
            // Get execution table for this procedure
            const tableId = 'execution_tbody_' + procedureId;
            const tbody = document.getElementById(tableId);
            
            if (!tbody) return;
            
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
            
            // Get planned end date from procedure
            const plannedEndDate = card.querySelector('[data-planned-end-date]')?.dataset.plannedEndDate;
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
    checkPreliminaryProcedureOverages();
    
    // Handle financial overage icon clicks for preliminary activities
    document.querySelectorAll('[id^="prel-justification-icons-"] .financial-overage-icon').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const procedureId = this.dataset.procedureId;
            const financialSection = document.getElementById('financial-justification-section-' + procedureId);
            if (financialSection) {
                financialSection.style.display = 'block';
                financialSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Handle timeline overage icon clicks for preliminary activities
    document.querySelectorAll('[id^="prel-justification-icons-"] .timeline-overage-icon').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const procedureId = this.dataset.procedureId;
            const technicalSection = document.getElementById('technical-justification-section-' + procedureId);
            if (technicalSection) {
                technicalSection.style.display = 'block';
                technicalSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
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