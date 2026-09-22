@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" dir="rtl">
    <div class="row">
        <div class="col-12">
            @can('roles.create')
            <form method="POST" action="{{ route('roles.store') }}" id="roleForm">
            @endcan
                @csrf

                {{-- Role Basic Info --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary font-weight-bold">
                            <i class="fas fa-plus-circle me-2"></i>إضافة دور جديد
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label class="form-label font-weight-bold">اسم الدور</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        </div>
                                        <input type="text" name="name"
                                               class="form-control @error('name') is-invalid @enderror"
                                               placeholder="أدخل اسم الدور (مثال: مدير مشروع)"
                                               value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <div class="custom-control custom-switch mt-4">
                                        <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" value="1" checked>
                                        <label class="custom-control-label font-weight-bold" for="is_active">تفعيل الدور فور الإنشاء</label>
                                    </div>
                                    <small class="text-muted d-block mt-2">يمكنك تعطيل الدور لاحقاً من قائمة الأدوار.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label font-weight-bold">الوصف</label>
                            <textarea name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="وصف مختصر لمسؤوليات هذا الدور..."
                                      rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Permissions Matrix --}}
                @include('roles.partials._permissions_matrix', ['submitButtonText' => 'حفظ الدور والصلاحيات'])

                {{-- Form Actions --}}
                <div class="mt-4 text-center">
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary px-5">
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
