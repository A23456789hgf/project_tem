<div class="project-table-container">
    <div class="project-table-header">
        <i class="fas fa-users-cog me-1"></i>الجهات الإشرافية
    </div>

    <div class="project-table-wrapper">
        <table class="project-table" id="supervisingAuthoritiesTable" data-entity-options-url="{{ route('projects.entity-options', isset($project) ? ['project_id' => $project->id] : []) }}" data-old-input="{{ old('supervising_authorities') !== null ? 'true' : 'false' }}" data-old-rows="{{ json_encode(old('supervising_authorities', []), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) }}">
            <thead>
                <tr>
                    <th>نوع الجهة</th>
                    <th>اسم الجهة</th>
                    <th>الجهة الأم</th>
                    <th width="100">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($project) && $project->supervisingAuthorities->count() > 0)
                    @foreach($project->supervisingAuthorities as $index => $authority)
                        <tr data-authority-index="{{ $index }}">
                            <td>
                                <select name="supervising_authorities[{{ $index }}][authority_type]"
                                        class="form-select entity-type-select">
                                    <option value="internal" {{ $authority->authority_type == 'internal' ? 'selected' : '' }}>داخلية</option>
                                    <option value="external" {{ $authority->authority_type == 'external' ? 'selected' : '' }}>خارجية</option>
                                </select>
                            </td>
                            <td>
                                @php
                                    $entityId = null;
                                    $entityName = null;
                                    $fieldName = 'internal_entity_id';

                                    if ($authority->authority_type == 'internal') {
                                        $entityId = $authority->internal_entity_id;
                                        $entityName = $authority->internalEntity ? $authority->internalEntity->name : null;
                                        $fieldName = 'internal_entity_id';
                                    } else {
                                        $entityId = $authority->authority_id;
                                        $entityName = $authority->authority ? $authority->authority->agency_name : null;
                                        $fieldName = 'authority_id';
                                    }
                                @endphp
                                <select name="supervising_authorities[{{ $index }}][{{ $fieldName }}]"
                                        class="form-select authority-select"
                                        data-entity-id="{{ $entityId }}">
                                    <option value="">اختر الجهة</option>
                                    @if($entityId)<option value="{{ $entityId }}" selected>{{ $entityName ?? $entityId }}</option>@endif
                                </select>
                            </td>
                            <td>
                                <input type="text"
                                       name="supervising_authorities[{{ $index }}][parent_name]"
                                       class="form-control parent-name-input"
                                       readonly
                                       value="لا توجد جهة أب"
                                       placeholder="سيتم تعبئته تلقائياً">
                                <input type="hidden"
                                       name="supervising_authorities[{{ $index }}][parent_id]"
                                       class="parent-id-input"
                                       value="">
                            </td>
                            <td>
                                <div class="project-action-buttons">
                                    <button type="button" class="project-btn project-btn-danger remove-authority" title="حذف الجهة">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr class="project-empty-row">
                        <td colspan="4" class="text-center">
                            <i class="fas fa-users-cog fa-2x mb-2 text-muted"></i><br>
                            لا توجد جهات إشرافية مضافة بعد
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="p-3">
            <button type="button" class="project-btn project-btn-primary" id="addSupervisingAuthorityBtn">
                <i class="fas fa-plus"></i>إضافة جهة إشرافية جديدة
            </button>
        </div>
    </div>
</div>

<style>
/* Additional styles for supervising authorities table */
#supervisingAuthoritiesTable .parent-name-input {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    cursor: not-allowed;
}

#supervisingAuthoritiesTable .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

#supervisingAuthoritiesTable .project-action-buttons {
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
