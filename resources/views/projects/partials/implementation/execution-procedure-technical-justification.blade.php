<div class="card border-0 bg-white shadow-lg mb-4 technical-justification-section" id="technical-justification-section-{{ $procedure->id }}" style="border-radius: 12px; overflow: hidden; display: none;" data-has-justifications="{{ ($procedure->technicalJustifications && $procedure->technicalJustifications->count() > 0) ? 'true' : 'false' }}">
    @php
        $latestJustification = $procedure->technicalJustifications()->latest()->first();
        $hasDelayHistory = isset($latestJustification) && $latestJustification && $latestJustification->delay_days > 0;
        $technicalJustifications = $procedure->technicalJustifications ?? collect([]);

        // Recalculate overage for Blade logic
        $latestExecution = $procedure->executions?->sortByDesc('actual_finish_date_gregorian')->first();
        $isOverTime = $latestExecution && $procedure->end_date && $latestExecution->actual_finish_date_gregorian > $procedure->end_date;
        $hasDelay = $isOverTime; // Use current overage for the alert and form
    @endphp

    <!-- Header with Gradient -->
    <div class="bg-gradient" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 30px 20px; color: white;">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1" style="font-weight: 700;">
                    <i class="fas fa-hourglass-end me-2"></i>التبريرات التقنية
                </h5>
                <p class="mb-0 small" style="opacity: 0.9;">متابعة الجدول الزمني والتأخيرات</p>
            </div>
            <i class="fas fa-timeline fa-3x" style="opacity: 0.2;"></i>
        </div>
    </div>

    <div class="card-body p-4">
        <!-- Technical Summary Cards -->
        <div class="row mb-5 g-3">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #667eea; background: linear-gradient(135deg, #f5f7ff 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: #667eea; letter-spacing: 0.5px;">المخطط</span>
                            <div class="bg-primary bg-opacity-10 rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calendar-check" style="color: #667eea; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: #667eea;">{{ $procedure->end_date?->format('d/m/Y') ?? '-' }}</div>
                        <small class="text-muted d-block mt-2">تاريخ الانتهاء المخطط</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f093fb; background: linear-gradient(135deg, #fff5f7 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: #f093fb; letter-spacing: 0.5px;">الفعلي</span>
                            <div class="bg-danger bg-opacity-10 rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calendar-times" style="color: #f093fb; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        @if($latestJustification && $latestJustification->actual_end_date)
                            <div style="font-size: 1.1rem; font-weight: 700; color: #f093fb;">{{ $latestJustification->actual_end_date->format('d/m/Y') }}</div>
                        @else
                            <div style="font-size: 1.1rem; font-weight: 700; color: #999;">-</div>
                        @endif
                        <small class="text-muted d-block mt-2">تاريخ الانتهاء الفعلي</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid {{ $hasDelay ? '#dc3545' : '#28a745' }}; background: linear-gradient(135deg, {{ $hasDelay ? '#fff5f5' : '#f5fff5' }} 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: {{ $hasDelay ? '#dc3545' : '#28a745' }}; letter-spacing: 0.5px;">{{ $hasDelay ? 'التأخير' : 'في الموعد' }}</span>
                            <div class="rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: {{ $hasDelay ? '#fce4e6' : '#e8f5e9' }};">
                                <i class="fas {{ $hasDelay ? 'fa-arrow-trend-up' : 'fa-check-circle' }}" style="color: {{ $hasDelay ? '#dc3545' : '#28a745' }}; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        @if($latestJustification && $latestJustification->delay_days)
                            <div style="font-size: 1.8rem; font-weight: 700; color: {{ $hasDelay ? '#dc3545' : '#28a745' }};">{{ abs($latestJustification->delay_days) }}</div>
                            <small class="text-muted d-block mt-2">{{ $hasDelay ? 'أيام تأخير' : 'يوم' }}</small>
                        @else
                            <div style="font-size: 1.8rem; font-weight: 700; color: #28a745;">0</div>
                            <small class="text-muted d-block mt-2">يوم</small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107; background: linear-gradient(135deg, #fffef5 0%, #ffffff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-bold text-uppercase" style="color: #ffc107; letter-spacing: 0.5px;">التقدم</span>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-hourglass-end" style="color: #ffc107; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        @if($procedure->end_date)
                            <div style="font-size: 1.8rem; font-weight: 700; color: #ffc107;">
                                @php
                                    $totalDays = max($procedure->duration_days, 1);
                                    $completionPercentage = min(100, round(($totalDays - max(0, $latestJustification?->delay_days ?? 0)) / $totalDays * 100, 1));
                                @endphp
                                {{ $completionPercentage }}%
                            </div>
                            <small class="text-muted d-block mt-2">من المدة الكلية</small>
                        @else
                            <div style="font-size: 1.8rem; font-weight: 700; color: #999;">-</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert for Delay -->
        @if($hasDelay)
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border: none; border-left: 5px solid #dc3545; background: linear-gradient(135deg, #fce4e6 0%, #ffffff 100%); padding: 20px;">
                <div class="d-flex align-items-start">
                    <div class="me-3" style="font-size: 2rem; color: #dc3545;">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-2" style="color: #dc3545; font-weight: 700;">
                            <i class="fas fa-exclamation-circle me-2"></i>تنبيه: تأخير في الجدول الزمني
                        </h6>
                        <p class="mb-0 text-dark" style="line-height: 1.6;">
                            تم تسجيل تأخير بمدة <strong style="color: #dc3545; font-size: 1.1rem;">{{ $latestJustification->delay_days }} أيام</strong> 
                            <br><small class="text-muted">يرجى تقديم تبرير تقني مفصل يوضح أسباب التأخير والإجراءات المتخذة للتصحيح.</small>
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="flex-shrink: 0;"></button>
                </div>
            </div>
        @endif

        <hr class="my-4" style="border-top: 2px dashed #e0e0e0;">

        <!-- Form Section -->
        @if($isOverTime)
            @can('projects.execution.storeProcedureTechnicalJustification')
            <form action="{{ route('projects.execution.storeProcedureTechnicalJustification', ['project' => $project]) }}" 
            @endcan
                  method="POST" 
                  enctype="multipart/form-data"
                  class="technical-justification-form">
                @csrf
                <input type="hidden" name="preliminary_procedure_id" value="{{ $procedure->id }}">
                <input type="hidden" name="planned_end_date" value="{{ $procedure->end_date?->format('Y-m-d') }}">
                @if($latestExecution && $latestExecution->actual_finish_date_gregorian)
                    <input type="hidden" name="actual_end_date" value="{{ $latestExecution->actual_finish_date_gregorian->format('Y-m-d') }}">
                @endif

                <h6 class="border-bottom pb-2 mb-4">
                    <i class="fas fa-file-alt me-2"></i>تقديم التبرير التقني
                </h6>

                <!-- Date and Delay Fields (Auto-populated) -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">تاريخ البداية المخطط</label>
                        <input type="text" class="form-control bg-light" name="planned_start_date_display" id="planned-start-date-{{ $procedure->id }}" readonly>
                        <input type="hidden" name="planned_start_date" id="planned-start-date-hidden-{{ $procedure->id }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">تاريخ النهاية المخطط</label>
                        <input type="text" class="form-control bg-light" name="planned_end_date_display" id="planned-end-date-{{ $procedure->id }}" value="{{ $procedure->end_date?->format('Y-m-d') }}" readonly>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">تاريخ البداية الفعلي</label>
                        <input type="text" class="form-control bg-light" name="actual_start_date_display" id="actual-start-date-{{ $procedure->id }}" readonly>
                         <input type="hidden" name="actual_start_date" id="actual-start-date-hidden-{{ $procedure->id }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">تاريخ النهاية الفعلي</label>
                        <input type="text" class="form-control bg-light" name="actual_end_date_display" id="actual-end-date-{{ $procedure->id }}" readonly>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-danger">مدة التأخير (أيام)</label>
                    <input type="text" class="form-control bg-danger bg-opacity-10 text-danger fw-bold" name="delay_days_display" id="delay-days-{{ $procedure->id }}" readonly>
                    <input type="hidden" name="delay_days" id="delay-days-hidden-{{ $procedure->id }}">
                </div>

                <!-- Justification Field -->
                <div class="mb-4">
                    <label for="justification-{{ $procedure->id }}" class="form-label fw-semibold">
                        التبرير التقني 
                    </label>
                    <textarea class="form-control form-control-lg" 
                              id="justification-{{ $procedure->id }}" 
                              name="justification" 
                              rows="5" 
                              placeholder="يرجى شرح أسباب التأخير والإجراءات المتخذة لتصحيح الموقف...">{{ old('justification') }}</textarea>
                    <small class="form-text text-muted mt-2">
                        <i class="fas fa-info-circle me-1"></i>تضمين التفاصيل الفنية والمشاكل التقنية والحلول المقترحة
                    </small>
                </div>

                <!-- File Upload -->
                <div class="mb-4">
                    <label for="attachments-{{ $procedure->id }}" class="form-label fw-semibold">
                        المرفقات الداعمة <span class="text-muted">(اختياري)</span>
                    </label>
                    <div class="card border-2 border-dashed bg-light p-4 text-center cursor-pointer" style="cursor: pointer;">
                        <input type="file" 
                               id="attachments-{{ $procedure->id }}" 
                               name="attachments[]" 
                               multiple 
                               class="d-none"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                        <p class="mb-0"><strong>اسحب الملفات هنا أو انقر للاختيار</strong></p>
                        <small class="text-muted">الملفات المدعومة: PDF، Word، Excel، صور (الحد الأقصى 10 MB لكل ملف)</small>
                        <div id="files-preview-{{ $procedure->id }}" class="mt-3"></div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex gap-3 justify-content-end mt-5 pt-3 border-top">
                    <button type="button" 
                            class="btn btn-light border" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#technicalJustificationCollapse-{{ $procedure->id }}">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>حفظ التبرير التقني
                    </button>
                </div>
            </form>
        @else
            <div class="alert alert-success border-0 bg-success bg-opacity-10 mb-4 p-4 rounded-3 text-center">
                <i class="fas fa-check-circle fs-3 mb-2 d-block"></i>
                <h6 class="mb-1 fw-bold">الجدول الزمني منتظم</h6>
                <p class="mb-0 small">التنفيذ حالياً ضمن الحدود المخطط لها. لا يتطلب الأمر تقديم تبرير تقني.</p>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="button" 
                        class="btn btn-secondary btn-sm" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#technicalJustificationCollapse-{{ $procedure->id }}">
                    <i class="fas fa-times me-1"></i>إغلاق
                </button>
            </div>
        @endif

        <!-- Technical Justifications Log - TABLE VIEW -->
        @if($technicalJustifications->count() > 0)
            <hr class="my-4">
            
            <h6 class="border-bottom pb-2 mb-4">
                <i class="fas fa-history me-2"></i>سجل التبريرات التقنية
            </h6>
            
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="technicalJustificationsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%;" class="text-center">#</th>
                            <th style="width: 12%;">تاريخ الإضافة</th>
                            <th style="width: 10%;">أضاف بواسطة</th>
                            <th style="width: 10%;">المخطط</th>
                            <th style="width: 10%;">الفعلي</th>
                            <th style="width: 8%;">التأخير</th>
                            <th style="width: 10%;">الحالة</th>
                            <th style="width: 22%;">التبرير التقني</th>
                            <th style="width: 10%;" class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($technicalJustifications as $index => $justification)
                            <tr>
                                <td class="text-center fw-bold">{{ $technicalJustifications->count() - $index }}</td>
                                <td>
                                    <small class="text-muted d-block">{{ $justification->created_at?->format('d/m/Y') ?? '-' }}</small>
                                    <small class="text-muted">{{ $justification->created_at?->format('H:i') ?? '-' }}</small>
                                </td>
                                <td>{{ $justification->createdBy?->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $justification->planned_end_date?->format('d/m/Y') ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-danger border">
                                        {{ $justification->actual_end_date?->format('d/m/Y') ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $justification->delay_days > 0 ? 'bg-danger' : 'bg-success' }}">
                                        {{ abs($justification->delay_days ?? 0) }} يوم
                                    </span>
                                </td>
                                <td>
                                    <span class="badge px-3 py-2 bg-{{ $justification->approval_status === 'approved' ? 'success' : ($justification->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                                        @if($justification->approval_status === 'approved')
                                            <i class="fas fa-check me-1"></i>موافق عليه
                                        @elseif($justification->approval_status === 'rejected')
                                            <i class="fas fa-times me-1"></i>مرفوض
                                        @else
                                            <i class="fas fa-hourglass-half me-1"></i>قيد الانتظار
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="{{ $justification->justification }}">
                                        {{ Str::limit($justification->justification, 80) }}
                                    </div>
                                    @if(strlen($justification->justification) > 80)
                                        <a href="#" class="small text-primary view-details" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#justificationModal{{ $justification->id }}">
                                            <i class="fas fa-eye me-1"></i>عرض التفاصيل
                                        </a>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#justificationModal{{ $justification->id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        @if($justification->attachments && count($justification->attachments) > 0)
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-success dropdown-toggle" 
                                                    data-bs-toggle="dropdown" 
                                                    aria-expanded="false">
                                                <i class="fas fa-file"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                @foreach($justification->attachments as $attachment)
                                                    <li>
                                                        <a class="dropdown-item" 
                                                           href="{{ asset('storage/' . $attachment) }}" 
                                                           target="_blank">
                                                            <i class="fas fa-download me-2"></i>
                                                            {{ Str::limit(basename($attachment), 30) }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        
                                        @if($justification->approval_status !== 'approved')
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary add-attachment-btn"
                                                    data-justification-id="{{ $justification->id }}">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Modal for Detailed View -->
                            <div class="modal fade" id="justificationModal{{ $justification->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title">
                                                <i class="fas fa-file-alt me-2"></i>
                                                تفاصيل التبرير التقني
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Details in Modal -->
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block mb-1">
                                                            <i class="fas fa-calendar text-secondary me-1"></i>تاريخ الإضافة
                                                        </small>
                                                        <div class="fw-semibold">{{ $justification->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block mb-1">
                                                            <i class="fas fa-user text-secondary me-1"></i>أضاف بواسطة
                                                        </small>
                                                        <div class="fw-semibold">{{ $justification->createdBy?->name ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block mb-1">الحالة</small>
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
                                            </div>

                                            <!-- Dates -->
                                            <div class="row mb-4">
                                                <div class="col-md-4">
                                                    <div class="card border-0 bg-light p-3">
                                                        <small class="text-muted d-block mb-1">التاريخ المخطط</small>
                                                        <div class="fw-semibold">{{ $justification->planned_end_date?->format('d/m/Y') ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card border-0 bg-light p-3">
                                                        <small class="text-muted d-block mb-1">التاريخ الفعلي</small>
                                                        <div class="fw-semibold text-danger">{{ $justification->actual_end_date?->format('d/m/Y') ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card border-0 bg-light p-3">
                                                        <small class="text-muted d-block mb-1">مدة التأخير</small>
                                                        <div class="fw-semibold {{ $justification->delay_days > 0 ? 'text-danger' : 'text-success' }}">
                                                            {{ abs($justification->delay_days ?? 0) }} يوم
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Justification Text -->
                                            <div class="mb-4">
                                                <strong class="d-block text-dark mb-2">التبرير التقني:</strong>
                                                <div class="p-3 bg-light rounded border" style="max-height: 200px; overflow-y: auto;">
                                                    <p class="text-dark lh-lg">{{ $justification->justification }}</p>
                                                </div>
                                            </div>

                                            <!-- Attachments -->
                                            <div class="mt-4 pt-4 border-top">
                                                <strong class="d-block text-dark mb-3">
                                                    <i class="fas fa-file me-1 text-info"></i>المرفقات
                                                </strong>
                                                
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
                                            </div>

                                            <!-- Reviewer Info -->
                                            @if($justification->reviewed_by)
                                                <div class="mt-4 pt-4 border-top">
                                                    <strong class="d-block text-dark mb-3">
                                                        <i class="fas fa-user-check me-1 text-success"></i>معلومات المراجعة
                                                    </strong>
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
                                                                <div class="p-2 bg-light rounded border">
                                                                    <p class="small text-dark lh-lg">{{ $justification->reviewer_notes }}</p>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">لا توجد تبريرات تقنية مسجلة</h6>
                <p class="small text-muted">قم بإضافة التبرير التقني الأول</p>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('attachments-{{ $procedure->id }}');
    const previewContainer = document.getElementById('files-preview-{{ $procedure->id }}');
    const dropZone = fileInput?.parentElement;
    const form = document.querySelector('.technical-justification-form');

    if (fileInput && dropZone) {
        fileInput.addEventListener('change', function(e) {
            updateFilePreview(e.target.files);
        });

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.classList.add('border-primary', 'bg-primary-subtle');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropZone.classList.remove('border-primary', 'bg-primary-subtle');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.classList.remove('border-primary', 'bg-primary-subtle');
            fileInput.files = e.dataTransfer.files;
            updateFilePreview(e.dataTransfer.files);
        });

        dropZone.addEventListener('click', function() {
            fileInput.click();
        });
    }

    function updateFilePreview(files) {
        previewContainer.innerHTML = '';
        if (files.length > 0) {
            const previewDiv = document.createElement('div');
            previewDiv.className = 'mt-2';

            Array.from(files).forEach((file, index) => {
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                const badge = document.createElement('span');
                badge.className = 'badge bg-info text-white me-2 mb-2 py-2 px-3';
                badge.innerHTML = `<i class="fas fa-file me-1"></i>${file.name} (${sizeMB} MB)`;
                previewDiv.appendChild(badge);
            });

            previewContainer.appendChild(previewDiv);
        }
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const justification = document.getElementById('justification-{{ $procedure->id }}').value;
            const attachmentsInput = document.getElementById('attachments-{{ $procedure->id }}');
            
            if (!justification.trim()) {
                alert('يرجى إدخال التبرير التقني');
                return;
            }
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.createElement('div');
                    notification.className = 'alert alert-success alert-dismissible fade show';
                    notification.setAttribute('role', 'alert');
                    notification.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>تم إضافة التبرير بنجاح!</strong>
                        <div class="mt-2">
                            <p class="mb-2"><strong>التفاصيل:</strong></p>
                            <ul class="mb-2">
                                <li>التبرير: تم حفظ التبرير التقني بنجاح</li>
                                ${attachmentsInput.files.length > 0 ? `<li>المرفقات: تم إضافة ${attachmentsInput.files.length} ملف(ات)</li>` : ''}
                            </ul>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    
                    form.parentElement.insertBefore(notification, form);
                    form.reset();
                    previewContainer.innerHTML = '';
                    
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('حدث خطأ: ' + (data.message || 'فشل حفظ التبرير'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء حفظ التبرير');
            });
        });
    }

    // Handle add attachment buttons in table
    document.querySelectorAll('.add-attachment-btn').forEach(button => {
        button.addEventListener('click', function() {
            const justificationId = this.getAttribute('data-justification-id');
            
            // Create modal for adding attachments
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = `addAttachmentModal${justificationId}`;
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">إضافة مرفقات إضافية</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form class="add-attachments-form" data-justification-id="${justificationId}">
                                <div class="mb-3">
                                    <label class="form-label">اختر الملفات</label>
                                    <input type="file" class="form-control" 
                                           name="attachments[]" 
                                           multiple 
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-1"></i>إضافة المرفقات
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Handle form submission
            modal.querySelector('.add-attachments-form').addEventListener('submit', function(e) {
                e.preventDefault();
                // Add your AJAX submission logic here
                alert('سيتم إضافة المرفقات لاحقاً');
                bsModal.hide();
            });
            
            // Remove modal from DOM when hidden
            modal.addEventListener('hidden.bs.modal', function () {
                document.body.removeChild(modal);
            });
        });
    });
});
</script>

<style>
#technicalJustificationsTable th {
    font-weight: 700;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

#technicalJustificationsTable td {
    vertical-align: middle;
}

#technicalJustificationsTable tr:hover {
    background-color: #f5f5f5;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-group .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
</style>