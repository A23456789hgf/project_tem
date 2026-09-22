@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">

    <div class="page-header-box">
        <h2 class="page-main-title">
            تعديل الجهة الداخلية:
            <span style="color:#D4AF37">{{ $internalEntity->name }}</span>
        </h2>
        <p class="text-muted small">قم بتحديث البيانات ثم احفظ التغييرات</p>
        <div class="section-divider"></div>
    </div>

    @can('internal-entities.edit')
    <form action="{{ route('internal-entities.update', $internalEntity) }}" method="POST">
    @endcan
        @csrf
        @method('PUT')

        <div class="row g-4 align-items-end">

            {{-- ================= السطر الأول ================= --}}
            {{-- الاسم --}}
            <div class="col-md-4">
                <label class="field-label"><span class="text-danger">*</span> اسم الجهة</label>
                <input type="text" name="name"
                       class="form-control custom-field @error('name') is-invalid @enderror"
                       value="{{ old('name', $internalEntity->name) }}" required>
                @error('name')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- الرمز --}}
            <div class="col-md-2">
                <label class="field-label">رمز الجهة</label>
                <input type="text" name="entity_code"
                       class="form-control custom-field @error('entity_code') is-invalid @enderror"
                       value="{{ old('entity_code', $internalEntity->entity_code) }}"
                       maxlength="2">
                @error('entity_code')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- نوع الجهة --}}
            <div class="col-md-3">
                <div class="form-check form-switch d-flex align-items-center mb-2">
                    <input type="hidden" name="entity_type" value="Department">
                    <input type="checkbox" class="form-check-input ms-3" style="width: 2.3em; height: 1.1em;"
                        name="entity_type" id="entity_type" value="Company" {{ old('entity_type', $internalEntity->entity_type) == 'Company' ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-dark mb-0" for="entity_type">
                        كيان رئيسي (Company)
                    </label>
                </div>
                <small class="text-muted d-block mt-1">قم بالتفعيل إذا كانت الجهة كياناً رئيسياً مستقلاً.</small>
                @error('entity_type')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- الجهة الأم --}}
            <div class="col-md-3">
                <label class="field-label">الجهة الأم</label>
                <select id="parent_id" name="parent_id"
                        class="form-select select2">
                    <option value="">-- لا توجد جهة أم --</option>
                    @forelse($parentEntities as $entity)
                        @php
                            // تنظيف الاسم إذا بدأ بـ '=' (مرجع إكسل) - اختياري
                            $displayName = $entity->name;
                            if (strpos($displayName, '=') === 0) {
                                // يمكنك تعيين اسم افتراضي أو تسجيل خطأ
                                $displayName = '⚠️ ' . $displayName; // تنبيه
                            }
                        @endphp
                        <option value="{{ $entity->id }}"
                            {{ old('parent_id', $internalEntity->parent_id) == $entity->id ? 'selected' : '' }}
                            data-authority-id="{{ $entity->authority_id }}">
                            {{ $displayName }}
                            @if($entity->authority)
                                ({{ $entity->authority->agency_name }})
                            @endif
                        </option>
                    @empty
                        <option value="" disabled>لا توجد جهات أم متاحة</option>
                    @endforelse
                </select>
                <small class="text-muted d-block mt-1">اختر جهة أم لتحديد السلطة تلقائيًا.</small>
                @error('parent_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- ================= السطر الثاني ================= --}}
            {{-- الجهة الإشرافية (السلطة) --}}
            <div class="col-md-4">
                <label class="field-label">الجهة الإشرافية</label>
                <select id="authority_id" name="authority_id"
                        class="form-select select2">
                    <option value="">-- اختر السلطة --</option>
                    @forelse($authorities as $authority)
                        @php
                            $gov = $authority->governorate->name ?? '';
                            $dir = $authority->directorate->name ?? '';
                            $authName = $authority->agency_name;
                            if (strpos($authName, '=') === 0) {
                                $authName = '⚠️ ' . $authName; // تنبيه
                            }
                        @endphp
                        <option value="{{ $authority->id }}"
                            {{ old('authority_id', $internalEntity->authority_id) == $authority->id ? 'selected' : '' }}
                            data-governorate="{{ $gov }}"
                            data-directorate="{{ $dir }}">
                            {{ $authName }}
                            @if($gov || $dir)
                                ({{ $gov }} - {{ $dir }})
                            @endif
                        </option>
                    @empty
                        <option value="" disabled>لا توجد سلطات متاحة</option>
                    @endforelse
                </select>
                <small class="text-muted d-block mt-1">تحدد السلطة المحافظة والمديرية تلقائيًا.</small>
                @error('authority_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- المحافظة --}}
            <div class="col-md-4">
                <label class="field-label">المحافظة</label>
                <input type="text" id="governorate"
                       class="form-control bg-light"
                       readonly>
            </div>

            {{-- المديرية --}}
            <div class="col-md-4">
                <label class="field-label">المديرية</label>
                <input type="text" id="directorate"
                       class="form-control bg-light"
                       readonly>
            </div>

            {{-- ================= السطر الثالث ================= --}}
            {{-- الحالة --}}
            <div class="col-md-4 mt-3 d-flex align-items-center">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1"
                           class="form-check-input" id="isActiveCheckbox"
                           {{ old('is_active', $internalEntity->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="isActiveCheckbox">
                        نشط
                    </label>
                </div>
            </div>

            {{-- ================= أزرار التحكم ================= --}}
            <div class="col-12 mt-4 border-top pt-4 d-flex justify-content-end gap-3">
                <a href="{{ route('internal-entities.index') }}" class="btn btn-secondary">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    تحديث
                </button>
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
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        height: 48px;
        width: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        line-height: 30px;
        padding-right: 0;
        padding-left: 0;
    }
    .select2-container--bootstrap-5 .select2-dropdown {
        border-radius: 12px;
    }
    .select2-container--bootstrap-5 .select2-results__option {
        padding: 10px;
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
        allowClear: true,
        dropdownParent: $('body')
    });

    /**
     * تحديث المحافظة والمديرية بناءً على السلطة المختارة حالياً
     */
    function updateGeoFromAuthority() {
        let selected = $('#authority_id option:selected');
        let gov = selected.data('governorate') || '';
        let dir = selected.data('directorate') || '';
        $('#governorate').val(gov);
        $('#directorate').val(dir);
    }

    /**
     * معالجة تغيير الجهة الأم:
     * - إذا تم اختيار جهة أم، نأخذ authority_id منها (إن وجد) ونضعه في حقل السلطة.
     * - إذا لم تكن للجهة الأم سلطة، نمسح حقل السلطة.
     * - إذا تم إلغاء اختيار الجهة الأم، نترك السلطة كما هي.
     * - بعد تغيير السلطة، نحدّث المحافظة والمديرية.
     */
    function handleParentChange() {
        let selectedParent = $('#parent_id option:selected');
        let authorityId = selectedParent.data('authority-id');

        if ($('#parent_id').val()) {
            // تم اختيار جهة أم
            if (authorityId) {
                $('#authority_id').val(authorityId).trigger('change.select2');
            } else {
                // الجهة الأم ليس لها سلطة -> نمسح السلطة
                $('#authority_id').val('').trigger('change.select2');
            }
        } else {
            // لا توجد جهة أم -> لا نغير السلطة (تبقى كما هي)
            // فقط نقوم بتحديث المحافظة والمديرية بناءً على السلطة الحالية
            // (سيتم استدعاء updateGeoFromAuthority بعد ذلك)
        }

        // تحديث الحقول الجغرافية بناءً على السلطة المحددة حالياً
        updateGeoFromAuthority();
    }

    // ربط الأحداث
    $('#parent_id').on('change', function () {
        handleParentChange();
    });

    $('#authority_id').on('change', function () {
        updateGeoFromAuthority();
    });

    // تشغيل أولي عند تحميل الصفحة:
    // - نضبط المحافظة والمديرية بناءً على السلطة المخزنة (old أو قيمة النموذج)
    // - يتم تنفيذ ذلك بعد تهيئة Select2
    setTimeout(function () {
        updateGeoFromAuthority();
    }, 300);
});
</script>
@endsection