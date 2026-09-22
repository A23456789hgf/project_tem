@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4" dir="rtl">

        <div class="card shadow-sm">

            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">

                <h5 class="mb-0 text-primary font-weight-bold d-flex align-items-center">
                    <x-icon name="eye" class="ms-2" />
                    تفاصيل طلب النزول #{{ $requestDescend->id }}
                </h5>

                    <div class="d-flex align-items-center gap-2">
                        @if(in_array($requestDescend->status, ['pending_approval', 'under_technical_review', 'under_financial_review']))
                            <button type="button" class="btn btn-warning btn-sm d-inline-flex align-items-center gap-2 font-weight-bold" data-bs-toggle="modal" data-bs-target="#approvalModal">
                                <x-icon name="shield" />
                                معالجة الاعتماد
                            </button>
                        @endif

                        @can('requests_descend.financial', $requestDescend)
                        <a href="{{ route('requests_descend.financial', $requestDescend->id) }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2">
                            <x-icon name="dollar-sign" />
                            الملف المالي للنزول
                        </a>
                        @endcan

                        @can('requests_descend.edit', $requestDescend)
                        <a href="{{ route('requests_descend.edit', $requestDescend->id) }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                            <x-icon name="edit" />
                            تعديل
                        </a>
                        @endcan

                        <a href="{{ route('requests_descend.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2">
                            <x-icon name="arrow-right" />
                            العودة للقائمة
                        </a>
                    </div>
                </div>

                <!-- Approval Modal -->
                <div class="modal fade" id="approvalModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content border-0 rounded-4 shadow-sm text-start">
                            <div class="modal-header bg-light border-bottom-0 rounded-top-4">
                                <h5 class="modal-title fw-bold text-primary"><i class="fas fa-shield-alt me-2"></i> قرار الاعتماد أو المراجعة</h5>
                                <button type="button" class="btn-close m-0 ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            @can('project-requests.approve')
                            <form action="{{ route('requests_descend.processApproval', $requestDescend->id) }}" method="POST">
                                @csrf
                                <div class="modal-body p-4">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-dark">حدد القرار <span class="text-danger">*</span></label>
                                        <select name="action" class="form-select border-2" required>
                                            <option value="">-- اختر الإجراء المناسب --</option>
                                            <option value="approve">اعتماد نهائي</option>
                                            <option value="technical_review">إحالة للمراجعة الفنية</option>
                                            <option value="financial_review">إحالة للمراجعة المالية</option>
                                            <option value="return">إعادة لمنشئ الطلب (للتعديل)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark">الملاحظات (اختياري)</label>
                                        <textarea name="notes" class="form-control border-2" rows="3" placeholder="أدخل أي ملاحظات مرافقة للقرار..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer border-top-0 bg-light rounded-bottom-4">
                                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ القرار</button>
                                </div>
                            </form>
                            @endcan
                        </div>
                    </div>
                </div>
                <!-- End Modal -->

            </div>

            <div class="card-body">

                <h6 class="text-uppercase text-secondary font-weight-bold mb-3">
                    المعلومات العامة
                </h6>

                <div class="row mb-4">

                    <div class="col-md-6 mb-3">
                        <strong>مرتبط بمشروع؟</strong>

                        <p>
                            @if($requestDescend->is_linked_to_project)
                                <span class="badge bg-success">
                                    نعم
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    لا
                                </span>
                            @endif
                        </p>
                    </div>

                    @if($requestDescend->is_linked_to_project)
                        <div class="col-md-6 mb-3">
                            <strong>اسم المشروع:</strong>

                            <p>
                                {{ $requestDescend->project ? $requestDescend->project->project_name : 'غير متوفر' }}
                            </p>
                        </div>
                    @endif

                    <div class="col-md-6 mb-3">
                        <strong>الأولوية:</strong>

                        <p>
                            {{ $requestDescend->priority ?? 'غير متوفر' }}
                        </p>
                    </div>

                </div>

                <div class="row mb-4">

                    <div class="col-md-12 mb-3">
                        <strong>الاحتياجات:</strong>

                        <p class="text-muted border p-3 rounded bg-light">
                            {{ $requestDescend->needs ?: 'غير متوفر' }}
                        </p>
                    </div>

                    <div class="col-md-12 mb-3">
                        <strong>سبب النزول:</strong>

                        <p class="text-muted border p-3 rounded bg-light">
                            {{ $requestDescend->reason_for_drop ?: 'غير متوفر' }}
                        </p>
                    </div>

                    <div class="col-md-12 mb-3">
                        <strong>هدف النزول:</strong>

                        <p class="text-muted border p-3 rounded bg-light">
                            {{ $requestDescend->objective_of_drop ?: 'غير متوفر' }}
                        </p>
                    </div>

                    @if($requestDescend->notes)
                    <div class="col-md-12 mb-3">
                        <strong>ملاحظات الاعتماد والمراجعة:</strong>

                        <p class="text-muted border p-3 rounded" style="background-color: #fff3cd; border-color: #ffeeba;">
                            <i class="fas fa-info-circle me-1 text-warning"></i> {{ $requestDescend->notes }}
                        </p>
                    </div>
                    @endif

                </div>

                <hr>

                <h6 class="text-uppercase text-secondary font-weight-bold mb-3">
                    الأنشطة
                </h6>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped text-center align-middle">

                        <thead class="bg-light">
                            <tr>
                                <th>النشاط</th>
                                <th>المخرجات المتوقعة</th>
                                <th>من تاريخ</th>
                                <th>إلى تاريخ</th>
                                <th>المدة (بالأيام)</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($requestDescend->activities as $activity)

                                <tr>

                                    <td>
                                        {{ $activity->activity }}
                                    </td>

                                    <td>
                                        {{ $activity->expected_output ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $activity->from_date ? $activity->from_date->format('Y-m-d') : '-' }}
                                    </td>

                                    <td>
                                        {{ $activity->to_date ? $activity->to_date->format('Y-m-d') : '-' }}
                                    </td>

                                    <td>
                                        {{ $activity->duration }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        لا توجد أنشطة مضافة.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>
@endsection