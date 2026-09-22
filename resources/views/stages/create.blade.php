@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">إضافة مرحلة جديدة</h1>

    @can('stages.create')
    <form action="{{ route('stages.store') }}" method="POST">
    @endcan
        @csrf

        <div class="mb-3">
            <label for="name_ar" class="form-label">اسم المرحلة (عربي)</label>
            <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar') }}" required>
        </div>

        <div class="mb-3">
            <label for="code" class="form-label">كود المرحلة</label>
            <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
        </div>

        <div class="mb-3">
            <label for="parent_id" class="form-label">المرحلة الأب</label>
            <select name="parent_id" class="form-select">
                <option value="">-- لا يوجد --</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                        {{ $parent->name_ar }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="order" class="form-label">ترتيب العرض</label>
            <input type="number" name="order" class="form-control" value="{{ old('order', 0) }}" required>
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">نوع المرحلة</label>
            <select name="type" class="form-select" required>
                <option value="assessment">تقييم</option>
                <option value="approval" selected>موافقة</option>
                <option value="implementation">تنفيذ</option>
                <option value="execution">إجراء</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">حفظ المرحلة</button>
    </form>
</div>
@endsection
