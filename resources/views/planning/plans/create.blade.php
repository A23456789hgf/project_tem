@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        @can('plans.create')
        <form action="{{ route('plans.store') }}" method="POST" id="planForm">
        @endcan
            @csrf

            <!-- Header Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plus-circle me-2"></i> إضافة خطة جديدة
                    </h5>
                    <a href="{{ route('plans.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-right"></i> العودة للقائمة
                    </a>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <!-- التاريخ الميلادي -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">تاريخ البدء (ميلادي) *</label>
                            <input type="date" id="start_date_g" name="start_date_g"
                                class="form-control @error('start_date_g') is-invalid @enderror"
                                value="{{ old('start_date_g') }}" required>
                            @error('start_date_g')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">تاريخ الانتهاء (ميلادي) *</label>
                            <input type="date" id="end_date_g" name="end_date_g"
                                class="form-control @error('end_date_g') is-invalid @enderror"
                                value="{{ old('end_date_g') }}" required>
                            @error('end_date_g')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- التاريخ الهجري -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">تاريخ البدء (هجري)</label>
                            <input type="text" id="start_date_h" name="start_date_h" class="form-control bg-light" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">تاريخ الانتهاء (هجري)</label>
                            <input type="text" id="end_date_h" name="end_date_h" class="form-control bg-light" readonly>
                        </div>

                        <!-- الجهة -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">الجهة المقدمة للخطة *</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ Auth::user()->entity?->name ?? 'لم يتم تعيين جهة' }}" readonly>
                            <input type="hidden" name="submitting_entity_id" value="{{ Auth::user()->entity_id }}">
                            <small class="text-muted">يتم تعيين الجهة آلياً حسب حساب المستخدم الحالي.</small>
                        </div>

                    </div>
                </div>
            </div>

            @include('planning.plans.partials._projects_table', ['hideActivities' => true])

            <!-- Actions -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body d-flex justify-content-end gap-2">
                    <a href="{{ route('plans.index') }}" class="btn btn-light border">إلغاء</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> حفظ الخطة السنوية
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('styles')
@endsection

@section('scripts')

    <script>
        $(document).ready(function () {

            $('.select2').select2({
                width: '100%',
                dropdownParent: $('body')
            });

            function toHijri(dateString) {
                if (!dateString) return '';
                try {
                    const gDate = new Date(dateString);
                    if (isNaN(gDate.getTime())) return '';
                    const formatter = new Intl.DateTimeFormat('en-u-ca-islamic-umalqura', {
                        year: 'numeric', month: '2-digit', day: '2-digit'
                    });
                    const parts = formatter.formatToParts(gDate);
                    let y, m, d;
                    for (let p of parts) {
                        if (p.type === 'year') y = p.value;
                        if (p.type === 'month') m = p.value;
                        if (p.type === 'day') d = p.value;
                    }
                    return `${y}/${m}/${d}`;
                } catch (e) {
                    console.error("Hijri conversion error:", e);
                    return '';
                }
            }

            // تحويل تاريخ البدء
            $('#start_date_g').on('change', function () {
                $('#start_date_h').val(toHijri($(this).val()));
            });

            // تحويل تاريخ الانتهاء
            $('#end_date_g').on('change', function () {
                $('#end_date_h').val(toHijri($(this).val()));
            });

            if ($('#start_date_g').val()) {
                $('#start_date_h').val(toHijri($('#start_date_g').val()));
            }
            if ($('#end_date_g').val()) {
                $('#end_date_h').val(toHijri($('#end_date_g').val()));
            }

        });
    </script>
@endsection