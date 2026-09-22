@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container">
    <div class="main-card">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0" style="color: #001f3f;">
                    <i class="fas fa-file-invoice-dollar text-primary me-2"></i> البنود المالية
                </h2>
                <div class="title-line"></div>
            </div>
            
            <div class="d-flex gap-2">
                @can('financial-items.create')
                <a href="{{ route('financial-items.create') }}" class="btn btn-success px-4 rounded-3 fw-bold shadow-sm auth-perm-financial-items-create">
                    <i class="fas fa-plus me-1"></i> إضافة بند جديد
                </a>
                @endcan

                <div class="dropdown">
                    <button class="btn btn-navy-gold dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-database me-1"></i> البيانات
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                        @can('financial-items.export')
                        <li><a class="dropdown-item py-2 auth-perm-financial-items-export" href="{{ route('config.export', ['entity' => 'financial-item']) }}"><i class="fas fa-download text-success me-2"></i> تصدير Excel</a></li>
                        @endcan
                        @can('financial-items.import')
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 auth-perm-financial-items-import" href="{{ route('config.import', ['entity' => 'financial-item']) }}"><i class="fas fa-upload text-info me-2"></i> استيراد ملف</a></li>
                        @endcan
                    </ul>
                </div>
            </div>
        </div>

        

        <!-- Search/Filter Section -->
        <div class="mb-4 p-3 bg-light rounded-3">
            <form action="{{ route('financial-items.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="field-label">البحث</label>
                    <input type="text" id="search" name="search" class="form-control custom-field" 
                           placeholder="ابحث برقم أو اسم البند..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label for="status" class="field-label">تصفية حسب الحالة</label>
                    <select name="status" id="status" class="form-select custom-field" onchange="this.form.submit()">
                        <option value="">جميع الحالات</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>🟡 بانتظار المراجعة</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>🟢 معتمد ونشط</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>🔴 مرفوض</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-navy-gold shadow-sm flex-fill">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                    <a href="{{ route('financial-items.index') }}" class="btn btn-cancel-custom flex-fill">
                        <i class="fas fa-times me-1"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>رقم البند</th>
                        <th class="text-start">اسم البند</th>
                        <th>الحالة</th>
                        <th style="width: 150px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr class="{{ ($item->status == 0 || $item->status === 'pending') ? 'table-warning' : '' }}">
                        <td class="text-muted fw-bold">{{ $item->id }}</td>
                        <td><span class="badge-label">{{ $item->code }}</span></td>
                        <td class="text-name">{{ $item->name }}</td>
                        <td>
                            {!! \App\Services\ReferenceDataApprovalService::renderStatusBadge($item->status) !!}
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                @if($item->status == 0 || $item->status === 'pending')
                                @can('financial-items.approve')
                                <form action="{{ route('financial-items.approve', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="اعتماد وموافق" onclick="return confirm('هل تريد اعتماد هذا البند المالي؟')">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </form>
                                @endcan
                                @can('financial-items.reject')
                                <form action="{{ route('financial-items.reject', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="رفض" onclick="return confirm('هل تريد رفض هذا البند المالي؟')">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </form>
                                @endcan
                                @endif

                                @can('financial-items.edit')
                                <a href="{{ route('financial-items.edit', $item) }}" class="btn-action btn btn-sm btn-outline-warning auth-perm-financial-items-edit" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('financial-items.delete')
                                <form action="{{ route('financial-items.destroy', $item) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-action btn btn-sm btn-outline-danger auth-perm-financial-items-delete" onclick="return confirmAction(this, 'هل تريد الحذف؟')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">لا توجد بيانات متاحة حالياً</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
