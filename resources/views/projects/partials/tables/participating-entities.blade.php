<div class="project-table-container">
    <div class="project-table-header">
        <i class="fas fa-handshake me-1"></i>الجهات المشاركة
    </div>

    <div class="project-table-wrapper">
        <table class="project-table" id="participatingEntitiesTable" data-entity-options-url="{{ route('projects.entity-options', isset($project) ? ['project_id' => $project->id] : []) }}" data-old-input="{{ old('participating_entities') !== null ? 'true' : 'false' }}" data-old-rows="{{ json_encode(old('participating_entities', []), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) }}">
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
                                    $entityName = $entity->authority_type == 'internal' ? ($entity->internalEntity?->name ?? null) : ($entity->authority?->agency_name ?? null);
                                @endphp
                                <select name="participating_entities[{{ $index }}][{{ $entity->authority_type == 'internal' ? 'internal_entity_id' : 'authority_id' }}]"
                                        class="form-select authority-select"
                                        data-entity-id="{{ $displayValue }}">
                                    <option value="">اختر الجهة</option>
                                    @if($displayValue)<option value="{{ $displayValue }}" selected>{{ $entityName ?? $displayValue }}</option>@endif
                                </select>
                            </td>
                            <td>
                                <input type="text" 
                                       name="participating_entities[{{ $index }}][parent_name]"
                                       class="form-control parent-name-input"
                                       readonly
                                       value="لا توجد جهة أب"
                                       placeholder="سيتم تعبئته تلقائياً">
                                <input type="hidden" 
                                       name="participating_entities[{{ $index }}][parent_id]"
                                       class="parent-id-input"
                                       value="">
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
