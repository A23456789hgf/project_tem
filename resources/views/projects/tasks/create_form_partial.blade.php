{{-- Assignment section and task details reused by edit view --}}
<div class="section-block mb-4">

    <div class="section-divider">
        <span><i class="fas fa-user-check me-1"></i> الإسناد</span>
    </div>

    <div class="row g-4">

        <div class="col-12">
            <label class="form-label fw-bold">نوع الإسناد *</label>

            <div class="d-flex gap-4">
                <div class="form-check">
                    @can('task.assign')
                        <input type="radio" name="assignment_type" value="user" {{ (old('assignment_type', auth()->user()->can('task.assign') ? ($task->assignment_type ?? 'user') : 'entity') == 'user') ? 'checked' : '' }}>
                        <label>مستخدم</label>
                    @endcan
                </div>

                <div class="form-check">
                    <input type="radio" name="assignment_type" value="entity" {{ (old('assignment_type', auth()->user()->can('task.assign') ? ($task->assignment_type ?? 'user') : 'entity') == 'entity') ? 'checked' : '' }}>
                    <label>جهة</label>
                </div>
            </div>
        </div>

        @can('task.assign')
            <div class="col-md-6" id="user_assignment_container">
                <label>إسناد إلى مستخدم</label>
                <select name="assigned_to[]" multiple class="form-select">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ in_array($user->id, (array) old('assigned_to', $task->assignees->pluck('id')->all() ?? [])) ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        @endcan

        <div class="col-md-6 d-none" id="entity_assignment_container" data-preselected-users="{{ json_encode(old('assigned_to', isset($task) ? $task->assignees->pluck('id')->all() : [])) }}">
            <div class="mb-3">
                <label class="form-label fw-bold">إسناد إلى جهة *</label>
                <select id="assigned_entity_id" name="assigned_entity_id" class="form-select select2-enable"></select>
            </div>
            @can('task.assign')
                <div class="mb-3 d-none" id="entity_users_container">
                    <label class="form-label fw-bold">المستخدمون المسؤولون في هذه الجهة</label>
                    <select id="entity_users_select" name="assigned_to[]" multiple class="form-select select2-enable"></select>
                </div>
            @endcan
        </div>

    </div>
</div>

<div class="section-block mb-4">
    <div class="section-divider">
        <span><i class="fas fa-edit me-1"></i> تفاصيل المهمة</span>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <label>عنوان المهمة *</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $task->title ?? '') }}">
        </div>

        <div class="col-12">
            <label>الوصف</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $task->description ?? '') }}</textarea>
        </div>

        <div class="col-12">
            <label>المرفقات</label>
            <input type="file" name="attachments[]" multiple class="form-control">
        </div>
    </div>
</div>

<div class="section-block">
    <div class="section-divider">
        <span><i class="fas fa-tags me-1"></i> التصنيف</span>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <label>الحالة</label>
            <select name="status" class="form-select">
                <option value="todo" {{ (old('status', $task->status ?? '') == 'todo') ? 'selected' : '' }}>قيد الانتظار</option>
                <option value="in_progress" {{ (old('status', $task->status ?? '') == 'in_progress') ? 'selected' : '' }}>قيد التنفيذ</option>
                <option value="completed" {{ (old('status', $task->status ?? '') == 'completed') ? 'selected' : '' }}>مكتملة</option>
                <option value="cancelled" {{ (old('status', $task->status ?? '') == 'cancelled') ? 'selected' : '' }}>ملغاة</option>
            </select>
        </div>

        <div class="col-md-4">
            <label>الأولوية</label>
            <select name="priority" class="form-select">
                <option value="low" {{ (old('priority', $task->priority ?? '') == 'low') ? 'selected' : '' }}>منخفضة</option>
                <option value="medium" {{ (old('priority', $task->priority ?? '') == 'medium') ? 'selected' : '' }}>متوسطة</option>
                <option value="high" {{ (old('priority', $task->priority ?? '') == 'high') ? 'selected' : '' }}>عالية</option>
                <option value="urgent" {{ (old('priority', $task->priority ?? '') == 'urgent') ? 'selected' : '' }}>عاجلة</option>
            </select>
        </div>

        <div class="col-md-4">
            <label>تاريخ الاستحقاق</label>
            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($task->due_date)->toDateString() ?? '') }}">
        </div>
    </div>
</div>
