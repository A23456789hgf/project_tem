<div class="project-table-container">
    <div class="project-table-header">
        <i class="fas fa-cogs me-1"></i>الجهات المنفذة
    </div>

    <div class="project-table-wrapper">
        <table class="project-table" id="implementingEntitiesTable" data-entity-options-url="{{ route('projects.entity-options', isset($project) ? ['project_id' => $project->id] : []) }}" data-old-input="{{ old('implementing_entities') !== null ? 'true' : 'false' }}" data-old-rows="{{ json_encode(old('implementing_entities', []), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) }}">
            <thead>
                <tr>
                    <th>نوع الجهة</th>
                    <th>اسم الجهة</th>
                    <th>الجهة الأم</th>
                    <th width="100">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($project) && $project->implementingEntities->count() > 0)
                    @foreach($project->implementingEntities as $index => $entity)
                        <tr data-entity-index="{{ $index }}">
                            <td>
                                <select name="implementing_entities[{{ $index }}][authority_type]"
                                        class="form-select entity-type-select">
                                    <option value="internal" {{ $entity->authority_type == 'internal' ? 'selected' : '' }}>داخلية</option>
                                    <option value="external" {{ $entity->authority_type == 'external' ? 'selected' : '' }}>خارجية</option>
                                </select>
                            </td>
                            <td>
                                @php
                                    $entityId = null;
                                    $entityName = null;
                                    $fieldName = 'internal_entity_id';

                                    if ($entity->authority_type == 'internal') {
                                        $entityId = $entity->internal_entity_id;
                                        $entityName = $entity->internalEntity ? $entity->internalEntity->name : null;
                                        $fieldName = 'internal_entity_id';
                                    } else {
                                        $entityId = $entity->authority_id;
                                        $entityName = $entity->authority ? $entity->authority->agency_name : null;
                                        $fieldName = 'authority_id';
                                    }
                                @endphp
                                <select name="implementing_entities[{{ $index }}][{{ $fieldName }}]"
                                        class="form-select authority-select"
                                        data-entity-id="{{ $entityId }}">
                                    <option value="">اختر الجهة</option>
                                    @if($entityId)<option value="{{ $entityId }}" selected>{{ $entityName ?? $entityId }}</option>@endif
                                </select>
                            </td>
                            <td>
                                <input type="text"
                                       name="implementing_entities[{{ $index }}][parent_name]"
                                       class="form-control parent-name-input"
                                       readonly
                                       value="لا توجد جهة أب"
                                       placeholder="سيتم تعبئته تلقائياً">
                                <input type="hidden"
                                       name="implementing_entities[{{ $index }}][parent_id]"
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
                            <i class="fas fa-cogs fa-2x mb-2 text-muted"></i><br>
                            لا توجد جهات منفذة مضافة بعد
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="p-3">
            <button type="button" class="project-btn project-btn-primary" id="addImplementingEntityBtn">
                <i class="fas fa-plus"></i>إضافة جهة منفذة جديدة
            </button>
        </div>
    </div>
</div>

<style>
/* Additional styles for implementing entities table */
#implementingEntitiesTable .parent-name-input {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    cursor: not-allowed;
}

#implementingEntitiesTable .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

#implementingEntitiesTable .project-action-buttons {
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
