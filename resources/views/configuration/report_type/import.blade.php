@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container-fluid px-5 py-4">
    <div class="page-header-box">
        <h2 class="page-main-title">استيراد أنواع التقارير</h2>
        <p class="text-muted small">يمكنك رفع ملف Excel يحتوي على أسماء أنواع التقارير</p>
        <div class="section-divider"></div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 p-4">

                {{-- Download Template --}}
                <div class="alert alert-info border-0 rounded-3 mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    قبل البدء، قم بتحميل <strong>قالب الاستيراد</strong> لمعرفة التنسيق المطلوب.
                    <a href="{{ route('report-types.download-template') }}" class="btn btn-sm btn-outline-primary ms-3 fw-bold">
                        <i class="fas fa-file-excel me-1"></i> تحميل القالب
                    </a>
                </div>

                @can('reports.view')
                <form action="{{ route('report-types.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label for="file" class="field-label">
                            اختر ملف Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file"
                               class="form-control custom-field @error('file') is-invalid @enderror"
                               name="file" id="file"
                               accept=".xlsx,.xls"
                               required>
                        @error('file')
                        <small class="text-danger mt-1 d-block">{{ $message }}</small>
                        @enderror
                        <small class="text-muted mt-1 d-block">الصيغ المقبولة: .xlsx, .xls</small>
                    </div>

                    <div class="d-flex justify-content-end gap-3 border-top pt-4">
                        <a href="{{ route('report-types.index') }}" class="btn btn-cancel-custom">
                            <i class="fas fa-times me-2"></i> إلغاء
                        </a>
                        <button type="submit" class="btn btn-navy-gold shadow-sm">
                            <i class="fas fa-upload ms-2"></i> رفع وتنفيذ الاستيراد
                        </button>
                    </div>
                </form>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
