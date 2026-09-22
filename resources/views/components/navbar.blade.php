<!-- الشريط العلوي -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid d-flex align-items-center justify-content-between">

        <!-- القسم الأيسر: الشعار والاسم -->
        <div class="d-flex align-items-center gap-3">
            <button class="menu-toggle" id="sidebarToggle" style="background: none; border: none; color: #c9a961;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                    style="width:18px;height:18px;fill:currentColor;" aria-hidden="true">
                    <path
                        d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z" />
                </svg>
            </button>
            <a class="navbar-brand mb-0 h1 d-flex align-items-center gap-2" href="{{ route('dashboard') }}"
                style="font-size: 1.1rem;">
                <!-- تم تصغير الشعار ليتناسب مع التصميم الحديث -->
                <img src="{{ asset('/images/logo.png') }}" alt="الشعار" class="navbar-logo" width="32" height="32"
                    style="object-fit: contain;" />
                <span class="brand-text" style="font-size: 1rem;">وزارة الزراعة والثروة السمكية والموارد المائية</span>
            </a>
        </div>

        <!-- القسم الأيمن: الإشعارات والملف الشخصي -->
        <div class="d-flex align-items-center gap-3">

            @can('notifications.view')
                <div class="notifications-wrapper" style="font-size: 0.9rem;">
                    @include('components.notifications')
                </div>
            @endcan

            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle p-0 text-decoration-none border-0" type="button"
                    id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: #fff !important;">
                    <div class="navbar-profile-avatar bg-white d-flex align-items-center justify-content-center"
                        style="color: #1e3a5f; pointer-events: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                            style="width:14px;height:14px;fill:#1e3a5f;flex-shrink:0;pointer-events: none;" aria-hidden="true">
                            <path
                                d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z" />
                        </svg>
                    </div>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 py-1" aria-labelledby="userDropdown"
                    style="font-size: 0.9rem;">
                    @can('profile.view')
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-1"
                                href="{{ route('profile.show') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512"
                                    style="width:14px;height:14px;fill:#1e3a5f;flex-shrink:0;" aria-hidden="true">
                                    <path
                                        d="M248 8C111 8 0 119 0 256s111 248 248 248 248-111 248-248S385 8 248 8zm0 96c48.6 0 88 39.4 88 88s-39.4 88-88 88-88-39.4-88-88 39.4-88 88-88zm0 344c-58.7 0-111.3-26.6-146.5-68.2 18.8-35.4 55.6-59.8 98.5-59.8 2.4 0 4.8.4 7.1 1.1 13 4.2 26.6 6.9 40.9 6.9 14.3 0 28-2.7 40.9-6.9 2.3-.7 4.7-1.1 7.1-1.1 42.9 0 79.7 24.4 98.5 59.8C359.3 421.4 306.7 448 248 448z" />
                                </svg>
                                <span style="color: #1e3a5f;">الملف الشخصي</span>
                            </a>
                        </li>
                    @endcan
                    <li>
                        <hr class="dropdown-divider my-1">
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-1 text-danger"
                            href="javascript:void(0)" onclick="confirmLogout()">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
                                style="width:16px;height:16px;fill:#dc3545;flex-shrink:0;" aria-hidden="true">
                                <path
                                    d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z" />
                            </svg>
                            <span>تسجيل الخروج</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
    /* تصغير الشعار وجعله متجاوباً */
    .navbar-logo {
        height: 32px !important;
        width: 32px !important;
        object-fit: contain;
        transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
        .navbar-logo {
            height: 28px !important;
            width: 28px !important;
        }
    }

    @media (max-width: 480px) {
        .navbar-logo {
            height: 24px !important;
            width: 24px !important;
        }
    }

    /* تنسيق اسم الوزارة بشكل احترافي */
    .brand-text {
        font-family: 'Cairo', sans-serif;
        font-weight: 300;
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    }

    /* تصغير وتعديل لون أيقونة الملف الشخصي */
    .navbar-profile-avatar {
        width: 28px !important;
        height: 28px !important;
        border-radius: 50% !important;
        color: #1e3a5f !important;
        /* اللون الكحلي */
        background-color: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.3s ease;
    }

    /* تصغير وتعديل لون أيقونة الإشعارات */
    .notification-trigger {
        background: #ffffff !important;
        width: 28px !important;
        height: 28px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        border: none !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .notification-trigger:hover {
        background: #eef2f7 !important;
        transform: scale(1.05);
    }

    /* أيقونات الإشعارات - مضمنة كـ SVG */
</style>

<!-- سكريبت SweetAlert (local) -->
<script src="{{ asset('js/libs/sweetalert2.all.min.js') }}"></script>
@can('logout')
<form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
@endcan

<script>
    function confirmLogout() {
        Swal.fire({
            title: 'هل تود تسجيل الخروج؟',
            text: 'سيتم إنهاء جلستك الحالية بأمان',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-sign-out-alt me-1"></i> نعم، خروج',
            cancelButtonText: '<i class="fas fa-times me-1"></i> إلغاء',
            customClass: {
                confirmButton: 'btn btn-primary px-4 fw-bold',
                cancelButton: 'btn btn-light text-dark px-4 border'
            },
            buttonsStyling: true,
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logout-form').submit();
            }
        });
    }
