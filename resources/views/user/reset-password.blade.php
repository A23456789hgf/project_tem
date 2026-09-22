@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>إعادة تعيين كلمة المرور</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('users.show', $user) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> العودة
            </a>
        </div>
    </div>

    

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">إعادة تعيين كلمة مرور: {{ $user->name }}</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> أدخل كلمة مرور جديدة للمستخدم
                    </div>

                    @can('users.reset-password')
                    <form action="{{ route('users.updatePassword', $user) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="user_id" class="form-label">معرف المستخدم</label>
                            <input type="text" class="form-control" value="{{ $user->user_id }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور الجديدة *</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required minlength="1" maxlength="50">
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">تأكيد كلمة المرور *</label>
                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                   id="password_confirmation" name="password_confirmation" required minlength="1" maxlength="50">
                            @error('password_confirmation')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-check"></i> تحديث كلمة المرور
                                </button>
                                <a href="{{ route('users.show', $user) }}" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                            </div>
                        </div>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
