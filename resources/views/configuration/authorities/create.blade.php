@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box text-center">
        <h2 class="page-main-title">إضافة جهة جديدة</h2>
        <p class="text-muted small">قم بتعريف جهة جديدة في الهيكل التنظيمي</p>
        <div class="section-divider mx-auto"></div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="main-card p-5 shadow-lg border-0 rounded-4">
                @can('authorities.create')
                <form action="{{ route('authorities.store') }}" method="POST">
                @endcan
                    @csrf
                    <input type="hidden" name="view" value="{{ request('view', 'tree') }}">
                    
                    <div class="row g-4">
                        <!-- نوع الجهة -->
                        <div class="col-md-6">
                            <label class="field-label">نوع الجهة <span class="text-danger">*</span></label>
                            <select name="type_entity_id" id="type_entity_id" class="form-select select2">
                                <option value="">-- اختر نوع الجهة --</option>
                                @if(isset($typeEntities) && $typeEntities->isNotEmpty())
                                    @foreach($typeEntities as $type)
                                        <option value="{{ $type->id }}" {{ old('type_entity_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="" disabled>لا توجد أنواع جهات متاحة</option>
                                @endif
                            </select>
                            @error('type_entity_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- حالة التفعيل -->
                        <div class="col-md-3">
                            <label class="field-label">الحالة</label>
                            <div class="form-check form-switch custom-switch p-3 bg-light rounded-3 border mt-1 d-flex align-items-center justify-content-between">
                                <label class="form-check-label fw-bold mb-0" for="is_active" style="color: #001f3f;" id="is_active_label">
                                    {{ old('is_active', 1) ? 'مفعل' : 'غير مفعل' }}
                                </label>
                                <input class="form-check-input ms-0" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} onchange="document.getElementById('is_active_label').textContent = this.checked ? 'مفعل' : 'غير مفعل'">
                            </div>
                            @error('is_active')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- جهة ممولة -->
                        <div class="col-md-3">
                            <label class="field-label">جهة ممولة</label>
                            <div class="form-check form-switch custom-switch p-3 bg-light rounded-3 border mt-1 d-flex align-items-center justify-content-between">
                                <label class="form-check-label fw-bold mb-0" for="is_funded" style="color: #001f3f;" id="is_funded_label">
                                    {{ old('is_funded', 0) ? 'ممولة' : 'غير ممولة' }}
                                </label>
                                <input class="form-check-input ms-0" type="checkbox" id="is_funded" name="is_funded" value="1" {{ old('is_funded', false) ? 'checked' : '' }} onchange="document.getElementById('is_funded_label').textContent = this.checked ? 'ممولة' : 'غير ممولة'">
                            </div>
                            @error('is_funded')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- اسم الجهة -->
                        <div class="col-md-12">
                            <label class="field-label">اسم الجهة <span class="text-danger">*</span></label>
                            <input type="text" name="agency_name" class="form-control custom-field" 
                                   placeholder="أدخل اسم الجهة..." value="{{ old('agency_name') }}" required autofocus>
                            @error('agency_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- الجهة الأم -->
                        <div class="col-md-12">
                            <label class="field-label">الجهة الأم (اختياري)</label>
                            <select name="parent_id" id="parent_id" class="form-select select2" onchange="handleParentChange()">
                                <option value="">بدون جهة رئيسية (جهة أم)</option>
                                @if(isset($authorities) && $authorities->isNotEmpty())
                                    @foreach($authorities as $auth)
                                        @if(!str_starts_with($auth->agency_name, '='))
                                            <option value="{{ $auth->id }}" 
                                                    {{ old('parent_id', $parentId ?? '') == $auth->id ? 'selected' : '' }}
                                                    data-governorate-id="{{ $auth->governorate_id }}"
                                                    data-directorate-id="{{ $auth->directorate_id }}">
                                                {{ $auth->agency_name }}
                                                <!-- @if($auth->parent)
                                                    (تابع لـ: {{ $auth->parent->agency_name }})
                                                @endif -->
                                            </option>
                                        @endif
                                    @endforeach
                                @else
                                    <option value="" disabled>لا توجد جهات متاحة للاختيار</option>
                                @endif
                            </select>
                            @error('parent_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- المحافظة -->
                        <div class="col-md-6">
                            <label class="field-label">المحافظة (اختياري)</label>
                            <select name="governorate_id" id="governorate_id" class="form-select select2">
                                <option value="">-- اختر المحافظة --</option>
                                @if(isset($governorates) && $governorates->isNotEmpty())
                                    @foreach($governorates as $gov)
                                        <option value="{{ $gov->id }}" {{ old('governorate_id') == $gov->id ? 'selected' : '' }}>
                                            {{ $gov->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="small text-muted mt-1">اتركه فارغاً إذا كانت الجهة مركزية</div>
                            @error('governorate_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- المديرية -->
                        <div class="col-md-6">
                            <label class="field-label">المديرية (اختياري)</label>
                            <select name="directorate_id" id="directorate_id" class="form-select select2">
                                <option value="">-- اختر المديرية --</option>
                            </select>
                            @error('directorate_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- أزرار التحكم -->
                        <div class="col-12 mt-5 pt-4 border-top">
                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ session('authorities_index_url', route('authorities.index', ['view' => request('view', 'tree')])) }}" class="btn btn-cancel-custom rounded-pill">
                                    <i class="fas fa-times me-2"></i> إلغاء
                                </a>
                                <button type="submit" class="btn btn-navy-gold shadow-sm px-5 py-2 rounded-pill fw-bold">
                                    <i class="fas fa-save ms-2"></i> حفظ البيانات
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .select2-container--default .select2-selection--single {
        height: 50px;
        border: 1px solid #ced4da;
        border-radius: 12px;
        padding: 10px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { 
        height: 48px; 
    }
    .custom-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }
    .custom-switch .form-check-input:checked {
        background-color: #001f3f;
        border-color: #001f3f;
    }
</style>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // تهيئة Select2
        $('.select2').select2({ 
            width: '100%', 
            dir: 'rtl', 
            dropdownParent: $('body') 
        });

        // جلب المديريات عند تغيير المحافظة
        $('#governorate_id').on('change', function(e, directorateId = null) {
            var govId = $(this).val();
            var $directorateSelect = $('#directorate_id');
            
            $directorateSelect.empty().append('<option value="">-- اختر المديرية --</option>');
            
            if (govId) {
                $.ajax({
                    url: '/authorities/get-directorates/' + govId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            $.each(response.data, function(index, item) {
                                var selected = (directorateId == item.id) ? 'selected' : '';
                                $directorateSelect.append('<option value="' + item.id + '" ' + selected + '>' + item.name + '</option>');
                            });
                            $directorateSelect.trigger('change');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching directorates:', xhr);
                    }
                });
            }
        });

        // دالة معالجة تغيير الجهة الأم
        window.handleParentChange = function() {
            var selected = $('#parent_id').find(':selected');
            var govId = selected.data('governorate-id');
            var dirId = selected.data('directorate-id');

            if (selected.val() && govId) {
                // إذا تم اختيار جهة أم ولديها محافظة، نملأ الحقول
                $('#governorate_id').val(govId).trigger('change', [dirId]);
            } else if (!selected.val()) {
                // إذا تم اختيار "بدون جهة رئيسية"، نترك الحقول فارغة
                $('#governorate_id').val('').trigger('change');
            }
            
            // التأكد من أن الحقول غير مجمدة
            $('#governorate_id').prop('disabled', false);
            $('#directorate_id').prop('disabled', false);
        };

        // تشغيل المنطق عند التحميل
        setTimeout(function() {
            handleParentChange();
        }, 100);

        // في حالة وجود خطأ في old()، نعيد تعيين القيم
        var oldGovernorate = "{{ old('governorate_id') }}";
        if (oldGovernorate) {
            $('#governorate_id').val(oldGovernorate).trigger('change');
        }
    });
</script>
@endsection