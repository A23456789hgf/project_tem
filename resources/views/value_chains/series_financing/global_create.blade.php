@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="mb-5 border-bottom pb-4">
            <h1 class="display-6 fw-bold text-dark mb-2">إضافة جهة تمويل</h1>
            <p class="text-muted font-light">إضافة مصدر تمويل جديد واختيار سلسلة القيمة
            </p>

            <a href="{{ route('global-financings.index') }}"
                class="btn btn-link p-0 text-decoration-none text-primary mt-2">
                <i class="fas fa-arrow-right ms-1"></i> العودة لقائمة جهات التمويل
            </a>
        </div>

        @can('value_chains.create')
        <form action="{{ route('global-financings.store') }}" method="POST" class="row g-4">
        @endcan
            @csrf

            {{-- سلسلة القيمة --}}
            <div class="col-md-12 mb-2">
                <label class="form-label fw-bold">سلسلة القيمة <span class="text-danger">*</span></label>
                <select name="value_chain_id" id="value_chain_id" class="form-select form-select-lg border-0 bg-light rounded-3" required>
                    <option value="">اختر سلسلة القيمة...</option>
                    @foreach($valueChains as $vc)
                        <option value="{{ $vc->id }}" @selected(old('value_chain_id') == $vc->id)>{{ $vc->name }}</option>
                    @endforeach
                </select>
                @error('value_chain_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- نوع التمويل --}}
            <div class="col-md-6 mb-2">
                <label class="form-label fw-bold">نوع التمويل <span class="text-danger">*</span></label>
                <select name="value_chain_financing_type_id" id="value_chain_financing_type_id"
                    class="form-select form-select-lg border-0 bg-light rounded-3 @error('value_chain_financing_type_id') is-invalid @enderror"
                    required>
                    <option value="">اختر نوع التمويل...</option>
                    @foreach($financingTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('value_chain_financing_type_id') == $type->id)>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                @error('value_chain_financing_type_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- نوع الجهة --}}
            <div class="col-md-6 mb-2">
                <label class="form-label fw-bold">نوع الجهة <span class="text-danger">*</span></label>

                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="entity_type" id="entity_type_internal"
                            value="internal" {{ old('entity_type', 'internal') == 'internal' ? 'checked' : '' }}>
                        <label class="form-check-label" for="entity_type_internal">جهة داخلية</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="entity_type" id="entity_type_external"
                            value="external" {{ old('entity_type') == 'external' ? 'checked' : '' }}>
                        <label class="form-check-label" for="entity_type_external">جهة خارجية</label>
                    </div>
                </div>

                @error('entity_type')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- الجهة الداخلية --}}
            <div class="col-md-6" id="internal_entity_group">
                <label class="form-label fw-bold">الجهة الداخلية <span class="text-danger">*</span></label>
                <select name="internal_entity_id" id="internal_entity_id"
                    class="form-select form-select-lg border-0 bg-light rounded-3 @error('internal_entity_id') is-invalid @enderror">
                    <option value="" data-parent-name="ـ">اختر الجهة...</option>
                    @foreach($internalEntities as $entity)
                        <option value="{{ $entity->id }}" data-parent-name="{{ $entity->parent ? $entity->parent->name : 'ـ' }}"
                            @selected(old('internal_entity_id') == $entity->id)>
                            {{ $entity->name }}
                        </option>
                    @endforeach
                </select>
                @error('internal_entity_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- الجهة الخارجية --}}
            <div class="col-md-6" id="external_entity_group" style="display: none;">
                <label class="form-label fw-bold">الجهة الخارجية <span class="text-danger">*</span></label>
                <select name="authority_id" id="authority_id"
                    class="form-select form-select-lg border-0 bg-light rounded-3 @error('authority_id') is-invalid @enderror">
                    <option value="" data-parent-name="ـ">اختر الجهة...</option>
                    @foreach($authorities as $authority)
                        <option value="{{ $authority->id }}"
                            data-parent-name="{{ $authority->parent ? ($authority->parent->agency_name ?? $authority->parent->name) : 'ـ' }}"
                            @selected(old('authority_id') == $authority->id)>
                            {{ $authority->name }}
                        </option>
                    @endforeach
                </select>
                @error('authority_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- الجهة الأم --}}
            <div class="col-md-6" id="parent_entity_display_group">
                <label class="form-label fw-bold text-muted">الجهة الأم المعتمدة</label>
                <input type="text" id="parent_entity_display"
                    class="form-control form-control-lg border-0 bg-secondary bg-opacity-10 rounded-3 text-muted" readonly
                    placeholder="سيتم تعبئته تلقائياً عند اختيار الجهة">
            </div>

            {{-- الأزرار (يبقى سطر كامل) --}}
            <div class="col-12 d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5 rounded-3 shadow-sm">
                    حفظ وإضافة
                </button>

                <a href="{{ route('global-financings.index') }}"
                    class="btn btn-outline-secondary btn-lg px-4 rounded-3">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const $internalRadio = $('#entity_type_internal');
            const $externalRadio = $('#entity_type_external');
            const $internalGroup = $('#internal_entity_group');
            const $externalGroup = $('#external_entity_group');
            const $internalSelect = $('#internal_entity_id');
            const $externalSelect = $('#authority_id');
            const $parentDisplay = $('#parent_entity_display');

            // Apply Select2 if not already applied
            if (typeof window.initGlobalSelect2 === 'function') {
                window.initGlobalSelect2($('#value_chain_id'), false);
                window.initGlobalSelect2($internalSelect, false);
                window.initGlobalSelect2($externalSelect, false);
            } else {
                $('#value_chain_id').select2();
                $internalSelect.select2();
                $externalSelect.select2();
            }

            function updateParentDisplay(e) {
                let parentName = '';
                
                // If triggered by select2:select, use the data object
                if (e && e.type === 'select2:select') {
                    const data = e.params.data;
                    if (data && data.element && data.element.dataset.parentName) {
                        parentName = data.element.dataset.parentName;
                    } else if (data && data.parent_name) {
                        // Fallback if using AJAX search
                        parentName = data.parent_name;
                    }
                } else {
                    // Fallback for regular change or initial load
                    if ($internalRadio.is(':checked')) {
                        let selectedOption = $internalSelect.find('option:selected');
                        if (selectedOption.val()) {
                            parentName = selectedOption.attr('data-parent-name') || 'لا توجد جهة أب';
                        }
                    } else if ($externalRadio.is(':checked')) {
                        let selectedOption = $externalSelect.find('option:selected');
                        if (selectedOption.val()) {
                            parentName = selectedOption.attr('data-parent-name') || 'لا توجد جهة أب';
                        }
                    }
                }
                
                $parentDisplay.val(parentName || 'لا توجد جهة أب');
            }

            function toggleEntityFields() {
                if ($internalRadio.is(':checked')) {
                    $internalGroup.show();
                    $externalGroup.hide();
                } else {
                    $internalGroup.hide();
                    $externalGroup.show();
                }
                updateParentDisplay();
            }

            $('input[name="entity_type"]').on('change', toggleEntityFields);
            
            $internalSelect.on('change select2:select', updateParentDisplay);
            $externalSelect.on('change select2:select', updateParentDisplay);
            
            // Initial state
            toggleEntityFields();
        });
    </script>
@endpush
