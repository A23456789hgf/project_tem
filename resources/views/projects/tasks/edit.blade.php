@extends('layouts.app')

@section('styles')
    {{-- نفس ستايلات صفحة الإنشاء للاستقرار البصري --}}
    @include('projects.tasks.create_styles_partial')
@endsection

@section('content')
    @php
        // Determine scope from task
        $scope = 'general';
        if (!empty($task->project_id) && !empty($task->value_chain_id)) {
            $scope = 'project_value_chain';
        } elseif (!empty($task->project_id)) {
            $scope = 'project';
        } elseif (!empty($task->value_chain_id)) {
            $scope = 'value_chain';
        }
    @endphp

    <div class="container-fluid py-4 animate-fade" dir="rtl" style="max-width: var(--content-max-width);">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2" style="font-size: 0.82rem;">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-decoration-none">
                                        <i class="fas fa-home me-1"></i>الرئيسية
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('tasks.index') }}" class="text-muted text-decoration-none">المهام</a>
                                </li>
                                <li class="breadcrumb-item active text-primary fw-bold">تعديل مهمة</li>
                            </ol>
                        </nav>
                        <h1 class="h4 fw-bold text-dark mb-0">
                            <i class="fas fa-edit me-2 text-primary"></i>تعديل مهمة
                        </h1>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="btn btn-cancel btn-premium">
                        <i class="fas fa-arrow-right"></i> رجوع للقائمة
                    </a>
                </div>
            </div>
        </div>

        <div class="card create-task-card bg-white">

            <div class="card-header-custom d-flex align-items-center gap-3">
                <div class="header-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h5>تفاصيل المهمة</h5>
            </div>

            @can('task.edit')
            <form action="{{ route('tasks.updateGlobal', $task->id) }}" method="POST" enctype="multipart/form-data">
            @endcan
                @csrf
                @method('PUT')

                <div class="card-body p-4">
                    {{-- استخدمت نفس بنية الحقول كما في create --}}
                    <div class="section-block mb-4">
                        <div class="section-divider">
                            <span><i class="fas fa-info-circle me-1"></i> المعلومات الأساسية</span>
                        </div>

                        <div class="row g-4">

                            <div class="col-12">
                                <label class="form-label fw-bold">نطاق المهمة *</label>
                                <select id="task_scope" name="task_scope" class="form-select">
                                    <option value="general" {{ $scope === 'general' ? 'selected' : '' }}>مهمة عامة</option>
                                    <option value="project" {{ $scope === 'project' ? 'selected' : '' }}>ضمن مشروع</option>
                                    <option value="value_chain" {{ $scope === 'value_chain' ? 'selected' : '' }}>ضمن سلسلة قيمة</option>
                                    <option value="project_value_chain" {{ $scope === 'project_value_chain' ? 'selected' : '' }}>مشروع + سلسلة قيمة</option>
                                </select>
                            </div>

                            <div id="project-section" class="col-12 d-none">
                                <label class="form-label">المشروع *</label>
                                <select id="project_id" name="project_id" class="form-select">
                                    <option value="">— اختر المشروع —</option>
                                    @foreach($taskProjects as $taskProject)
                                        <option value="{{ $taskProject->id }}" {{ (old('project_id', $task->project_id) == $taskProject->id) ? 'selected' : '' }}>
                                            {{ $taskProject->project_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="value-chain-section" class="col-12 d-none">
                                <label class="form-label">سلسلة القيمة *</label>
                                <select id="value_chain_id" name="value_chain_id" class="form-select">
                                    <option value="">— اختر السلسلة —</option>
                                    @foreach($valueChains as $chain)
                                        <option value="{{ $chain->id }}" {{ (old('value_chain_id', $task->value_chain_id) == $chain->id) ? 'selected' : '' }}>{{ $chain->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="chain-projects-section" class="col-12 d-none">
                                <label class="form-label">مشاريع مرتبطة بسلسلة القيمة</label>
                                <select id="chain_project_name" name="chain_project_name" class="form-select select2-enable">
                                    <option value="">— اختر مشروعاً من السلسلة —</option>
                                </select>
                            </div>

                            <div id="chain-project-activities-section" class="col-12 d-none">
                                <label class="form-label">أنشطة المشروع</label>
                                <select id="chain_project_activity" name="chain_project_activity" class="form-select select2-enable">
                                    <option value="">— اختر نشاطاً —</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    {{-- بقية الأقسام: الإسناد والتفاصيل تصرف كما في create، مع تهيئة القيم --}}
                    @include('projects.tasks.create_form_partial', ['task' => $task])

                </div>

                <div class="card-footer-custom d-flex justify-content-between">
                    <small class="text-muted">(*) الحقول المطلوبة</small>
                    <div class="d-flex gap-2">
                        <a href="{{ route('tasks.index') }}" class="btn btn-light">إلغاء</a>
                        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const oldChainProject = "{{ old('chain_project_name', $task->chain_project_name ?? '') }}";
                const oldChainActivity = "{{ old('chain_project_activity', $task->chain_project_activity ?? '') }}";
                const activitiesUrl = "{{ route('tasks.activities') }}";
                const proceduresUrl = "{{ route('tasks.procedures') }}";

                const taskScopeSelect = document.getElementById('task_scope');
                const projectSection = document.getElementById('project-section');
                const valueChainSection = document.getElementById('value-chain-section');
                const activitiesWrapper = document.getElementById('activities-wrapper');
                const projectSelect = document.getElementById('project_id');
                const valueChainSelect = document.getElementById('value_chain_id');

                const toggleCheckbox = document.getElementById('is_within_activities');
                const toggleCard = document.getElementById('toggle-card-label');
                const activitiesSection = document.getElementById('activities-section');
                const activitySelect = document.getElementById('activity_select');
                const activityTypeInput = document.getElementById('activity_type');
                const procedureSelect = document.getElementById('procedure_select');

                function applyScope(scope) {
                    if (projectSection) projectSection.classList.add('d-none');
                    if (valueChainSection) valueChainSection.classList.add('d-none');
                    if (activitiesWrapper) activitiesWrapper.classList.add('d-none');
                    if (projectSelect) projectSelect.removeAttribute('required');
                    if (valueChainSelect) valueChainSelect.removeAttribute('required');

                    if (toggleCheckbox && toggleCheckbox.checked) {
                        toggleCheckbox.checked = false;
                        if (toggleCard) toggleCard.classList.remove('active');
                        if (activitiesSection) {
                            activitiesSection.classList.add('d-none');
                            activitiesSection.classList.remove('slide-down', 'slide-up');
                        }
                        resetActivities();
                        resetProcedures();
                    }

                    if (scope === 'project') {
                        if (projectSection) projectSection.classList.remove('d-none');
                        if (activitiesWrapper) activitiesWrapper.classList.remove('d-none');
                        if (projectSelect) projectSelect.removeAttribute('required');
                    } else if (scope === 'value_chain') {
                        if (valueChainSection) valueChainSection.classList.remove('d-none');
                        if (valueChainSelect) valueChainSelect.removeAttribute('required');
                    } else if (scope === 'project_value_chain') {
                        if (projectSection) projectSection.classList.remove('d-none');
                        if (valueChainSection) valueChainSection.classList.remove('d-none');
                        if (activitiesWrapper) activitiesWrapper.classList.remove('d-none');
                        if (projectSelect) projectSelect.removeAttribute('required');
                        if (valueChainSelect) valueChainSelect.removeAttribute('required');
                    }
                }

                function toggleSection() {
                    if (!toggleCheckbox) return;
                    if (toggleCheckbox.checked) {
                        activitiesSection.classList.remove('d-none');
                        activitiesSection.classList.add('slide-down');
                        toggleCard.classList.add('active');
                        loadActivities(projectSelect.value);
                    } else {
                        activitiesSection.classList.add('slide-up');
                        toggleCard.classList.remove('active');
                        setTimeout(() => {
                            activitiesSection.classList.add('d-none');
                            activitiesSection.classList.remove('slide-up', 'slide-down');
                            resetActivities();
                            resetProcedures();
                        }, 300);
                    }
                }

                function resetActivities() {
                    if (activitySelect) activitySelect.innerHTML = '<option value="">— اختر النشاط —</option>';
                    if (activityTypeInput) activityTypeInput.value = '';
                }

                function resetProcedures() {
                    if (procedureSelect) procedureSelect.innerHTML = '<option value="">— اختر الإجراء —</option>';
                }

                function loadActivities(projectId) {
                    if (!activitySelect || !activitiesUrl) return;
                    resetActivities();
                    resetProcedures();
                    if (!projectId) return;

                    fetch(`${activitiesUrl}?project_id=${projectId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(data => {
                            data.forEach(item => {
                                const opt = document.createElement('option');
                                opt.value = item.id;
                                opt.textContent = item.label;
                                opt.dataset.type = item.type;
                                activitySelect.appendChild(opt);
                            });

                            const oldActivityId = "{{ old('activity_id', $task->activity_id ?? '') }}";
                            const oldActivityType = "{{ old('activity_type', $task->activity_type ?? '') }}";
                            if (oldActivityId) {
                                activitySelect.value = oldActivityId;
                                activityTypeInput.value = oldActivityType;
                                loadProcedures(oldActivityId, oldActivityType);
                            }
                        })
                        .catch(() => { });
                }

                function loadProcedures(activityId, activityType) {
                    if (!procedureSelect || !proceduresUrl) return;
                    resetProcedures();
                    if (!activityId || !activityType) return;

                    fetch(`${proceduresUrl}?activity_id=${activityId}&activity_type=${activityType}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(data => {
                            data.forEach(item => {
                                const opt = document.createElement('option');
                                opt.value = item.id;
                                opt.textContent = item.name;
                                procedureSelect.appendChild(opt);
                            });

                            const oldProcedureId = "{{ old('procedure_id', $task->procedure_id ?? '') }}";
                            if (oldProcedureId) {
                                procedureSelect.value = oldProcedureId;
                            }
                        })
                        .catch(() => { });
                }

                $(taskScopeSelect).on('change', function () { applyScope(this.value); });

                if (toggleCheckbox) { toggleCheckbox.addEventListener('change', toggleSection); }

                $(projectSelect).on('change', function () { if (toggleCheckbox && toggleCheckbox.checked) { loadActivities(this.value); } loadOrganizations(); });

                $(valueChainSelect).on('change', function () {
                    loadOrganizations();
                    const vcId = this.value;
                    const chainProjectsSelect = document.getElementById('chain_project_name');
                    const chainProjectsSection = document.getElementById('chain-projects-section');
                    const chainActivitiesSelect = document.getElementById('chain_project_activity');
                    const chainActivitiesSection = document.getElementById('chain-project-activities-section');

                    if (!vcId) {
                        chainProjectsSelect.innerHTML = '<option value="">— اختر مشروعاً من السلسلة —</option>';
                        chainProjectsSection.classList.add('d-none');
                        chainActivitiesSelect.innerHTML = '<option value="">— اختر نشاطاً —</option>';
                        chainActivitiesSection.classList.add('d-none');
                        return;
                    }

                    fetch(new URL("{{ route('tasks.chain_projects') }}", window.location.origin) + '?value_chain_id=' + encodeURIComponent(vcId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(projects => {
                            chainProjectsSelect.innerHTML = '<option value="">— اختر مشروعاً من السلسلة —</option>';
                            if (projects && projects.length) {
                                projects.forEach(p => {
                                    const opt = document.createElement('option');
                                    opt.value = p.id;
                                    opt.textContent = p.name;
                                    chainProjectsSelect.appendChild(opt);
                                });
                                if (oldChainProject) { chainProjectsSelect.value = oldChainProject; }
                                chainProjectsSection.classList.remove('d-none');
                                if (typeof window.$ !== 'undefined' && typeof window.$.fn.select2 !== 'undefined') {
                                    try { $(chainProjectsSelect).select2('destroy'); } catch (e) { }
                                    $(chainProjectsSelect).select2({ placeholder: '— اختر مشروعاً من السلسلة —', width: '100%' });
                                }
                                if (oldChainProject) { try { $(chainProjectsSelect).trigger('change'); } catch (e) { chainProjectsSelect.dispatchEvent(new Event('change')); } }
                            } else {
                                chainProjectsSection.classList.add('d-none');
                                chainActivitiesSection.classList.add('d-none');
                            }
                        })
                        .catch(console.error);
                });

                const assignmentTypeRadios = document.querySelectorAll('input[name="assignment_type"]');
                const userContainer = document.getElementById('user_assignment_container');
                const entityContainer = document.getElementById('entity_assignment_container');
                const assignedEntitySelect = document.getElementById('assigned_entity_id');

                function toggleAssignmentType() {
                    const selectedType = document.querySelector('input[name="assignment_type"]:checked').value;
                    if (selectedType === 'user') { userContainer.classList.remove('d-none'); entityContainer.classList.add('d-none'); }
                    else { userContainer.classList.add('d-none'); entityContainer.classList.remove('d-none'); loadOrganizations(); }
                }

                assignmentTypeRadios.forEach(radio => { radio.addEventListener('change', toggleAssignmentType); });

                function loadOrganizations() {
                    const projectId = projectSelect ? projectSelect.value : '';
                    const valueChainId = valueChainSelect ? valueChainSelect.value : '';
                    const checkedType = document.querySelector('input[name="assignment_type"]:checked');
                    if (!checkedType || checkedType.value !== 'entity') { return; }

                    const url = new URL("{{ route('api.tasks.organizations') }}", window.location.origin);
                    if (projectId) url.searchParams.append('project_id', projectId);
                    if (valueChainId) url.searchParams.append('value_chain_id', valueChainId);

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(groups => {
                            const oldVal = "{{ old('assigned_entity_id', $task->assigned_entity_id ?? '') }}";
                            assignedEntitySelect.innerHTML = '<option value="">— اختر الجهة —</option>';
                            Object.keys(groups).forEach(groupLabel => {
                                const items = groups[groupLabel];
                                if (items && items.length > 0) {
                                    const optgroup = document.createElement('optgroup');
                                    optgroup.label = groupLabel;
                                    items.forEach(item => {
                                        const opt = document.createElement('option');
                                        opt.value = item.id;
                                        opt.textContent = item.name;
                                        if (oldVal && oldVal == item.id) { opt.selected = true; }
                                        optgroup.appendChild(opt);
                                    });
                                    assignedEntitySelect.appendChild(optgroup);
                                }
                            });
                            if (typeof window.$ !== 'undefined' && typeof window.$.fn !== 'undefined' && typeof window.$.fn.select2 !== 'undefined') {
                                try { $(assignedEntitySelect).select2('destroy'); } catch (e) { }
                                $(assignedEntitySelect).select2({ placeholder: '— اختر الجهة —', width: '100%' });
                            }

                            if (assignedEntitySelect.value) {
                                loadEntityUsers(assignedEntitySelect.value);
                            }
                        })
                        .catch(console.error);
                }

                const entityUsersContainer = document.getElementById('entity_users_container');
                const entityUsersSelect = document.getElementById('entity_users_select');

                function loadEntityUsers(entityId) {
                    if (!entityId) {
                        if (entityUsersContainer) entityUsersContainer.classList.add('d-none');
                        if (entityUsersSelect) entityUsersSelect.innerHTML = '';
                        return;
                    }

                    let preselected = [];
                    try {
                        const preselectedAttr = entityContainer.getAttribute('data-preselected-users');
                        if (preselectedAttr) {
                            preselected = JSON.parse(preselectedAttr);
                        }
                    } catch(e) { console.error('Error parsing preselected users', e); }

                    const url = "{{ route('projects.api.entities.users', ['entityId' => ':entityId']) }}".replace(':entityId', entityId);
                    fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(users => {
                        entityUsersSelect.innerHTML = '';
                        if (users && users.length > 0) {
                            users.forEach(user => {
                                const option = document.createElement('option');
                                option.value = user.id;
                                option.textContent = user.name;
                                if (preselected && (preselected.includes(user.id.toString()) || preselected.includes(user.id))) {
                                    option.selected = true;
                                }
                                entityUsersSelect.appendChild(option);
                            });
                            entityUsersContainer.classList.remove('d-none');
                            
                            if (typeof window.$ !== 'undefined' && typeof window.$.fn !== 'undefined' && typeof window.$.fn.select2 !== 'undefined') {
                                try { $(entityUsersSelect).select2('destroy'); } catch(e){}
                                $(entityUsersSelect).select2({
                                    placeholder: '— اختر المستخدمين المسؤولين —',
                                    width: '100%'
                                });
                            }
                        } else {
                            entityUsersContainer.classList.add('d-none');
                        }
                    })
                    .catch(console.error);
                }

                if (typeof window.$ !== 'undefined') {
                    $(assignedEntitySelect).on('change', function() {
                        loadEntityUsers(this.value);
                    });
                } else if (assignedEntitySelect) {
                    assignedEntitySelect.addEventListener('change', function() {
                        loadEntityUsers(this.value);
                    });
                }

                $(activitySelect).on('change', function () {
                    const selectedOption = this.options[this.selectedIndex];
                    const type = selectedOption?.dataset?.type || '';
                    activityTypeInput.value = type;
                    loadProcedures(this.value, type);
                });

                $(document).on('change', '#chain_project_name', function () {
                    const projectName = this.value;
                    const vcId = valueChainSelect.value;
                    const chainActivitiesSelect = document.getElementById('chain_project_activity');
                    const chainActivitiesSection = document.getElementById('chain-project-activities-section');

                    chainActivitiesSelect.innerHTML = '<option value="">— اختر نشاطاً —</option>';
                    if (!vcId || !projectName) { chainActivitiesSection.classList.add('d-none'); return; }

                    const url = new URL("{{ route('tasks.chain_project_activities') }}", window.location.origin);
                    url.searchParams.append('value_chain_id', vcId);
                    url.searchParams.append('project_name', projectName);

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(acts => {
                            if (acts && acts.length) {
                                acts.forEach(a => {
                                    const opt = document.createElement('option');
                                    opt.value = a.id;
                                    opt.textContent = a.name;
                                    chainActivitiesSelect.appendChild(opt);
                                });
                                if (oldChainActivity) { chainActivitiesSelect.value = oldChainActivity; }
                                chainActivitiesSection.classList.remove('d-none');
                                if (typeof window.$ !== 'undefined' && typeof window.$.fn.select2 !== 'undefined') {
                                    try { $(chainActivitiesSelect).select2('destroy'); } catch (e) { }
                                    $(chainActivitiesSelect).select2({ placeholder: '— اختر نشاطاً —', width: '100%' });
                                }
                            } else { chainActivitiesSection.classList.add('d-none'); }
                        })
                        .catch(console.error);
                });

                applyScope(taskScopeSelect.value);
                toggleAssignmentType();

                if (valueChainSelect && valueChainSelect.value) { try { $(valueChainSelect).trigger('change'); } catch (e) { valueChainSelect.dispatchEvent(new Event('change')); } }

                if (toggleCheckbox && toggleCheckbox.checked && activitiesWrapper && !activitiesWrapper.classList.contains('d-none')) {
                    if (toggleCard) toggleCard.classList.add('active');
                    if (projectSelect && projectSelect.value) { loadActivities(projectSelect.value); }
                }
            })();
        </script>
    @endpush
@endsection
@endsection
