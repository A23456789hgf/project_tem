@if($isOldProject && !$project->entity_modified)
    <div class="modal fade" id="editEntityModal{{ $project->id }}" tabindex="-1" aria-labelledby="editEntityModalLabel{{ $project->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark py-2">
                    <h6 class="modal-title mb-0" id="editEntityModalLabel{{ $project->id }}">تعديل الجهة المقدمة للمشروع</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @can('projects.edit-entity')
                <form action="{{ route('projects.update-entity', $project) }}" method="POST" onsubmit="return confirm('تنبيه: تغيير الجهة المقدمة هو إجراء نهائي لمرة واحدة فقط ولا يمكن التراجع عنه أو تعديله لاحقًا. هل أنت متأكد من الاستمرار؟');">
                @endcan
                    @csrf
                    @method('PUT')
                    <div class="modal-body py-3">
                        <div class="alert alert-danger small py-2">
                            <strong>تنبيه هام!</strong>
                            تغيير الجهة المقدمة هو إجراء نهائي لمرة واحدة فقط ولا يمكن التراجع عنه أو تعديله لاحقًا.
                        </div>
                        <table class="table table-sm table-bordered small">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 35%;">اسم المشروع</th>
                                    <td>{{ $project->project_name }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">رقم النموذج</th>
                                    <td>{{ $project->form_number }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">التمويل (برنامج/مجال)</th>
                                    <td>{{ optional($project->program)->name }} / {{ optional($project->domain)->name }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">الجهة المقدمة الحالية</th>
                                    <td class="text-danger fw-bold">{{ $project->created_by_entity }}</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="mb-3 mt-3">
                            <label for="creator_entity_id_{{ $project->id }}" class="form-label small fw-bold">اختر الجهة المقدمة الجديدة</label>
                            <select class="form-select form-select-sm" id="creator_entity_id_{{ $project->id }}" name="creator_entity_id" required>
                                <option value="">-- اختر الجهة --</option>
                                @if(isset($entities) && (is_countable($entities) ? count($entities) > 0 : !empty($entities)))
                                    @foreach($entities as $entity)
                                        @php
                                            $entityId = data_get($entity, 'id');
                                            $entityName = data_get($entity, 'displayName') ?? data_get($entity, 'name');
                                        @endphp
                                        <option value="{{ $entityId }}" {{ $project->creator_entity_id == $entityId ? 'selected' : '' }}>{{ $entityName }}</option>
                                    @endforeach
                                @else
                                    <option disabled aria-disabled="true">لا توجد جهات مسموحة لك في هذا السياق</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer py-2 bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning btn-sm fw-bold">حفظ التعديل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
