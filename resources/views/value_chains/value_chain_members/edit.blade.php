@extends('layouts.app')

@section('content')

    <div class="container py-4">

        <h3 class="mb-4">تعديل عضو</h3>

        @can('value_chain_members.edit')
        <form method="POST" action="{{ route('value-chain-members.update', $member->id) }}" class="row g-4">
        @endcan

            @csrf
            @method('PUT')

            {{-- الدور --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">الدور</label>
                <select name="role" class="form-select">
                    <option value="program_manager" {{ $member->role == 'program_manager' ? 'selected' : '' }}>
                        مدير البرنامج
                    </option>
                    <option value="chain_officer" {{ $member->role == 'chain_officer' ? 'selected' : '' }}>
                        ضابط سلسلة
                    </option>
                    <option value="coordinator" {{ $member->role == 'coordinator' ? 'selected' : '' }}>
                        منسق
                    </option>
                </select>
            </div>

            {{-- الاسم --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">اسم الموظف</label>
                <input type="text" name="name" class="form-control" value="{{ $member->name }}" required>
            </div>

            {{-- سلسلة القيمة --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">سلسلة القيمة</label>
                <select name="value_chain_id" class="form-select">
                    @foreach($valueChains as $chain)
                        <option value="{{ $chain->id }}" {{ $member->value_chain_id == $chain->id ? 'selected' : '' }}>
                            {{ $chain->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- الهاتف --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">الهاتف</label>
                <input type="text" name="phone" class="form-control" value="{{ $member->phone }}">
            </div>

            {{-- المحافظة --}}
            <div class="col-md-4">
                <label class="form-label fw-bold">المحافظة</label>
                <select name="governorate_id" id="governorate_id" class="form-select">

                    <option value="">-- اختر المحافظة --</option>

                    @foreach($governorates as $gov)
                        <option value="{{ $gov->id }}" {{ $member->governorate_id == $gov->id ? 'selected' : '' }}>
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
                <button class="btn btn-primary px-4">
                    تحديث
                </button>

                <a href="{{ route('value-chain-members.index') }}" class="btn btn-secondary px-4">
                    رجوع
                </a>
            </div>

        </form>

    </div>

@endsection


{{-- AJAX Script --}}
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function () {

            let selectedGov = "{{ $member->governorate_id }}";
            let selectedDir = "{{ $member->directorate_id }}";

            function loadDirectorates(governorateId, selected = null) {

                if (!governorateId) {
                    $('#directorate_id').html('<option value="">-- اختر المديرية --</option>');
                    return;
                }

                $('#directorate_id').html('<option>جاري التحميل...</option>');

                $.ajax({
                    url: '/directorates/by-governorate/' + governorateId,
                    type: 'GET',
                    success: function (data) {

                        $('#directorate_id').html('<option value="">-- اختر المديرية --</option>');

                        $.each(data, function (key, value) {

                            let isSelected = (value.id == selected) ? 'selected' : '';

                            $('#directorate_id').append(
                                `<option value="${value.id}" ${isSelected}>
                                ${value.name}
                            </option>`
                            );

                        });

                    }
                });
            }

            // عند تغيير المحافظة
            $('#governorate_id').on('change', function () {
                loadDirectorates($(this).val(), null);
            });

            // تحميل تلقائي عند فتح الصفحة (للتعديل)
            if (selectedGov) {
                loadDirectorates(selectedGov, selectedDir);
            }

        });
    </script>
@endpush