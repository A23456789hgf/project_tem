@php
    $isDraft = in_array($project->status, ['draft', 'completed_draft'], true) || $project->isDraft();
    $isRolledBack = ($project->status === 'rolled_back_for_review');
    $isPendingApproval = ($project->status === 'pending_approval');
    $isInExecution = in_array($project->status, ['in_execution', 'in_progress'], true);
    $isRejected = ($project->status === 'rejected');

    // Fetch active step
    $activeStep = $project->projectApprovals()
        ->where('is_active', true)
        ->with(['entity', 'authority', 'reviewedByUser'])
        ->first();

    // Internal entities list for consultation
    $entitiesList = \App\Models\InternalEntity::withoutGlobalScopes()
        ->where('is_active', true)
        ->where('id', '!=', auth()->user()?->entity_id ?? 0)
        ->orderBy('name')
        ->get();

    // Existing consultations
    $referrals = \App\Models\ProjectReferral::where('project_id', $project->id)
        ->with(['referringEntity', 'referredEntity', 'referringUser'])
        ->orderBy('created_at', 'desc')
        ->get();
@endphp

<div class="approval-action-center">
    {{-- ========================================================================= --}}
    {{-- 1. DRAFT STATE ACTIONS --}}
    {{-- ========================================================================= --}}
    @if($isDraft)
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-file-signature me-2"></i> إجراءات المسودة
                </h5>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">مسودة (Draft)</span>
            </div>
            <div class="card-body p-4 text-center">
                <div class="my-3">
                    <p class="text-muted mb-4">المشروع ما زال في طور المسودة لدى الجهة المنشئة. يمكنك مراجعة وتعديل بيانات المشروع، أو تأكيد إغلاق المسودة لإرسالها للاعتماد الرسمي.</p>
                    
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        @can('update', $project)
                            <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-outline-secondary btn-lg px-4 rounded-pill">
                                <i class="fas fa-edit me-2"></i> تعديل بيانات المسودة
                            </a>
                        @endcan

                        @can('closeDraft', $project)
                            <button type="button" class="btn btn-primary btn-lg px-5 rounded-pill shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#closeDraftActionModal">
                                <i class="fas fa-paper-plane me-2"></i> إغلاق المسودة وإرسالها للاعتماد
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal for Close Draft --}}
        @can('closeDraft', $project)
            <div class="modal fade" id="closeDraftActionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <form action="{{ route('projects.approval.submit', $project) }}" method="POST">
                            @csrf
                            <div class="modal-header bg-primary text-white py-3 border-0">
                                <h5 class="modal-title fw-bold">
                                    <i class="fas fa-paper-plane me-2"></i> تأكيد إغلاق المسودة
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="text-dark mb-3">هل أنت متأكد من رغبتك في إغلاق المسودة وإرسال المشروع لسلسلة الاعتماد؟</p>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">ملاحظات الإرسال (اختياري):</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="اكتب أي ملاحظات توضيحية ترغب بإرفاقها..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light border-0 py-3">
                                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">تأكيد وإرسال</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

    {{-- ========================================================================= --}}
    {{-- 2. RESUBMIT ACTIONS (ROLLED BACK FOR REVIEW) --}}
    {{-- ========================================================================= --}}
    @elseif($isRolledBack)
        <div class="card border-warning border-2 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-warning bg-opacity-10 py-3 border-bottom border-warning border-opacity-25 d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-redo me-2 text-warning"></i> إعادة تقديم المشروع (Resubmit)
                </h5>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">مُعاد للمراجعة واستكمال النواقص</span>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-warning border-0 rounded-3 mb-4">
                    <h6 class="fw-bold mb-1"><i class="fas fa-info-circle me-1"></i> تم إرجاع هذا المشروع لاستكمال بيانات أو نواقص</h6>
                    <p class="mb-0 small">بعد معالجة المطلوب، يمكن لمخولي الجهة المنشئة إعادة تقديم المشروع، وسيعود إلى نفس المرحلة التي طلبت الاستكمال.</p>
                </div>

                @can('resubmit', $project)
                    <form action="{{ route('projects.approval.resubmit', $project) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">ملاحظات ما تم استكماله / معالجته:</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="وضّح الإجراءات أو التعديلات التي تمت بناءً على طلب الاستكمال..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">مرفقات داعمة (اختياري):</label>
                            <input type="file" name="attachment" class="form-control">
                            <small class="text-muted">الملفات المدعومة: PDF, Word, Excel, صور (بحد أقصى 20 ميجابايت)</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            @can('update', $project)
                                <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-outline-secondary rounded-pill px-4">
                                    <i class="fas fa-edit me-1"></i> تعديل بيانات المشروع
                                </a>
                            @endcan
                            <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i> إعادة تقديم المشروع للاعتماد
                            </button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-secondary border-0 rounded-3 text-center mb-0">
                        <i class="fas fa-lock me-1"></i> إعادة التقديم متاحة فقط لمستخدمي الجهة المنشئة للمشروع.
                    </div>
                @endcan
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- 3. ACTIVE APPROVAL STEP ACTIONS --}}
    {{-- ========================================================================= --}}
    @elseif($isPendingApproval && $activeStep)
        <div class="card border-primary border-2 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-primary text-white py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-gavel fs-5"></i>
                    <h5 class="mb-0 fw-bold">الخطوة الحالية النشطة: {{ $activeStep->getResolvedStageName() }}</h5>
                </div>
                <span class="badge bg-white text-primary fw-bold px-3 py-2 rounded-pill">
                    الخطوة #{{ $activeStep->step_order }}
                </span>
            </div>

            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted d-block">الجهة المسؤولة عن المراجعة:</small>
                            <strong class="text-dark fs-6">{{ $activeStep->entity?->name ?? $activeStep->authority?->agency_name ?? 'الجهة المعنية' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted d-block">نوع المرحلة / الإجراء المطلوب:</small>
                            <strong class="text-primary fs-6">{{ $activeStep->getPhaseArabicName() }}</strong>
                        </div>
                    </div>
                </div>

                @can('approve', $project)
                    <div class="border-top pt-4">
                        <h6 class="fw-bold text-dark mb-3">اتخاذ قرار بشأن هذه المرحلة:</h6>
                        <div class="d-flex flex-wrap gap-3">
                            {{-- Button 1: Approve --}}
                            <button type="button" class="btn btn-success btn-lg px-4 rounded-pill shadow-sm fw-bold flex-grow-1" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="fas fa-check-circle me-2"></i> موافقة / اعتماد
                            </button>

                            {{-- Button 2: Request Completion --}}
                            <button type="button" class="btn btn-warning btn-lg px-4 rounded-pill shadow-sm fw-bold flex-grow-1 text-dark" data-bs-toggle="modal" data-bs-target="#requestActionModal">
                                <i class="fas fa-undo me-2"></i> طلب استكمال نواقص
                            </button>

                            {{-- Button 3: Reject --}}
                            <button type="button" class="btn btn-danger btn-lg px-4 rounded-pill shadow-sm fw-bold flex-grow-1" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="fas fa-times-circle me-2"></i> رفض المشروع
                            </button>
                        </div>
                    </div>
                @else
                    <div class="alert alert-secondary border-0 rounded-3 d-flex align-items-center gap-3 mb-0">
                        <i class="fas fa-lock fs-4 text-secondary"></i>
                        <div>
                            <strong class="d-block text-dark">بانتظار إجراء الجهة المسؤولة</strong>
                            <small class="text-muted">المشروع بانتظار اتخاذ إجراء من قبل مراجعي <strong>{{ $activeStep->entity?->name ?? $activeStep->authority?->agency_name ?? 'الجهة المختصة' }}</strong> المخولين بصلاحية ({{ $activeStep->getPhaseArabicName() }}).</small>
                        </div>
                    </div>
                @endcan
            </div>
        </div>

        {{-- Modals for Active Step --}}
        @can('approve', $project)
            {{-- Modal: Approve --}}
            <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <form action="{{ route('projects.approval.approve', $project) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header bg-success text-white py-3 border-0">
                                <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i> اعتماد المرحلة الحالية</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="text-dark mb-3">سيتم اعتماد خطوة <strong>{{ $activeStep->getResolvedStageName() }}</strong> ونقل المشروع للمرحلة التالية في السلسلة الهرمية.</p>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">ملاحظات الاعتماد (اختياري):</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="أدخل أي ملاحظات فنية أو إدارية ترغب بتسجيلها..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">مرفق الاعتماد (اختياري):</label>
                                    <input type="file" name="attachment" class="form-control">
                                </div>
                            </div>
                            <div class="modal-footer bg-light border-0 py-3">
                                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">تأكيد الاعتماد</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Modal: Request Action / Completion --}}
            <div class="modal fade" id="requestActionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        @can('approvals.request-action')
                        <form action="{{ route('projects.approval.requestAction', $project) }}" method="POST" enctype="multipart/form-data">
                        @endcan
                            @csrf
                            <div class="modal-header bg-warning text-dark py-3 border-0">
                                <h5 class="modal-title fw-bold"><i class="fas fa-undo me-2"></i> طلب استكمال نواقص / مراجعة</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">إرجاع المشروع إلى: <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-column gap-2 mt-1">
                                        <div class="form-check p-3 bg-light rounded-3 border">
                                            <input class="form-check-input ms-2" type="radio" name="return_target" id="targetCreator" value="creator_entity" checked>
                                            <label class="form-check-label fw-bold text-dark" for="targetCreator">
                                                الجهة المنشئة للمشروع (Creator Entity)
                                                <small class="d-block text-muted fw-normal">يعود المشروع للجهة المنشئة لاستكمال النواقص وإعادة التقديم.</small>
                                            </label>
                                        </div>
                                        <div class="form-check p-3 bg-light rounded-3 border">
                                            <input class="form-check-input ms-2" type="radio" name="return_target" id="targetPrevious" value="previous_step">
                                            <label class="form-check-label fw-bold text-dark" for="targetPrevious">
                                                المرحلة السابقة مباشرة (Previous Step)
                                                <small class="d-block text-muted fw-normal">يعود المشروع للخطوة السابقة مباشرة لإعادة مراجعتها.</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">سبب طلب الاستكمال / الملاحظات: <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" placeholder="وضّح بالتفصيل النواقص والملاحظات المطلوب استكمالها (10 أحرف كحد أدنى)..." required minlength="10"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">مرفق توضيحي (اختياري):</label>
                                    <input type="file" name="attachment" class="form-control">
                                </div>
                            </div>
                            <div class="modal-footer bg-light border-0 py-3">
                                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">تأكيد طلب الاستكمال</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Modal: Reject --}}
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        @can('approvals.reject')
                        <form action="{{ route('projects.approval.reject', $project) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header bg-danger text-white py-3 border-0">
                                <h5 class="modal-title fw-bold"><i class="fas fa-times-circle me-2"></i> رفض المشروع</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="alert alert-danger border-0 rounded-3 mb-3">
                                    <i class="fas fa-exclamation-triangle me-1"></i> تحذير: رفض المشروع سيؤدي إلى إيقاف سلسلة الاعتماد وإرجاعه إلى المسودة.
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">سبب الرفض: <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" placeholder="أدخل أسباب الرفض بالتفصيل (10 أحرف كحد أدنى)..." required minlength="10"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">مرفق تقرير الرفض (اختياري):</label>
                                    <input type="file" name="attachment" class="form-control">
                                </div>
                            </div>
                            <div class="modal-footer bg-light border-0 py-3">
                                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">تأكيد الرفض</button>
                            </div>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        @endcan
    @endif

    {{-- ========================================================================= --}}
    {{-- 4. INDEPENDENT CONSULTATION / REFERRAL SECTION --}}
    {{-- ========================================================================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden mt-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-comments text-info fs-5"></i>
                <h5 class="mb-0 fw-bold text-dark">الاستشارات والإحالات الفنية (Consultations / Referrals)</h5>
            </div>
            @can('refer', $project)
                <button type="button" class="btn btn-outline-info rounded-pill px-3 btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#consultationModal">
                    <i class="fas fa-paper-plane me-1"></i> طلب استشارة جديدة
                </button>
            @endcan
        </div>

        <div class="card-body p-4">
            <p class="text-muted small mb-3">
                <i class="fas fa-info-circle me-1"></i> طلبات الاستشارة والإحالة هي إجراءات استشارية مستقلة ولا تؤثر على المرحلة النشطة أو تقدم المشروع في سلسلة الاعتماد.
            </p>

            @if($referrals->isEmpty())
                <div class="text-center py-4 bg-light rounded-3 text-muted">
                    <i class="fas fa-comment-slash fs-4 d-block mb-2 text-secondary opacity-50"></i>
                    لا توجد استشارات أو إحالات مسجلة لهذا المشروع حتى الآن.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>الجهة المحال إليها</th>
                                <th>نص الاستشارة</th>
                                <th>المرسل</th>
                                <th>التاريخ</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($referrals as $ref)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong class="text-dark">{{ $ref->referredEntity->name ?? "الجهة #{$ref->referred_entity_id}" }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-secondary">{{ Str::limit($ref->referral_text, 80) }}</span>
                                    </td>
                                    <td>{{ $ref->referringUser->name ?? ($ref->referringEntity->name ?? 'غير محدد') }}</td>
                                    <td><small class="text-muted">{{ $ref->created_at ? $ref->created_at->format('Y-m-d H:i') : '-' }}</small></td>
                                    <td>
                                        @if($ref->status === 'replied')
                                            <span class="badge bg-success">تم الرد</span>
                                        @else
                                            <span class="badge bg-warning text-dark">قيد الانتظار</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: Consultation / Referral --}}
    @can('refer', $project)
        <div class="modal fade" id="consultationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <form action="{{ route('projects.approval.referral', $project) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header bg-info text-white py-3 border-0">
                            <h5 class="modal-title fw-bold"><i class="fas fa-comments me-2"></i> طلب استشارة / إحالة</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">الجهة المطلوب استشارتها: <span class="text-danger">*</span></label>
                                <select name="referred_entity_id" id="modal2_referred_entity_id" class="form-select" required>
                                    <option value="">-- اختر الجهة المستشارة --</option>
                                    @foreach($entitiesList as $ent)
                                        <option value="{{ $ent->id }}">{{ $ent->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">المستخدم المستلم: <span class="text-danger">*</span></label>
                                <select name="referred_user_id" id="modal2_referred_user_id" class="form-select" required>
                                    <option value="">-- اختر المستخدم المستلم --</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">نص الاستشارة / الاستفسار الفني: <span class="text-danger">*</span></label>
                                <textarea name="referral_text" class="form-control" rows="4" placeholder="اكتب الاستشارة أو الاستفسار المطلوب بالتفصيل (10 أحرف كحد أدنى)..." required minlength="10"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">مرفقات الاستشارة (اختياري):</label>
                                <input type="file" name="attachments[]" class="form-control" multiple>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0 py-3">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-info text-white rounded-pill px-4 fw-bold">إرسال الاستشارة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const entitySelect = document.getElementById('modal2_referred_entity_id');
        const userSelect = document.getElementById('modal2_referred_user_id');

        if (entitySelect) {
            entitySelect.addEventListener('change', function() {
                const entityId = this.value;
                userSelect.innerHTML = '<option value="">-- اختر المستخدم المستلم --</option>';
                
                if (entityId) {
                    fetch(`/api/entities/${entityId}/users`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.users) {
                                data.users.forEach(user => {
                                    const option = document.createElement('option');
                                    option.value = user.id;
                                    option.textContent = user.name;
                                    userSelect.appendChild(option);
                                });
                            }
                        })
                        .catch(error => console.error('Error fetching users:', error));
                }
            });
        }
    });
</script>
