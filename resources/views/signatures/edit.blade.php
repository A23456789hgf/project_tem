@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="main-card shadow-lg border-0">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-0" style="color: #001f3f;">
                            <i class="fas fa-edit text-warning me-2"></i> تعديل بيانات التوقيع
                        </h2>
                        <div class="title-line"></div>
                    </div>
                </div>

                @can('signatures.edit')
                <form action="{{ route('signatures.update', $signature) }}" method="POST" enctype="multipart/form-data" class="modern-form">
                @endcan
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-4">
                        <!-- Name -->
                        <div class="col-md-12">
                            <label for="name" class="field-label fw-bold mb-2">الاسم الكامل <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" name="name" id="name" class="form-control custom-field border-start-0 @error('name') is-invalid @enderror" 
                                       placeholder="أدخل الاسم الثلاثي/الرباعي" value="{{ old('name', $signature->name) }}" required>
                            </div>
                            @error('name')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Job Title -->
                        <div class="col-md-12">
                            <label for="job_title" class="field-label fw-bold mb-2">المسمى الوظيفي / الصفة <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-briefcase text-muted"></i></span>
                                <input type="text" name="job_title" id="job_title" class="form-control custom-field border-start-0 @error('job_title') is-invalid @enderror" 
                                       placeholder="مثال: وزير الزراعة والري" value="{{ old('job_title', $signature->job_title) }}" required>
                            </div>
                            @error('job_title')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Display Order -->
                        <div class="col-md-6">
                            <label for="display_order" class="field-label fw-bold mb-2">ترتيب العرض</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-sort-amount-down text-muted"></i></span>
                                <input type="number" name="display_order" id="display_order" class="form-control custom-field border-start-0" 
                                       value="{{ old('display_order', $signature->display_order) }}">
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch p-3 bg-light rounded-3 border w-100 d-flex justify-content-between align-items-center">
                                <label class="form-check-label fw-bold" for="is_active">تفعيل الحساب</label>
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ $signature->is_active ? 'checked' : '' }}>
                            </div>
                        </div>

                        <!-- Signature Image -->
                        <div class="col-md-12">
                            <label for="signature_image" class="field-label fw-bold mb-2">صورة التوقيع</label>
                            
                            @if($signature->signature_path)
                                <div class="mb-3">
                                    <p class="small text-muted mb-2">التوقيع الحالي:</p>
                                    <img src="{{ asset('storage/' . $signature->signature_path) }}" alt="Current Signature" style="max-height: 80px;" class="rounded border p-2 bg-white">
                                </div>
                            @endif

                            <div class="card bg-light border-dashed p-4 text-center">
                                <input type="file" name="signature_image" id="signature_image" class="form-control d-none">
                                <label for="signature_image" class="cursor-pointer mb-0">
                                    <i class="fas fa-sync-alt fa-3x text-muted mb-3"></i>
                                    <p class="mb-1 fw-bold">اضغط لتغيير صورة التوقيع</p>
                                    <p class="small text-muted mb-0">PNG, JPG, JPEG (Max 2MB)</p>
                                </label>
                                <div id="image-preview" class="mt-3 d-none">
                                    <img src="#" alt="Preview" style="max-height: 100px;" class="rounded border shadow-sm p-2 bg-white">
                                </div>
                            </div>
                            @error('signature_image')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-5">
                        <button type="submit" class="btn btn-navy-gold py-3 px-5 fw-bold shadow-sm flex-grow-1">
                            <i class="fas fa-save me-2"></i> تحديث البيانات
                        </button>
                        <a href="{{ route('signatures.index') }}" class="btn btn-outline-secondary py-3 px-4 fw-bold shadow-sm">
                            إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('signature_image').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('image-preview');
                preview.querySelector('img').src = e.target.result;
                preview.classList.remove('d-none');
            }
            reader.readAsDataURL(file);
        }
    });
</script>

<style>
    .border-dashed {
        border-style: dashed !important;
        border-width: 2px !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>
@endsection
