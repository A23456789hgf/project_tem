@extends('layouts.app')

@section('title', 'استيراد التمويلات')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">استيراد تمويل المشاريع</h4>
                    <a href="{{ route('projects.financings.import.template') }}" class="btn btn-primary">
                        <i class="fas fa-download"></i> تحميل قالب التمويلات
                    </a>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    
                    <form action="{{ route('projects.financings.import.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="file">اختر ملف الإكسل (Excel/CSV)</label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" required accept=".xlsx, .xls, .csv">
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">رفع ومعاينة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
