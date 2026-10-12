@extends('layouts.app')

@section('styles')
    <style>
        /* ===== متغيرات الألوان ===== */
        :root {
            --card-bg: #ffffff;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-focus-border: #3b82f6;
            --label-color: #334155;
            --section-bg: #f1f5f9;
            --switch-active: #3b82f6;
            --danger: #ef4444;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.06), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            --shadow-lg: 0 20px 50px -12px rgba(0, 0, 0, 0.08);
            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 20px;
        }

        /* ===== البطاقة الرئيسية ===== */
        .create-task-card {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }

        .create-task-card:hover {
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.1);
        }

        /* ===== شريط عنوان البطاقة ===== */
        .card-header-custom {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 1.5rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .card-header-custom::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .card-header-custom::after {
            content: '';
            position: absolute;
            bottom: -60%;
            left: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }

        .card-header-custom h5 {
            color: #fff;
            font-weight: 700;
            margin: 0;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .card-header-custom .header-icon {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }

        /* ===== حقول الإدخال ===== */
        .form-group-custom {
            position: relative;
        }

        .form-group-custom .form-label {
            color: var(--label-color);
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .form-group-custom .form-label .label-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            flex-shrink: 0;
        }

        .form-group-custom .form-label .label-icon.blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .form-group-custom .form-label .label-icon.green {
            background: #f0fdf4;
            color: #22c55e;
        }

        .form-group-custom .form-label .label-icon.purple {
            background: #faf5ff;
            color: #a855f7;
        }

        .form-group-custom .form-label .label-icon.orange {
            background: #fff7ed;
            color: #f97316;
        }

        .form-group-custom .form-label .label-icon.red {
            background: #fef2f2;
            color: #ef4444;
        }

        .form-group-custom .form-label .label-icon.teal {
            background: #f0fdfa;
            color: #14b8a6;
        }

        .form-group-custom .form-label .label-icon.indigo {
            background: #eef2ff;
            color: #6366f1;
        }

        .form-control,
        .form-select {
            padding: 0.7rem 1rem;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--input-border);
            background-color: var(--input-bg);
            transition: all 0.25s ease;
            font-size: 0.9rem;
            color: #1e293b;
        }

        .form-control:hover,
        .form-select:hover {
            border-color: #cbd5e1;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #ffffff;
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
            outline: none;
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: var(--danger);
            background-color: #fef2f2;
        }

        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.08);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* ===== قسم الأنشطة (المميز) ===== */
        .activities-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #eff6ff 100%);
            border: 1.5px dashed #93c5fd;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .activities-section .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activities-section .section-title i {
            font-size: 0.9rem;
        }

        /* ===== مفتاح التبديل (Toggle Switch) ===== */
        .toggle-card {
            background: var(--section-bg);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            border: 1.5px solid transparent;
            cursor: pointer;
        }

        .toggle-card:hover {
            background: #e8f0fe;
        }

        .toggle-card.active {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-color: #93c5fd;
        }

        .toggle-card .toggle-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .toggle-card .toggle-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .toggle-card.active .toggle-icon {
            background: #3b82f6;
            color: #fff;
        }

        .toggle-card .toggle-text h6 {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 2px 0;
        }

        .toggle-card .toggle-text p {
            font-size: 0.78rem;
            color: #64748b;
            margin: 0;
        }

        .form-check-input:checked {
            background-color: var(--switch-active);
            border-color: var(--switch-active);
        }

        .form-check-input {
            width: 2.8em;
            height: 1.5em;
            cursor: pointer;
        }

        /* ===== الأزرار ===== */
        .btn-premium {
            border-radius: var(--radius-sm);
            padding: 0.7rem 1.75rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
        }

        .btn-save {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: none;
            color: #fff;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-save:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            color: #fff;
        }

        .btn-cancel {
            background: #fff;
            border: 1.5px solid #e2e8f0;
            color: #64748b;
        }

        .btn-cancel:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #475569;
        }

        /* ===== الفوتر ===== */
        .card-footer-custom {
            background: #fafbfc;
            border-top: 1px solid #f1f5f9;
            padding: 1.25rem 2rem;
        }

        /* ===== الفاصل بين الأقسام ===== */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0.5rem 0 0.25rem;
        }

        .section-divider span {
            font-size: 0.78rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to left, transparent, #e2e8f0);
        }

        /* ===== تأثيرات الحركة ===== */
        .slide-down {
            animation: slideDown 0.35s ease forwards;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                max-height: 500px;
                transform: translateY(0);
            }
        }

        .slide-up {
            animation: slideUp 0.3s ease forwards;
        }

        @keyframes slideUp {
            from {
                opacity: 1;
                max-height: 500px;
            }

            to {
                opacity: 0;
                max-height: 0;
            }
        }

        /* ===== شارة "*" ===== */
        .required-badge {
            font-size: 0.65rem;
            background: #fef2f2;
            color: var(--danger);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            margin-right: auto;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4 animate-fade" dir="rtl" style="max-width: var(--content-max-width);">

        {{-- ===== رأس الصفحة ===== --}}
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
                                <li class="breadcrumb-item active text-primary fw-bold">إضافة مهمة</li>
                            </ol>
                        </nav>
                        <h1 class="h4 fw-bold text-dark mb-0">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>إضافة مهمة جديدة
                        </h1>
                        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">
                            أضف مهمة جديدة وحدد تفاصيلها بالكامل
                        </p>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="btn btn-cancel btn-premium">
                        <i class="fas fa-arrow-right"></i> رجوع للقائمة
                    </a>
                </div>
            </div>
        </div>

        {{-- ===== بطاقة النموذج ===== --}}
        <div class="card create-task-card bg-white">

            {{-- ===== Header ===== --}}
            <div class="card-header-custom d-flex align-items-center gap-3">
                <div class="header-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h5>تفاصيل المهمة</h5>
            </div>

            @can('task.create')
            <form action="{{ route('tasks.store') }}" method="POST" enctype="multipart/form-data">
            @endcan
                @csrf

                <div class="card-body p-4">

                    {{-- ================= المعلومات الأساسية ================= --}}
                    <div class="section-block mb-4">
                        <div class="section-divider">
                            <span><i class="fas fa-info-circle me-1"></i> المعلومات الأساسية</span>
                        </div>

                        <div class="row g-4">

                            {{-- نطاق المهمة --}}
                            <div class="col-12">
                                <label class="form-label fw-bold">نطاق المهمة *</label>
                                <select id="task_scope" name="task_scope" class="form-select">
                                    <option value="general">مهمة عامة</option>
                                    <option value="project">ضمن مشروع</option>
                                    <option value="value_chain">ضمن سلسلة قيمة</option>
                                    <option value="project_value_chain">مشروع + سلسلة قيمة</option>
                                </select>
                            </div>

                            {{-- المشروع --}}
                            <div id="project-section" class="col-12 d-none">
                                <label class="form-label">المشروع *</label>
                                <select id="project_id" name="project_id" class="form-select">
                                    <option value="">— اختر المشروع —</option>
                                    @foreach($taskProjects as $taskProject)
                                        <option value="{{ $taskProject->id }}">
                                            {{ $taskProject->project_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- سلسلة القيمة --}}
                          {{-- سلسلة القيمة --}}
<div id="value-chain-section" class="col-12 d-none">
    <label class="form-label">سلسلة القيمة *</label>
    <select id="value_chain_id" name="value_chain_id" class="form-select">
        <option value="">— اختر السلسلة —</option>
        @foreach($valueChains as $chain)
            <option value="{{ $chain->id }}" {{ old('value_chain_id') == $chain->id ? 'selected' : '' }}>
                {{ $chain->name }}
            </option>
        @endforeach
    </select>
</div>

{{-- مشاريع مرتبطة بسلسلة القيمة --}}
<div id="chain-projects-section" class="col-12 d-none">
    <label class="form-label">المشروع المرتبط بالسلسلة</label>
    <select id="chain_project_name" name="chain_project_name" class="form-select select2-enable">
        <option value="">— اختر مشروعاً من السلسلة —</option>
        @if(old('chain_project_name'))
            <option value="{{ old('chain_project_name') }}" selected>{{ old('chain_project_name') }}</option>
        @endif
    </select>
</div>

{{-- أنشطة المشروع المرتبط بالسلسلة --}}
<div id="chain-project-activities-section" class="col-12 d-none">
    <label class="form-label">النشاط المرتبط بالمشروع</label>
    <select id="chain_project_activity" name="chain_project_activity" class="form-select select2-enable">
        <option value="">— اختر نشاطاً —</option>
        @if(old('chain_project_activity'))
            <option value="{{ old('chain_project_activity') }}" selected>{{ old('chain_project_activity') }}</option>
        @endif
    </select>
</div>

                            {{-- مشاريع مرتبطة بسلسلة القيمة (من chain_plans.project_name) --}}
                            <div id="chain-projects-section" class="col-12 d-none">
                                <label class="form-label">مشاريع مرتبطة بسلسلة القيمة</label>
                                <select id="chain_project_name" name="chain_project_name" class="form-select select2-enable">
                                    <option value="">— اختر مشروعاً من السلسلة —</option>
                                </select>
                            </div>

                            {{-- أنشطة المشروع (من chain_plans.activity_name) --}}
                            <div id="chain-project-activities-section" class="col-12 d-none">
                                <label class="form-label">أنشطة المشروع</label>
                                <select id="chain_project_activity" name="chain_project_activity" class="form-select select2-enable">
                                    <option value="">— اختر نشاطاً —</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    {{-- ================= الإسناد ================= --}}
                    <div class="section-block mb-4">

                        <div class="section-divider">
                            <span><i class="fas fa-user-check me-1"></i> الإسناد</span>
                        </div>

                        <div class="row g-4">

                            {{-- نوع الإسناد --}}
                            <div class="col-12">
                                <label class="form-label fw-bold">نوع الإسناد *</label>

                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        @can('task.assign')
                                        <input type="radio" name="assignment_type" value="user" {{ old('assignment_type', auth()->user()->can('task.assign') ? 'user' : 'entity') === 'user' ? 'checked' : '' }}>
                                            <label>مستخدم</label>
                                        @endcan
                                    </div>

                                    <div class="form-check">
                                        <input type="radio" name="assignment_type" value="entity" {{ old('assignment_type', auth()->user()->can('task.assign') ? 'user' : 'entity') === 'entity' ? 'checked' : '' }}>
                                        <label>جهة</label>
                                    </div>
                                </div>
                            </div>

                            {{-- المستخدم --}}
                            @can('task.assign')
                                <div class="col-md-6" id="user_assignment_container">
                                    <label>إسناد إلى مستخدم</label>
                                    <select name="assigned_to[]" multiple class="form-select">
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endcan

                            {{-- الجهة --}}
                            <div class="col-md-6 d-none" id="entity_assignment_container">
                                <label>إسناد إلى جهة</label>
                                <select id="assigned_entity_id" name="assigned_entity_id" class="form-select select2-enable"></select>
                            </div>

                        </div>
                    </div>

                    {{-- ================= التفاصيل ================= --}}
                    <div class="section-block mb-4">

                        <div class="section-divider">
                            <span><i class="fas fa-edit me-1"></i> تفاصيل المهمة</span>
                        </div>

                        <div class="row g-4">

                            <div class="col-12">
                                <label>عنوان المهمة *</label>
                                <input type="text" name="title" class="form-control">
                            </div>

                            <div class="col-12">
                                <label>الوصف</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-12">
                                <label>المرفقات</label>
                                <input type="file" name="attachments[]" multiple class="form-control">
                            </div>

                        </div>
                    </div>

                    {{-- ================= التصنيف ================= --}}
                    <div class="section-block">

                        <div class="section-divider">
                            <span><i class="fas fa-tags me-1"></i> التصنيف</span>
                        </div>

                        <div class="row g-4">

                            <div class="col-md-4">
                                <label>الحالة</label>
                                <select name="status" class="form-select">
                                    <option value="todo">قيد الانتظار</option>
                                    <option value="in_progress">قيد التنفيذ</option>
                                    <option value="completed">مكتملة</option>
                                    <option value="cancelled">ملغاة</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label>الأولوية</label>
                                <select name="priority" class="form-select">
                                    <option value="low">منخفضة</option>
                                    <option value="medium">متوسطة</option>
                                    <option value="high">عالية</option>
                                    <option value="urgent">عاجلة</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label>تاريخ الاستحقاق</label>
                                <input type="date" name="due_date" class="form-control">
                            </div>

                        </div>
                    </div>

                </div>

                {{-- ===== Footer ===== --}}
                <div class="card-footer-custom d-flex justify-content-between">

                    <small class="text-muted">
                        (*) الحقول المطلوبة
                    </small>

                    <div class="d-flex gap-2">
                        <a href="{{ route('tasks.index') }}" class="btn btn-light">إلغاء</a>
                        <button type="submit" class="btn btn-primary">حفظ المهمة</button>
                    </div>

                </div>

            </form>
        </div>
    </div>

    @push('scripts')
        <script>
                (function () {
                const oldChainProject = "{{ old('chain_project_name') }}";
                const oldChainActivity = "{{ old('chain_project_activity') }}";
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

                // ---- Apply scope visibility ----
                function applyScope(scope) {
                    // إخفاء كل الحقول أولاً
                    if (projectSection) projectSection.classList.add('d-none');
                    if (valueChainSection) valueChainSection.classList.add('d-none');
                    if (activitiesWrapper) activitiesWrapper.classList.add('d-none');
                    if (projectSelect) projectSelect.removeAttribute('required');
                    if (valueChainSelect) valueChainSelect.removeAttribute('required');

                    // إعادة تعيين checkbox إذا كان مفعلاً
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

                    // تطبيق المنطق حسب النطاق المختار
                    if (scope === 'project') {
                        // ضمن مشروع: يظهر المشروع وخيار الأنشطة
                        if (projectSection) projectSection.classList.remove('d-none');
                        if (activitiesWrapper) activitiesWrapper.classList.remove('d-none');
                        if (projectSelect) projectSelect.removeAttribute('required');
                    } else if (scope === 'value_chain') {
                        // ضمن سلسلة القيمة: يظهر سلسلة القيمة فقط
                        if (valueChainSection) valueChainSection.classList.remove('d-none');
                        if (valueChainSelect) valueChainSelect.removeAttribute('required');
                    } else if (scope === 'project_value_chain') {
                        // ضمن مشروع وسلسلة القيمة: يظهر الكل
                        if (projectSection) projectSection.classList.remove('d-none');
                        if (valueChainSection) valueChainSection.classList.remove('d-none');
                        if (activitiesWrapper) activitiesWrapper.classList.remove('d-none');
                        if (projectSelect) projectSelect.removeAttribute('required');
                        if (valueChainSelect) valueChainSelect.removeAttribute('required');
                    }
                    // 'general' => كل شيء مخفي
                }

                // ---- Toggle activities section ----
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

                // ---- Reset helpers ----
                function resetActivities() {
                    if (!activitySelect) return;
                    activitySelect.innerHTML = '<option value="">— اختر النشاط —</option>';
                    if (activityTypeInput) activityTypeInput.value = '';
                }

                function resetProcedures() {
                    if (!procedureSelect) return;
                    procedureSelect.innerHTML = '<option value="">— اختر الإجراء —</option>';
                }

                // ---- Load activities via AJAX ----
                function loadActivities(projectId) {
                    // Activities UI may be absent in the general create form; guard accordingly
                    if (!activitySelect) return;
                    resetActivities();
                    resetProcedures();
                    if (!projectId) return;

                    fetch(`${activitiesUrl}?project_id=${projectId}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(r => r.json())
                        .then(data => {
                            data.forEach(item => {
                                const opt = document.createElement('option');
                                opt.value = item.id;
                                opt.textContent = item.label;
                                opt.dataset.type = item.type;
                                activitySelect.appendChild(opt);
                            });

                            const oldActivityId = "{{ old('activity_id') }}";
                            const oldActivityType = "{{ old('activity_type') }}";
                            if (oldActivityId && activitySelect) {
                                activitySelect.value = oldActivityId;
                                if (activityTypeInput) activityTypeInput.value = oldActivityType;
                                loadProcedures(oldActivityId, oldActivityType);
                            }
                        })
                        .catch(() => { });
                }

                // ---- Load procedures via AJAX ----
                function loadProcedures(activityId, activityType) {
                    resetProcedures();
                    if (!activityId || !activityType) return;

                    fetch(`${proceduresUrl}?activity_id=${activityId}&activity_type=${activityType}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(r => r.json())
                        .then(data => {
                            data.forEach(item => {
                                const opt = document.createElement('option');
                                opt.value = item.id;
                                opt.textContent = item.name;
                                procedureSelect.appendChild(opt);
                            });

                            const oldProcedureId = "{{ old('procedure_id') }}";
                            if (oldProcedureId) {
                                procedureSelect.value = oldProcedureId;
                            }
                        })
                        .catch(() => { });
                }

                // ---- Event listeners ----
                $(taskScopeSelect).on('change', function () {
                    applyScope(this.value);
                });

                // Vanilla fallback listener in case jQuery is not available or errors occur
                if (taskScopeSelect && typeof taskScopeSelect.addEventListener === 'function') {
                    taskScopeSelect.addEventListener('change', function () { applyScope(this.value); });
                }

                if (toggleCheckbox) {
                    toggleCheckbox.addEventListener('change', toggleSection);
                }

                $(projectSelect).on('change', function () {
                    if (toggleCheckbox && toggleCheckbox.checked) {
                        loadActivities(this.value);
                    }
                    loadOrganizations();
                });

                if (projectSelect && typeof projectSelect.addEventListener === 'function') {
                    projectSelect.addEventListener('change', function () {
                        if (toggleCheckbox && toggleCheckbox.checked) {
                            loadActivities(this.value);
                        }
                        loadOrganizations();
                    });
                }

                $(valueChainSelect).on('change', function () {
                    loadOrganizations();
                    // Load chain projects for this value chain
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

                    fetch(new URL("{{ route('tasks.chain_projects') }}", window.location.origin) + '?value_chain_id=' + encodeURIComponent(vcId), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(r => r.json())
                        .then(projects => {
                            chainProjectsSelect.innerHTML = '<option value="">— اختر مشروعاً من السلسلة —</option>';
                            if (projects && projects.length) {
                                projects.forEach(p => {
                                    const opt = document.createElement('option');
                                    opt.value = p.name;
                                    opt.textContent = p.name;
                                    chainProjectsSelect.appendChild(opt);
                                });
                                // If there was a previous value (old input or edit), preselect it
                                if (oldChainProject) {
                                    chainProjectsSelect.value = oldChainProject;
                                }
                                chainProjectsSection.classList.remove('d-none');
                                // initialize select2
                                if (typeof window.$ !== 'undefined' && typeof window.$.fn.select2 !== 'undefined') {
                                    try { $(chainProjectsSelect).select2('destroy'); } catch (e) { }
                                    $(chainProjectsSelect).select2({ placeholder: '— اختر مشروعاً من السلسلة —', width: '100%' });
                                }
                                // If we preselected, trigger change to load activities
                                if (oldChainProject) {
                                    try { $(chainProjectsSelect).trigger('change'); } catch (e) { chainProjectsSelect.dispatchEvent(new Event('change')); }
                                }
                            } else {
                                chainProjectsSection.classList.add('d-none');
                                chainActivitiesSection.classList.add('d-none');
                            }
                        })
                        .catch(console.error);
                });

                // Vanilla fallback for value chain select
                if (valueChainSelect && typeof valueChainSelect.addEventListener === 'function') {
                    valueChainSelect.addEventListener('change', function () {
                        loadOrganizations();
                        const vcId = this.value;
                        const chainProjectsSelect = document.getElementById('chain_project_name');
                        const chainProjectsSection = document.getElementById('chain-projects-section');
                        const chainActivitiesSelect = document.getElementById('chain_project_activity');
                        const chainActivitiesSection = document.getElementById('chain-project-activities-section');

                        if (!vcId) {
                            if (chainProjectsSelect) chainProjectsSelect.innerHTML = '<option value="">— اختر مشروعاً من السلسلة —</option>';
                            if (chainProjectsSection) chainProjectsSection.classList.add('d-none');
                            if (chainActivitiesSelect) chainActivitiesSelect.innerHTML = '<option value="">— اختر نشاطاً —</option>';
                            if (chainActivitiesSection) chainActivitiesSection.classList.add('d-none');
                            return;
                        }

                        fetch(new URL("{{ route('tasks.chain_projects') }}", window.location.origin) + '?value_chain_id=' + encodeURIComponent(vcId), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                            .then(r => r.json())
                            .then(projects => {
                                if (chainProjectsSelect) chainProjectsSelect.innerHTML = '<option value="">— اختر مشروعاً من السلسلة —</option>';
                                if (projects && projects.length) {
                                    projects.forEach(p => {
                                        const opt = document.createElement('option');
                                        opt.value = p.name;
                                        opt.textContent = p.name;
                                        if (chainProjectsSelect) chainProjectsSelect.appendChild(opt);
                                    });
                                    if (oldChainProject && chainProjectsSelect) {
                                        chainProjectsSelect.value = oldChainProject;
                                    }
                                    if (chainProjectsSection) chainProjectsSection.classList.remove('d-none');
                                    if (typeof window.$ !== 'undefined' && typeof window.$.fn.select2 !== 'undefined' && chainProjectsSelect) {
                                        try { $(chainProjectsSelect).select2('destroy'); } catch (e) { }
                                        $(chainProjectsSelect).select2({ placeholder: '— اختر مشروعاً من السلسلة —', width: '100%' });
                                    }
                                    if (oldChainProject && chainProjectsSelect) {
                                        try { $(chainProjectsSelect).trigger('change'); } catch (e) { chainProjectsSelect.dispatchEvent(new Event('change')); }
                                    }
                                } else {
                                    if (chainProjectsSection) chainProjectsSection.classList.add('d-none');
                                    if (chainActivitiesSection) chainActivitiesSection.classList.add('d-none');
                                }
                            })
                            .catch(err => { console.error('Chain projects load failed', err); });
                    });
                }

                // ---- Assignment Type logic ----
                const assignmentTypeRadios = document.querySelectorAll('input[name="assignment_type"]');
                const userContainer = document.getElementById('user_assignment_container');
                const entityContainer = document.getElementById('entity_assignment_container');
                const assignedEntitySelect = document.getElementById('assigned_entity_id');

                function toggleAssignmentType() {
                    const selectedType = document.querySelector('input[name="assignment_type"]:checked').value;
                    if (selectedType === 'user') {
                        userContainer.classList.remove('d-none');
                        entityContainer.classList.add('d-none');
                    } else {
                        userContainer.classList.add('d-none');
                        entityContainer.classList.remove('d-none');
                        loadOrganizations();
                    }
                }

                assignmentTypeRadios.forEach(radio => {
                    radio.addEventListener('change', toggleAssignmentType);
                });

                function loadOrganizations() {
                    const projectId = projectSelect.value;
                    const valueChainId = valueChainSelect.value;

                    const checkedType = document.querySelector('input[name="assignment_type"]:checked');
                    if (!checkedType || checkedType.value !== 'entity') {
                        return;
                    }

                    const url = new URL("{{ route('api.tasks.organizations') }}", window.location.origin);
                    if (projectId) url.searchParams.append('project_id', projectId);
                    if (valueChainId) url.searchParams.append('value_chain_id', valueChainId);

                    fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(r => r.json())
                        .then(groups => {
                            const oldVal = "{{ old('assigned_entity_id') }}";
                            assignedEntitySelect.innerHTML = '<option value="">— اختر الجهة —</option>';

                            // groups is an object keyed by group label, each value is an array of {id, name}
                            Object.keys(groups).forEach(groupLabel => {
                                const items = groups[groupLabel];
                                if (items && items.length > 0) {
                                    const optgroup = document.createElement('optgroup');
                                    optgroup.label = groupLabel;
                                    items.forEach(item => {
                                        const opt = document.createElement('option');
                                        opt.value = item.id;
                                        opt.textContent = item.name;
                                        if (oldVal && oldVal == item.id) {
                                            opt.selected = true;
                                        }
                                        optgroup.appendChild(opt);
                                    });
                                    assignedEntitySelect.appendChild(optgroup);
                                }
                            });
                            // Reinitialize Select2 for search if available
                            if (typeof window.$ !== 'undefined' && typeof window.$.fn !== 'undefined' && typeof window.$.fn.select2 !== 'undefined') {
                                try { $(assignedEntitySelect).select2('destroy'); } catch (e) { }
                                $(assignedEntitySelect).select2({
                                    placeholder: '— اختر الجهة —',
                                    width: '100%'
                                });
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
                    .then(payload => {
                        const users = Array.isArray(payload) ? payload : (payload.users || []);
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

                if (activitySelect) {
                    $(activitySelect).on('change', function () {
                        const selectedOption = this.options[this.selectedIndex];
                        const type = selectedOption?.dataset?.type || '';
                        if (activityTypeInput) activityTypeInput.value = type;
                        loadProcedures(this.value, type);
                    });
                }

                // When a chain project is selected, load activities for it
                $(document).on('change', '#chain_project_name', function () {
                    const projectName = this.value;
                    const vcId = valueChainSelect.value;
                    const chainActivitiesSelect = document.getElementById('chain_project_activity');
                    const chainActivitiesSection = document.getElementById('chain-project-activities-section');

                    chainActivitiesSelect.innerHTML = '<option value="">— اختر نشاطاً —</option>';
                    if (!vcId || !projectName) {
                        chainActivitiesSection.classList.add('d-none');
                        return;
                    }

                    const url = new URL("{{ route('tasks.chain_project_activities') }}", window.location.origin);
                    url.searchParams.append('value_chain_id', vcId);
                    url.searchParams.append('project_name', projectName);

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(acts => {
                            if (acts && acts.length) {
                                acts.forEach(a => {
                                    const opt = document.createElement('option');
                                    opt.value = a.name;
                                    opt.textContent = a.name;
                                    chainActivitiesSelect.appendChild(opt);
                                });
                                // Preselect old activity if present
                                if (oldChainActivity) {
                                    chainActivitiesSelect.value = oldChainActivity;
                                }
                                chainActivitiesSection.classList.remove('d-none');
                                if (typeof window.$ !== 'undefined' && typeof window.$.fn.select2 !== 'undefined') {
                                    try { $(chainActivitiesSelect).select2('destroy'); } catch (e) { }
                                    $(chainActivitiesSelect).select2({ placeholder: '— اختر نشاطاً —', width: '100%' });
                                }
                            } else {
                                chainActivitiesSection.classList.add('d-none');
                            }
                        })
                        .catch(console.error);
                });

                // ---- On page load: restore state ----
                applyScope(taskScopeSelect.value);
                toggleAssignmentType();

                // If a value chain is already selected (old input / edit), trigger loading of chain projects
                if (valueChainSelect && valueChainSelect.value) {
                    try { $(valueChainSelect).trigger('change'); } catch (e) { valueChainSelect.dispatchEvent(new Event('change')); }
                }

                if (toggleCheckbox && toggleCheckbox.checked && activitiesWrapper && !activitiesWrapper.classList.contains('d-none')) {
                    if (toggleCard) toggleCard.classList.add('active');
                    if (projectSelect && projectSelect.value) {
                        loadActivities(projectSelect.value);
                    }
                }
            })();
        </script>
    @endpush
@endsection
