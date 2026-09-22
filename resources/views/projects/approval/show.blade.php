@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 font-weight-bold mb-0">{{ $project->project_name }}</h1>
                    <small class="text-muted">رقم النموذج: {{ $project->form_number }}</small>
                </div>
                <a href="{{ route('projects.approval.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> رجوع
                </a>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">معلومات المشروع</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>اسم المشروع:</strong> {{ $project->project_name }}</p>
                            <p><strong>رقم النموذج:</strong> {{ $project->form_number }}</p>
                            <p><strong>المطور:</strong> {{ $project->createdBy->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الحالة:</strong>
                                @php
                                    $statusColor = [
                                        'pending' => 'secondary',
                                        'in_process' => 'info',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                    ][$project->approval_status] ?? 'secondary';
                                    $statusAr = [
                                        'pending' => 'قيد الانتظار',
                                        'in_process' => 'قيد المعالجة',
                                        'approved' => 'موافق عليه',
                                        'rejected' => 'مرفوض',
                                    ][$project->approval_status] ?? 'غير معروف';
                                @endphp
                                <span class="badge badge-{{ $statusColor }}">{{ $statusAr }}</span>
                            </p>
                            <p><strong>المرحلة الحالية:</strong>
                                @if ($project->currentApprovalStage)
                                    <span class="badge badge-primary">{{ $project->currentApprovalStage->name }}</span>
                                @else
                                    <span class="badge badge-secondary">-</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> مراحل الموافقة</h5>
                </div>
                <div class="card-body">
                    <div class="approval-progress">
                        @foreach ($approval['progress'] as $index => $item)
                            @php
                                $statusBg = match($item['status']) {
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    'requires_action' => 'bg-warning',
                                    default => 'bg-secondary'
                                };
                                $statusAr = match($item['status']) {
                                    'approved' => 'موافق عليه',
                                    'rejected' => 'مرفوض',
                                    'requires_action' => 'يتطلب إجراء',
                                    default => 'قيد الانتظار'
                                };
                            @endphp
                            <div class="approval-stage mb-4 pb-3 border-bottom">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="d-flex align-items-start">
                                        <div class="stage-number me-3">
                                            <div class="badge {{ $statusBg }} rounded-circle" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold;">
                                                {{ $index + 1 }}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $item['stage']->name }}</h6>
                                            <small class="text-muted">{{ $item['stage']->description }}</small>
                                        </div>
                                    </div>
                                    <span class="badge badge-{{ $item['status'] === 'approved' ? 'success' : ($item['status'] === 'rejected' ? 'danger' : ($item['status'] === 'requires_action' ? 'warning' : 'secondary')) }}">
                                        {{ $statusAr }}
                                    </span>
                                </div>
                                @if ($item['completed_at'])
                                    <div class="ps-5 mt-3">
                                        <div class="small">
                                            <p class="mb-2"><i class="fas fa-calendar"></i> <strong>التاريخ والوقت:</strong> {{ $item['completed_at']->format('Y-m-d H:i') }}</p>
                                            @if ($item['reviewer_name'])
                                                <p class="mb-2"><i class="fas fa-user"></i> <strong>المراجع:</strong> {{ $item['reviewer_name'] }}</p>
                                            @endif
                                            @if ($item['action_required'])
                                                <div class="alert alert-warning py-2 px-3 mb-2">
                                                    <strong><i class="fas fa-exclamation-circle"></i> إجراء مطلوب:</strong><br>{{ $item['action_required'] }}
                                                </div>
                                            @endif
                                            @if ($item['notes'])
                                                <p class="mb-0"><strong><i class="fas fa-sticky-note"></i> ملاحظات:</strong><br>{{ $item['notes'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if ($canApprove && $project->approval_status === 'in_process')
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-gavel"></i> إجراءات الموافقة</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="fas fa-check-circle"></i> الموافقة
                            </button>
                            <button class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#requestActionModal">
                                <i class="fas fa-exclamation-triangle"></i> طلب إجراء
                            </button>
                            <button class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="fas fa-times-circle"></i> الرفض
                            </button>
                        </div>
                    </div>
                </div>
            @elseif ($project->approval_status === 'rejected' && $project->created_by_user_id === auth()->id())
                <div class="card mb-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-redo"></i> إعادة إرسال</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">تم رفض المشروع. يمكنك إعادة إرسال المشروع بعد إجراء التعديلات المطلوبة.</p>
                        <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#resubmitModal">
                            <i class="fas fa-paper-plane"></i> إعادة إرسال
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history"></i> سجل الحركة</h5>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    @if ($approval['history']->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach ($approval['history'] as $log)
                                @php
                                    $statusAr = match($log->status) {
                                        'approved' => 'موافق عليه',
                                        'rejected' => 'مرفوض',
                                        'requires_action' => 'يتطلب إجراء',
                                        default => 'قيد الانتظار'
                                    };
                                @endphp
                                <div class="list-group-item border-start border-3" style="border-color: {{ $log->status === 'approved' ? '#28a745' : ($log->status === 'rejected' ? '#dc3545' : ($log->status === 'requires_action' ? '#ffc107' : '#6c757d')) }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong class="d-block">
                                                @if ($log->approvalStage)
                                                    {{ $log->approvalStage->name }}
                                                @else
                                                    النظام
                                                @endif
                                            </strong>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-calendar"></i> {{ $log->logged_at->format('Y-m-d H:i') }}
                                            </small>
                                        </div>
                                        <span class="badge badge-{{ $log->status === 'approved' ? 'success' : ($log->status === 'rejected' ? 'danger' : ($log->status === 'requires_action' ? 'warning' : 'secondary')) }}">
                                            {{ $statusAr }}
                                        </span>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-user-circle"></i> <strong>المستخدم:</strong> {{ $log->user->name }}
                                        </small>
                                        @if ($log->entity)
                                            <small class="text-info d-block">
                                                <i class="fas fa-user-tag"></i> <strong>الدور:</strong> {{ $log->entity }}
                                            </small>
                                        @endif
                                    </div>
                                    @if ($log->action_required)
                                        <div class="alert alert-warning py-2 px-2 mt-2 mb-0" style="font-size: 0.85rem;">
                                            <strong><i class="fas fa-exclamation-circle"></i> إجراء مطلوب:</strong>
                                            <small class="d-block mt-1">{{ $log->action_required }}</small>
                                        </div>
                                    @endif
                                    @if ($log->notes)
                                        <small class="d-block mt-2 p-2" style="background-color: #f8f9fa; border-radius: 4px; border-right: 3px solid #17a2b8;">
                                            <i class="fas fa-sticky-note text-info"></i> {{ $log->notes }}
                                        </small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info mb-0 m-3">
                            <i class="fas fa-info-circle"></i> لا يوجد سجل حركة بعد.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if ($canApprove)
    <div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle"></i> الموافقة على المشروع</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('projects.approval.approve', $project) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="notes">ملاحظات (اختياري)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="أضف أي ملاحظات..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success">الموافقة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="requestActionModal" tabindex="-1" role="dialog" aria-labelledby="requestActionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> طلب إجراء</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @can('approvals.request-action')
                <form action="{{ route('projects.approval.requestAction', $project) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="action_required">الإجراء المطلوب </label>
                            <textarea class="form-control" id="action_required" name="action_required" rows="4" placeholder="اشرح الإجراء المطلوب..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="action_notes">ملاحظات (اختياري)</label>
                            <textarea class="form-control" id="action_notes" name="notes" rows="3" placeholder="أضف أي ملاحظات إضافية..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning">طلب إجراء</button>
                    </div>
                </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle"></i> رفض المشروع</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @can('approvals.reject')
                <form action="{{ route('projects.approval.reject', $project) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="reason">سبب الرفض </label>
                            <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="يرجى تقديم سبب مفصل..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">رفض</button>
                    </div>
                </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="modal fade" id="resubmitModal" tabindex="-1" role="dialog" aria-labelledby="resubmitModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-redo"></i> إعادة إرسال المشروع</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @can('projects.submit')
                <form action="{{ route('projects.approval.resubmit', $project) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> سيتم إعادة إرسال المشروع إلى نفس المرحلة للمراجعة مرة أخرى.
                        </div>
                        <div class="form-group">
                            <label for="resubmit_notes">ملاحظات (اختياري)</label>
                            <textarea class="form-control" id="resubmit_notes" name="notes" rows="3" placeholder="اشرح التعديلات التي تم إجراؤها..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">إعادة إرسال</button>
                    </div>
                </form>
                @endcan
            </div>
        </div>
    </div>
@endif

<style>
    .approval-stage {
        padding: 15px;
        border-left: 4px solid #007bff;
        background-color: #f8f9fa;
        border-radius: 4px;
    }

    .stage-connector {
        height: 30px;
        border-left: 2px dashed #dee2e6;
        margin-left: 20px;
    }

    .badge-lg {
        font-size: 1.1rem;
    }
</style>
@endsection