</script>

<!-- طبقة التعتيم للجوال -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- القائمة الجانبية -->
<div class="sidebar-container" id="sidebar">
    <div class="sidebar-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">القائمة الجانبية</h5>
        <button type="button" class="btn-close btn-close-white d-lg-none" id="mobileMenuClose"
            aria-label="إغلاق"></button>
    </div>

    <div class="sidebar-content">
        <ul class="nav flex-column">
            @php
                $user = auth()->user();
            @endphp

            @can('main_modules.dashboard')
                <li class="nav-item auth-perm-dashboard-sidebar" data-sidebar-module="dashboard">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <x-icon name="tachometer-alt" /> الصفحة الرئيسية
                    </a>
                </li>
            @endcan

            @canany(['projects.sidebar', 'projects-implementation.sidebar', 'quality.sidebar', 'execution.sidebar'])
                <li class="nav-item auth-perm-projects-sidebar auth-perm-execution-sidebar auth-perm-quality-sidebar"
                    data-sidebar-module="projects">
                    @php
                        $isProjectsActive = request()->routeIs('projects.*')
                            || request()->routeIs('execution.*')
                            || request()->routeIs('projects.quality.*')
                            || request()->routeIs('approvals.*')
                            || request()->routeIs('consultations.*')
                            || request()->routeIs('project-referrals.*');
                    @endphp

                    <a class="nav-link d-flex justify-content-between align-items-center {{ $isProjectsActive ? 'active' : '' }}"
                        data-bs-toggle="collapse" href="#submenu-projects" role="button"
                        aria-expanded="{{ $isProjectsActive ? 'true' : 'false' }}" aria-controls="submenu-projects">

                        <span>
                            <x-icon name="project-diagram" /> مشروع
                        </span>

                        <x-icon name="chevron-down" class="small" />
                    </a>

                    <div class="collapse ps-3 {{ $isProjectsActive ? 'show' : '' }}" id="submenu-projects">
                        <ul class="nav flex-column">
                            @can('projects.sidebar')
                                <li class="nav-item auth-perm-projects-sidebar">
                                    <a class="nav-link {{ request()->routeIs('projects.index') ? 'active' : '' }}"
                                        href="{{ route('projects.index') }}">
                                        قائمة المشاريع
                                    </a>
                                </li>
                                <li class="nav-item auth-perm-projects-sidebar">
                                    <a class="nav-link {{ request()->routeIs('projects.budgets.index') ? 'active' : '' }}"
                                        href="{{ route('projects.budgets.index') }}">
                                        الموازنات
                                    </a>
                                </li>
                                <li class="nav-item auth-perm-projects-sidebar">
                                    <a class="nav-link {{ request()->routeIs('projects.achievements.all') ? 'active' : '' }}"
                                        href="{{ route('projects.achievements.all') }}">
                                        إنجازات المشاريع السابقة
                                    </a>
                                </li>
                                <li class="nav-item auth-perm-approvals-sidebar">
                                    <a class="nav-link {{ request()->routeIs('approvals.*') || request()->routeIs('projects.approval.*') ? 'active' : '' }}"
                                        href="{{ route('approvals.index') }}">
                                        <i class="fas fa-clipboard-check me-2"></i> مركز المراجعة والاعتمادات
                                    </a>
                                </li>
                                <li class="nav-item auth-perm-referrals-sidebar">
                                    <a class="nav-link {{ request()->routeIs('consultations.*') || request()->routeIs('project-referrals.*') ? 'active' : '' }}"
                                        href="{{ route('consultations.index') }}">
                                        <i class="fas fa-comments me-2"></i> الاستشارات والإحالات
                                    </a>
                                </li>
                            @endcan

                            @can('projects-implementation.sidebar')
                                <li class="nav-item auth-perm-projects-implementation-sidebar">
                                    <a class="nav-link {{ request()->routeIs('projects.implementation.index') ? 'active' : '' }}"
                                        href="{{ route('projects.implementation.index') }}">
                                        المشاريع المنفذة
                                    </a>
                                </li>
                            @endcan

                            @can('quality.sidebar')
                                <li class="nav-item auth-perm-quality-sidebar">
                                    <a class="nav-link {{ request()->routeIs('projects.quality.*') ? 'active' : '' }}"
                                        href="{{ route('projects.quality.index') }}">
                                        <x-icon name="chart-line" class="me-2" />جودة المشاريع
                                    </a>
                                </li>
                            @endcan

                            @can('execution.sidebar')
                                <li class="nav-item auth-perm-execution-sidebar">
                                    <a class="nav-link {{ request()->routeIs('execution.tracking') ? 'active' : '' }}"
                                        href="{{ route('execution.tracking') }}">
                                        <x-icon name="tasks" class="me-2" />متابعة التنفيذ
                                    </a>
                                </li>
                            @endcan

                        </ul>
                    </div>
                </li>
            @endcanany



            @can('main_modules.tasks')
                <li class="nav-item auth-perm-tasks-sidebar" data-sidebar-module="tasks">
                    <a class="nav-link {{ request()->routeIs('tasks.*') || request()->routeIs('projects.tasks.*') ? 'active' : '' }}"
                        href="{{ route('tasks.index') }}">
                        <x-icon name="clipboard-list" /> إدارة المهام
                    </a>
                </li>
            @endcan

            <!-- @can('main_modules.requests-descend')
                <li class="nav-item auth-perm-requests_descend-sidebar" data-sidebar-module="requests_descend">
                    <a class="nav-link {{ request()->routeIs('requests_descend.*') ? 'active' : '' }}"
                        href="{{ route('requests_descend.index') }}">
                        <x-icon name="arrow-down" /> إدارة النزول
                    </a>
                </li>
            @endcan -->

            @can('main_modules.correspondence')
            <li class="nav-item auth-perm-referrals-sidebar auth-perm-correspondence-sidebar auth-perm-memoirs-sidebar"
                data-sidebar-module="correspondence">
                @php
                    $isReferralsActive = request()->routeIs('project-referrals.*')
                        || request()->routeIs('referrals.*')
                        || request()->routeIs('correspondence.*')
                        || request()->routeIs('memoirs.*');
                @endphp

                <a class="nav-link d-flex justify-content-between align-items-center {{ $isReferralsActive ? 'active' : '' }}"
                    data-bs-toggle="collapse" href="#submenu-referrals" role="button"
                    aria-expanded="{{ $isReferralsActive ? 'true' : 'false' }}" aria-controls="submenu-referrals">

                    <span>
                        <x-icon name="exchange-alt" /> إدارة الإحالات
                    </span>

                    <x-icon name="chevron-down" class="small" />
                </a>

                <div class="collapse ps-3 {{ $isReferralsActive ? 'show' : '' }}" id="submenu-referrals">
                    <ul class="nav flex-column">
                        @can('referrals.sidebar')
                            <li class="nav-item auth-perm-referrals-sidebar">
                                <a class="nav-link {{ request()->routeIs('project-referrals.index') ? 'active' : '' }}"
                                    href="{{ route('project-referrals.index') }}">
                                    إحالات المشاريع
                                </a>
                            </li>
                        @endcan

                        @can('correspondence.sidebar')
                            <li class="nav-item auth-perm-correspondence-sidebar">
                                <a class="nav-link {{ request()->routeIs('correspondence.index') ? 'active' : '' }}"
                                    href="{{ route('correspondence.index') }}">
                                    لوحة متابعة الإحالات والمراسلات
                                </a>
                            </li>
                        @endcan

                        @can('memoirs.sidebar')
                            <li class="nav-item auth-perm-memoirs-sidebar">
                                <a class="nav-link {{ request()->routeIs('memoirs.index') ? 'active' : '' }}"
                                    href="{{ route('memoirs.index') }}">
                                    <x-icon name="file-signature" class="me-1" /> المذكرات الإدارية
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
            @endcanany

            @can('main_modules.planning')
                <li class="nav-item auth-perm-plans-sidebar" data-sidebar-module="planning">
                    @php
                        $isPlanningActive = request()->routeIs('plans.*');
                    @endphp

                    <a class="nav-link d-flex justify-content-between align-items-center {{ $isPlanningActive ? 'active' : '' }}"
                        data-bs-toggle="collapse" href="#submenu-planning" role="button"
                        aria-expanded="{{ $isPlanningActive ? 'true' : 'false' }}" aria-controls="submenu-planning">
                        <span>
                            <x-icon name="paste" /> إدارة التخطيط
                        </span>
                        <x-icon name="chevron-down" class="small" />
                    </a>

                    <div class="collapse ps-3 {{ $isPlanningActive ? 'show' : '' }}" id="submenu-planning">
                        <ul class="nav flex-column">
                            @can('plans.sidebar-index')
                                <li class="nav-item auth-perm-plans-index">
                                    <a class="nav-link {{ request()->routeIs('plans.index') ? 'active' : '' }}"
                                        href="{{ route('plans.index') }}">
                                        الخطط والمشاريع
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>
                </li>
            @endcan

            @can('main_modules.reports')
                <li class="nav-item auth-perm-reports-sidebar" data-sidebar-module="reports">
                    @php
                        $isReportsActive = request()->routeIs('projects.reports.*');
                    @endphp

                    <a class="nav-link d-flex justify-content-between align-items-center {{ $isReportsActive ? 'active' : '' }}"
                        data-bs-toggle="collapse" href="#submenu-reports" role="button"
                        aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}" aria-controls="submenu-reports">
                        <span>
                            <x-icon name="chart-pie" /> إدارة التقارير
                        </span>
                        <x-icon name="chevron-down" class="small" />
                    </a>

                    <div class="collapse ps-3 {{ $isReportsActive ? 'show' : '' }}" id="submenu-reports">
                        <ul class="nav flex-column">
                            @can('reports.sidebar-index')
                                <li class="nav-item auth-perm-reports-index">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.index') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.index') }}">
                                        لوحة معلومات التقارير
                                    </a>
                                </li>
                            @endcan

                            @can('reports.overview.view')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.overview') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.overview') }}">
                                        النظرة الشاملة
                                    </a>
                                </li>
                            @endcan

                            @can('reports.status.view')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.status') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.status') }}">
                                        تقرير الحالة
                                    </a>
                                </li>
                            @endcan

                            @can('reports.progress.view')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.progress') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.progress') }}">
                                        تقرير التقدم الزمني
                                    </a>
                                </li>
                            @endcan

                            @can('reports.implementation.view')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.implementation') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.implementation') }}">
                                        تقرير الإنجاز والتنفيذ
                                    </a>
                                </li>
                            @endcan

                            @can('reports.financial.view')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.financial') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.financial') }}">
                                        التقرير المالي للمشاريع
                                    </a>
                                </li>
                            @endcan

                            @can('reports.financial_erpnext.view')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.financial_erpnext') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.financial_erpnext') }}">
                                        التقرير المالي الموحد (ERPNext)
                                    </a>
                                </li>
                            @endcan
                            
                            @can('reports.profit_and_loss.view')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.profit_and_loss') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.profit_and_loss') }}">
                                        قائمة الأرباح والخسائر
                                    </a>
                                </li>
                            @endcan
                            
                            @can('reports.pl_expense_summary.view')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.pl_expense_summary') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.pl_expense_summary') }}">
                                        ملخص الإيرادات والنفقات
                                    </a>
                                </li>
                            @endcan

                            @can('reports.quality.view')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.reports.quality') ? 'active' : '' }}"
                                        href="{{ route('projects.reports.quality') }}">
                                        تقرير الجودة
                                    </a>
                                </li>
                            @endcan
                         </ul>
                    </div>
                </li>
            @endcan

            @can('main_modules.empowerment')
                <li class="nav-item auth-perm-empowerment-sidebar" data-sidebar-module="empowerment">
                    @php
                        $isEmpowermentActive = request()->routeIs('projects.empowerment*');
                    @endphp
                    <a class="nav-link d-flex justify-content-between align-items-center {{ $isEmpowermentActive ? 'active' : '' }}"
                        data-bs-toggle="collapse" href="#submenu-empowerment" role="button"
                        aria-expanded="{{ $isEmpowermentActive ? 'true' : 'false' }}" aria-controls="submenu-empowerment">
                        <span>
                            <x-icon name="hand-holding-usd" /> إدارة التمكين
                        </span>
                        <x-icon name="chevron-down" class="small" />
                    </a>

                    <div class="collapse ps-3 {{ $isEmpowermentActive ? 'show' : '' }}" id="submenu-empowerment">
                        <ul class="nav flex-column">
                            @can('empowerment.sidebar-loans')
                                <li class="nav-item auth-perm-empowerment-loans-view">
                                    <a class="nav-link {{ request()->routeIs('projects.empowerment') ? 'active' : '' }}"
                                        href="{{ route('projects.empowerment') }}">
                                        القروض والحسابات
                                    </a>
                                </li>
                            @endcan

                            @can('empowerment.sidebar-beneficiaries')
                                <li class="nav-item auth-perm-empowerment-beneficiaries-view">
                                    <a class="nav-link {{ request()->routeIs('projects.empowerment.beneficiaries.all') ? 'active' : '' }}"
                                        href="{{ route('projects.empowerment.beneficiaries.all') }}">
                                        مستفيدو القروض
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>
                </li>
            @endcan

            {{-- @can('main_modules.value-chains')
            <li class="nav-item auth-perm-value-chains-sidebar" data-sidebar-module="value-chains">
                @php
                    $isValueChainsActive = request()->routeIs('value-chains.*') || request()->routeIs('value-chain-members.*') || request()->routeIs('global-financings.*') || request()->routeIs('chain_plans.*');
                @endphp
                <a class="nav-link d-flex justify-content-between align-items-center {{ $isValueChainsActive ? 'active' : '' }}"
                    data-bs-toggle="collapse" href="#submenu-value-chains" role="button"
                    aria-expanded="{{ $isValueChainsActive ? 'true' : 'false' }}" aria-controls="submenu-value-chains">
                    <span>
                        <x-icon name="sitemap" /> إدارة سلاسل القيمة
                    </span>
                    <x-icon name="chevron-down" class="small" />
                </a>

                <div class="collapse ps-3 {{ $isValueChainsActive ? 'show' : '' }}" id="submenu-value-chains">
                    <ul class="nav flex-column">
                        @can('value-chains.view')
                            <li class="nav-item auth-perm-value-chains-view">
                                <a class="nav-link {{ request()->routeIs('value-chains.index') ? 'active' : '' }}"
                                    href="{{ route('value-chains.index') }}">
                                    سلاسل القيمة
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('chain_plans.*') ? 'active' : '' }}"
                                    href="{{ route('chain_plans.index') }}">
                                    خطط السلاسل
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('value-chain-members.*') ? 'active' : '' }}"
                                    href="{{ route('value-chain-members.index') }}">
                                    أعضاء السلاسل
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('global-financings.*') ? 'active' : '' }}"
                                    href="{{ route('global-financings.index') }}">
                                    تمويلات السلاسل
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('value-chains.participating-entities.*') ? 'active' : '' }}"
                                    href="{{ route('value-chains.index') }}">
                                    الجهات المشاركة في السلاسل
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
            @endcanany --}}

            @php
                $encodingPermissions = [
                    'programs.sidebar',
                    'domains.sidebar',
                    'subdomains.sidebar',
                    'interventions.sidebar',
                    'governorates.sidebar',
                    'directorates.sidebar',
                    'sub-areas.sidebar',
                    'villages.sidebar',
                    'financial-items.sidebar',
                    'funding-sources.sidebar',
                    'financing-types.sidebar',
                    'financing-forms.sidebar',
                    'sub-financing-forms.sidebar',
                    'authorities.sidebar',
                    'internal-entities.sidebar',
                    'entity-officers.sidebar',
                    'entity-authorities.sidebar',
                    'priorities.sidebar',
                    'report-types.sidebar',
                    'units.sidebar',
                    'beneficiary-groups.sidebar',
                    'signatures.sidebar',
                    'donors.sidebar',
                    'configuration.sidebar',
                    'entity_officers.view',
                    'entity-authorities.view',
                    'sms.manage',
                    'value-chain-financing-types.view'
                ];
            @endphp

            @can('main_modules.encoding')
            <li class="nav-item auth-perm-configuration-sidebar" data-sidebar-module="encoding">
                @php
                    $encodingRoutes = ['programs.*', 'domains.*', 'subdomains.*', 'interventions.*', 'governorates.*', 'directorates.*', 'sub-areas.*', 'villages.*', 'financial-items.*', 'funding-sources.*', 'financing-types.*', 'value-chain-financing-types.*', 'formfinancing.*', 'subfinancing-forms.*', 'authorities.*', 'main-routers.*', 'sub-routers.*', 'priorities.*', 'report-types.*', 'units.*', 'beneficiary-groups.*', 'configuration.sms.*'];
                    $isEncodingActive = request()->routeIs(...$encodingRoutes);
                @endphp
                <a class="nav-link d-flex justify-content-between align-items-center {{ $isEncodingActive ? 'active' : '' }}"
                    data-bs-toggle="collapse" href="#submenu-encoding" role="button"
                    aria-expanded="{{ $isEncodingActive ? 'true' : 'false' }}" aria-controls="submenu-encoding">
                    <span><x-icon name="cogs" /> الترميزات</span>
                    <x-icon name="chevron-down" class="small" />
                </a>
                <div class="collapse ps-3 {{ $isEncodingActive ? 'show' : '' }}" id="submenu-encoding">
                    <ul class="nav flex-column">
                        @can('programs.sidebar')
                            <li class="nav-item auth-perm-programs-sidebar">
                                <a class="nav-link {{ request()->routeIs('programs.*') ? 'active' : '' }}"
                                    href="{{ route('programs.index') }}">البرامج</a>
                            </li>
                        @endcan

                        @can('domains.sidebar')
                            <li class="nav-item auth-perm-domains-sidebar">
                                <a class="nav-link {{ request()->routeIs('domains.*') ? 'active' : '' }}"
                                    href="{{ route('domains.index') }}">المجالات</a>
                            </li>
                        @endcan

                        @can('subdomains.sidebar')
                            <li class="nav-item auth-perm-subdomains-sidebar">
                                <a class="nav-link {{ request()->routeIs('subdomains.*') ? 'active' : '' }}"
                                    href="{{ route('subdomains.index') }}">المجالات الفرعية</a>
                            </li>
                        @endcan

                        @can('interventions.sidebar')
                            <li class="nav-item auth-perm-interventions-sidebar">
                                <a class="nav-link {{ request()->routeIs('interventions.*') ? 'active' : '' }}"
                                    href="{{ route('interventions.index') }}">أنواع التدخل</a>
                            </li>
                        @endcan

                        @can('governorates.sidebar')
                            <li class="nav-item auth-perm-governorates-sidebar">
                                <a class="nav-link {{ request()->routeIs('governorates.*') ? 'active' : '' }}"
                                    href="{{ route('governorates.index') }}">المحافظة</a>
                            </li>
                        @endcan

                        @can('directorates.sidebar')
                            <li class="nav-item auth-perm-directorates-sidebar">
                                <a class="nav-link {{ request()->routeIs('directorates.*') ? 'active' : '' }}"
                                    href="{{ route('directorates.index') }}">المديرية</a>
                            </li>
                        @endcan

                        @can('sub-areas.sidebar')
                            <li class="nav-item auth-perm-sub-areas-sidebar">
                                <a class="nav-link {{ request()->routeIs('sub-areas.*') ? 'active' : '' }}"
                                    href="{{ route('sub-areas.index') }}">العزلة / المنطقة</a>
                            </li>
                        @endcan

                        @can('villages.sidebar')
                            <li class="nav-item auth-perm-villages-sidebar">
                                <a class="nav-link {{ request()->routeIs('villages.*') ? 'active' : '' }}"
                                    href="{{ route('villages.index') }}">القرية / الحارة</a>
                            </li>
                        @endcan

                        @can('financial-items.sidebar')
                            <li class="nav-item auth-perm-financial-items-sidebar">
                                <a class="nav-link {{ request()->routeIs('financial-items.*') ? 'active' : '' }}"
                                    href="{{ route('financial-items.index') }}">البنود المالية</a>
                            </li>
                        @endcan

                        @can('funding-sources.sidebar')
                            <li class="nav-item auth-perm-funding-sources-sidebar">
                                <a class="nav-link {{ request()->routeIs('funding-sources.*') ? 'active' : '' }}"
                                    href="{{ route('funding-sources.index') }}">مصدر التمويل</a>
                            </li>
                        @endcan

                        @can('financing-types.sidebar')
                            <li class="nav-item auth-perm-financing-types-sidebar">
                                <a class="nav-link {{ request()->routeIs('financing-types.*') ? 'active' : '' }}"
                                    href="{{ route('financing-types.index') }}">نوع التمويل </a>
                            </li>
                        @endcan

                        @can('value-chain-financing-types.view')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('value-chain-financing-types.*') ? 'active' : '' }}"
                                    href="{{ route('value-chain-financing-types.index') }}">
                                    أنواع تمويل السلاسل
                                </a>
                            </li>
                        @endcan

                        @can('financing-forms.sidebar')
                            <li class="nav-item auth-perm-financing-forms-sidebar">
                                <a class="nav-link {{ request()->routeIs('formfinancing.*') ? 'active' : '' }}"
                                    href="{{ route('formfinancing.index') }}">اشكال التمويل </a>
                            </li>
                        @endcan

                        @can('sub-financing-forms.sidebar')
                            <li class="nav-item auth-perm-sub-financing-forms-sidebar">
                                <a class="nav-link {{ request()->routeIs('subfinancing-forms.*') ? 'active' : '' }}"
                                    href="{{ route('subfinancing-forms.index') }}">اشكال التمويل الفرعية </a>
                            </li>
                        @endcan
                        @can('internal-entities.sidebar')
                            <li class="nav-item auth-perm-internal-entities-sidebar">
                                <a class="nav-link {{ request()->routeIs('internal-entities.*') ? 'active' : '' }}"
                                    href="{{ route('internal-entities.index') }}">
                                    الجهات الداخلية
                                </a>
                            </li>
                        @endcan
                        @can('authorities.sidebar')
                            <li class="nav-item auth-perm-authorities-sidebar">
                                <a class="nav-link {{ request()->routeIs('authorities.*') ? 'active' : '' }}"
                                    href="{{ route('authorities.index') }}"> الجهات الخارجية </a>
                            </li>
                        @endcan


                        @can('type-entity.sidebar')
                            <li class="nav-item auth-perm-type-entity-sidebar">
                                <a class="nav-link {{ request()->routeIs('type-entity.*') ? 'active' : '' }}"
                                    href="{{ route('type-entity.index') }}">
                                    أنواع الجهات
                                </a>
                            </li>
                        @endcan

                        @can('entity-officers.sidebar')
                            <li class="nav-item auth-perm-entity-officers-sidebar">
                                <a class="nav-link {{ request()->routeIs('entity-officers.*') ? 'active' : '' }}"
                                    href="{{ route('entity-officers.index') }}">
                                    مسؤولي الجهات
                                </a>
                            </li>
                        @endcan

                        @can('entity-authorities.sidebar')
                            <li class="nav-item auth-perm-entity-authorities-sidebar">
                                <a class="nav-link {{ request()->routeIs('entity-authorities.*') ? 'active' : '' }}"
                                    href="{{ route('entity-authorities.index') }}">
                                    صلاحيات الجهات
                                </a>
                            </li>
                        @endcan

                        @can('priorities.sidebar')
                            <li class="nav-item auth-perm-priorities-sidebar">
                                <a class="nav-link {{ request()->routeIs('priorities.*') ? 'active' : '' }}"
                                    href="{{ route('priorities.index') }}"> الأولويات </a>
                            </li>
                        @endcan

                        @can('report-types.sidebar')
                            <li class="nav-item auth-perm-report-types-sidebar">
                                <a class="nav-link {{ request()->routeIs('report-types.*') ? 'active' : '' }}"
                                    href="{{ route('report-types.index') }}"> أنواع التقارير </a>
                            </li>
                        @endcan

                        @can('units.sidebar')
                            <li class="nav-item auth-perm-units-sidebar">
                                <a class="nav-link {{ request()->routeIs('units.*') ? 'active' : '' }}"
                                    href="{{ route('units.index') }}"> الوحدة </a>
                            </li>
                        @endcan

                        @can('beneficiary-groups.sidebar')
                            <li class="nav-item auth-perm-beneficiary-groups-sidebar">
                                <a class="nav-link {{ request()->routeIs('beneficiary-groups.*') ? 'active' : '' }}"
                                    href="{{ route('beneficiary-groups.index') }}"> الفئات المستفيدة </a>
                            </li>
                        @endcan

                        @can('signatures.sidebar')
                            <li class="nav-item auth-perm-signatures-sidebar">
                                <a class="nav-link {{ request()->routeIs('signatures.*') ? 'active' : '' }}"
                                    href="{{ route('signatures.index') }}">
                                    إدارة التواقيع
                                </a>
                            </li>
                        @endcan

                        @can('sms.manage')
                            <li class="nav-item auth-perm-sms-manage">
                                <a class="nav-link {{ request()->routeIs('configuration.sms.*') ? 'active' : '' }}"
                                    href="{{ route('configuration.sms.logs') }}">
                                    إدارة رسائل SMS
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
            @endcanany

            @can('main_modules.users')
            <li class="nav-item auth-perm-users-sidebar auth-perm-roles-permissions-sidebar auth-perm-audit-logs-sidebar auth-perm-import-logs-sidebar"
                data-sidebar-module="users">
                @php
                    $userManagementRoutes = ['users.*', 'roles.*', 'roles-permissions.*', 'admin.audit-logs.*', 'import-logs.*', 'admin.entity-stages.*'];
                    $isUserManagementActive = request()->routeIs(...$userManagementRoutes);
                @endphp
                <a class="nav-link d-flex justify-content-between align-items-center {{ $isUserManagementActive ? 'active' : '' }}"
                    data-bs-toggle="collapse" href="#submenu-users" role="button"
                    aria-expanded="{{ $isUserManagementActive ? 'true' : 'false' }}" aria-controls="submenu-users">
                    <span><x-icon name="users-cog" /> إدارة المستخدمين</span>
                    <x-icon name="chevron-down" class="small" />
                </a>
                <div class="collapse ps-3 {{ $isUserManagementActive ? 'show' : '' }}" id="submenu-users">
                    <ul class="nav flex-column">
                        @can('users.sidebar')
                            <li class="nav-item auth-perm-users-sidebar">
                                <a class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}"
                                    href="{{ route('users.index') }}">قائمة المستخدمين</a>
                            </li>
                        @endcan

                        @can('internal-entities.sidebar')
                            <li class="nav-item auth-perm-internal-entities-sidebar">
                                <a class="nav-link {{ request()->routeIs('admin.entity-stages.*') ? 'active' : '' }}"
                                    href="{{ route('admin.entity-stages.index') }}">
                                    مراحل المراجعة والاعتماد
                                </a>
                            </li>
                        @endcan

                        @can('roles-permissions.sidebar')
                            <li class="nav-item auth-perm-roles-permissions-sidebar">
                                <a class="nav-link {{ request()->routeIs('roles.index*') ? 'active' : '' }}"
                                    href="{{ route('roles.index') }}">إدارة الأدوار والصلاحيات </a>
                            </li>
                        @endcan

                        @can('audit-logs.view')
                            <li class="nav-item auth-perm-audit-logs-sidebar">
                                <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
                                    href="{{ route('admin.audit-logs.index') }}">سجل الأنشطة</a>
                            </li>
                        @endcan

                        @can('import-logs.sidebar')
                            <li class="nav-item auth-perm-import-logs-sidebar">
                                <a class="nav-link {{ request()->routeIs('import-logs.*') ? 'active' : '' }}"
                                    href="{{ route('import-logs.index') }}">سجل الاستيراد الشامل</a>
                            </li>
                        @endcan

                        @can('permissions-report.view')
                            <li class="nav-item auth-perm-permissions-report">
                                <a class="nav-link {{ request()->routeIs('permissions.report') ? 'active' : '' }}"
                                    href="{{ route('permissions.report') }}">تقرير الصلاحيات</a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
            @endcanany
        </ul>
    </div>
