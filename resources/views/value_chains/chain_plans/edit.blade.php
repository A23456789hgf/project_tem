@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="main-card">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0 text-warning d-flex align-items-center gap-2">
                        <x-icon name="edit" /> تعديل خطة سلسلة
                    </h2>
                    <div class="title-line"></div>
                </div>
                <a href="{{ route('chain_plans.index') }}"
                    class="btn btn-secondary px-4 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                    <x-icon name="arrow-right" /> رجوع
                </a>
            </div>

            @can('value_chains.edit')
            <form action="{{ route('chain_plans.update', $chainPlan->id) }}" method="POST">
            @endcan
                @csrf
                @method('PUT')
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">المحافظة</label>
                        <select name="governorate_id" id="governorate_id" class="form-select select2">
                            <option value="">اختر المحافظة</option>
                            @foreach($governorates as $gov)
                                <option value="{{ $gov->id }}" {{ (old('governorate_id', $chainPlan->governorate_id) == $gov->id) ? 'selected' : '' }}>{{ $gov->name }}</option>
                            @endforeach
                        </select>
                        @error('governorate_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">المديرية</label>
                        <select name="directorate_id" id="directorate_id" class="form-select select2">
                            <option value="">اختر المديرية</option>
                            @foreach($directorates as $dir)
                                <option value="{{ $dir->id }}" {{ (old('directorate_id', $chainPlan->directorate_id) == $dir->id) ? 'selected' : '' }}>{{ $dir->name }}</option>
                            @endforeach
                        </select>
                        @error('directorate_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">السلسلة <span class="text-danger">*</span></label>
                        <input type="text" name="value_chain_id" class="form-control" value="{{ old('value_chain_id', $chainPlan->value_chain_id) }}">
                        @error('value_chain_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">المجال <span class="text-danger">*</span></label>
                        <input type="text" name="domain_id" class="form-control" value="{{ old('domain_id', $chainPlan->domain_id) }}">
                        @error('domain_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">المؤشر <span class="text-danger">*</span></label>
                        <input type="text" name="indicator" class="form-control"
                            value="{{ old('indicator', $chainPlan->indicator) }}" required>
                        @error('indicator')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">العدد <span class="text-danger">*</span></label>
                        <input type="text" name="number" class="form-control" value="{{ old('number', $chainPlan->number) }}">
                        @error('number')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">اسم المشروع</label>
                        <input type="text" name="project_name" class="form-control"
                            value="{{ old('project_name', $chainPlan->project_name) }}" placeholder="أدخل اسم المشروع" list="projects_list">
                        <datalist id="projects_list"></datalist>
                        @error('project_name')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">اسم النشاط</label>
                        <input type="text" name="activity_name" class="form-control"
                            value="{{ old('activity_name', $chainPlan->activity_name) }}" placeholder="أدخل اسم النشاط" list="activities_list">
                        <datalist id="activities_list"></datalist>
                        @error('activity_name')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- نوع التمويل (يبقى كما هو) --}}
                    <div class="col-md-12">
                        <label class="form-label fw-bold">نوع التمويل</label>
                        <input type="text" name="value_chain_financing_type_id" class="form-control" value="{{ old('value_chain_financing_type_id', $chainPlan->value_chain_financing_type_id) }}">
                        @error('value_chain_financing_type_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    @php
                        $authoritiesList = isset($authorities) ? $authorities : \App\Models\Authority::where('is_active', true)->orderBy('agency_name')->get();
                        $parseAuthValue = function($val) {
                            if (empty($val) || $val === '-') return [];
                            if (is_array($val)) return $val;
                            $decoded = json_decode($val, true);
                            return is_array($decoded) ? $decoded : [$val];
                        };
                        $selFunding = $parseAuthValue(old('funding_source_id', $chainPlan->funding_source_id));
                        $selAuthority = $parseAuthValue(old('authority_id', $chainPlan->authority_id));
                        $selImplementing = $parseAuthValue(old('implementing_entity_id', $chainPlan->implementing_entity_id));
                    @endphp

                    {{-- مصدر التمويل --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">مصدر التمويل</label>
                        <select id="funding_source_id_select" class="form-select select2-auth-multi" multiple data-placeholder="اختر مصدر التمويل...">
                            @foreach($authoritiesList as $authority)
                                <option value="{{ $authority->id }}" {{ in_array((string)$authority->id, array_map('strval', $selFunding)) ? 'selected' : '' }}>
                                    {{ $authority->agency_name ?? $authority->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="funding_source_id" id="funding_source_id_hidden" value="{{ old('funding_source_id', $chainPlan->funding_source_id) }}">
                        @error('funding_source_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- الجهة المنفذة --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">الجهة المنفذة</label>
                        <select id="authority_id_select" class="form-select select2-auth-multi" multiple data-placeholder="اختر الجهة المنفذة...">
                            @foreach($authoritiesList as $authority)
                                <option value="{{ $authority->id }}" {{ in_array((string)$authority->id, array_map('strval', $selAuthority)) ? 'selected' : '' }}>
                                    {{ $authority->agency_name ?? $authority->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="authority_id" id="authority_id_hidden" value="{{ old('authority_id', $chainPlan->authority_id) }}">
                        @error('authority_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- الجهة المشرفة --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">الجهة المشرفة</label>
                        <select id="implementing_entity_id_select" class="form-select select2-auth-multi" multiple data-placeholder="اختر الجهة المشرفة...">
                            @foreach($authoritiesList as $authority)
                                <option value="{{ $authority->id }}" {{ in_array((string)$authority->id, array_map('strval', $selImplementing)) ? 'selected' : '' }}>
                                    {{ $authority->agency_name ?? $authority->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="implementing_entity_id" id="implementing_entity_id_hidden" value="{{ old('implementing_entity_id', $chainPlan->implementing_entity_id) }}">
                        @error('implementing_entity_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit"
                        class="btn btn-primary px-5 rounded-3 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                        <x-icon name="save" /> حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

            $('#governorate_id').on('change', function () {
                var govId = $(this).val();
                var dirSelect = $('#directorate_id');
                dirSelect.empty().append('<option value="">اختر المديرية</option>');

                if (govId) {
                    $.ajax({
                        url: '{{ route("lookup.search") }}',
                        data: { type: 'directorate', governorate_id: govId, limit: 100 },
                        dataType: 'json',
                        success: function (response) {
                            if (response.results) {
                                $.each(response.results, function (i, item) {
                                    if (item.id !== '0') {
                                        dirSelect.append('<option value="' + item.id + '">' + item.text + '</option>');
                                    }
                                });
                            }
                        }
                    });
                }
            });

            // جلب المشاريع بناءً على السلسلة المختارة
            $('[name="value_chain_id"]').on('change input', function (e) {
                var chainId = $(this).val();
                
                // تفريغ الحقول التابعة فقط إذا كان التغيير يدوي وليس عند التحميل
                if (e.originalEvent) {
                    $('#projects_list').empty();
                    $('#activities_list').empty();
                    $('[name="project_name"]').val('');
                    $('[name="activity_name"]').val('');
                }
                
                if (chainId && chainId.length > 0) {
                    $.ajax({
                        url: '{{ route("chain_plans.projects_by_chain") }}',
                        data: { value_chain_id: chainId },
                        dataType: 'json',
                        success: function (response) {
                            if (!e.originalEvent) {
                                $('#projects_list').empty();
                            }
                            if (response.projects) {
                                $.each(response.projects, function (i, item) {
                                    $('#projects_list').append('<option value="' + item + '">');
                                });
                            }
                        }
                    });
                }
            });

            // جلب الأنشطة بناءً على المشروع المختار والسلسلة
            $('[name="project_name"]').on('change input', function (e) {
                var chainId = $('[name="value_chain_id"]').val();
                var projectName = $(this).val();
                
                // تفريغ الأنشطة التابعة فقط إذا كان التغيير يدوي
                if (e.originalEvent) {
                    $('#activities_list').empty();
                    $('[name="activity_name"]').val('');
                }
                
                if (chainId && projectName && projectName.length > 0) {
                    $.ajax({
                        url: '{{ route("chain_plans.activities_by_project") }}',
                        data: { 
                            value_chain_id: chainId,
                            project_name: projectName
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (!e.originalEvent) {
                                $('#activities_list').empty();
                            }
                            if (response.activities) {
                                $.each(response.activities, function (i, item) {
                                    $('#activities_list').append('<option value="' + item + '">');
                                });
                            }
                        }
                    });
                }
            });
            
            // جلب عند التحميل إذا كان هناك قيمة
            if ($('[name="value_chain_id"]').val()) {
                $('[name="value_chain_id"]').trigger('change');
            }
            if ($('[name="project_name"]').val()) {
                $('[name="project_name"]').trigger('change');
            }

            $('.select2-auth-multi').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true });

            function syncMultiSelectAuth(selectId, hiddenId) {
                var $sel = $('#' + selectId);
                var $hid = $('#' + hiddenId);
                
                function updateHidden() {
                    var vals = $sel.val();
                    $hid.val(vals && vals.length > 0 ? JSON.stringify(vals) : '');
                }
                
                $sel.on('change', updateHidden);
                if (!$hid.val()) {
                    updateHidden();
                }
            }
            syncMultiSelectAuth('funding_source_id_select', 'funding_source_id_hidden');
            syncMultiSelectAuth('authority_id_select', 'authority_id_hidden');
            syncMultiSelectAuth('implementing_entity_id_select', 'implementing_entity_id_hidden');
        });
    </script>
@endpush