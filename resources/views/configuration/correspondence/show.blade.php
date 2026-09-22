@extends('layouts.app')

@section('content')
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
                        @if($correspondence->status !== 'closed' && $canClose)
                            <button type="button" class="btn btn-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#closeModal">
                                <i class="fas fa-lock me-1"></i>إغلاق
                            </button>
                        @endif
                        
                        
                        {{-- زر الانتقال السريع للرد --}}
                        @if($canReply && $correspondence->status !== 'closed')
                            <button type="button" class="btn btn-success btn-sm me-1" data-bs-toggle="collapse" data-bs-target="#reply-collapse" aria-expanded="false" aria-controls="reply-collapse">
                                <i class="fas fa-reply me-1"></i>إضافة رد
                            </button>
                        @endif
                        
                        {{-- زر الإحالة --}}
                        @if($canRefer && $correspondence->status !== 'closed')
                            <button type="button" class="btn btn-info btn-sm me-1 text-white" data-bs-toggle="collapse" data-bs-target="#referral-collapse" aria-expanded="false" aria-controls="referral-collapse">
                                <i class="fas fa-share me-1"></i>إحالة
                            </button>
                        @endif

                        {{-- زر التوجيه الداخلي --}}
                        @if($correspondence->status !== 'closed' && $subDepartments->count() > 0)
                            @can('correspondence.forward')
                                <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#forwardModal">
                                    <i class="fas fa-forward me-1"></i>توجيه
                                </button>
                            @endcan
                        @endif
                        
                        <a href="{{ route('correspondence.print', $correspondence->id) }}" class="btn btn-primary me-1" target="_blank">
                            <i class="fas fa-print me-1"></i>طباعة المراسلة (A4)
                        </a>

                        <a href="{{ route('correspondence.index') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>رجوع
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">رقم المراسلة</th>
                                    <td>
                                        <span class="badge bg-secondary">{{ $correspondence->correspondence_number }}</span>
                                        @if($correspondence->is_overdue)
                                            <span class="badge bg-danger ms-1">متأخرة</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>من</th>
                                    <td>
                                        {{ $correspondence->senderEntity->name }}
                                        <br>
                                        <small class="text-muted">{{ $correspondence->senderUser->name }}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>إلى</th>
                                    <td>{{ $correspondence->recipientEntity->name }}</td>
                                </tr>
                                <tr>
                                    <th>تاريخ الإرسال</th>
                                    <td>{{ $correspondence->formatted_sent_at }}</td>
                                </tr>
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
                                        {{-- الحالة تتحديث تلقائياً بناءً على الإجراءات (رد، إحالة، توجيه) --}}
                                        {{-- Status updates automatically based on actions (reply, referral, forward) --}}
                                        <span class="badge bg-{{ $correspondence->status == 'pending' ? 'warning' : ($correspondence->status == 'replied' ? 'success' : ($correspondence->status == 'referred' ? 'info' : ($correspondence->status == 'returned' ? 'danger' : 'secondary'))) }}">
                                            {{ $correspondence->status_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>الأولوية</th>
                                    <td>
                                        <span class="badge bg-{{ $correspondence->priority == 'urgent' ? 'danger' : ($correspondence->priority == 'high' ? 'warning' : 'success') }}">
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
                                <img src="{{ $qrCodeData }}" alt="QR Code" style="width: 120px; height: 120px;">
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
                                <h6><i class="fas fa-paperclip me-2"></i>المرفقات ({{ $correspondence->attachment_count }})</h6>
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
                                                   class="btn btn-sm btn-primary" 
                                                   target="_blank">
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
                    <small>تم الإنشاء: {{ $correspondence->formatted_created_at }} • آخر تحديث: {{ $correspondence->updated_at->format('Y-m-d H:i') }}</small>
                </div>
            </div>

            <!-- Message Thread -->
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
                                            {{ $correspondence->parent->correspondence_number }} - {{ $correspondence->parent->subject }}
                                        </a>
                                    </div>
                                    <span class="badge bg-secondary">{{ $correspondence->parent->status_label }}</span>
                                </li>
                            @endif
                            
                            @foreach($correspondence->children as $child)
                                <li class="list-group-item d-flex justify-content-between align-items-center {{ $child->id == $correspondence->id ? 'active' : '' }}">
                                    <div>
                                        <i class="fas fa-{{ $child->correspondence_type == 'reply' ? 'reply' : 'undo' }} me-2"></i>
                                        <strong>{{ $child->correspondence_type == 'reply' ? 'رد' : 'إرجاع' }}:</strong> 
                                        <a href="{{ route('correspondence.show', $child->id) }}" class="{{ $child->id == $correspondence->id ? 'text-white' : '' }}">
                                            {{ $child->correspondence_number }} - {{ $child->subject }}
                                        </a>
                                    </div>
                                    <span class="badge bg-{{ $child->status == 'pending' ? 'warning' : 'success' }}">{{ $child->status_label }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Message Movement Table -->
            <div class="card mb-4 border-primary shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>سجل حركة المراسلة</h6>
                    <span class="badge bg-light text-primary">{{ $correspondence->getFullThreadTimeline()->count() }} إجراء</span>
                </div>
                <div class="card-body p-0">
                    @include('correspondence.partials.movement-table')
                </div>
            </div>
            <!-- Reply Form -->
            <div id="reply-section"></div>
            @can('correspondence.reply')
            @if($canReply && $correspondence->status !== 'closed')
                <div class="collapse mb-4" id="reply-collapse">
                    <div class="card card-body shadow-sm border-success">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-success fw-bold"><i class="fas fa-reply me-2"></i>إضافة رد جديد</h6>
                            <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#reply-collapse"></button>
                        </div>
                        <form action="{{ route('correspondence.reply', $correspondence->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="reply_text" class="form-label">نص الرد <span class="text-danger">*</span></label>
                                <textarea name="reply_text" id="reply_text" rows="4" class="form-control" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-9 mb-3">
                                    <label for="attachments" class="form-label">المرفقات</label>
                                    <input type="file" name="attachments[]" id="attachments" class="form-control" multiple>
                                    <small class="form-text text-muted">يمكن رفع ملفات متعددة (الحد الأقصى 10 ميجابايت لكل ملف)</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="confidential" id="confidential" value="1">
                                        <label class="form-check-label" for="confidential">
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
            @endif
            @endcan

            <!-- Referral Form -->
            <div id="referral-section"></div>
            @can('correspondence.referral')
            @if($canRefer && $correspondence->status !== 'closed')
                <div class="collapse mb-4" id="referral-collapse">
                    <div class="card card-body shadow-sm border-info">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-info fw-bold"><i class="fas fa-share me-2"></i>إحالة المراسلة</h6>
                            <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#referral-collapse"></button>
                        </div>
                        <form action="{{ route('correspondence.referral', $correspondence->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="referred_to_entity_id" class="form-label">الجهة المحال إليها <span class="text-danger">*</span></label>
                                    <select name="referred_to_entity_id" id="referred_to_entity_id" class="form-select select2" required>
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
                                    <label for="deadline" class="form-label">الموعد النهائي</label>
                                    <input type="date" name="deadline" id="deadline" class="form-control" min="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="priority" class="form-label">الأولوية</label>
                                    <select name="priority" id="priority" class="form-select">
                                        <option value="normal">عادية</option>
                                        <option value="high">عالية</option>
                                        <option value="urgent">عاجلة</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="referral_text" class="form-label">نص الإحالة / التعليمات <span class="text-danger">*</span></label>
                                <textarea name="referral_text" id="referral_text" rows="3" class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="referral_attachments" class="form-label">المرفقات</label>
                                <input type="file" name="attachments[]" id="referral_attachments" class="form-control" multiple>
                            </div>
                            <div class="text-start">
                                <button type="submit" class="btn btn-info text-white">
                                    <i class="fas fa-share me-1"></i>إرسال الإحالة
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            @endcan
        </div>
    </div>
</div>

<!-- Close Correspondence Modal -->
<div class="modal fade" id="closeModal" tabindex="-1" aria-labelledby="closeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="closeModalLabel">إغلاق المراسلة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @can('correspondence.close')
            <form action="{{ route('correspondence.close', $correspondence->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="close_reason" class="form-label">سبب الإغلاق <span class="text-danger">*</span></label>
                        <textarea name="close_reason" id="close_reason" rows="4" class="form-control" required placeholder="يرجى كتابة سبب إغلاق المراسلة..."></textarea>
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
<div class="modal fade" id="forwardModal" tabindex="-1" aria-labelledby="forwardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="forwardModalLabel"><i class="fas fa-forward me-2"></i>توجيه المراسلة لأقسام فرعية</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @can('correspondence.forward')
            <form action="{{ route('correspondence.forward', $correspondence->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الأقسام الموجه إليها <span class="text-danger">*</span></label>
                        <select name="to_entity_ids[]" class="form-select select2-modal" multiple required style="width: 100%">
                            @foreach($subDepartments as $subDept)
                                <option value="{{ $subDept->id }}">{{ $subDept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modal_general_notes" class="form-label">ملاحظات التوجيه</label>
                        <textarea name="general_notes" id="modal_general_notes" rows="3" class="form-control" placeholder="اكتب التعليمات أو الملاحظات للأقسام..."></textarea>
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

@endsection

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/ar.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 for entity selector
    $('#referred_to_entity_id').select2({
        placeholder: 'اختر الجهة...',
        allowClear: true,
        language: "ar"
    });

    $('.select2').select2({
        placeholder: 'اختر الأقسام الفرعية...',
        language: "ar"
    });

    $('.select2-modal').select2({
        dropdownParent: $('#forwardModal'),
        placeholder: 'اختر الأقسام الفرعية...',
        language: "ar"
    });

    // Set minimum date for deadline
    const today = new Date().toISOString().split('T')[0];
    $('#deadline').attr('min', today);

    // File size validation
    $('#attachments, #referral_attachments').on('change', function() {
        const maxSize = 10 * 1024 * 1024; // 10MB in bytes
        const files = this.files;
        
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxSize) {
                alert(`الملف ${files[i].name} يتجاوز الحد الأقصى المسموح به (10 ميجابايت)`);
                $(this).val('');
                return;
            }
        }
    });
});

// تم استبدال تأكيد العلم اليدوي بالتأكيد التلقائي عند الاطلاع
// Manual acknowledgement replaced by automatic acknowledgement on view
</script>
@endpush