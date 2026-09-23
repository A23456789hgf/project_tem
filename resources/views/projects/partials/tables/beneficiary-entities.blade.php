<div class="project-table-container">
    <div class="project-table-header">
        <i class="fas fa-hand-holding-heart"></i>الجهات المستفيدة
    </div>

    <div class="project-table-wrapper">
        <table class="project-table" id="beneficiaryEntitiesTable">
            <thead>
                <tr>
                    <th>نوع الجهة</th>
                    <th>اسم الجهة</th>
                    <th>الجهة الأم</th>
                    <th width="100">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($project) && $project->beneficiaryEntities->count() > 0)
                    @foreach($project->beneficiaryEntities as $index => $entity)
                        <tr data-entity-index="{{ $index }}">
                            <td>
                                <select name="beneficiary_entities[{{ $index }}][authority_type]"
                                        class="form-select entity-type-select">
                                    <option value="internal" {{ $entity->authority_type == 'internal' ? 'selected' : '' }}>داخلية</option>
                                    <option value="external" {{ $entity->authority_type == 'external' ? 'selected' : '' }}>خارجية</option>
                                </select>
                            </td>
                            <td>
                                @php
                                    $entityId = $entity->authority_type == 'internal' ? $entity->internal_entity_id : $entity->authority_id;
                                @endphp
                                <select name="beneficiary_entities[{{ $index }}][{{ $entity->authority_type == 'internal' ? 'internal_entity_id' : 'authority_id' }}]"
                                        class="form-select authority-select"
                                        data-value="{{ $entityId }}">
                                    <option value="">اختر الجهة</option>
                                    @if($entity->authority_type == 'internal')
                                        @foreach($internalEntities as $internal)
                                            <option value="{{ $internal['id'] }}" 
                                                {{ $entityId == $internal['id'] ? 'selected' : '' }}>
                                                {{ $internal['entity_name'] }}
                                            </option>
                                        @endforeach
                                    @else
                                        @foreach($authorities as $authority)
                                            <option value="{{ $authority->id }}" 
                                                {{ $entityId == $authority->id ? 'selected' : '' }}>
                                                {{ $authority->agency_name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </td>
                            <td>
                                <input type="text" 
                                       name="beneficiary_entities[{{ $index }}][parent_name]"
                                       class="form-control parent-name-input"
                                       readonly
                                       value="{{ $entity->parent ? $entity->parent->agency_name : 'لا توجد جهة أب' }}"
                                       placeholder="سيتم تعبئته تلقائياً">
                                <input type="hidden" 
                                       name="beneficiary_entities[{{ $index }}][parent_id]"
                                       class="parent-id-input"
                                       value="{{ $entity->parent_id }}">
                            </td>
                            <td>
                                <div class="project-action-buttons">
                                    <button type="button" class="project-btn project-btn-danger remove-entity" title="حذف الجهة">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr class="project-empty-row">
                        <td colspan="4" class="text-center">
                            <i class="fas fa-hand-holding-heart fa-2x mb-2"></i><br>
                            لا توجد جهات مستفيدة مضافة بعد
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="p-3">
            <button type="button" class="project-btn project-btn-primary" id="addBeneficiaryEntityBtn">
                <i class="fas fa-plus"></i>إضافة جهة مستفيدة جديدة
            </button>
        </div>
    </div>
</div>

<script>
// Beneficiary Entities Management Module
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof AppUtils === 'undefined') return;

        const BeneficiaryEntityManager = {
            counter: {{ isset($project) ? $project->beneficiaryEntities->count() : 0 }},
            authorities: @json($authorities ?? []),
            internalEntities: @json($internalEntities ?? []),

            init: function() {
                this.bindEvents();
                this.initializeExistingEntities();
            },

            bindEvents: function() {
                const addBtn = document.getElementById('addBeneficiaryEntityBtn');
                if (addBtn) {
                    addBtn.addEventListener('click', () => this.addEntity());
                }

                document.addEventListener('click', (e) => {
                    if (e.target.closest('#beneficiaryEntitiesTable .remove-entity')) {
                        this.removeEntity(e.target.closest('tr'));
                    }
                });

                document.addEventListener('change', (e) => {
                    if (e.target.closest('#beneficiaryEntitiesTable')) {
                        if (e.target.classList.contains('entity-type-select')) {
                            this.handleEntityTypeChange(e.target);
                        } else if (e.target.classList.contains('authority-select')) {
                            this.handleAuthorityChange(e.target);
                        }
                    }
                });
            },

            initializeExistingEntities: function() {
                const rows = document.querySelectorAll('#beneficiaryEntitiesTable tbody tr:not(.project-empty-row)');
                rows.forEach(row => {
                    const typeSelect = row.querySelector('.entity-type-select');
                    const authoritySelect = row.querySelector('.authority-select');
                    
                    if (typeSelect && authoritySelect) {
                        const entityType = typeSelect.value;
                        const savedValue = authoritySelect.getAttribute('data-value') || authoritySelect.value;
                        
                        this.populateAuthorityOptions(authoritySelect, entityType);
                        
                        if (savedValue) {
                            authoritySelect.value = savedValue;
                        }

                        if (authoritySelect.value) {
                            this.fillParentAuthority(row, authoritySelect.value, entityType);
                        }
                    }
                });
            },

            addEntity: function() {
                const tbody = document.querySelector('#beneficiaryEntitiesTable tbody');
                const emptyRow = tbody.querySelector('.project-empty-row');
                if (emptyRow) emptyRow.remove();

                const row = this.createEntityRow();
                tbody.appendChild(row);
                AppUtils.Utils.animate(row, 'fadeIn');
                this.counter++;
            },

            createEntityRow: function() {
                const row = document.createElement('tr');
                row.dataset.entityIndex = this.counter;

                // Default to internal
                const authoritiesOptions = this.internalEntities.map(entity => 
                    `<option value="${entity.id}">${entity.entity_name}</option>`
                ).join('');

                row.innerHTML = `
                    <td>
                        <select name="beneficiary_entities[${this.counter}][authority_type]"
                                class="form-select entity-type-select">
                            <option value="internal" selected>داخلية</option>
                            <option value="external">خارجية</option>
                        </select>
                    </td>
                    <td>
                        <select name="beneficiary_entities[${this.counter}][internal_entity_id]"
                                class="form-select authority-select">
                            <option value="">اختر الجهة</option>
                            ${authoritiesOptions}
                        </select>
                    </td>
                    <td>
                        <input type="text" 
                               name="beneficiary_entities[${this.counter}][parent_name]"
                               class="form-control parent-name-input"
                               readonly
                               placeholder="سيتم تعبئته تلقائياً">
                        <input type="hidden" 
                               name="beneficiary_entities[${this.counter}][parent_id]"
                               class="parent-id-input">
                    </td>
                    <td>
                        <div class="project-action-buttons">
                            <button type="button" class="project-btn project-btn-danger remove-entity" title="حذف الجهة">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;

                return row;
            },

            removeEntity: function(row) {
                AppUtils.Table.removeRow(row, false).then(() => {
                    this.checkEmptyTable();
                });
            },

            handleEntityTypeChange: function(select) {
                const row = select.closest('tr');
                const authoritySelect = row.querySelector('.authority-select');
                const index = row.dataset.entityIndex;
                const fieldName = select.value === 'internal' ? 'internal_entity_id' : 'authority_id';
                authoritySelect.setAttribute('name', `beneficiary_entities[${index}][${fieldName}]`);
                
                this.populateAuthorityOptions(authoritySelect, select.value);
                
                // Clear selection
                authoritySelect.value = '';
                this.fillParentAuthority(row, null, select.value);
            },

            handleAuthorityChange: function(select) {
                const row = select.closest('tr');
                const typeSelect = row.querySelector('.entity-type-select');
                this.fillParentAuthority(row, select.value, typeSelect.value);
            },

            populateAuthorityOptions: function(authoritySelect, type) {
                let options = '<option value="">اختر الجهة</option>';
                
                if (type === 'internal') {
                    options += this.internalEntities.map(entity => 
                        `<option value="${entity.id}">${entity.entity_name}</option>`
                    ).join('');
                } else {
                    options += this.authorities.map(auth => 
                        `<option value="${auth.id}">${auth.agency_name}</option>`
                    ).join('');
                }
                
                authoritySelect.innerHTML = options;
            },

            fillParentAuthority: function(row, authorityValue, type) {
                const parentNameInput = row.querySelector('.parent-name-input');
                const parentIdInput = row.querySelector('.parent-id-input');
                
                parentNameInput.value = '';
                parentIdInput.value = '';
                
                if (!authorityValue) return;
                
                if (type === 'internal') {
                    const selectedEntity = this.internalEntities.find(e => e.id == authorityValue);
                    if (selectedEntity) {
                        parentNameInput.value = selectedEntity.father_name || 'لا توجد جهة أب';
                        parentIdInput.value = ''; 
                    }
                } else {
                    const selectedAuthority = this.authorities.find(auth => auth.id == authorityValue);
                    if (selectedAuthority) {
                        if (selectedAuthority.parent_id) {
                            const parentAuthority = this.authorities.find(auth => auth.id == selectedAuthority.parent_id);
                            if (parentAuthority) {
                                parentNameInput.value = parentAuthority.agency_name;
                                parentIdInput.value = parentAuthority.id;
                            }
                        } else {
                            parentNameInput.value = 'لا توجد جهة أب';
                            parentIdInput.value = '';
                        }
                    }
                }
            },

            checkEmptyTable: function() {
                const tbody = document.querySelector('#beneficiaryEntitiesTable tbody');
                AppUtils.Table.checkEmpty(tbody, `
                    <i class="fas fa-hand-holding-heart fa-2x mb-2 text-muted"></i><br>
                    لا توجد جهات مستفيدة مضافة بعد
                `);
            }
        };

        BeneficiaryEntityManager.init();
        window.BeneficiaryEntityManager = BeneficiaryEntityManager;
    });
})();
</script>
