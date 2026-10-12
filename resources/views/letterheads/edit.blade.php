@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" dir="rtl">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">تعديل الترويسة</h5>
                <div class="text-muted small">{{ $letterhead->name }}</div>
            </div>
            <a href="{{ route('letterheads.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>

        <div class="card-body">
            <form action="{{ route('letterheads.update', $letterhead) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('letterheads._form')

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('letterheads.index') }}" class="btn btn-light">إلغاء</a>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
