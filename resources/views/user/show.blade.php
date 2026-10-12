@extends('layouts.app')

@php
    /*
     |--------------------------------------------------------------------------
     | حماية المتغيّرات (Defensive defaults)
     |--------------------------------------------------------------------------
     | يضمن هذا البلوك وجود $organization و $administrativeOrganization
     | حتى لو لم يمرّرهما الـ Controller (أو في حال وجود كاش قديم).
     */
    if (!isset($organization)) {
        $organization = method_exists($user, 'organization')
            ? $user->organization()
            : [
                'type'  => $user->organization_type ?? 'internal',
                'model' => null,
            ];
    }

    if (!isset($administrativeOrganization)) {
        $administrativeOrganization = null;
        if (!empty($user->administrative_scope_id)) {
            $administrativeOrganization = ($user->organization_type === 'external')
                ? \App\Models\Authority::find($user->administrative_scope_id)
                : \App\Models\InternalEntity::find($user->administrative_scope_id);
        }
    }
@endphp

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>بيانات المستخدم: {{ $user->name }}</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">المعلومات الأساسية</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="fw-bold">معرف المستخدم:</label>
                            <p>{{ $user->user_id }}</p>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">الاسم الكامل:</label>
                            <p>{{ $user->name }}</p>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">نوع المستخدم:</label>
                            <p>
                                @if(($organization['type'] ?? null) === 'external')
                                    <span class="badge bg-warning text-dark">خارجي (جهة خارجية)</span>
                                @else
                                    <span class="badge bg-primary">داخلي (الوزارة / الجهات التابعة)</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">الجهة:</label>
                            <p class="fw-semibold text-dark">
                                {{ $organization['model']?->name ?? $organization['model']?->agency_name ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="fw-bold">رقم الهاتف:</label>
                            <p>{{ $user->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">العمل:</label>
                            <p>{{ $user->work ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">مسؤولية الموافقات:</label>
                            <p>
                                @if($user->responsibility)
                                    <span class="badge bg-info text-dark">
                                        {{ $user->responsibility instanceof \App\Enums\UserResponsibilityType
                                            ? $user->responsibility->label()
                                            : (\App\Enums\UserResponsibilityType::tryFrom($user->responsibility)?->label() ?? $user->responsibility) }}
                                    </span>
                                @else
                                    <span class="text-muted">بدون مسؤولية خاصة</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">النطاق الإداري:</label>
                            <p>{{ $administrativeOrganization?->name ?? $administrativeOrganization?->agency_name ?? 'غير محدد' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="fw-bold">المحافظة والمديرية الأساسية:</label>
                            <p>
                                {{ $user->governorate_name ?? $user->governorate?->name ?? 'N/A' }}
                                /
                                {{ $user->directorate_name ?? $user->directorate?->name ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">تصنيف النطاق الجغرافي:</label>
                            <p>
                                @if($user->is_geographic_subset)
                                    <span class="badge" style="background-color: #6f42c1;">
                                        <i class="fas fa-map-marker-alt"></i> مجموعة جغرافية محددة
                                    </span>
                                @else
                                    <span class="badge bg-secondary">نطاق عام</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">معلومات الدور والحالة</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="fw-bold">الدور:</label>
                            <p><span class="badge bg-info fs-6">{{ $user->role_display_name }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">حالة الحساب:</label>
                            <p>
                                @if($user->status === 'Active')
                                    <span class="badge bg-success fs-6">نشط</span>
                                @else
                                    <span class="badge bg-danger fs-6">معطل</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="fw-bold">تصنيف المستخدم المالي (Financial User):</label>
                            <p>
                                @if($user->is_financial_user)
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-coins me-1"></i> مستخدم مالي (Financial User)
                                    </span>
                                @else
                                    <span class="badge bg-secondary fs-6">مستخدم عادي</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">مزامنة ERPNext:</label>
                            <p>
                                @if($user->erpnext_user_id)
                                    <span class="fw-semibold font-monospace">{{ $user->erpnext_user_id }}</span>
                                    @if($user->erpnext_sync_status === 'synced')
                                        <span class="badge bg-success ms-1">متزامن</span>
                                    @elseif($user->erpnext_sync_status === 'failed')
                                        <span class="badge bg-danger ms-1">فشل المزامنة</span>
                                    @else
                                        <span class="badge bg-warning text-dark ms-1">قيد المزامنة</span>
                                    @endif
                                    @if($user->erpnext_synced_at)
                                        <small class="text-muted d-block mt-1">
                                            تاريخ المزامنة: {{ $user->erpnext_synced_at->format('Y-m-d H:i') }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">غير مرتبط بـ ERPNext</span>
                                @endif
                                @if($user->erpnext_sync_error)
                                    <span class="text-danger small d-block mt-1">
                                        <strong>الخطأ:</strong> {{ $user->erpnext_sync_error }}
                                    </span>
                                @endif
                                @if($user->is_financial_user || $user->isLinkedToErpNext())
                                    <form action="{{ route('users.retryErpNextSync', $user) }}" method="POST" class="d-inline-block mt-2">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-sync-alt me-1"></i> إعادة محاولة المزامنة مع ERPNext
                                        </button>
                                    </form>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            @if($user->geographicScopes && $user->geographicScopes->count() > 0)
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-map-marked-alt"></i> النطاقات الجغرافية الإضافية</h5>
                </div>
                <div class="card-body">
                    @php
                        $groupedScopes = $user->geographicScopes->groupBy('governorate_id');
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>المحافظة</th>
                                    <th>المديريات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupedScopes as $govId => $scopes)
                                    @php
                                        $governorate = $scopes->first()->governorate;
                                        $directorates = $scopes->filter(fn($s) => $s->directorate_id != null)
                                                              ->map(fn($s) => $s->directorate);
                                    @endphp
                                    <tr>
                                        <td class="fw-bold align-middle" style="width: 30%;">
                                            {{ $governorate ? $governorate->name : 'جميع المحافظات' }}
                                        </td>
                                        <td>
                                            @if($directorates->isEmpty() || $scopes->contains(fn($s) => $s->directorate_id == null))
                                                <span class="badge bg-primary">كافة المديريات</span>
                                            @else
                                                <ul class="mb-0 ps-3">
                                                    @foreach($directorates as $directorate)
                                                        <li>{{ $directorate ? $directorate->name : 'غير محدد' }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">معلومات الإنشاء والتعديل</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="fw-bold">تاريخ الإنشاء:</label>
                            <p>{{ $user->created_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">تاريخ آخر تعديل:</label>
                            <p>{{ $user->updated_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                    </div>
                    @if($user->createdBy)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="fw-bold">تم الإنشاء بواسطة:</label>
                            <p>{{ $user->createdBy->name }}</p>
                        </div>
                    </div>
                    @endif
                    @if($user->updatedBy)
                    <div class="row">
                        <div class="col-md-6">
                            <label class="fw-bold">تم التعديل بواسطة:</label>
                            <p>{{ $user->updatedBy->name }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">الإجراءات</h5>
                </div>
                <div class="card-body">
                    @can('users.view')
                        <a href="{{ route('users.activity-log', $user) }}" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-history"></i> عرض سجل الأنشطة
                        </a>
                    @endcan

                    @if($user->username !== 'root')
                        @can('users.edit')
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-warning w-100 mb-2">
                                <i class="fas fa-edit"></i> تعديل البيانات
                            </a>
                        @endcan
                    @endif
                    @can('users.reset-password')
                        <a href="{{ route('users.resetPassword', $user) }}" class="btn btn-info w-100 mb-2">
                            <i class="fas fa-key"></i> إعادة تعيين كلمة المرور
                        </a>
                    @endcan
                    @if($user->status === 'Active' && $user->id !== auth()->id() && $user->username !== 'root')
                        @can('users.disable')
                            <form action="{{ route('users.disable', $user) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100 mb-2"
                                        onclick="return confirmAction(this, 'هل تريد تعطيل هذا المستخدم؟')">
                                    <i class="fas fa-ban"></i> تعطيل الحساب
                                </button>
                            </form>
                        @endcan
                    @elseif($user->status === 'Disabled' && $user->username !== 'root')
                        @can('users.enable')
                            <form action="{{ route('users.enable', $user) }}" method="POST"
                                  onsubmit="return confirmAction(this, 'هل أنت متأكد من عملية التفعيل؟')">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 mb-2">
                                    <i class="fas fa-check"></i> تفعيل الحساب
                                </button>
                            </form>
                        @endcan
                    @endif

                    @can('users.delete')
                        @if($user->id !== auth()->id() && $user->username !== 'root')
                            <form action="{{ route('users.destroy', $user) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100"
                                        onclick="return confirmAction(this, 'هل تريد حذف هذا المستخدم؟')">
                                    <i class="fas fa-trash"></i> حذف المستخدم
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection