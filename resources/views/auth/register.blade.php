@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-8 col-lg-6">
            <div class="custom-card">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-user-plus fa-3x" style="color: var(--primary-dark);"></i>
                        </div>
                        <h2 class="card-title mb-2" style="color: var(--primary-dark); font-weight: 700;">تسجيل حساب جديد</h2>
                        <p class="text-muted">إنشاء حساب جديد في نظام إدارة المشاريع</p>
                    </div>

                    

                    @can('auth.register')
                    <form action="{{ route('auth.register') }}" method="POST">
                    @endcan
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label fw-bold">
                                    <i class="fas fa-user me-1"></i> اسم المستخدم *
                                </label>
                                <input type="text" class="form-control form-control-lg @error('username') is-invalid @enderror"
                                       id="username" name="username" value="{{ old('username') }}" required
                                       style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px 15px;">
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-bold">
                                    <i class="fas fa-signature me-1"></i> الاسم الكامل *
                                </label>
                                <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required
                                       style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px 15px;">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope me-1"></i> البريد الإلكتروني *
                            </label>
                            <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}" required
                                   style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px 15px;">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-bold">
                                    <i class="fas fa-key me-1"></i> كلمة المرور *
                                </label>
                                <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror"
                                       id="password" name="password" required
                                       style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px 15px;">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label fw-bold">
                                    <i class="fas fa-key me-1"></i> تأكيد كلمة المرور *
                                </label>
                                <input type="password" class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror"
                                       id="password_confirmation" name="password_confirmation" required
                                       style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px 15px;">
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label fw-bold">
                                    <i class="fas fa-phone me-1"></i> رقم الهاتف *
                                </label>
                                <input type="text" class="form-control form-control-lg @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone') }}" required
                                       style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px 15px;">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label fw-bold">
                                    <i class="fas fa-building me-1"></i> القسم
                                </label>
                                <input type="text" class="form-control form-control-lg"
                                       id="department" name="department" value="{{ old('department') }}"
                                       style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px 15px;">
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg" style="height: 50px; font-weight: 600;">
                                <i class="fas fa-user-plus me-2"></i> إنشاء الحساب
                            </button>
                        </div>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">
                            <small class="text-muted">لديك حساب بالفعل؟</small>
                            <a href="{{ route('login') }}" class="text-decoration-none fw-bold ms-1" style="color: var(--primary-dark);">
                                تسجيل الدخول
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');

    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('كلمة المرور غير متطابقة');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }

    password.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
});
</script>
@endsection
