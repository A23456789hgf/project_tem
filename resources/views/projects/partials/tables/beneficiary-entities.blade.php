<div class="project-table-container">
    <div class="project-table-header">
        <i class="fas fa-hand-holding-heart"></i>الجهات المستفيدة
    </div>

    <div class="project-table-wrapper">
        <table class="project-table" id="beneficiaryEntitiesTable" data-entity-options-url="{{ route('projects.entity-options', isset($project) ? ['project_id' => $project->id] : []) }}" data-old-input="{{ old('beneficiary_entities') !== null ? 'true' : 'false' }}" data-old-rows="{{ json_encode(old('beneficiary_entities', []), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) }}">
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
                                    $entityName = $entity->authority_type == 'internal' ? $entity->internalEntity?->name : $entity->authority?->agency_name;
                                @endphp
                                <select name="beneficiary_entities[{{ $index }}][{{ $entity->authority_type == 'internal' ? 'internal_entity_id' : 'authority_id' }}]"
                                        class="form-select authority-select"
                                        data-entity-id="{{ $entityId }}">
                                    <option value="">اختر الجهة</option>
                                    @if($entityId)<option value="{{ $entityId }}" selected>{{ $entityName ?? $entityId }}</option>@endif
                                </select>
                            </td>
                            <td>
                                <input type="text" 
                                       name="beneficiary_entities[{{ $index }}][parent_name]"
                                       class="form-control parent-name-input"
                                       readonly
                                       value="لا توجد جهة أب"
                                       placeholder="سيتم تعبئته تلقائياً">
                                <input type="hidden" 
                                       name="beneficiary_entities[{{ $index }}][parent_id]"
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
