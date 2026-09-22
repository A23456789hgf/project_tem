@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/project-tables.css') }}">
@endsection

@section('content')
<div class="container py-4" dir="rtl">

    {{-- الهيدر الرئيسي للملف --}}
    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
        <div>
            <h5 class="mb-1 text-dark fw-bold">إضافة مستخدم جديد</h5>
            <p class="text-muted small mb-0">أدخل بيانات المستخدم الجديد مباشرة في النموذج أدناه.</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary px-3">
            <i class="fas fa-arrow-right ms-1"></i> العودة للقائمة
        </a>
    </div>

    @can('users.create')
    <form action="{{ route('users.store') }}" method="POST" class="row g-3 needs-validation" novalidate>
    @endcan
        @csrf

        @if ($errors->any())
            <div class="col-12">
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- الاسم الكامل --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small mb-1">الاسم الكامل <span class="text-danger">*</span></label>
            <input type="text" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" placeholder="مثلاً: محمد علي" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- العمل / الوظيفة --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small mb-1">العمل</label>
            <input type="text" name="work" class="form-control"
                   value="{{ old('work') }}" placeholder="مثلاً: مهندس نظم">
        </div>

        {{-- الدور / الصلاحية --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small mb-1">الدور / الصلاحية <span class="text-danger">*</span></label>
            <select name="role_id" class="form-select search-select" required>
                <option value="">-- اختر الدور --</option>
                @foreach($roles as $id => $name)
                    <option value="{{ $id }}" @selected(old('role_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- نوع المستخدم (الجهة) --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small mb-1">نوع المستخدم <span class="text-danger">*</span></label>
            <select name="organization_type" id="organization_type" class="form-select @error('organization_type') is-invalid @enderror" required>
                <option value="internal" @selected(old('organization_type', 'internal') == 'internal')>داخلي (الوزارة / الجهات التابعة)</option>
                <option value="external" @selected(old('organization_type') == 'external')>خارجي (جهة خارجية)</option>
            </select>
            @error('organization_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- الجهة الداخلية --}}
        <div class="col-md-6" id="internal_entity_container" style="{{ old('organization_type', 'internal') === 'external' ? 'display: none;' : '' }}">
            <label class="form-label fw-semibold text-muted small mb-1">الجهة الداخلية <span class="text-danger">*</span></label>
            <select name="entity_id" id="entity_id" class="form-select search-select @error('entity_id') is-invalid @enderror" {{ old('organization_type', 'internal') === 'external' ? 'disabled' : '' }}>
                <option value="">-- اختر الجهة الداخلية --</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}" @selected(old('entity_id') == $entity->id)>{{ $entity->name }}</option>
                @endforeach
            </select>
            @error('entity_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- الجهة الخارجية --}}
        <div class="col-md-6" id="external_authority_container" style="{{ old('organization_type', 'internal') === 'external' ? '' : 'display: none;' }}">
            <label class="form-label fw-semibold text-muted small mb-1">الجهة الخارجية <span class="text-danger">*</span></label>
            <select name="authority_id" id="authority_id" class="form-select search-select @error('authority_id') is-invalid @enderror" {{ old('organization_type', 'internal') === 'external' ? '' : 'disabled' }}>
                <option value="">-- اختر الجهة الخارجية --</option>
                @foreach($authorities as $authority)
                    <option value="{{ $authority->id }}" @selected(old('authority_id') == $authority->id)>{{ $authority->agency_name }}</option>
                @endforeach
            </select>
            @error('authority_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- مسؤولية الموافقات --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small mb-1">مسؤولية الموافقات</label>
            <select name="responsibility" id="responsibility" class="form-select @error('responsibility') is-invalid @enderror">
                <option value="">-- بدون مسؤولية في دورة الموافقات --</option>
                @foreach(\App\Enums\UserResponsibilityType::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('responsibility') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('responsibility') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text text-muted">يُستخدم لتحديد دور المستخدم في مراحل الموافقات (فنية / مالية / اعتماد).</div>
        </div>


        {{-- رقم الهاتف --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small mb-1">رقم الهاتف <span class="text-danger">*</span></label>
            <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone') }}" placeholder="777xxxxxx" required>
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- حالة الحساب السريع --}}
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch p-0 m-0 d-flex align-items-center pb-2">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="status" value="Active" id="statusSwitch" checked style="cursor: pointer;">
                <label class="form-check-label fw-semibold text-dark small mb-0" for="statusSwitch" style="cursor: pointer;">الحساب نشط وجاهز للاستخدام</label>
            </div>
        </div>

        {{-- صندوق كلمة المرور الافتراضية الأنيق --}}
        <div class="col-12">
            <div class="card border bg-light-subtle rounded-3 p-3 my-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-primary px-3 py-2 fs-6 font-monospace" style="letter-spacing: 1px;">123456</span>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark small">تعيين كلمة مرور افتراضية تلقائياً</h6>
                        <small class="text-muted">سيُطالب النظام المستخدم بتغيير كلمة المرور الإلزامية هذه عند أول عملية تسجيل دخول له.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- النطاق الجغرافي --}}
        <div class="col-12 my-3">
            @include('user.partials.geographic_management_table', [
                'user' => null,
                'geographicScopes' => []
            ])
        </div>

        {{-- أزرار التحكم السفلية المتناسقة --}}
        <div class="col-12 d-flex align-items-center justify-content-end gap-2 mt-4">
            <button type="reset" class="btn btn-light border px-4 fw-semibold small text-secondary">
                تفريغ الحقول
            </button>
            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                <i class="fas fa-save me-1"></i> حفظ وإضافة المستخدم
            </button>
        </div>

    </form>
</div>

<style>
    /* تنسيقات إضافية لضمان الأناقة والمظهر النظيف */
    .form-control, .form-select {
        border-color: #e2e8f0;
        font-size: 0.9rem;
        padding-top: 0.45rem;
        padding-bottom: 0.45rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #a8b2fc;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background-color: #fff;
    }
    .btn {
        font-size: 0.875rem;
        border-radius: 0.375rem;
    }
    .form-switch {
        padding-right: 0 !important;
    }
</style>
@endsection

@section('scripts')
    <script>
    $(document).ready(function () {
        function initSelect2(element) {
            if ($.fn.select2) {
                $(element).select2({
                    dir: "rtl",
                    theme: "bootstrap-5",
                    width: '100%'
                });
            }
        }

        // Initialize general select2 elements that are currently visible
        $('.search-select:visible').each(function() {
            initSelect2(this);
        });

        function toggleOrganizationFields(isInitial) {
            var type = $('#organization_type').val();
            if (type === 'external') {
                $('#internal_entity_container').hide();
                $('#entity_id').prop('disabled', true);

                $('#external_authority_container').show();
                $('#authority_id').prop('disabled', false);
                initSelect2('#authority_id');
            } else {
                $('#external_authority_container').hide();
                $('#authority_id').prop('disabled', true);

                $('#internal_entity_container').show();
                $('#entity_id').prop('disabled', false);
                initSelect2('#entity_id');
            }
        }

        $('#organization_type').on('change', function() {
            toggleOrganizationFields(false);
        });

        toggleOrganizationFields(true);
    });
    </script>
@endsection