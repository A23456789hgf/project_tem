@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 h-100 overflow-auto">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white overflow-hidden">
                <div class="card-body p-4 position-relative">
                    <div style="position: absolute; right: -20px; top: -20px; font-size: 15rem; opacity: 0.1; transform: rotate(-15deg);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="bg-white text-primary p-4 rounded-4 shadow-sm" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-microscope fa-2x"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h2 class="fw-bold mb-1">تقرير مراجعة مصفوفة الصلاحيات الشاملة</h2>
                            <p class="mb-0 opacity-75">مراجعة وتحليل الثغرات في الصلاحيات والعمليات عبر صفحات النظام</p>
                        </div>
                            @if(auth()->user()->can('reports.permissions.print') || auth()->user()->can('permissions.report') || auth()->user()->can('reports.print'))
                            <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn btn-light text-primary fw-bold rounded-pill px-4 shadow-sm auth-perm-reports-permissions-print">
                                <i class="fas fa-print me-2"></i>طباعة التقرير
                            </a>
                            @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary p-3 rounded-4 me-3">
                            <i class="fas fa-key fa-lg"></i>
                        </div>
                        <h6 class="text-secondary fw-bold mb-0">إجمالي الصلاحيات</h6>
                    </div>
                    <h3 class="fw-bold mb-0 text-primary">{{ $totalPermissions }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-danger-subtle text-danger p-3 rounded-4 me-3">
                            <i class="fas fa-exclamation-triangle fa-lg"></i>
                        </div>
                        <h6 class="text-secondary fw-bold mb-0">الثغرات المكتشفة</h6>
                    </div>
                    <h3 class="fw-bold mb-0 text-danger">{{ count($gaps) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success-subtle text-success p-3 rounded-4 me-3">
                            <i class="fas fa-users-cog fa-lg"></i>
                        </div>
                        <h6 class="text-secondary fw-bold mb-0">الأدوار المراجعة</h6>
                    </div>
                    <h3 class="fw-bold mb-0 text-success">{{ $totalRoles }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-dark text-white shadow">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-white text-dark p-3 rounded-4 me-3 shadow-lg">
                            <i class="fas fa-cubes fa-lg"></i>
                        </div>
                        <h6 class="text-white-50 fw-bold mb-0">الوحدات النظامية</h6>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $moduleStats->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- 1. الثغرات (صلاحيات مفقودة) -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white border-0 py-4 px-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">العمليات غير المحمية (صلاحيات مفقودة)</h5>
                            <p class="text-muted small mb-0">تحليل كود النظام: دوال المتحكمات التي لا تملك صلاحيات مقابلة</p>
                        </div>
                        @can('roles-permissions.edit')
                        <form action="{{ route('permissions.auto-register') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="return confirm('هل أنت متأكد من تسجيل كافة الصلاحيات المفقودة آلياً؟')">
                                <i class="fas fa-magic me-2"></i>
                                تسجيل الكل آلياً
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="bg-light-subtle text-secondary small border-0">
                                <tr>
                                    <th class="py-3 px-4 fw-bold">المتحكم</th>
                                    <th class="py-3 px-4 fw-bold">العملية</th>
                                    <th class="py-3 px-4 fw-bold">الاسم المقترح</th>
                                    <th class="py-3 px-4 fw-bold text-center">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody class="border-0">
                                @forelse($gaps as $gap)
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="fw-bold text-primary-emphasis small">
                                            {{ str_replace('App\\Http\\Controllers\\', '', $gap['controller']) }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <code class="px-2 py-1 bg-secondary-subtle rounded text-danger small fw-bold">
                                            {{ $gap['method'] }}()
                                        </code>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="badge bg-info-subtle text-info border border-info-subtle fw-medium">
                                            {{ $gap['suggested_name'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <button class="btn btn-sm btn-icon btn-light rounded-circle shadow-sm">
                                            <i class="fas fa-plus text-primary"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-5 text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-check-circle text-success fa-3x opacity-25"></i>
                                        </div>
                                        <h6 class="text-secondary">لا توجد ثغرات مكتشفة. كود النظام محمي بالكامل.</h6>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. نظرة عامة على الأدوار -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white border-0 py-4 px-4">
                    <h5 class="fw-bold mb-1 text-dark">نظرة عامة على الأدوار</h5>
                    <p class="text-muted small mb-0">حالة الأدوار ونسبة تغطية الصلاحيات لكل دور</p>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush border-0">
                        @foreach($roles as $role)
                        <div class="list-group-item py-3 px-4 border-light d-flex align-items-center justify-content-between hover-bg-light transition-all">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-4 shadow-sm p-3 me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">{{ $role->name }}</h6>
                                    <p class="mb-0 text-muted small">{{ $role->permissions_count }} صلاحية من {{ $totalPermissions }}</p>
                                </div>
                            </div>
                            <div class="text-end" style="width: 120px;">
                                @php $pct = ($totalPermissions > 0) ? ($role->permissions_count / $totalPermissions) * 100 : 0; @endphp
                                <div class="progress rounded-pill bg-light border" style="height: 6px;">
                                    <div class="progress-bar rounded-pill {{ $role->full_access ? 'bg-success' : 'bg-primary' }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="small fw-bold {{ $role->full_access ? 'text-success' : 'text-primary' }} mt-1 d-block">{{ round($pct) }}%</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-4 { border-radius: 1rem !important; }
    .bg-light-subtle { background-color: #f8f9fa !important; }
    .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
    .transition-all { transition: all 0.2s ease-in-out; }
    .hover-bg-light:hover { background-color: #f8fafc; }
</style>
@endsection
