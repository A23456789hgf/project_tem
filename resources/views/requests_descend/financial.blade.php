@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4" dir="rtl">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary font-weight-bold d-flex align-items-center">
                    <x-icon name="dollar-sign" class="ms-2" />
                    الملف المالي لطلب النزول #{{ $requestDescend->id }}
                </h5>
                <div class="d-flex align-items-center gap-2">
                    @if($requestDescend->financial_status !== 'approved')
                        @can('project-requests.view')
                        <form action="{{ route('requests_descend.financial.status', $requestDescend->id) }}" method="POST"
                            class="d-inline">
                            @csrf
                            @if($requestDescend->financial_status === 'draft')
                                <input type="hidden" name="financial_status" value="confirmed">
                                <button type="submit" class="btn btn-warning btn-sm"
                                    onclick="return confirmAction(this, 'هل أنت متأكد من تأكيد المسودة؟')">تأكيد المسودة</button>
                            @elseif($requestDescend->financial_status === 'confirmed')
                                <input type="hidden" name="financial_status" value="approved">
                                <button type="submit" class="btn btn-success btn-sm"
                                    onclick="return confirmAction(this, 'هل أنت متأكد من اعتماد الصرف؟ لا يمكن التعديل بعد الاعتماد.')">اعتماد
                                    الصرف</button>

                                <button type="button" class="btn btn-secondary btn-sm"
                                    onclick="document.getElementById('revert-draft').submit();">إعادة للمسودة</button>
                            @endif
                        </form>
                        @endcan
                    @endif
                    @if($requestDescend->financial_status === 'confirmed')
                        @can('project-requests.view')
                        <form id="revert-draft" action="{{ route('requests_descend.financial.status', $requestDescend->id) }}"
                            method="POST" class="d-none">
                            @csrf
                            <input type="hidden" name="financial_status" value="draft">
                        </form>
                        @endcan
                    @endif

                    <a href="{{ route('requests_descend.show', $requestDescend->id) }}"
                        class="btn btn-outline-secondary btn-sm">العودة للطلب</a>
                </div>
            </div>

            <div class="card-body">
                
                

                <div class="mb-4">
                    <strong>حالة الملف المالي: </strong>
                    @if($requestDescend->financial_status === 'draft')
                        <span class="badge bg-secondary">مسودة</span>
                    @elseif($requestDescend->financial_status === 'confirmed')
                        <span class="badge bg-warning text-dark">تم تأكيد المسودة</span>
                    @elseif($requestDescend->financial_status === 'approved')
                        <span class="badge bg-success">معتمد للصرف</span>
                    @endif
                </div>

                @if($requestDescend->financial_status === 'draft')
                    <div class="card bg-light mb-4 border-0">
                        <div class="card-body">
                            <h6 class="font-weight-bold mb-3">إضافة عضو جديد للنزول</h6>
                            @can('project-requests.create')
                            <form action="{{ route('requests_descend.financial.member.store', $requestDescend->id) }}"
                                method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small font-weight-bold">الاسم</label>
                                        <input type="text" name="name" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small font-weight-bold">الجهة (Entity)</label>
                                        <select name="entity_id" class="form-select form-select-sm" required>
                                            <option value="">اختر الجهة...</option>
                                            @foreach($entities as $entity)
                                                <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>ش
                                    <div class="col-md-2">
                                        <label class="form-label small font-weight-bold">العمل</label>
                                        <input type="text" name="work" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small font-weight-bold">المبلغ اليومي</label>
                                        <input type="number" name="daily_amount" class="form-control form-control-sm"
                                            step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small font-weight-bold">المدة</label>
                                        <input type="number" name="duration" class="form-control form-control-sm" step="0.01"
                                            min="0" required>
                                    </div>
                                    <div class="col-12 mt-3 text-start">
                                        <button type="submit" class="btn btn-primary btn-sm">إضافة العضو</button>
                                    </div>
                                </div>
                            </form>
                            @endcan
                        </div>
                    </div>
                @endif

                <h6 class="text-uppercase text-secondary font-weight-bold mb-3">أعضاء النزول الميداني والتكاليف</h6>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>الاسم</th>
                                <th>الجهة</th>
                                <th>العمل</th>
                                <th>المبلغ اليومي</th>
                                <th>المدة</th>
                                <th>الإجمالي</th>
                                @if($requestDescend->financial_status === 'draft')
                                    <th>الإجراءات</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandTotal = 0; @endphp
                            @forelse($requestDescend->members as $member)
                                @php $grandTotal += $member->total; @endphp
                                <tr>
                                    <td>{{ $member->name }}</td>
                                    <td>{{ optional($member->entity)->name }}</td>
                                    <td>{{ $member->work }}</td>
                                    <td>{{ number_format($member->daily_amount, 2) }}</td>
                                    <td>{{ $member->duration }}</td>
                                    <td><strong>{{ number_format($member->total, 2) }}</strong></td>
                                    @if($requestDescend->financial_status === 'draft')
                                        <td>
                                            <form
                                                action="{{ route('requests_descend.financial.member.destroy', [$requestDescend->id, $member->id]) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirmAction(this, 'هل أنت متأكد من الحذف؟')">حذف</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $requestDescend->financial_status === 'draft' ? 7 : 6 }}"
                                        class="text-center text-muted py-3">
                                        لا يوجد أعضاء مضافين حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($requestDescend->members->count() > 0)
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="5" class="text-end font-weight-bold">الإجمالي الكلي:</td>
                                    <td class="font-weight-bold">{{ number_format($grandTotal, 2) }}</td>
                                    @if($requestDescend->financial_status === 'draft')
                                        <td></td>
                                    @endif
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection