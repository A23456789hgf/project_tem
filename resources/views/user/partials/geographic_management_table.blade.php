

@php
    $entities = $entities ?? collect();
    $authorities = $authorities ?? collect();
    $governorates = $governorates ?? collect();
    $directorates = $directorates ?? collect();
    $geographicScopes = $geographicScopes ?? [];
    
    $selectedAdminScope = old('administrative_scope_id', $user->administrative_scope_id ?? '');
    $currentOrgType = old('organization_type', isset($user) ? ($user->organization_type ?? ($user->authority_id ? 'external' : 'internal')) : 'internal');

    // استخدام القيم القديمة من session أو القيم الحالية
    $oldScopes = old('geographic_scopes', $geographicScopes);
    
    // إذا كانت oldScopes فارغة أو ليست مصفوفة، نضيف صفاً فارغاً واحداً
    if (empty($oldScopes) || !is_array($oldScopes)) {
        $oldScopes = [
            [
                'governorate_id' => '',
                'directorate_id' => ''
            ]
        ];
    }
    
    // تحديد عدد الصفوف للمؤشر
    $rowCount = count($oldScopes);
@endphp

<div class="project-table-container mt-4">
    <div class="project-table-header">
        <i class="fas fa-map-marker-alt"></i>
        إعدادات النطاقات الإدارية والجغرافية
    </div>

    <div class="project-table-wrapper">
        <div class="p-3 bg-light border-bottom">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        النطاق الإداري (الكيان التابع)
                    </label>
                    <select
                        name="administrative_scope_id"
                        id="administrative_scope_id"
                        class="form-select form-select-lg border-0 bg-light rounded-3 search-select">
                        <option value="">-- بدون جهة --</option>
                        @if($currentOrgType === 'external')
                            @foreach($authorities as $authority)
                                <option value="{{ $authority->id }}" @selected($selectedAdminScope == $authority->id)>
                                    {{ $authority->agency_name }}
                                </option>
                            @endforeach
                        @else
                            @foreach($entities as $entity)
                                <option value="{{ $entity->id }}" @selected($selectedAdminScope == $entity->id)>
                                    {{ $entity->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('administrative_scope_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <i class="fas fa-info-circle text-primary"></i>
                        ملاحظة: اختيار النطاق الجغرافي اختياري
                    </label>
                    <small class="d-block text-muted">
                        يمكن ترك النطاق الجغرافي فارغاً أو تحديد محافظات/مديريات معينة للتحكم في وصول المستخدم.
                    </small>
                </div>
            </div>
        </div>

        <table class="project-table" id="geographic-scopes-table">
            <thead>
            <tr>
                <th width="45%">المحافظة (اختياري)</th>
                <th width="45%">المديرية (اختياري)</th>
                <th width="10%">الإجراءات</th>
            </tr>
            </thead>
            <tbody id="geographic-scopes-tbody">
                @foreach($oldScopes as $index => $scope)
                    <tr class="geo-row" data-row="{{ $index }}">
                        <td>
                            <select 
                                name="geographic_scopes[{{ $index }}][governorate_id]" 
                                class="form-select governorate-select search-select"
                                data-index="{{ $index }}">
                                <option value="" {{ empty($scope['governorate_id']) ? 'selected' : '' }}>-- بدون تحديد (اختياري) --</option>
                                <option value="all" {{ (isset($scope['governorate_id']) && $scope['governorate_id'] === 'all') ? 'selected' : '' }}>جميع المحافظات</option>
                                @foreach($governorates as $governorate)
                                    <option value="{{ $governorate->id }}"
                                        {{ (isset($scope['governorate_id']) && $scope['governorate_id'] == $governorate->id) ? 'selected' : '' }}>
                                        {{ $governorate->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error("geographic_scopes.{$index}.governorate_id")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </td>
                        <td>
                            <select 
                                name="geographic_scopes[{{ $index }}][directorate_id]" 
                                class="form-select directorate-select search-select"
                                data-index="{{ $index }}"
                                data-selected="{{ $scope['directorate_id'] ?? '' }}">
                                <option value="" {{ empty($scope['directorate_id']) ? 'selected' : '' }}>-- بدون تحديد (اختياري) --</option>
                                <option value="all" {{ (isset($scope['directorate_id']) && $scope['directorate_id'] === 'all') ? 'selected' : '' }}>جميع المديريات</option>
                                @if(!empty($scope['governorate_id']) && isset($directorates))
                                    @foreach($directorates as $directorate)
                                        @if($directorate->governorate_id == $scope['governorate_id'])
                                            <option value="{{ $directorate->id }}"
                                                {{ (isset($scope['directorate_id']) && $scope['directorate_id'] == $directorate->id) ? 'selected' : '' }}>
                                                {{ $directorate->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                @endif
                            </select>
                            @error("geographic_scopes.{$index}.directorate_id")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </td>
                        <td>
                            <button 
                                type="button" 
                                class="btn btn-sm btn-danger remove-geo-row">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="p-3">
            <button
                type="button"
                class="project-btn project-btn-primary"
                id="add-geo-row">
                <i class="fa fa-plus"></i>
                إضافة نطاق جغرافي جديد
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    // مصفوفات الخيارات للجهات الداخلية والخارجية
    const internalEntitiesOptions = [
        { id: '', text: '-- بدون جهة --' },
        @foreach($entities as $entity)
        { id: '{{ $entity->id }}', text: '{{ e($entity->name) }}' },
        @endforeach
    ];

    const externalAuthoritiesOptions = [
        { id: '', text: '-- بدون جهة --' },
        @foreach($authorities as $authority)
        { id: '{{ $authority->id }}', text: '{{ e($authority->agency_name) }}' },
        @endforeach
    ];

    function updateAdministrativeScopeOptions(orgType, keepSelected = true) {
        const $adminScope = $('#administrative_scope_id');
        if ($adminScope.length === 0) return;

        const currentVal = keepSelected ? $adminScope.val() : '';
        $adminScope.empty();

        const options = (orgType === 'external') ? externalAuthoritiesOptions : internalEntitiesOptions;

        options.forEach(function(opt) {
            const isSelected = (currentVal !== '' && String(opt.id) === String(currentVal));
            $adminScope.append(new Option(opt.text, opt.id, false, isSelected));
        });

        if ($.fn.select2 && $adminScope.hasClass('select2-hidden-accessible')) {
            $adminScope.trigger('change');
        }
    }

    $(document).on('change', '#organization_type', function() {
        const orgType = $(this).val();
        updateAdministrativeScopeOptions(orgType, false);
    });

    // مؤشر للصفوف الجديدة
    let rowIndex = {{ $rowCount }};

    // دالة تحميل المديريات بناءً على المحافظة المختارة
    function loadDirectorates(governorateId, $select, selectedDirId = null) {
        // إظهار حالة التحميل
        $select.html('<option value="">جاري التحميل...</option>');
        $select.prop('disabled', true);

        if (!governorateId || governorateId === 'all') {
            let isAllSelected = (governorateId === 'all' || selectedDirId === 'all') ? 'selected' : '';
            $select.html('<option value="">-- بدون تحديد (اختياري) --</option><option value="all" ' + isAllSelected + '>جميع المديريات</option>');
            if (isAllSelected) {
                $select.val('all');
            } else {
                $select.val('');
            }
            $select.prop('disabled', governorateId === 'all');
            $select.trigger('change'); // لتحديث select2
            return;
        }

        // إضافة CSRF token
        $.ajax({
            url: "{{ route('directorates.by-governorate') }}",
            type: "GET",
            data: { 
                governorate_id: governorateId,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                let html = '<option value="">-- بدون تحديد (اختياري) --</option>';
                html += '<option value="all" ' + (selectedDirId == 'all' || selectedDirId === null ? 'selected' : '') + '>جميع المديريات</option>';
                
                // التحقق من نوع البيانات القادمة
                if (Array.isArray(response) && response.length > 0) {
                    response.forEach(function(dir) {
                        let selected = (selectedDirId == dir.id) ? 'selected' : '';
                        html += `<option value="${dir.id}" ${selected}>${dir.name}</option>`;
                    });
                } else if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                    // إذا كانت البيانات مغلّفة في مفتاح data (مثل Laravel Resource)
                    response.data.forEach(function(dir) {
                        let selected = (selectedDirId == dir.id) ? 'selected' : '';
                        html += `<option value="${dir.id}" ${selected}>${dir.name}</option>`;
                    });
                } else {
                    html += '<option value="" disabled>لا توجد مديريات</option>';
                }
                
                $select.html(html);
                $select.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                
                $select.html('<option value="">حدث خطأ في التحميل</option>');
                $select.prop('disabled', false);
                
                // عرض رسالة خطأ للمستخدم
                if (typeof toastr !== 'undefined') {
                    toastr.error('حدث خطأ في تحميل المديريات، يرجى المحاولة مرة أخرى');
                }
            }
        });
    }

    // عند تغيير المحافظة في أي صف
    $(document).on("change", ".governorate-select", function() {
        let govId = $(this).val();
        let row = $(this).closest("tr");
        let dirSelect = row.find(".directorate-select");
        let currentDirId = dirSelect.val();

        // إذا تم اختيار "بدون تحديد" (فارغ) أو "جميع المحافظات"
        if (!govId || govId === 'all') {
            let isAll = (govId === 'all') ? 'selected' : '';
            dirSelect.html('<option value="">-- بدون تحديد (اختياري) --</option><option value="all" ' + isAll + '>جميع المديريات</option>');
            if (govId === 'all') {
                dirSelect.val('all'); // تحديد جميع المديريات افتراضياً عند اختيار جميع المحافظات
            } else {
                dirSelect.val('');
            }
            dirSelect.prop('disabled', govId === 'all'); // تعطيل المديريات إذا تم اختيار جميع المحافظات
            dirSelect.trigger('change'); // لتحديث select2 إن وجد
            return;
        }

        // تحميل المديريات
        loadDirectorates(govId, dirSelect, currentDirId);
    });

    // تحميل المديريات للصفوف الموجودة عند تحميل الصفحة
    $(".geo-row").each(function() {
        let row = $(this);
        let govSelect = row.find(".governorate-select");
        let dirSelect = row.find(".directorate-select");
        let govId = govSelect.val();
        let selectedDir = dirSelect.data("selected");

        if (govId) {
            loadDirectorates(govId, dirSelect, selectedDir);
        }
    });

    // إضافة صف جديد
    $("#add-geo-row").click(function() {
        let newIndex = rowIndex++;
        
        let newRow = `
            <tr class="geo-row" data-row="${newIndex}">
                <td>
                    <select 
                        name="geographic_scopes[${newIndex}][governorate_id]" 
                        class="form-select governorate-select search-select"
                        data-index="${newIndex}">
                        <option value="">-- بدون تحديد (اختياري) --</option>
                        <option value="all">جميع المحافظات</option>
                        @foreach($governorates as $governorate)
                            <option value="{{ $governorate->id }}">{{ $governorate->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select 
                        name="geographic_scopes[${newIndex}][directorate_id]" 
                        class="form-select directorate-select search-select"
                        data-index="${newIndex}"
                        data-selected="">
                        <option value="">-- بدون تحديد (اختياري) --</option>
                        <option value="all">جميع المديريات</option>
                    </select>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-geo-row">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        const $newRow = $(newRow);
        $("#geographic-scopes-tbody").append($newRow);

        // تهيئة Select2 على الصف الجديد فقط (دون إعادة تهيئة العناصر المهيّأة سابقاً)
        if (typeof $.fn.select2 === 'function') {
            $newRow.find('.search-select').select2();
        }
    });

    // حذف صف
    $(document).on("click", ".remove-geo-row", function() {
        let row = $(this).closest("tr");
        let rows = $(".geo-row");
        
        if (rows.length > 1) {
            row.remove();
            
            // إعادة ترقيم الصفوف المتبقية
            $(".geo-row").each(function(index) {
                let newIndex = index;
                $(this).find('select').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        let newName = name.replace(/\[[0-9]+\]/, `[${newIndex}]`);
                        $(this).attr('name', newName);
                    }
                });
                $(this).data('row', newIndex);
            });
        } else {
            // إذا كان الصف الوحيد، قم بتفريغ القيم بدلاً من الحذف
            row.find('select').val('');
            row.find('select').trigger('change');
        }
    });

    // عند اختيار كيان، تعبئة النطاقات الجغرافية تلقائياً
    $('#entity_id').change(function() {
        let entityId = $(this).val();
        if (!entityId) {
            return;
        }

        $.ajax({
            url: "{{ route('entities.get-geo') }}",
            type: "GET",
            data: { 
                entity_id: entityId,
                _token: "{{ csrf_token() }}"
            },
            success: function(data) {
                if (data.governorate_id && data.directorate_id) {
                    // الحصول على أول صف في الجدول
                    let firstRow = $(".geo-row").first();
                    
                    // تعيين المحافظة
                    firstRow.find(".governorate-select").val(data.governorate_id).trigger('change');
                    
                    // بعد تحميل المديريات، حدد المديرية المناسبة
                    setTimeout(function() {
                        let dirSelect = firstRow.find(".directorate-select");
                        if (dirSelect.find(`option[value="${data.directorate_id}"]`).length > 0) {
                            dirSelect.val(data.directorate_id).trigger('change');
                        }
                    }, 500);
                }
            },
            error: function(xhr, status, error) {
                console.error('حدث خطأ في تحميل بيانات الكيان:', error);
            }
        });
    });

    // التحقق من صحة النماذج عند الإرسال (اختيار المحافظة اختياري بالكامل)
    $('form').on('submit', function(e) {
        let hasError = false;
        
        $(".geo-row").each(function() {
            let gov = $(this).find(".governorate-select").val();
            let dir = $(this).find(".directorate-select").val();
            
            // إذا كان هناك مديرية محددة ولكن لا توجد محافظة مختارة
            if (dir && dir !== 'all' && (!gov || gov === '')) {
                hasError = true;
                $(this).find(".governorate-select").addClass('is-invalid');
                if (typeof toastr !== 'undefined') {
                    toastr.error('يجب اختيار المحافظة التابعة لها المديرية المحددة');
                }
            }
        });
        
        // إذا كان هناك خطأ، منع الإرسال
        if (hasError) {
            e.preventDefault();
            return false;
        }
    });

    // إزالة رسائل الخطأ عند تغيير القيم
    $(document).on('change', '.governorate-select, .directorate-select', function() {
        $(this).removeClass('is-invalid');
    });

});
</script>
@endpush