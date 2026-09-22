@extends('layouts.app')

@section('content')
    @include('configuration.shared_styles')

    <div class="container-fluid px-5 py-4">

        <div class="page-header-box">
            <h2 class="page-main-title">إضافة جهة داخلية جديدة</h2>
            <p class="text-muted small">يرجى تعبئة البيانات المطلوبة</p>
            <div class="section-divider"></div>
        </div>

        @can('internal-entities.create')
        <form action="{{ route('internal-entities.store') }}" method="POST">
        @endcan
            @csrf

            <div class="row g-4 align-items-end">

                {{-- اسم الجهة --}}
                <div class="col-md-5">
                    <label for="name" class="field-label"><span class="text-danger">*</span> اسم الجهة</label>
                    <input type="text" id="name" name="name"
                        class="form-control custom-field @error('name') is-invalid @enderror" placeholder="أدخل اسم الجهة"
                        value="{{ old('name') }}" required>
                </div>

                {{-- رمز الجهة --}}
                <div class="col-md-3">
                    <label for="entity_code" class="field-label">رمز الجهة</label>
                    <input type="text" id="entity_code" name="entity_code"
                        class="form-control custom-field @error('entity_code') is-invalid @enderror" placeholder="مثال: CT"
                        value="{{ old('entity_code') }}" maxlength="2">
                </div>

                {{-- نوع الجهة --}}
                <div class="col-md-4">
                    <div class="form-check form-switch d-flex align-items-center mb-2">
                        <input type="hidden" name="entity_type" value="Department">
                        <input type="checkbox" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;"
                            name="entity_type" id="entity_type" value="Company" {{ old('entity_type') == 'Company' ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark mb-0" for="entity_type">
                            كيان رئيسي (Company)
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">قم بالتفعيل إذا كانت الجهة كياناً رئيسياً مستقلاً، أو عطله إذا كانت قسماً تابعاً.</small>
                </div>

                {{-- الجهة الأم --}}
                <div class="col-md-6">
                    <label for="parent_id" class="field-label">الجهة الأم</label>
                    <select id="parent_id" name="parent_id" class="form-select select2">
                        <option value="">-- لا توجد جهة أم (جهة رئيسية) --</option>
                        @foreach($parentEntities as $entity)
                            <option value="{{ $entity->id }}"
                                {{ old('parent_id') == $entity->id ? 'selected' : '' }}
                                data-authority-id="{{ $entity->authority_id }}"
                                data-governorate="{{ $entity->governorate->name ?? '' }}"
                                data-directorate="{{ $entity->directorate->name ?? '' }}">
                                {{ $entity->name }}
                                @if($entity->authority)
                                    ({{ $entity->authority->agency_name }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">اختر جهة أم لربط هذه الجهة بها في الهيكل التنظيمي.</small>
                </div>

                {{-- الجهة الإشرافية --}}
                <div class="col-md-6">
                    <label for="authority_id" class="field-label">الجهة الإشرافية</label>
                    <select id="authority_id" name="authority_id" class="form-select select2">
                        <option value="">-- اختر الجهة الإشرافية --</option>
                        @foreach($authorities as $authority)
                            <option value="{{ $authority->id }}"
                                {{ old('authority_id') == $authority->id ? 'selected' : '' }}
                                data-governorate="{{ $authority->governorate->name ?? '' }}"
                                data-directorate="{{ $authority->directorate->name ?? '' }}">
                                {{ $authority->agency_name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">تحدد هذه الجهة المحافظة والمديرية تلقائيًا.</small>
                </div>

                {{-- المحافظة --}}
                <div class="col-md-3">
                    <label class="form-label fw-bold">المحافظة</label>
                    <input type="text" id="governorate" class="form-control bg-light" readonly>
                </div>

                {{-- المديرية --}}
                <div class="col-md-3">
                    <label class="field-label fw-bold">المديرية</label>
                    <input type="text" id="directorate" class="form-control bg-light" readonly>
                </div>

                {{-- الحالة --}}
                <div class="col-12 mt-4">
                    <div class="switch-box">
                        <div class="form-check form-switch d-flex align-items-center m-0">
                            <input type="checkbox" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;"
                                name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold text-dark mb-0" for="is_active">
                                الحالة (نشط)
                            </label>
                        </div>
                    </div>
                </div>

                {{-- الأزرار --}}
                <div class="col-12 mt-5 pt-4 border-top">
                    <div class="d-flex justify-content-end gap-3">
                        <a href="{{ route('internal-entities.index') }}" class="btn btn-cancel-custom">إلغاء</a>
                        <button type="submit" class="btn btn-navy-gold shadow-sm">حفظ</button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <style>
        .select2-container--bootstrap-5 .select2-selection--single {
            height: 50px;
            border-radius: 12px;
            padding: 10px;
            display: flex;
            align-items: center;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            // تهيئة Select2
            $('.select2').select2({
                width: '100%',
                dir: 'rtl',
                theme: 'bootstrap-5',
                placeholder: '-- اختر --',
                allowClear: true,
                dropdownParent: $('body')
            });

            // دالة لتحديث المحافظة والمديرية من الجهة الإشرافية المختارة
            function updateGeoFromAuthority() {
                let selected = $('#authority_id option:selected');
                let gov = selected.data('governorate') || '';
                let dir = selected.data('directorate') || '';
                $('#governorate').val(gov);
                $('#directorate').val(dir);
            }

            // دالة لتحديث الجهة الإشرافية والمحافظة/المديرية عند اختيار جهة أم
            function handleParentChange() {
                let selectedParent = $('#parent_id option:selected');
                let authorityId = selectedParent.data('authority-id');

                if ($('#parent_id').val()) {
                    if (authorityId) {
                        // تعيين الجهة الإشرافية من الجهة الأم (إن وُجدت)
                        $('#authority_id').val(authorityId).trigger('change.select2');
                    } else {
                        // إذا لم يكن للجهة الأم سلطة، نمسح اختيار السلطة
                        $('#authority_id').val('').trigger('change.select2');
                    }
                    // تعيين المحافظة والمديرية من بيانات الجهة الأم
                    $('#governorate').val(selectedParent.data('governorate') || '');
                    $('#directorate').val(selectedParent.data('directorate') || '');
                } else {
                    // إذا لم يتم اختيار جهة أم، نمسح السلطة والحقول الجغرافية
                    $('#authority_id').val('').trigger('change.select2');
                    $('#governorate').val('');
                    $('#directorate').val('');
                }
            }

            // عند تغيير الجهة الأم
            $('#parent_id').on('change', function () {
                handleParentChange();
            });

            // عند تغيير الجهة الإشرافية (يدويًا)
            $('#authority_id').on('change', function () {
                updateGeoFromAuthority();
            });

            // تشغيل أولي عند تحميل الصفحة (لتحديث الحقول بناءً على القيم القديمة إن وجدت)
            handleParentChange();
        });
    </script>
@endsection