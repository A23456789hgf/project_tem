@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-map me-2"></i>تفاصيل العزلة: {{ $subArea->name }}
                </h5>
                <div class="d-flex gap-2">
                    @can('sub-areas.edit')
                    <a href="{{ route('sub-areas.edit', $subArea->id) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-edit me-1"></i>تعديل
                    </a>
                    @endcan
                    <a href="{{ route('sub-areas.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row">
                <!-- معلومات العزلة -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات العزلة</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold text-muted">اسم العزلة:</td>
                                    <td>{{ $subArea->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">الحالة:</td>
                                    <td>
                                        @if($subArea->is_active)
                                            <span class="badge bg-success">نشط</span>
                                        @else
                                            <span class="badge bg-danger">غير نشط</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">تاريخ الإنشاء:</td>
                                    <td>{{ $subArea->created_at ? $subArea->created_at->format('Y-m-d H:i:s') : 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">آخر تحديث:</td>
                                    <td>{{ $subArea->updated_at ? $subArea->updated_at->format('Y-m-d H:i:s') : 'غير محدد' }}</td>
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
                                        @if($subArea->governorate)
                                            <span class="badge bg-primary">{{ $subArea->governorate->name }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">المديرية:</td>
                                    <td>
                                        @if($subArea->directorate)
                                            <span class="badge bg-info">{{ $subArea->directorate->name }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">المسار الكامل:</td>
                                    <td class="small">
                                        @if($subArea->governorate && $subArea->directorate)
                                            {{ $subArea->governorate->name }} 
                                            <i class="fas fa-chevron-left mx-1"></i>
                                            {{ $subArea->directorate->name }}
                                            <i class="fas fa-chevron-left mx-1"></i>
                                            <strong>{{ $subArea->name }}</strong>
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
            
            <!-- القرى التابعة -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-village me-2"></i>القرى التابعة لهذه العزلة</h6>
                        </div>
                        <div class="card-body">
                            @php
                                $villages = \App\Models\Village::where('sub_area_id', $subArea->id)->get();
                            @endphp
                            
                            @if($villages->count() > 0)
                                <div class="row">
                                    @foreach($villages as $village)
                                        <div class="col-md-4 mb-2">
                                            <div class="card border-success">
                                                <div class="card-body py-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span>{{ $village->name }}</span>
                                                        @if($village->is_active)
                                                            <span class="badge bg-success">نشط</span>
                                                        @else
                                                            <span class="badge bg-danger">غير نشط</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <span class="badge bg-info">إجمالي القرى: {{ $villages->count() }}</span>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    لا توجد قرى مسجلة في هذه العزلة حتى الآن.
                                </div>
                            @endif
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
                                @can('sub-areas.edit')
                                <a href="{{ route('sub-areas.edit', $subArea->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>تعديل البيانات
                                </a>
                                @endcan
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-1"></i>حذف العزلة
                                </button>
                                @can('villages.create')
                                <a href="{{ route('villages.create') }}?sub_area_id={{ $subArea->id }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i>إضافة قرية جديدة
                                </a>
                                @endcan
                                <a href="{{ route('sub-areas.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-list me-1"></i>عرض جميع العزل
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
                <p>هل أنت متأكد من حذف العزلة <strong>{{ $subArea->name }}</strong>؟</p>
                @if($villages->count() > 0)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        تحذير: هذه العزلة تحتوي على {{ $villages->count() }} قرية. حذف العزلة سيؤثر على هذه القرى.
                    </div>
                @endif
                <p class="text-danger small">تحذير: هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                @can('sub-areas.delete')
                <form action="{{ route('sub-areas.destroy', $subArea->id) }}" method="POST" class="d-inline" onsubmit="return confirmAction(this, 'هل أنت متأكد من عملية الحذف؟')">
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