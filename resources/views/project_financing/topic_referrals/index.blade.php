@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>إدارة الإحالات</h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createTopicModal">
                        <i class="fas fa-plus me-1"></i>إحالة موضوع جديد
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>الموضوع</th>
                                    <th>بواسطة</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>آخر إحالة</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topics as $topic)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $topic->subject }}</td>
                                        <td>{{ $topic->creator->name }}</td>
                                        <td>{{ $topic->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @if($topic->latestActivity)
                                                <span class="badge bg-info">
                                                    {{ $topic->latestActivity->referral_number }}
                                                    ({{ $topic->latestActivity->toDepartment->name }})
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($topic->status == 'active')
                                                <span class="badge bg-success">نشط</span>
                                            @else
                                                <span class="badge bg-secondary">مغلق</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('referrals.show', $topic->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>عرض التفاصيل
                                            </a>
                                            @can('referrals.export')
                                            <a href="{{ route('referrals.export', $topic->id) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-file-excel me-1"></i>تصدير
                                            </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">لا توجد إحالات مسجلة</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $topics->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Topic Modal -->
<div class="modal fade" id="createTopicModal" tabindex="-1" aria-labelledby="createTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @can('referrals.create')
            <form action="{{ route('referrals.store') }}" method="POST" enctype="multipart/form-data">
            @endcan
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createTopicModalLabel">إنشاء إحالة موضوع جديد</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subject" class="form-label">الموضوع <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">تفاصيل الإحالات <span class="text-danger">*</span></h6>
                            <button type="button" class="btn btn-sm btn-success" id="add-referral">
                                <i class="fas fa-plus me-1"></i>إضافة إحالة
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="referrals-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%">الجهة المحال إليها</th>
                                            <th style="width: 40%">نص الإحالة</th>
                                            <th style="width: 25%">المرفقات</th>
                                            <th style="width: 5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="referrals-container">
                                        <tr class="referral-row">
                                            <td>
                                                <select class="form-select select2-dynamic" name="referrals[0][to_department_id]" required>
                                                    <option value="">اختر الجهة...</option>
                                                    @foreach($departments as $dept)
                                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <textarea class="form-control" name="referrals[0][referral_text]" rows="2" required></textarea>
                                            </td>
                                            <td>
                                                <input type="file" class="form-control" name="referrals[0][attachments][]" multiple>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger remove-referral" disabled>
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ وإرسال الإحالات</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let referralIndex = 1;

    // Add referral row
    $('#add-referral').click(function() {
        const newRow = `
            <tr class="referral-row">
                <td>
                    <select class="form-select select2-dynamic" name="referrals[${referralIndex}][to_department_id]" required>
                        <option value="">اختر الجهة...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <textarea class="form-control" name="referrals[${referralIndex}][referral_text]" rows="2" required></textarea>
                </td>
                <td>
                    <input type="file" class="form-control" name="referrals[${referralIndex}][attachments][]" multiple>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-referral">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#referrals-container').append(newRow);
        
        // Initialize select2 for the new row
        $('.select2-dynamic').select2({
            dropdownParent: $('#createTopicModal')
        });
        
        referralIndex++;
        updateRemoveButtons();
    });

    // Remove referral row
    $(document).on('click', '.remove-referral', function() {
        $(this).closest('tr').remove();
        updateRemoveButtons();
    });

    function updateRemoveButtons() {
        const rowCount = $('.referral-row').length;
        if (rowCount <= 1) {
            $('.remove-referral').prop('disabled', true);
        } else {
            $('.remove-referral').prop('disabled', false);
        }
    }

    // Initial select2
    $('.select2-dynamic').select2({
        dropdownParent: $('#createTopicModal')
    });
});
</script>
@endpush
@endsection