</div>

<style>
    /* تكبير شعار الطير الجمهوري وجعله متجاوباً */
    .navbar-logo {
        height: 50px !important;
        width: 50px !important;
        object-fit: contain;
        transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
        .navbar-logo {
            height: 42px !important;
            width: 42px !important;
        }
    }

    @media (max-width: 480px) {
        .navbar-logo {
            height: 35px !important;
            width: 35px !important;
        }
    }

    /* تنسيق اسم الوزارة بشكل احترافي */
    .brand-text {
        font-family: 'Cairo', sans-serif;
        font-weight: 300;
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    }

    /* تصغير وتعديل لون أيقونة الملف الشخصي */
    .navbar-profile-avatar {
        width: 28px !important;
        height: 28px !important;
        border-radius: 50% !important;
        color: #002147 !important;
        /* اللون الكحلي */
        background-color: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.3s ease;
    }

    /* تصغير وتعديل لون أيقونة الإشعارات */
    .notification-trigger {
        background: #ffffff !important;
        width: 28px !important;
        height: 28px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        border: none !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .notification-trigger:hover {
        background: #eef2f7 !important;
        transform: scale(1.05);
    }

    /* SVG SideBar Styling */
    .sidebar-container .nav-link svg.svg-icon {
        width: 20px;
        height: 20px;
        margin-left: 12px;
        flex-shrink: 0;
        fill: currentColor !important;
        vertical-align: middle;
    }

    .sidebar-container .nav-link .svg-icon-chevron-down {
        width: 12px;
        height: 12px;
        margin-left: 0;
        margin-right: auto;
        transition: transform 0.2s ease;
        order: -1;
    }

    .sidebar-container .nav-link[aria-expanded="true"] .svg-icon-chevron-down {
        transform: rotate(180deg);
    }
</style>