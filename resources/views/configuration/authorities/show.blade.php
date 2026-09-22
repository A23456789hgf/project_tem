@extends('layouts.app')

@section('title', 'عرض الجهة')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">عرض الجهة: {{ $authority->agency_name }}</h6>
                    <a href="{{ session('authorities_index_url', route('authorities.index')) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> العودة للقائمة
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="text-end">اسم الجهة:</th>
                                    <td>{{ $authority->agency_name }}</td>
                                </tr>
                                <tr>
                                    <th class="text-end">المحافظة:</th>
                                    <td>{{ $authority->governorate ? $authority->governorate->name : 'المركز الرئيسي' }}</td>
                                </tr>
                                @if($authority->directorate)
                                <tr>
                                    <th class="text-end">المديرية:</th>
                                    <td>{{ $authority->directorate->name }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th class="text-end">نوع الجهة:</th>
                                    <td>{{ $authority->typeEntity ? $authority->typeEntity->name : 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-end">الحالة:</th>
                                    <td>
                                        <span class="badge badge-{{ $authority->is_active ? 'success' : 'danger' }}">
                                            {{ $authority->is_active ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-end">جهة ممولة:</th>
                                    <td>
                                        <span class="badge badge-{{ $authority->is_funded ? 'success' : 'secondary' }}">
                                            {{ $authority->is_funded ? 'ممولة' : 'غير ممولة' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-end">تاريخ الإنشاء:</th>
                                    <td>{{ $authority->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th class="text-end">تاريخ آخر تحديث:</th>
                                    <td>{{ $authority->updated_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($authority->parent)
                                <h5>الجهة الأب</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <th class="text-end">اسم الجهة:</th>
                                        <td>{{ $authority->parent->agency_name }}</td>
                                    </tr>
                                    {{-- <tr>
                                        <th class="text-end">اسم الأب:</th>
                                        <td>{{ $authority->parent->father_name ?? 'غير محدد' }}</td>
                                    </tr> --}}
                                    <tr>
                                        <th class="text-end">الحالة:</th>
                                        <td>
                                            <span class="badge badge-{{ $authority->parent->is_active ? 'success' : 'danger' }}">
                                                {{ $authority->parent->is_active ? 'نشط' : 'غير نشط' }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </div>
                    </div>

                    @if($authority->children->count() > 0)
                        <h5 class="mt-4">الجهات الفرعية</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>اسم الجهة</th>
                                        {{-- <th>اسم الأب</th> --}}
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($authority->children as $child)
                                        <tr>
                                            <td>{{ $child->agency_name }}</td>
                                            {{-- <td>{{ $child->father_name ?? 'غير محدد' }}</td> --}}
                                            <td>
                                                <span class="badge badge-{{ $child->is_active ? 'success' : 'danger' }}">
                                                    {{ $child->is_active ? 'نشط' : 'غير نشط' }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('authorities.show', $child->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('authorities.edit')
                                                <a href="{{ route('authorities.edit', $child->id) }}" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-4">
                        @can('authorities.edit')
                        <a href="{{ route('authorities.edit', $authority->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        @endcan
                        @can('authorities.delete')
                        <form action="{{ route('authorities.destroy', $authority->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger"
                                    onclick="return confirmAction(this, 'هل أنت متأكد من حذف هذه الجهة؟')">
                                <i class="fas fa-trash"></i> حذف
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection