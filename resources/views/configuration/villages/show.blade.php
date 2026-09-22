@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-village me-2"></i>تفاصيل القرية: {{ $village->name }}
                </h5>
                <div class="d-flex gap-2">
                    @can('villages.edit')
                    <a href="{{ route('villages.edit', $village->id) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-edit me-1"></i>تعديل
                    </a>
                    @endcan
                    <a href="{{ route('villages.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row">
                <!-- معلومات القرية -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات القرية</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold text-muted">اسم القرية:</td>
                                    <td>{{ $village->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">الحالة:</td>
                                    <td>
                                        @if($village->is_active)
                                            <span class="badge bg-success">نشط</span>
                                        @else
                                            <span class="badge bg-danger">غير نشط</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">تاريخ الإنشاء:</td>
                                    <td>{{ $village->created_at ? $village->created_at->format('Y-m-d H:i:s') : 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">آخر تحديث:</td>
                                    <td>{{ $village->updated_at ? $village->updated_at->format('Y-m-d H:i:s') : 'غير محدد' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- الموقع الجغرافي -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>الموقع الجغرافي</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold text-muted">المحافظة:</td>
                                    <td>
                                        @if($village->governorate)
                                            <span class="badge bg-primary">{{ $village->governorate->name }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">المديرية:</td>
                                    <td>
                                        @if($village->directorate)
                                            <span class="badge bg-info">{{ $village->directorate->name }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">العزلة/المنطقة:</td>
                                    <td>
                                        @if($village->subArea)
                                            <span class="badge bg-warning text-dark">{{ $village->subArea->name }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">المسار الكامل:</td>
                                    <td class="small">
                                        @if($village->governorate && $village->directorate && $village->subArea)
                                            {{ $village->governorate->name }} 
                                            <i class="fas fa-chevron-left mx-1"></i>
                                            {{ $village->directorate->name }}
                                            <i class="fas fa-chevron-left mx-1"></i>
                                            {{ $village->subArea->name }}
                                            <i class="fas fa-chevron-left mx-1"></i>
                                            <strong>{{ $village->name }}</strong>
                                        @else
                                            <span class="text-muted">بيانات غير مكتملة</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- إجراءات إضافية -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>الإجراءات المتاحة</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-2 flex-wrap">
                                @can('villages.edit')
                                <a href="{{ route('villages.edit', $village->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>تعديل البيانات
                                </a>
                                @endcan
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-1"></i>حذف القرية
                                </button>
                                <a href="{{ route('villages.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-list me-1"></i>عرض جميع القرى
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف القرية <strong>{{ $village->name }}</strong>؟</p>
                <p class="text-danger small">تحذير: هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                @can('villages.delete')
                <form action="{{ route('villages.destroy', $village->id) }}" method="POST" class="d-inline" onsubmit="return confirmAction(this, 'هل أنت متأكد من عملية الحذف؟')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">حذف</button>
                </form>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection