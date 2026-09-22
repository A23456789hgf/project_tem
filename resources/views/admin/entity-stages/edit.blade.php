@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-primary fw-bold">تعديل مراحل الجهة: {{ $tab === 'internal' ? $entity->name : $authority->name }}</h4>
            <small class="text-muted">تفعيل أو تعطيل مراحل الموافقات وتعيين المسؤولين</small>
        </div>
        <a href="{{ route('admin.entity-stages.index') }}" class="btn btn-outline-secondary shadow-sm">
            <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4"><i class="fas fa-exclamation-triangle me-1"></i> {{ session('warning') }}</div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-5">
            @can('stages.edit')
            <form action="{{ route('admin.entity-stages.update', $tab === 'internal' ? $entity->id : $authority->id) }}" method="POST">
            @endcan
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" value="{{ $tab }}">
                
                <h5 class="fw-bold mb-4">المراحل المتاحة للجهة</h5>
                
                <div class="row g-4">
                    @foreach($stageTypes as $stageType)
                        @php
                            if ($tab === 'internal') {
                                $stageConfig = $entity->approvalStages->firstWhere('stage', $stageType->value);
                            } else {
                                $stageConfig = $stages->firstWhere('stage', $stageType->value);
                            }
                            $isEnabled = $stageConfig !== null && $stageConfig->is_active;
                            $assignedUserId = $stageConfig?->responsible_user_id;
                        @endphp
                        
                        <div class="col-12">
                            <div class="card border border-1 shadow-sm rounded-4 stage-edit-card {{ $isEnabled ? 'border-primary' : '' }}">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        
                                        <!-- Stage Toggle -->
                                        <div class="col-md-4 d-flex align-items-center gap-3">
                                            <div class="form-check form-switch fs-3 m-0">
                                                <input class="form-check-input stage-toggle" type="checkbox" role="switch" 
                                                    name="stages[{{ $stageType->value }}][enabled]" 
                                                    value="1" 
                                                    id="stage_toggle_{{ $stageType->value }}"
                                                    data-target="#user_select_container_{{ $stageType->value }}"
                                                    data-stage="{{ $stageType->value }}"
                                                    {{ old("stages.{$stageType->value}.enabled", $isEnabled) ? 'checked' : '' }}>
                                            </div>
                                            <div>
                                                <label class="form-check-label fw-bold d-block fs-5" for="stage_toggle_{{ $stageType->value }}">
                                                    {{ $stageType->label() }}
                                                </label>
                                                <span class="badge bg-secondary">الترتيب: {{ $stageType->stageOrder() }}</span>
                                            </div>
                                        </div>

                                        <!-- User Selection -->
                                        <div class="col-md-8">
                                            <div id="user_select_container_{{ $stageType->value }}" 
                                                 class="{{ old("stages.{$stageType->value}.enabled", $isEnabled) ? '' : 'd-none' }}">
                                                
                                                <label class="form-label fw-semibold text-muted small">المسؤول عن هذه المرحلة</label>
                                                <select name="stages[{{ $stageType->value }}][responsible_user_id]" 
                                                        class="form-select user-select" 
                                                        data-id="{{ $tab === 'internal' ? $entity->id : $authority->id }}"
                                                        data-stage="{{ $stageType->value }}">
                                                    
                                                    <option value="">-- يتم تحديده تلقائياً من المستخدمين إن وجد / أو اختر يدوياً --</option>
                                                    @if($assignedUserId && $stageConfig->responsibleUser)
                                                        <option value="{{ $assignedUserId }}" selected>
                                                            {{ $stageConfig->responsibleUser->name }} (الحالي)
                                                        </option>
                                                    @endif
                                                </select>
                                                <div class="form-text">سيتم جلب المستخدمين الذين لديهم مسؤولية "{{ $stageType->label() }}" في هذه الجهة.</div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($tab === 'external')
                <hr class="my-5 border-light">
                <h5 class="fw-bold mb-4">إعداد المسار (الوجهة التالية)</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">الوجهة التالية في المسار <span class="text-danger">*</span></label>
                        <select name="route_destination_type" id="route_destination_type" class="form-select" required>
                            <option value="ministry" @selected(old('route_destination_type', $route?->destination_type) == 'ministry')>الوزارة كجهة نهائية (Ministry Root)</option>
                            <option value="authority" @selected(old('route_destination_type', $route?->destination_type) == 'authority')>جهة خارجية أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="destination_authority_container" style="display: {{ old('route_destination_type', $route?->destination_type) == 'authority' ? 'block' : 'none' }};">
                        <label class="form-label fw-bold">الجهة الخارجية الوجهة <span class="text-danger">*</span></label>
                        <select name="destination_authority_id" class="form-select select2-search">
                            <option value="">-- اختر الجهة الخارجية الوجهة --</option>
                            @foreach($authorities as $auth)
                                <option value="{{ $auth->id }}" @selected(old('destination_authority_id', $route?->destination_authority_id) == $auth->id)>
                                    {{ $auth->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif

                <div class="d-flex justify-content-end gap-3 mt-5 border-top pt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .stage-edit-card {
        transition: border-color 0.2s;
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        
        // Handle toggle visibility
        $('.stage-toggle').on('change', function() {
            const targetContainer = $($(this).data('target'));
            const card = $(this).closest('.stage-edit-card');
            
            if ($(this).is(':checked')) {
                targetContainer.removeClass('d-none');
                card.addClass('border-primary');
                
                // Fetch eligible users if the select is empty
                const selectEl = targetContainer.find('select');
                if(selectEl.find('option').length <= 1) {
                    loadEligibleUsers(selectEl);
                }
            } else {
                targetContainer.addClass('d-none');
                card.removeClass('border-primary');
            }
        });

        // Initialize Select2
        $('.user-select').select2({
            dir: "rtl",
            theme: "bootstrap-5",
            width: '100%'
        });

        // Load users on initial load for checked items
        $('.user-select').each(function() {
            if (!$(this).closest('div[id^="user_select_container"]').hasClass('d-none')) {
                // If there's only the default option, or just 1 selected option from backend, fetch full list
                loadEligibleUsers($(this));
            }
        });

        function loadEligibleUsers(selectElement) {
            const idValue = selectElement.data('id');
            const stage = selectElement.data('stage');
            const currentValue = selectElement.val(); // Keep current selection
            
            const params = { stage: stage };
            @if($tab === 'internal')
                params.entity_id = idValue;
                params.tab = 'internal';
            @else
                params.authority_id = idValue;
                params.tab = 'external';
            @endif

            $.ajax({
                url: "{{ route('admin.entity-stages.eligible-users') }}",
                data: params,
                success: function(users) {
                    // Store current option text if it exists so we don't lose it if it's not in the list (though it should be)
                    const currentText = selectElement.find('option:selected').text();
                    
                    selectElement.empty();
                    selectElement.append('<option value="">-- اختر المسؤول --</option>');
                    
                    let foundCurrent = false;
                    
                    users.forEach(function(user) {
                        const isSelected = (currentValue == user.id);
                        if (isSelected) foundCurrent = true;
                        
                        const option = new Option(user.name + ' (' + user.user_id + ')', user.id, false, isSelected);
                        selectElement.append(option);
                    });
                    
                    // Re-append the current value if it wasn't returned by AJAX (e.g. user was deactivated)
                    if (currentValue && !foundCurrent) {
                        selectElement.append(new Option(currentText + ' (غير متوفر في القائمة)', currentValue, false, true));
                    }
                    
                    selectElement.trigger('change');
                },
                error: function() {
                    console.error("Failed to load eligible users for stage " + stage);
                }
            });
        }
        
        $('#route_destination_type').on('change', function() {
            if ($(this).val() === 'authority') {
                $('#destination_authority_container').show();
                $('#destination_authority_container select').prop('required', true);
            } else {
                $('#destination_authority_container').hide();
                $('#destination_authority_container select').prop('required', false).val('').trigger('change');
            }
        });
    });
</script>
@endsection
