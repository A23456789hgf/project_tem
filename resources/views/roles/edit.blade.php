@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4" dir="rtl">
        <div class="row">
            <div class="col-12">
                @can('roles.edit')
                <form method="POST" action="{{ route('roles.update', $role) }}" id="roleForm">
                @endcan
                    @csrf
                    @method('PUT')

                    {{-- Role Basic Info --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 text-primary font-weight-bold">
                                <i class="fas fa-edit me-2"></i>تعديل بيانات الدور: {{ $role->name }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="form-label font-weight-bold">اسم الدور</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i
                                                        class="fas fa-user-tag text-primary"></i></span>
                                            </div>
                                            <input type="text" name="name"
                                                class="form-control @error('name') is-invalid @enderror"
                                                value="{{ old('name', $role->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        @if($role->name === 'مدير النظام')
                                            <small class="text-info d-block mt-2"><i class="fas fa-info-circle me-1"></i> لا
                                                ينصح بتغيير اسم دور مدير النظام الأساسي لضمان ثبات الإعدادات.</small>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="form-label font-weight-bold d-block">الحالة</label>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" name="is_active" class="custom-control-input"
                                                id="is_active" value="1" {{ $role->is_active ? 'checked' : '' }} {{ $role->name === 'مدير النظام' ? 'disabled' : '' }}>
                                            <label class="custom-control-label"
                                                for="is_active">{{ $role->is_active ? 'الدور نشط' : 'الدور معطل' }}</label>
                                        </div>
                                        @if($role->name === 'مدير النظام')
                                            <input type="hidden" name="is_active" value="1">
                                            <small class="text-muted d-block mt-2">هذا الدور أساسي ولا يمكن تعطيله.</small>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label font-weight-bold">الوصف</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="3">{{ old('description', $role->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Permissions Matrix --}}
                    @include('roles.partials._permissions_matrix', ['submitButtonText' => 'تحديث الدور والصلاحيات'])

                    {{-- Form Actions --}}
                    <div class="mt-4 text-center">
                        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary px-5">
                            رجوع
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection