@extends('layouts.app')

@section('content')

    <div class="container py-4">

        <h3 class="mb-4">إضافة عضو جديد</h3>

        @can('value_chain_members.create')
        <form method="POST" action="{{ route('value-chain-members.store') }}" class="row g-4">
        @endcan
            @csrf

            {{-- الدور --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">الدور</label>
                <select name="role" class="form-select" required>
                    <option value="">-- اختر --</option>
                    <option value="program_manager">مدير البرنامج</option>
                    <option value="chain_officer">ضابط سلسلة</option>
                    <option value="coordinator">منسق</option>
                </select>
            </div>

            {{-- الاسم --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">اسم الموظف</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            {{-- سلسلة القيمة --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">سلسلة القيمة</label>
                <select name="value_chain_id" class="form-select">
                    <option value="">-- بدون --</option>
                    @foreach($valueChains as $chain)
                        <option value="{{ $chain->id }}">
                            {{ $chain->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- الهاتف --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">رقم الهاتف</label>
                <input type="text" name="phone" class="form-control">
            </div>

            {{-- المحافظة --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">المحافظة</label>
                <select name="governorate_id" id="governorate_id" class="form-select">

                    <option value="">-- اختر المحافظة --</option>

                    @foreach($governorates as $gov)
                        <option value="{{ $gov->id }}">
                            {{ $gov->name }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- المديرية --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">المديرية</label>

                <select name="directorate_id" id="directorate_id" class="form-select">

                    <option value="">-- اختر المديرية --</option>

                </select>
            </div>

            {{-- الأزرار --}}
            <div class="col-12 d-flex gap-3 mt-3">
                <button class="btn btn-success px-4">
                    حفظ
                </button>

                <a href="{{ route('value-chain-members.index') }}" class="btn btn-secondary px-4">
                    رجوع
                </a>
            </div>

        </form>

    </div>

@endsection


{{-- AJAX --}}
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function () {

            $('#governorate_id').on('change', function () {

                let govId = $(this).val();

                $('#directorate_id').html('<option>جاري التحميل...</option>');

                if (govId) {
                    $.ajax({
                        url: '/directorates/by-governorate/' + govId,
                        type: 'GET',
                        success: function (data) {

                            $('#directorate_id').html('<option value="">-- اختر المديرية --</option>');

                            $.each(data, function (key, value) {
                                $('#directorate_id').append(
                                    `<option value="${value.id}">${value.name}</option>`
                                );
                            });

                        }
                    });
                } else {
                    $('#directorate_id').html('<option value="">-- اختر المديرية --</option>');
                }

            });

        });
    </script>
@endpush