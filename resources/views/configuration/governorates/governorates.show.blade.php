@extends('layouts.app')

@section('content')
<div class="container">
    <h4>المحافظات</h4>

    <form method="GET" class="mb-3">
        <input type="text" name="search" placeholder="بحث..." class="form-control" value="{{ request('search') }}">
    </form>

    <div class="d-flex gap-2 mb-3">
        @can('governorates.create')
        <a href="{{ route('governorates.create') }}" class="btn btn-success">إضافة محافظة</a>
        @endcan
        @can('governorates.import')
        <a href="{{ route('governorates.import') }}" class="btn btn-primary">استيراد محافظات</a>
        @endcan
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>
                    <a href="{{ route('governorates.index', ['sort' => 'name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}">
                        اسم المحافظة
                    </a>
                </th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($governorates as $index => $governorate)
                <tr>
                    <td>{{ $governorates->firstItem() + $index }}</td>
                    <td>{{ $governorate->name }}</td>
                    <td>
                        @can('governorates.edit')
                        <a href="{{ route('governorates.edit', $governorate) }}" class="btn btn-sm btn-warning">تعديل</a>
                        @endcan
                        @can('governorates.delete')
                        <form action="{{ route('governorates.destroy', $governorate) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirmAction(this, 'هل أنت متأكد؟')">حذف</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $governorates->links() }}
</div>
@endsection