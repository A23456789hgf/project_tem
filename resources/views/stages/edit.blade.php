@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">تعديل المرحلة: {{ $stage->name_ar }}</h1>

    @can('stages.edit')
    <form action="{{ route('stages.update', $stage->id) }}" method="POST">
    @endcan
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name_ar" class="form-label">اسم المرحلة (عربي)</label>
            <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar', $stage->name_ar) }}" required>
        </div>

        <div class="mb-3">
            <label for="code" class="form-label">كود المرحلة</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $stage->code) }}" required>
        </div>

        <div class="mb-3">
            <label for="parent_id" class="form-label">المرحلة الأب</label>
            <select name="parent_id" class="form-select">
                <option value="">-- لا يوجد --</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id', $stage->parent_id) == $parent->id ? 'selected' : '' }}>
                        {{ $parent->name_ar }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="order" class="form-label">ترتيب العرض</label>
            <input type="number" name="order" class="form-control" value="{{ old('order', $stage->order) }}" required>
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">نوع المرحلة</label>
            <select name="type" class="form-select" required>
                <option value="assessment" {{ $stage->type == 'assessment' ? 'selected' : '' }}>تقييم</option>
                <option value="approval" {{ $stage->type == 'approval' ? 'selected' : '' }}>موافقة</option>
                <option value="implementation" {{ $stage->type == 'implementation' ? 'selected' : '' }}>تنفيذ</option>
                <option value="execution" {{ $stage->type == 'execution' ? 'selected' : '' }}>إجراء</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">تحديث المرحلة</button>
    </form>
</div>
@endsection
