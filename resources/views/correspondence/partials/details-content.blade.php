<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <!-- Correspondence Details Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>
                        تفاصيل المراسلة - {{ $correspondence->correspondence_number }}
                        @if($correspondence->confidential)
                            <span class="badge bg-dark ms-2">سري</span>
                        @endif
                    </h5>
                    <div>
                        @can('close', $correspondence)
                            <button type="button" class="btn btn-warning btn-sm me-1 auth-perm-correspondence-close"
                                data-bs-toggle="modal" data-bs-target="#closeModal-{{ $correspondence->id }}">
                                <i class="fas fa-lock me-1"></i>إغلاق
                            </button>
                        @endcan


                        @can('reply', $correspondence)
                            @if(!auth()->user()->hasSignature())
                                <button type="button" class="btn btn-success btn-sm me-1 auth-perm-correspondence-reply"
                                    onclick="alert('عذراً، يجب عليك إضافة توقيعك الشخصي في ملفك حتى تتمكن من إضافة رد.'); window.location.href='{{ route('profile.show') }}';">
                                    <i class="fas fa-reply me-1"></i>إضافة رد
                                </button>
                            @else
                                <button type="button" class="btn btn-success btn-sm me-1 auth-perm-correspondence-reply"
                                    data-bs-toggle="collapse" data-bs-target="#reply-collapse-{{ $correspondence->id }}"
                                    aria-expanded="false" aria-controls="reply-collapse-{{ $correspondence->id }}">
                                    <i class="fas fa-reply me-1"></i>إضافة رد
                                </button>
                            @endif
                        @endcan

                        @can('refer', $correspondence)
                            @if(!auth()->user()->hasSignature())
                                <button type="button"
                                    class="btn btn-info btn-sm me-1 text-white auth-perm-correspondence-referral"
                                    onclick="alert('عذراً، يجب عليك إضافة توقيعك الشخصي في ملفك حتى تتمكن من إحالة المراسلة.'); window.location.href='{{ route('profile.show') }}';">
                                    <i class="fas fa-share me-1"></i>إحالة
                                </button>
                            @else
                                <button type="button"
                                    class="btn btn-info btn-sm me-1 text-white auth-perm-correspondence-referral"
                                    data-bs-toggle="collapse" data-bs-target="#referral-collapse-{{ $correspondence->id }}"
                                    aria-expanded="false" aria-controls="referral-collapse-{{ $correspondence->id }}">
                                    <i class="fas fa-share me-1"></i>إحالة
                                </button>
                            @endif
                        @endcan

                        @can('forward', $correspondence)
                            @if($correspondence->status !== 'closed')
                                @if(!auth()->user()->hasSignature())
                                    <button type="button" class="btn btn-primary btn-sm me-1 auth-perm-correspondence-forward"
                                        onclick="alert('عذراً، يجب عليك إضافة توقيعك الشخصي في ملفك حتى تتمكن من توجيه المراسلة.'); window.location.href='{{ route('profile.show') }}';">
                                        <i class="fas fa-forward me-1"></i>توجيه
                                    </button>
                                @else
                                    <button type="button" class="btn btn-primary btn-sm me-1 auth-perm-correspondence-forward"
                                        data-bs-toggle="modal" data-bs-target="#forwardModal-{{ $correspondence->id }}">
                                        <i class="fas fa-forward me-1"></i>توجيه
                                    </button>
                                @endif
                            @endif
                        @endcan

                        <button type="button" class="btn btn-outline-info btn-sm me-1" data-bs-toggle="modal" data-bs-target="#printPreviewModal-{{ $correspondence->id }}">
                            <i class="fas fa-eye me-1"></i>معاينة الطباعة
                        </button>

                        @can('print', $correspondence)
                            <a href="{{ route('correspondence.print', $correspondence->id) }}"
                                class="btn btn-secondary btn-sm me-1 auth-perm-correspondence-export" target="_blank">
                                <i class="fas fa-print me-1"></i>طباعة (PDF)
                            </a>
                        @endcan

                        @if(!isset($is_embedded) || !$is_embedded) <a href="{{ route('correspondence.index') }}"
                            class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>رجوع
                        </a> @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">رقم المراسلة</th>
                                    <td>
                                        <span
                                            class="badge bg-secondary">{{ $correspondence->correspondence_number }}</span>
                                        @if($correspondence->is_overdue)
                                            <span class="badge bg-danger ms-1">متأخرة</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>من</th>
                                    <td>
                                        {{ $correspondence->senderEntity?->name ?? 'غير محدد' }}
                                        <br>
                                        <small
                                            class="text-muted">{{ $correspondence->senderUser?->name ?? 'غير محدد' }}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>إلى</th>
                                    <td>{{ $correspondence->recipientEntity?->name ?? 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <th>تاريخ الإرسال</th>
                                    <td>{{ $correspondence->formatted_sent_at }}</td>
                                </tr>
                                @if($correspondence->project_id)
                                    <tr>
                                        <th>المشروع المرتبط</th>
                                        <td>
                                            <a href="{{ route('projects.show', $correspondence->project_id) }}"
                                                target="_blank">
                                                <i class="fas fa-project-diagram me-1 text-primary"></i>
                                                {{ $correspondence->project->project_name }}
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-5">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">الموضوع</th>
                                    <td>{{ $correspondence->subject }}</td>
                                </tr>
                                <tr>
                                    <th>الحالة</th>
                                    <td>
                                        <span
                                            class="badge bg-{{ $correspondence->status == 'pending' ? 'warning' : ($correspondence->status == 'replied' ? 'success' : ($correspondence->status == 'referred' ? 'info' : ($correspondence->status == 'returned' ? 'danger' : 'secondary'))) }}">
                                            {{ $correspondence->status_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>الأولوية</th>
                                    <td>
                                        <span
                                            class="badge bg-{{ $correspondence->priority == 'urgent' ? 'danger' : ($correspondence->priority == 'high' ? 'warning' : 'success') }}">
                                            {{ $correspondence->priority_label }}
                                        </span>
                                    </td>
                                </tr>
                                @if($correspondence->status === 'closed')
                                    <tr>
                                        <th>تاريخ الإغلاق</th>
                                        <td>{{ $correspondence->formatted_closed_at }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-2 text-center">
                            <div class="border p-2 bg-white d-inline-block">
                                <img src="{{ $qrCodeData ?? $correspondence->qrCodeData ?? '' }}" alt="QR Code"
                                    style="width: 120px; height: 120px;">
                                <div class="mt-1 small text-muted">مسح للوصول السريع</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="fas fa-file-alt me-2"></i>نص المراسلة:</h6>
                            <div class="border p-4 bg-light rounded">
                                {!! nl2br(e($correspondence->message_body)) !!}
                            </div>
                        </div>
                    </div>

                    @if($correspondence->notes)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6><i class="fas fa-sticky-note me-2"></i>ملاحظات:</h6>
                                <div class="border p-4 bg-light rounded">
                                    {!! nl2br(e($correspondence->notes)) !!}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($correspondence->close_reason)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6><i class="fas fa-info-circle me-2"></i>سبب الإغلاق:</h6>
                                <div class="border p-4 bg-light rounded">
                                    {!! nl2br(e($correspondence->close_reason)) !!}
                                    @if($correspondence->closedByUser)
                                        <div class="mt-2 text-muted">
                                            تم الإغلاق بواسطة: {{ $correspondence->closedByUser->name }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($correspondence->attachment_count > 0)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6><i class="fas fa-paperclip me-2"></i>المرفقات ({{ $correspondence->attachment_count }})
                                </h6>
                                <div class="list-group">
                                    @foreach($correspondence->attachments as $index => $attachment)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-file me-2"></i>
                                                <span>{{ $attachment['original_name'] }}</span>
                                                <small class="text-muted ms-2">
                                                    ({{ number_format($attachment['size'] / 1024, 2) }} ك.ب)
                                                </small>
                                            </div>
                                            <div>
                                                <a href="{{ route('correspondence.download', ['id' => $correspondence->id, 'type' => 'correspondence', 'index' => $loop->index]) }}"
                                                    class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-download"></i> تحميل
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer text-muted">
                    <small>تم الإنشاء: {{ $correspondence->formatted_created_at }} • آخر تحديث:
                        {{ $correspondence->updated_at->format('Y-m-d H:i') }}</small>
                </div>
            </div>

            @if($correspondence->parent || $correspondence->children->count() > 0)
                <div class="card mb-4 border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-sitemap me-2"></i>سلسلة المراسلات المرتبطة</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @if($correspondence->parent)
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                    <div>
                                        <i class="fas fa-level-up-alt me-2 text-primary"></i>
                                        <strong>المراسلة الأصلية:</strong>
                                        <a href="{{ route('correspondence.show', $correspondence->parent_id) }}">
                                            {{ $correspondence->parent->correspondence_number }} -
                                            {{ $correspondence->parent->subject }}
                                        </a>
                                    </div>
                                    <span class="badge bg-secondary">{{ $correspondence->parent->status_label }}</span>
                                </li>
                            @endif

                            @foreach($correspondence->children as $child)
                                <li
                                    class="list-group-item d-flex justify-content-between align-items-center {{ $child->id == $correspondence->id ? 'active' : '' }}">
                                    <div>
                                        <i
                                            class="fas fa-{{ $child->correspondence_type == 'reply' ? 'reply' : 'undo' }} me-2"></i>
                                        <strong>{{ $child->correspondence_type == 'reply' ? 'رد' : 'إرجاع' }}:</strong>
                                        <a href="{{ route('correspondence.show', $child->id) }}"
                                            class="{{ $child->id == $correspondence->id ? 'text-white' : '' }}">
                                            {{ $child->correspondence_number }} - {{ $child->subject }}
                                        </a>
                                    </div>
                                    <span
                                        class="badge bg-{{ $child->status == 'pending' ? 'warning' : 'success' }}">{{ $child->status_label }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Linked Tasks Table -->
            @include('correspondence.partials.tasks-table')

            <!-- Correspondence Actions Table (Referrals, Replies, Rejections) -->
            <div class="card mb-4 border-info shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>الإجراءات المتخذة (الإحالات، الردود،
                        والإرجاعات)</h6>
                </div>
                <div class="card-body p-0">
                    @include('correspondence.partials.actions-table')
                </div>
            </div>

            <!-- Message Movement Table -->
            <div class="card mb-4 border-primary shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>سجل حركة المراسلة الكامل</h6>
                    <span class="badge bg-light text-primary">{{ $correspondence->getFullThreadTimeline()->count() }}
                        إجراء</span>
                </div>
                <div class="card-body p-0">
                    @include('correspondence.partials.movement-table')
                </div>
            </div>
            <!-- Reply Form -->
            <div id="reply-section"></div>
            @can('reply', $correspondence)
                <div class="collapse mb-4" id="reply-collapse-{{ $correspondence->id }}">
                    <div class="card card-body shadow-sm border-success">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-success fw-bold"><i class="fas fa-reply me-2"></i>إضافة رد جديد</h6>
                            <button type="button" class="btn-close" data-bs-toggle="collapse"
                                data-bs-target="#reply-collapse-{{ $correspondence->id }}"></button>
                        </div>
                        <form action="{{ route('correspondence.reply', $correspondence->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="reply_text-{{ $correspondence->id }}" class="form-label">نص الرد <span
                                        class="text-danger">*</span></label>
                                <textarea name="reply_text" id="reply_text-{{ $correspondence->id }}" rows="4"
                                    class="form-control" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-9 mb-3">
                                    <label for="attachments-{{ $correspondence->id }}" class="form-label">المرفقات</label>
                                    <input type="file" name="attachments[]" id="attachments-{{ $correspondence->id }}"
                                        class="form-control" multiple>
                                    <small class="form-text text-muted">يمكن رفع ملفات متعددة (الحد الأقصى 10 ميجابايت لكل
                                        ملف)</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="confidential"
                                            id="confidential-{{ $correspondence->id }}" value="1">
                                        <label class="form-check-label" for="confidential-{{ $correspondence->id }}">
                                            رد سري
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-paper-plane me-1"></i>إرسال الرد
                                </button>
                                <button type="submit" name="is_return" value="1" class="btn btn-danger">
                                    <i class="fas fa-undo me-1"></i>إرجاع إلى المرسل
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            <!-- Referral Form -->
            <div id="referral-section"></div>
            @can('refer', $correspondence)
                <div class="collapse mb-4" id="referral-collapse-{{ $correspondence->id }}">
                    <div class="card card-body shadow-sm border-info">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-info fw-bold"><i class="fas fa-share me-2"></i>إحالة المراسلة</h6>
                            <button type="button" class="btn-close" data-bs-toggle="collapse"
                                data-bs-target="#referral-collapse-{{ $correspondence->id }}"></button>
                        </div>
                        <form action="{{ route('correspondence.referral', $correspondence->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="referred_to_entity_id-{{ $correspondence->id }}" class="form-label">الجهة
                                        المحال إليها <span class="text-danger">*</span></label>
                                    <select name="referred_to_entity_id"
                                        id="referred_to_entity_id-{{ $correspondence->id }}"
                                        class="form-select select2-dynamic" required>
                                        <option value="">اختر الجهة...</option>

                                        {{-- الإدارة العليا (سلسلة المراجع) --}}
                                        @php
                                            $managementEntities = Auth::user()->entity ? Auth::user()->entity->getParentChain() : collect();
                                        @endphp
                                        @if($managementEntities->count() > 0)
                                            <optgroup label="الإدارة العليا (المراجع)">
                                                @foreach($managementEntities as $mgmt)
                                                    <option value="{{ $mgmt->id }}">{{ $mgmt->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endif

                                        {{-- الأقسام الفرعية --}}
                                        @if(isset($subDepartments) && $subDepartments->count() > 0)
                                            <optgroup label="الأقسام والإدارات الفرعية التابعة">
                                                @foreach($subDepartments as $sub)
                                                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endif

                                        {{-- الجهات الأخرى والزميلة --}}
                                        <optgroup label="جهات أخرى وإدارات زميلة">
                                            @foreach($entities as $entity)
                                                {{-- تجنب التكرار مع الإدارة العليا والأقسام الفرعية والجهة الحالية --}}
                                                @php
                                                    $isMgmt = $managementEntities->contains('id', $entity->id);
                                                    $isSub = isset($subDepartments) && $subDepartments->contains('id', $entity->id);
                                                    $isCurrentRecipient = $entity->id === $correspondence->recipient_entity_id;
                                                @endphp

                                                @if(!$isMgmt && !$isSub && !$isCurrentRecipient)
                                                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="deadline-{{ $correspondence->id }}" class="form-label">الموعد
                                        النهائي</label>
                                    <input type="date" name="deadline" id="deadline-{{ $correspondence->id }}"
                                        class="form-control" min="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="priority-{{ $correspondence->id }}" class="form-label">الأولوية</label>
                                    <select name="priority" id="priority-{{ $correspondence->id }}" class="form-select">
                                        <option value="normal">عادية</option>
                                        <option value="high">عالية</option>
                                        <option value="urgent">عاجلة</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="referral_text-{{ $correspondence->id }}" class="form-label">نص الإحالة /
                                    التعليمات <span class="text-danger">*</span></label>
                                <textarea name="referral_text" id="referral_text-{{ $correspondence->id }}" rows="3"
                                    class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="referral_attachments-{{ $correspondence->id }}"
                                    class="form-label">المرفقات</label>
                                <input type="file" name="attachments[]" id="referral_attachments-{{ $correspondence->id }}"
                                    class="form-control" multiple>
                            </div>
                            <div class="text-start">
                                <button type="submit" class="btn btn-info text-white">
                                    <i class="fas fa-share me-1"></i>إرسال الإحالة
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</div>

<!-- Close Correspondence Modal -->
<div class="modal fade" id="closeModal-{{ $correspondence->id }}" tabindex="-1" aria-labelledby="closeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="closeModalLabel">إغلاق المراسلة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            @can('correspondence.close')
            <form action="{{ route('correspondence.close', $correspondence->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="close_reason-{{ $correspondence->id }}" class="form-label">سبب الإغلاق <span
                                class="text-danger">*</span></label>
                        <textarea name="close_reason" id="close_reason-{{ $correspondence->id }}" rows="4"
                            class="form-control" required placeholder="يرجى كتابة سبب إغلاق المراسلة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning">تأكيد الإغلاق</button>
                </div>
            </form>
            @endcan
        </div>
    </div>
</div>

<!-- Internal Forwarding Quick Modal -->
<div class="modal fade" id="forwardModal-{{ $correspondence->id }}" tabindex="-1" aria-labelledby="forwardModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="forwardModalLabel"><i class="fas fa-forward me-2"></i>توجيه المراسلة لأقسام
                    فرعية</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            @can('correspondence.forward')
            <form action="{{ route('correspondence.forward', $correspondence->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الأقسام الموجه إليها <span class="text-danger">*</span></label>
                        <select name="to_entity_ids[]" class="form-select select2-dynamic-modal" multiple required
                            style="width: 100%">
                            @foreach($subDepartments as $subDept)
                                <option value="{{ $subDept->id }}">{{ $subDept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modal_general_notes-{{ $correspondence->id }}" class="form-label">ملاحظات
                            التوجيه</label>
                        <textarea name="general_notes" id="modal_general_notes-{{ $correspondence->id }}" rows="3"
                            class="form-control" placeholder="اكتب التعليمات أو الملاحظات للأقسام..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إرسال التوجيه</button>
                </div>
            </form>
            @endcan
        </div>
    </div>
</div>

<!-- Print Preview Modal -->
<div class="modal fade" id="printPreviewModal-{{ $correspondence->id }}" tabindex="-1"
    aria-labelledby="printPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="printPreviewModalLabel">
                    <i class="fas fa-eye me-2"></i>معاينة المراسلة قبل الطباعة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="background-color: #f4f4f4;">
                <div class="d-flex justify-content-center py-4">
                    <div class="shadow-lg bg-white" style="width: 210mm; min-height: 297mm; overflow: hidden;">
                        <iframe src="{{ route('correspondence.preview', $correspondence->id) }}" frameborder="0"
                            style="width: 100%; height: 297mm; border: none;" id="previewIframe"></iframe>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-success" onclick="printFromPreview()">
                    <i class="fas fa-print me-1"></i>طباعة مباشرة
                </button>
                <a href="{{ route('correspondence.print', $correspondence->id) }}" class="btn btn-primary"
                    target="_blank">
                    <i class="fas fa-file-pdf me-1"></i>تصدير كملف PDF
                </a>
            </div>
        </div>
    </div>
</div>