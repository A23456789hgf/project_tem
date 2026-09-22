@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-primary fw-bold">مراحل المراجعة والاعتماد</h4>
            <small class="text-muted">إدارة مراحل الموافقات والمسارات</small>
        </div>
        @can('stages.create')
        <a href="{{ route('admin.entity-stages.create', ['tab' => $tab]) }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus me-1"></i> إضافة إعدادات جديدة
        </a>
        @endcan
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4 border-bottom border-primary">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'internal' ? 'active fw-bold border-primary border-bottom-0' : 'text-muted' }}" href="{{ route('admin.entity-stages.index', ['tab' => 'internal']) }}">
                الجهات الداخلية
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'external' ? 'active fw-bold border-primary border-bottom-0' : 'text-muted' }}" href="{{ route('admin.entity-stages.index', ['tab' => 'external']) }}">
                الجهات الخارجية والمسارات
            </a>
        </li>
    </ul>

    <!-- Filter Form -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.entity-stages.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">فلترة بالجهة</label>
                    @if($tab === 'internal')
                    <select name="entity_id" class="form-select select2-search" onchange="this.form.submit()">
                        <option value="">جميع الجهات الداخلية</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" @selected(request('entity_id') == $entity->id)>
                                {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>
                    @else
                    <select name="authority_id" class="form-select select2-search" onchange="this.form.submit()">
                        <option value="">جميع الجهات الخارجية</option>
                        @foreach($authorities as $authority)
                            <option value="{{ $authority->id }}" @selected(request('authority_id') == $authority->id)>
                                {{ $authority->name }}
                            </option>
                        @endforeach
                    </select>
                    @endif
                </div>
                <div class="col-md-2">
                    @if(request('entity_id') || request('authority_id'))
                        <a href="{{ route('admin.entity-stages.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary w-100">إلغاء الفلتر</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4"><i class="fas fa-check-circle me-1"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4"><i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4"><i class="fas fa-exclamation-triangle me-1"></i> {{ session('warning') }}</div>
    @endif

    <!-- Stages Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 px-4">الجهة</th>
                            <th class="py-3 px-4">المرحلة</th>
                            <th class="py-3 px-4 text-center">الترتيب</th>
                            <th class="py-3 px-4">المسؤول</th>
                            <th class="py-3 px-4 text-center">الحالة</th>
                            <th class="py-3 px-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stages as $stage)
                            <tr>
                                <td class="px-4 fw-semibold">{{ $tab === 'internal' ? $stage->entity->name : $stage->authority->name }}</td>
                                <td class="px-4">
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                                        {{ $stage->stageEnum()?->label() ?? $stage->stage }}
                                    </span>
                                </td>
                                <td class="px-4 text-center">
                                    <span class="badge bg-secondary rounded-circle" style="width: 25px; height: 25px; line-height: 18px;">
                                        {{ $stage->stage_order }}
                                    </span>
                                </td>
                                <td class="px-4">
                                    @if($stage->hasValidResponsibleUser())
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                {{ mb_substr($stage->responsibleUser->name, 0, 1) }}
                                            </div>
                                            <span class="fw-semibold">{{ $stage->responsibleUser->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-danger bg-danger bg-opacity-10 px-3 py-1 rounded-pill small fw-semibold">غير معين</span>
                                    @endif
                                </td>
                                <td class="px-4 text-center">
                                    @can('stages.edit')
                                    <form action="{{ route('admin.entity-stages.toggle', ['stage' => $stage->id, 'tab' => $tab]) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <div class="form-check form-switch d-flex justify-content-center m-0">
                                            <input class="form-check-input fs-5" style="cursor:pointer;" type="checkbox" role="switch" onchange="if(confirm('هل أنت متأكد من تغيير حالة هذه المرحلة؟')) { this.form.submit(); } else { this.checked = !this.checked; }" {{ $stage->is_active ? 'checked' : '' }} title="{{ $stage->is_active ? 'إيقاف المرحلة' : 'تفعيل المرحلة' }}">
                                        </div>
                                    </form>
                                    @endcan
                                </td>
                                <td class="px-4 text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @can('stages.edit')
                                        <a href="{{ route('admin.entity-stages.edit', ['entity' => $tab === 'internal' ? $stage->entity_id : $stage->authority_id, 'tab' => $tab]) }}" class="btn btn-sm btn-outline-primary" title="تعديل الإعدادات">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                        @can('stages.delete')
                                        <form action="{{ route('admin.entity-stages.destroy', ['entity' => $stage->id, 'tab' => $tab]) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه المرحلة؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف المرحلة">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h5>لا توجد سجلات مراحل مضافة</h5>
                                    <p>لم يتم العثور على أي مراحل مضافة، يمكنك إضافة مراحل جديدة الآن.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($stages->hasPages())
                <div class="p-4 border-top">
                    {{ $stages->links() }}
                </div>
            @endif
        </div>
    </div>

    @if($tab === 'external')
    <h5 class="mt-5 mb-3 fw-bold text-primary">المسارات المعدة للجهات الخارجية</h5>
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 px-4">الجهة الخارجية</th>
                            <th class="py-3 px-4">يوجه إلى</th>
                            <th class="py-3 px-4">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($routes ?? [] as $route)
                            <tr>
                                <td class="px-4 fw-semibold">{{ $route->authority->name }}</td>
                                <td class="px-4">
                                    @if($route->destination_type === 'ministry')
                                        <span class="badge bg-info text-dark"><i class="fas fa-building"></i> الوزارة (Ministry Root)</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="fas fa-arrow-left"></i> جهة خارجية: {{ $route->destinationAuthority?->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4">
                                    {!! $route->is_active ? '<span class="text-success"><i class="fas fa-check-circle"></i> مفعل</span>' : '<span class="text-danger"><i class="fas fa-times-circle"></i> متوقف</span>' !!}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">لا توجد مسارات مضافة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2-search').select2({
            dir: "rtl",
            theme: "bootstrap-5",
            width: '100%'
        });
    });
</script>
@endsection
