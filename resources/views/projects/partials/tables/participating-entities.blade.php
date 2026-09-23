<div class="project-table-container">
    <div class="project-table-header">
        <i class="fas fa-handshake me-1"></i>الجهات المشاركة
    </div>

    <div class="project-table-wrapper">
        <table class="project-table" id="participatingEntitiesTable">
            <thead>
                <tr>
                    <th>نوع الجهة</th>
                    <th>اسم الجهة</th>
                    <th>الجهة الأم</th>
                    <th width="100">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($project) && $project->participatingEntities->count() > 0)
                    @foreach($project->participatingEntities as $index => $entity)
                        <tr data-entity-index="{{ $index }}">
                            <td>
                                <select name="participating_entities[{{ $index }}][authority_type]"
                                        class="form-select entity-type-select">
                                    <option value="internal" {{ $entity->authority_type == 'internal' ? 'selected' : '' }}>داخلية</option>
                                    <option value="external" {{ $entity->authority_type == 'external' ? 'selected' : '' }}>خارجية</option>
                                </select>
                            </td>
                            <td>
                                @php
                                    $displayValue = $entity->authority_type == 'internal' ? $entity->internal_entity_id : $entity->authority_id;
                                    $parentName = 'لا توجد جهة أب';
                                    $parentId = $entity->parent_id;

                                    if ($entity->authority_type == 'internal') {
                                        $internal = $entity->internalEntity ?? \App\Models\InternalEntity::withoutGlobalScopes()->find($displayValue);
                                        if ($internal) {
                                            $parentInternal = $entity->parentInternalEntity ?? \App\Models\InternalEntity::withoutGlobalScopes()->find($entity->parent_id);
                                            $parentName = $parentInternal ? $parentInternal->name : 'لا توجد جهة أب';
                                            $parentId = $parentInternal ? $parentInternal->id : null;
                                        }
                                    } else {
                                        $auth = $entity->authority ?? \App\Models\Authority::withoutGlobalScopes()->find($displayValue);
                                        if ($auth) {
                                            $parentName = $entity->parent ? ($entity->parent->agency_name ?? $entity->parent->name) : 'لا توجد جهة أب';
                                        }
                                    }
                                @endphp
                                <select name="participating_entities[{{ $index }}][{{ $entity->authority_type == 'internal' ? 'internal_entity_id' : 'authority_id' }}]"
                                        class="form-select authority-select"
                                        data-value="{{ $displayValue }}">
                                    <option value="">اختر الجهة</option>
                                    @if($entity->authority_type == 'internal')
                                        @foreach($internalEntities as $internal)
                                            <option value="{{ $internal['id'] }}" 
                                                {{ $displayValue == $internal['id'] ? 'selected' : '' }}>
                                                {{ $internal['entity_name'] ?? $internal['name'] ?? '' }}
                                            </option>
                                        @endforeach
                                    @else
                                        @foreach($authorities as $authority)
                                            <option value="{{ $authority->id }}" 
                                                {{ $displayValue == $authority->id ? 'selected' : '' }}>
                                                {{ $authority->agency_name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </td>
                            <td>
                                <input type="text" 
                                       name="participating_entities[{{ $index }}][parent_name]"
                                       class="form-control parent-name-input"
                                       readonly
                                       value="{{ $parentName }}"
                                       placeholder="سيتم تعبئته تلقائياً">
                                <input type="hidden" 
                                       name="participating_entities[{{ $index }}][parent_id]"
                                       class="parent-id-input"
                                       value="{{ $parentId }}">
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
                            <i class="fas fa-handshake fa-2x mb-2 text-muted"></i><br>
                            لا توجد جهات مشاركة مضافة بعد
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="p-3">
            <button type="button" class="project-btn project-btn-primary" id="addParticipatingEntityBtn">
                <i class="fas fa-plus"></i>إضافة جهة مشاركة جديدة
            </button>
        </div>
    </div>
</div>

<script>
// Participating Entities Management Module (Local Arrays)
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof AppUtils === 'undefined') {
            console.error('AppUtils not found! تأكد من تحميل ملف app-utils.js');
        }

        const ParticipatingEntityManager = {
            counter: 0,
            authorities: @json($authorities ?? []),
            internalEntities: @json($internalEntities ?? []),

            init: function() {
                const rows = document.querySelectorAll('#participatingEntitiesTable tbody tr[data-entity-index]');
                rows.forEach(row => {
                    const idx = parseInt(row.dataset.entityIndex) || 0;
                    if (idx >= this.counter) this.counter = idx + 1;
                });

                this.bindEvents();
                this.initializeExistingEntities();
            },
            
            bindEvents: function() {
                const addBtn = document.getElementById('addParticipatingEntityBtn');
                if (addBtn) {
                    addBtn.addEventListener('click', () => this.addEntity());
                }

                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('.remove-entity');
                    if (!btn || !btn.closest('#participatingEntitiesTable')) return;
                    this.removeEntity(btn.closest('tr'));
                });

                document.addEventListener('change', (e) => {
                    if (!e.target.closest('#participatingEntitiesTable')) return;
                    
                    if (e.target.classList.contains('entity-type-select')) {
                        this.handleEntityTypeChange(e.target);
                    } else if (e.target.classList.contains('authority-select')) {
                        this.handleAuthorityChange(e.target);
                    }
                });
            },

            initializeExistingEntities: function() {
                const rows = document.querySelectorAll('#participatingEntitiesTable tbody tr:not(.project-empty-row)');
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
                    }
                });
            },

            addEntity: function() {
                const emptyRow = document.querySelector('.project-empty-row');
                if (emptyRow) emptyRow.remove();

                const tbody = document.querySelector('#participatingEntitiesTable tbody');
                const row = this.createEntityRow();
                tbody.appendChild(row);

                if (typeof AppUtils !== 'undefined' && AppUtils.Utils) {
                    AppUtils.Utils.animate(row, 'fadeIn');
                }
                this.counter++;
            },

            createEntityRow: function() {
                const row = document.createElement('tr');
                row.dataset.entityIndex = this.counter;

                const internalOptions = this.internalEntities.map(entity => 
                    `<option value="${entity.id}">${entity.entity_name || entity.name || ''}</option>`
                ).join('');

                row.innerHTML = `
                    <td>
                        <select name="participating_entities[${this.counter}][authority_type]"
                                class="form-select entity-type-select">
                            <option value="internal" selected>داخلية</option>
                            <option value="external">خارجية</option>
                        </select>
                    </td>
                    <td>
                        <select name="participating_entities[${this.counter}][internal_entity_id]"
                                class="form-select authority-select">
                            <option value="">اختر الجهة</option>
                            ${internalOptions}
                        </select>
                    </td>
                    <td>
                        <input type="text" 
                               name="participating_entities[${this.counter}][parent_name]"
                               class="form-control parent-name-input"
                               readonly
                               placeholder="سيتم تعبئته تلقائياً">
                        <input type="hidden" 
                               name="participating_entities[${this.counter}][parent_id]"
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
                if (typeof AppUtils !== 'undefined' && AppUtils.Table) {
                    AppUtils.Table.removeRow(row, false).then(() => {
                        this.checkEmptyTable();
                        this.renumberRows();
                    });
                } else {
                    row.remove();
                    this.checkEmptyTable();
                    this.renumberRows();
                }
            },

            handleEntityTypeChange: function(select) {
                const row = select.closest('tr');
                const authoritySelect = row.querySelector('.authority-select');
                const type = select.value;
                const index = row.dataset.entityIndex;
                const fieldName = type === 'internal' ? 'internal_entity_id' : 'authority_id';

                authoritySelect.setAttribute('name', `participating_entities[${index}][${fieldName}]`);
                this.populateAuthorityOptions(authoritySelect, type);
                authoritySelect.value = '';
                this.fillParentAuthority(row, null, type);
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
                        `<option value="${entity.id}">${entity.entity_name || entity.name || ''}</option>`
                    ).join('');
                } else {
                    options += this.authorities.map(auth => 
                        `<option value="${auth.id}">${auth.agency_name || ''}</option>`
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
                        parentNameInput.value = selectedEntity.father_name || selectedEntity.parent_name || 'لا توجد جهة أب';
                        parentIdInput.value = selectedEntity.parent_id || ''; 
                    }
                } else {
                    const selectedAuthority = this.authorities.find(auth => auth.id == authorityValue);
                    if (selectedAuthority && selectedAuthority.parent_id) {
                        const parentAuthority = this.authorities.find(auth => auth.id == selectedAuthority.parent_id);
                        if (parentAuthority) {
                            parentNameInput.value = parentAuthority.agency_name || '';
                            parentIdInput.value = parentAuthority.id || '';
                        }
                    } else {
                        parentNameInput.value = 'لا توجد جهة أب';
                        parentIdInput.value = '';
                    }
                }
            },

            checkEmptyTable: function() {
                const tbody = document.querySelector('#participatingEntitiesTable tbody');
                if (tbody.children.length === 0) {
                    tbody.innerHTML = `
                        <tr class="project-empty-row">
                            <td colspan="4" class="text-center">
                                <i class="fas fa-handshake fa-2x mb-2 text-muted"></i><br>
                                لا توجد جهات مشاركة مضافة بعد
                            </td>
                        </tr>
                    `;
                }
            },

            renumberRows: function() {
                const rows = document.querySelectorAll('#participatingEntitiesTable tbody tr:not(.project-empty-row)');
                let newCounter = 0;
                
                rows.forEach((row, index) => {
                    row.dataset.entityIndex = index;
                    
                    const entityTypeSelect = row.querySelector('.entity-type-select');
                    const authoritySelect = row.querySelector('.authority-select');
                    const parentNameInput = row.querySelector('.parent-name-input');
                    const parentIdInput = row.querySelector('.parent-id-input');
                    
                    const currentType = entityTypeSelect ? entityTypeSelect.value : 'internal';
                    const fieldName = currentType === 'internal' ? 'internal_entity_id' : 'authority_id';

                    if (entityTypeSelect) entityTypeSelect.name = `participating_entities[${index}][authority_type]`;
                    if (authoritySelect) authoritySelect.name = `participating_entities[${index}][${fieldName}]`;
                    if (parentNameInput) parentNameInput.name = `participating_entities[${index}][parent_name]`;
                    if (parentIdInput) parentIdInput.name = `participating_entities[${index}][parent_id]`;
                    
                    newCounter++;
                });
                
                this.counter = newCounter;
            },

            getEntitiesData: function() {
                const entities = [];
                const rows = document.querySelectorAll('#participatingEntitiesTable tbody tr:not(.project-empty-row)');
                
                rows.forEach(row => {
                    const entityType = row.querySelector('.entity-type-select')?.value;
                    const authorityId = row.querySelector('.authority-select')?.value;
                    const parentId = row.querySelector('.parent-id-input')?.value;
                    
                    if (entityType && authorityId) {
                        const item = {
                            authority_type: entityType,
                            parent_id: parentId || null
                        };
                        if (entityType === 'internal') {
                            item.internal_entity_id = authorityId;
                        } else {
                            item.authority_id = authorityId;
                        }
                        entities.push(item);
                    }
                });
                
                return entities;
            },

            validateEntities: function() {
                const entities = this.getEntitiesData();
                const authorityIds = entities.map(entity => entity.authority_id);
                const uniqueAuthorityIds = [...new Set(authorityIds)];
                
                if (authorityIds.length !== uniqueAuthorityIds.length) {
                    return {
                        valid: false,
                        message: 'لا يمكن اختيار نفس الجهة أكثر من مرة'
                    };
                }
                
                return {
                    valid: true,
                    entities: entities
                };
            }
        };

        ParticipatingEntityManager.init();
        window.ParticipatingEntityManager = ParticipatingEntityManager;
    });
})();
</script>

<style>
/* Additional styles for participating entities table */
#participatingEntitiesTable .parent-name-input {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    cursor: not-allowed;
}

#participatingEntitiesTable .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

#participatingEntitiesTable .project-action-buttons {
    display: flex;
    gap: 5px;
    justify-content: center;
}

/* Responsive design */
@media (max-width: 768px) {
    .table-responsive {
        margin: 10px 0;
        overflow-x: auto;
    }
    
    .project-action-buttons {
        flex-direction: column;
    }
}
</style>