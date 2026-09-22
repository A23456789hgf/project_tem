{{-- Modal: Add Entity for Old Projects --}}
{{-- Usage: @include('projects.partials.modals._add_entity_modal', ['project' => $project]) --}}

<div class="modal fade" id="addEntityModal{{ $project->id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow">

            {{-- Header --}}
            <div class="modal-header py-2" style="background: linear-gradient(135deg, #1e3a5f, #2c5f8a); color: white;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-building fa-lg"></i>
                    <div>
                        <h6 class="modal-title mb-0 text-white">إضافة جهة للمشروع</h6>
                        <small
                            class="opacity-75 text-white">{{ Str::limit($project->project_name ?? 'مشروع قديم', 50) }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body p-3">

                {{-- Entity Type Selector (Tabs) --}}
                <div class="mb-3">
                    <label class="form-label small fw-bold">نوع الجهة</label>
                    <div class="d-flex flex-wrap gap-2" id="entityTypeTabs{{ $project->id }}">
                        <button type="button" class="btn btn-sm entity-type-tab active"
                            style="background:#1e3a5f; color:white;" data-target="supervising-panel-{{ $project->id }}"
                            data-project="{{ $project->id }}">
                            <i class="fas fa-users-cog me-1"></i> الجهة الإشرافية
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary entity-type-tab"
                            data-target="implementing-panel-{{ $project->id }}" data-project="{{ $project->id }}">
                            <i class="fas fa-cogs me-1"></i> الجهة المنفذة
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary entity-type-tab"
                            data-target="beneficiary-panel-{{ $project->id }}" data-project="{{ $project->id }}">
                            <i class="fas fa-hands-helping me-1"></i> الجهة المستفيدة
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary entity-type-tab"
                            data-target="participating-panel-{{ $project->id }}" data-project="{{ $project->id }}">
                            <i class="fas fa-handshake me-1"></i> الجهة المشاركة
                        </button>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- Panel 1: Supervising --}}
                {{-- ============================================================ --}}
                <div class="entity-panel" id="supervising-panel-{{ $project->id }}">
                    @can('projects.edit')
                    <form action="{{ route('projects.update', $project->id) }}" method="POST">
                    @endcan
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="{{ $project->status }}">
                        <input type="hidden" name="_entity_section" value="supervising">
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                        <div class="table-responsive mb-2">
                            <table class="table table-sm table-bordered align-middle mb-0"
                                id="supervisingTable{{ $project->id }}">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small" style="width:130px;">نوع الجهة</th>
                                        <th class="small">اسم الجهة</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($project->supervisingAuthorities as $idx => $auth)
                                        <tr>
                                            <td>
                                                <select name="supervising_authorities[{{ $idx }}][authority_type]"
                                                    class="form-select form-select-sm">
                                                    <option value="internal" {{ $auth->authority_type == 'internal' ? 'selected' : '' }}>داخلية</option>
                                                    <option value="external" {{ $auth->authority_type == 'external' ? 'selected' : '' }}>خارجية</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="hidden"
                                                    name="supervising_authorities[{{ $idx }}][{{ $auth->authority_type == 'internal' ? 'internal_entity_id' : 'authority_id' }}]"
                                                    value="{{ $auth->authority_type == 'internal' ? $auth->internal_entity_id : $auth->authority_id }}">
                                                <span class="small">
                                                    @if($auth->authority_type == 'internal')
                                                        {{ optional($auth->internalEntity)->name ?? '—' }}
                                                    @else
                                                        {{ optional($auth->authority)->agency_name ?? '—' }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger remove-row-btn p-0"
                                                    style="width:24px;height:24px;font-size:11px;" title="حذف">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="no-rows-row">
                                            <td colspan="3" class="text-center text-muted small py-2">لا توجد جهات إشرافية
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="add-entity-row d-flex gap-2 mb-3">
                            <select class="form-select form-select-sm new-authority-type" style="max-width:130px;">
                                <option value="internal">داخلية</option>
                                <option value="external">خارجية</option>
                            </select>
                            <select class="form-select form-select-sm new-authority-select"
                                data-ajax-url="{{ route('lookup.search', ['include_pending' => 1]) }}" data-ajax-type="internal_entity">
                                <option value="">اختر الجهة...</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-primary add-supervising-row-btn flex-shrink-0"
                                data-table="supervisingTable{{ $project->id }}"
                                data-counter-key="sup-{{ $project->id }}" data-field-prefix="supervising_authorities">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save me-1"></i> حفظ الجهات الإشرافية
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ============================================================ --}}
                {{-- Panel 2: Implementing --}}
                {{-- ============================================================ --}}
                <div class="entity-panel d-none" id="implementing-panel-{{ $project->id }}">
                    @can('projects.edit')
                    <form action="{{ route('projects.update', $project->id) }}" method="POST">
                    @endcan
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="{{ $project->status }}">
                        <input type="hidden" name="_entity_section" value="implementing">
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                        <div class="table-responsive mb-2">
                            <table class="table table-sm table-bordered align-middle mb-0"
                                id="implementingTable{{ $project->id }}">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small" style="width:130px;">نوع الجهة</th>
                                        <th class="small">اسم الجهة</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($project->implementingEntities as $idx => $entity)
                                        <tr>
                                            <td>
                                                <select name="implementing_entities[{{ $idx }}][authority_type]"
                                                    class="form-select form-select-sm">
                                                    <option value="internal" {{ $entity->authority_type == 'internal' ? 'selected' : '' }}>داخلية</option>
                                                    <option value="external" {{ $entity->authority_type == 'external' ? 'selected' : '' }}>خارجية</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="hidden"
                                                    name="implementing_entities[{{ $idx }}][{{ $entity->authority_type == 'internal' ? 'internal_entity_id' : 'authority_id' }}]"
                                                    value="{{ $entity->authority_type == 'internal' ? $entity->internal_entity_id : $entity->authority_id }}">
                                                <span class="small">
                                                    @if($entity->authority_type == 'internal')
                                                        {{ optional($entity->internalEntity)->name ?? '—' }}
                                                    @else
                                                        {{ optional($entity->authority)->agency_name ?? '—' }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger remove-row-btn p-0"
                                                    style="width:24px;height:24px;font-size:11px;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="no-rows-row">
                                            <td colspan="3" class="text-center text-muted small py-2">لا توجد جهات منفذة
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="add-entity-row d-flex gap-2 mb-3">
                            <select class="form-select form-select-sm new-authority-type" style="max-width:130px;">
                                <option value="internal">داخلية</option>
                                <option value="external">خارجية</option>
                            </select>
                            <select class="form-select form-select-sm new-authority-select"
                                data-ajax-url="{{ route('lookup.search', ['include_pending' => 1]) }}" data-ajax-type="internal_entity">
                                <option value="">اختر الجهة...</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-primary add-entity-row-btn flex-shrink-0"
                                data-table="implementingTable{{ $project->id }}"
                                data-counter-key="imp-{{ $project->id }}" data-field-prefix="implementing_entities">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save me-1"></i> حفظ الجهات المنفذة
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ============================================================ --}}
                {{-- Panel 3: Beneficiary --}}
                {{-- ============================================================ --}}
                <div class="entity-panel d-none" id="beneficiary-panel-{{ $project->id }}">
                    @can('projects.edit')
                    <form action="{{ route('projects.update', $project->id) }}" method="POST">
                    @endcan
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="{{ $project->status }}">
                        <input type="hidden" name="_entity_section" value="beneficiary">
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                        <div class="table-responsive mb-2">
                            <table class="table table-sm table-bordered align-middle mb-0"
                                id="beneficiaryTable{{ $project->id }}">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small" style="width:130px;">نوع الجهة</th>
                                        <th class="small">اسم الجهة</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($project->beneficiaryEntities as $idx => $entity)
                                        <tr>
                                            <td>
                                                <select name="beneficiary_entities[{{ $idx }}][authority_type]"
                                                    class="form-select form-select-sm">
                                                    <option value="internal" {{ $entity->authority_type == 'internal' ? 'selected' : '' }}>داخلية</option>
                                                    <option value="external" {{ $entity->authority_type == 'external' ? 'selected' : '' }}>خارجية</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="hidden"
                                                    name="beneficiary_entities[{{ $idx }}][{{ $entity->authority_type == 'internal' ? 'internal_entity_id' : 'authority_id' }}]"
                                                    value="{{ $entity->authority_type == 'internal' ? $entity->internal_entity_id : $entity->authority_id }}">
                                                <span class="small">
                                                    @if($entity->authority_type == 'internal')
                                                        {{ optional($entity->internalEntity)->name ?? '—' }}
                                                    @else
                                                        {{ optional($entity->authority)->agency_name ?? '—' }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger remove-row-btn p-0"
                                                    style="width:24px;height:24px;font-size:11px;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="no-rows-row">
                                            <td colspan="3" class="text-center text-muted small py-2">لا توجد جهات مستفيدة
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="add-entity-row d-flex gap-2 mb-3">
                            <select class="form-select form-select-sm new-authority-type" style="max-width:130px;">
                                <option value="internal">داخلية</option>
                                <option value="external">خارجية</option>
                            </select>
                            <select class="form-select form-select-sm new-authority-select"
                                data-ajax-url="{{ route('lookup.search', ['include_pending' => 1]) }}" data-ajax-type="internal_entity">
                                <option value="">اختر الجهة...</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-primary add-entity-row-btn flex-shrink-0"
                                data-table="beneficiaryTable{{ $project->id }}"
                                data-counter-key="ben-{{ $project->id }}" data-field-prefix="beneficiary_entities">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save me-1"></i> حفظ الجهات المستفيدة
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ============================================================ --}}
                {{-- Panel 4: Participating --}}
                {{-- ============================================================ --}}
                <div class="entity-panel d-none" id="participating-panel-{{ $project->id }}">
                    @can('projects.edit')
                    <form action="{{ route('projects.update', $project->id) }}" method="POST">
                    @endcan
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="{{ $project->status }}">
                        <input type="hidden" name="_entity_section" value="participating">
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                        <div class="table-responsive mb-2">
                            <table class="table table-sm table-bordered align-middle mb-0"
                                id="participatingTable{{ $project->id }}">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small" style="width:130px;">نوع الجهة</th>
                                        <th class="small">اسم الجهة</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($project->participatingEntities as $idx => $entity)
                                        <tr>
                                            <td>
                                                <select name="participating_entities[{{ $idx }}][authority_type]"
                                                    class="form-select form-select-sm">
                                                    <option value="internal" {{ $entity->authority_type == 'internal' ? 'selected' : '' }}>داخلية</option>
                                                    <option value="external" {{ $entity->authority_type == 'external' ? 'selected' : '' }}>خارجية</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="hidden"
                                                    name="participating_entities[{{ $idx }}][{{ $entity->authority_type == 'internal' ? 'internal_entity_id' : 'authority_id' }}]"
                                                    value="{{ $entity->authority_type == 'internal' ? $entity->internal_entity_id : $entity->authority_id }}">
                                                <span class="small">
                                                    @if($entity->authority_type == 'internal')
                                                        {{ optional($entity->internalEntity)->name ?? '—' }}
                                                    @else
                                                        {{ optional($entity->authority)->agency_name ?? '—' }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger remove-row-btn p-0"
                                                    style="width:24px;height:24px;font-size:11px;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="no-rows-row">
                                            <td colspan="3" class="text-center text-muted small py-2">لا توجد جهات مشاركة
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="add-entity-row d-flex gap-2 mb-3">
                            <select class="form-select form-select-sm new-authority-type" style="max-width:130px;">
                                <option value="internal">داخلية</option>
                                <option value="external">خارجية</option>
                            </select>
                            <select class="form-select form-select-sm new-authority-select"
                                data-ajax-url="{{ route('lookup.search', ['include_pending' => 1]) }}" data-ajax-type="internal_entity">
                                <option value="">اختر الجهة...</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-primary add-entity-row-btn flex-shrink-0"
                                data-table="participatingTable{{ $project->id }}"
                                data-counter-key="par-{{ $project->id }}" data-field-prefix="participating_entities">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save me-1"></i> حفظ الجهات المشاركة
                            </button>
                        </div>
                    </form>
                </div>

            </div>{{-- end modal-body --}}

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
            </div>

        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Tab Switching
        $(document).off('click', '.entity-type-tab').on('click', '.entity-type-tab', function () {
            let target = $(this).data('target');
            let project = $(this).data('project');

            // Reset tabs
            $(`#entityTypeTabs${project} .entity-type-tab`).removeClass('active').css({
                'background': '',
                'color': ''
            }).addClass('btn-outline-secondary');

            // Activate clicked
            $(this).removeClass('btn-outline-secondary').addClass('active').css({
                'background': '#1e3a5f',
                'color': 'white'
            });

            // Switch Panels
            $(`#addEntityModal${project} .entity-panel`).addClass('d-none');
            $(`#${target}`).removeClass('d-none');
        });

        // 2. Dynamic Select2 init for the Add Row
        function initEntitySelect2($element) {
            if (typeof $.fn.select2 === 'undefined') return;

            if ($element.hasClass('select2-hidden-accessible')) {
                $element.select2('destroy');
            }

            $element.select2({
                dropdownParent: $element.closest('.modal-content'),
                placeholder: 'اختر الجهة...',
                allowClear: true,
                ajax: {
                    url: $element.data('ajax-url'),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            q: params.term,
                            type: $element.data('ajax-type')
                        };
                    },
                    processResults: function (data) {
                        let resultsArray = data.results || data.data || data;
                        if (!Array.isArray(resultsArray)) {
                            resultsArray = Object.values(resultsArray);
                        }

                        return {
                            results: $.map(resultsArray, function (item) {
                                return {
                                    text: item.text || item.name || item.agency_name || 'جهة غير مسماة',
                                    id: item.id
                                }
                            })
                        };
                    },
                    cache: true
                }
            });
        }

        // Init Select2 on Modal Open so width is correct
        $('.modal').off('shown.bs.modal.entitySelect2').on('shown.bs.modal.entitySelect2', function () {
            $(this).find('.new-authority-select').each(function () {
                initEntitySelect2($(this));
            });
        });

        // 3. Type Change
        $(document).off('change', '.new-authority-type').on('change', '.new-authority-type', function () {
            let type = $(this).val();
            let ajaxType = (type === 'internal') ? 'internal_entity' : 'authority';
            let $select = $(this).closest('.add-entity-row').find('.new-authority-select');

            $select.data('ajax-type', ajaxType);
            $select.val(null).empty().trigger('change');
            initEntitySelect2($select);
        });

        // 4. Add Row Logic
        let rowCounters = {};
        $(document).off('click', '.add-supervising-row-btn, .add-entity-row-btn').on('click', '.add-supervising-row-btn, .add-entity-row-btn', function () {
            let tableId = $(this).data('table');
            let counterKey = $(this).data('counter-key');
            let prefix = $(this).data('field-prefix');

            let $rowContainer = $(this).closest('.add-entity-row');
            let $typeSelect = $rowContainer.find('.new-authority-type');
            let $entitySelect = $rowContainer.find('.new-authority-select');

            let typeVal = $typeSelect.val();
            let entityId = $entitySelect.val();
            let entityName = $entitySelect.find('option:selected').text();

            if (!entityId) {
                alert('يرجى اختيار الجهة أولاً.');
                return;
            }

            if (!rowCounters[counterKey]) {
                rowCounters[counterKey] = $(`#${tableId} tbody tr:not(.no-rows-row)`).length;
            }

            let idx = rowCounters[counterKey]++ + 100;
            let idField = (typeVal === 'internal') ? 'internal_entity_id' : 'authority_id';

            let html = `
                    <tr>
                        <td>
                            <select name="${prefix}[${idx}][authority_type]" class="form-select form-select-sm">
                                <option value="internal" ${typeVal === 'internal' ? 'selected' : ''}>داخلية</option>
                                <option value="external" ${typeVal === 'external' ? 'selected' : ''}>خارجية</option>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="${prefix}[${idx}][${idField}]" value="${entityId}">
                            <span class="small">${entityName}</span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn p-0" style="width:24px;height:24px;font-size:11px;" title="حذف"><i class="fas fa-times"></i></button>
                        </td>
                    </tr>
                `;

            let $tbody = $(`#${tableId} tbody`);
            $tbody.find('.no-rows-row').remove();
            $tbody.append(html);

            $entitySelect.val(null).empty().trigger('change');
        });

        // 5. Remove Row
        $(document).off('click', '.entity-panel .remove-row-btn').on('click', '.entity-panel .remove-row-btn', function () {
            let $tbody = $(this).closest('tbody');
            $(this).closest('tr').remove();
            if ($tbody.find('tr').length === 0) {
                $tbody.append(`<tr class="no-rows-row"><td colspan="3" class="text-center text-muted small py-2">لا توجد جهات</td></tr>`);
            }
        });
    });
</script>
@endpush
@endonce