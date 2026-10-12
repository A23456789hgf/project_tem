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
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> سيتم إعادة تعيين كلمة المرور إلى القيمة الافتراضية: <strong>123456</strong>
                    </div>

                    @can('users.reset-password')
                    <form action="{{ route('users.updatePassword', $user) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="user_id" class="form-label">معرف المستخدم</label>
                            <input type="text" class="form-control" value="{{ $user->user_id }}" disabled>
                        </div>

                        <input type="hidden" name="password" value="123456">
                        <input type="hidden" name="password_confirmation" value="123456">

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="fas fa-undo"></i> إعادة تعيين للافتراضي (123456)
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
