@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/project-tables.css') }}">
@endsection

@section('content')
    <div class="container py-5">

        {{-- Header --}}
        <div class="mb-5 border-bottom pb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="display-6 fw-bold text-dark mb-2">تعديل بيانات المستخدم</h1>
                <p class="text-muted">تحديث بيانات المستخدم: {{ $user->name }}</p>
            </div>

            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left ms-1"></i> العودة
            </a>
        </div>

        {{-- Errors --}}
        

        @can('users.edit')
        <form action="{{ route('users.update', $user) }}" method="POST" class="row g-4">
        @endcan
            @csrf
            @method('PUT')

            {{-- ID --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">معرف المستخدم</label>
                <input type="text" class="form-control bg-light" value="{{ $user->user_id }}" disabled>
            </div>

            {{-- الاسم --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">الاسم الكامل *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $user->name) }}" required>
            </div>

            {{-- العمل --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">العمل</label>
                <input type="text" name="work" class="form-control" value="{{ old('work', $user->work) }}">
            </div>

            {{-- الهاتف --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">رقم الهاتف *</label>
                <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" required>
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- البريد الإلكتروني --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">البريد الإلكتروني <span class="text-danger" id="email_required_marker" style="{{ old('is_financial_user', $user->is_financial_user) ? '' : 'display: none;' }}">*</span></label>
                <input type="email" name="email" id="user_email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" placeholder="user@example.com">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text text-muted small">مطلوب عند تفعيل المستخدم المالي (Financial User) للمزامنة مع ERPNext.</div>
            </div>

            {{-- الدور --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">الدور *</label>
                <select name="role_id" class="form-select select2-search" required>
                    <option value="">اختر دور</option>
                    @foreach($roles as $id => $name)
                        <option value="{{ $id }}" @selected(old('role_id', $user->role_id) == $id)>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- نوع المستخدم (الجهة) --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">نوع المستخدم <span class="text-danger">*</span></label>
                <select name="organization_type" id="organization_type" class="form-select @error('organization_type') is-invalid @enderror" required>
                    <option value="internal" @selected(old('organization_type', $user->organization_type ?? 'internal') == 'internal')>داخلي (الوزارة / الجهات التابعة)</option>
                    <option value="external" @selected(old('organization_type', $user->organization_type) == 'external')>خارجي (جهة خارجية)</option>
                </select>
                @error('organization_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            @php
                $currentOrgType = old('organization_type', $user->organization_type ?? 'internal');
            @endphp

            {{-- الجهة الداخلية --}}
            <div class="col-md-6" id="internal_entity_container" style="{{ $currentOrgType === 'external' ? 'display: none;' : '' }}">
                <label class="form-label fw-bold">الجهة الداخلية <span class="text-danger">*</span></label>
                <select name="entity_id" id="entity_id" class="form-select select2-search @error('entity_id') is-invalid @enderror" {{ $currentOrgType === 'external' ? 'disabled' : '' }}>
                    <option value="">-- اختر الجهة --</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id }}" @selected(old('entity_id', $user->entity_id) == $entity->id)>
                            {{ $entity->name }}
                        </option>
                    @endforeach
                </select>
                @error('entity_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- الجهة الخارجية --}}
            <div class="col-md-6" id="external_authority_container" style="{{ $currentOrgType === 'external' ? '' : 'display: none;' }}">
                <label class="form-label fw-bold">الجهة الخارجية <span class="text-danger">*</span></label>
                <select name="authority_id" id="authority_id" class="form-select select2-search @error('authority_id') is-invalid @enderror" {{ $currentOrgType === 'external' ? '' : 'disabled' }}>
                    <option value="">-- اختر الجهة الخارجية --</option>
                    @foreach($authorities as $authority)
                        <option value="{{ $authority->id }}" @selected(old('authority_id', $user->authority_id) == $authority->id)>
                            {{ $authority->agency_name }}
                        </option>
                    @endforeach
                </select>
                @error('authority_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- حالة الحساب --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">حالة الحساب *</label>
                <select name="status" class="form-select select2-search" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $user->status) == $status)>
                            {{ $status === 'Active' ? 'نشط' : 'معطل' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- مستخدم مالي (Financial User) --}}
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch p-0 m-0 d-flex align-items-center pb-2">
                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_financial_user" value="1" id="financialUserSwitch" @checked(old('is_financial_user', $user->is_financial_user)) style="cursor: pointer;">
                    <label class="form-check-label fw-bold text-dark mb-0" for="financialUserSwitch" style="cursor: pointer;">
                        Financial User (مستخدم مالي)
                    </label>
                </div>
            </div>

            {{-- مسؤولية الموافقات --}}
            <div class="col-md-6">
                <label class="form-label fw-bold">مسؤولية الموافقات</label>
                <select name="responsibility" class="form-select select2-search @error('responsibility') is-invalid @enderror">
                    <option value="">-- بدون مسؤولية في دورة الموافقات --</option>
                    @foreach(\App\Enums\UserResponsibilityType::options() as $val => $label)
                        <option value="{{ $val }}" @selected(old('responsibility', $user->responsibility?->value) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('responsibility') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text text-muted">يُستخدم لتحديد دور المستخدم في مراحل الموافقات (فنية / مالية / اعتماد).</div>
            </div>


            {{-- النطاق الجغرافي --}}
            <div class="col-12">
                @include('user.partials.geographic_management_table', [
                    'user' => $user,
                    'geographicScopes' => $geographicScopes
                ])
            </div>

            @if($user->erpnext_user_id || $user->is_financial_user)
            {{-- بطاقة معلومات مزامنة ERPNext --}}
            <div class="col-12">
                <div class="card border bg-light-subtle rounded-3 p-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-sync-alt text-primary fa-lg"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">مزامنة ERPNext</h6>
                                <small class="text-muted">
                                    معرف ERPNext: <span class="fw-semibold text-dark">{{ $user->erpnext_user_id ?: 'غير متصل بعد' }}</span>
                                    @if($user->erpnext_synced_at)
                                        | آخر مزامنة: {{ $user->erpnext_synced_at->format('Y-m-d H:i') }}
                                    @endif
                                </small>
                            </div>
                        </div>
                        <div>
                            @if($user->erpnext_sync_status === 'synced')
                                <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> متزامن</span>
                            @elseif($user->erpnext_sync_status === 'pending')
                                <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-hourglass-half me-1"></i> قيد المزامنة</span>
                            @elseif($user->erpnext_sync_status === 'failed')
                                <span class="badge bg-danger px-3 py-2"><i class="fas fa-exclamation-triangle me-1"></i> فشل المزامنة</span>
                            @endif
                        </div>
                    </div>
                    @if($user->erpnext_sync_error)
                        <div class="alert alert-danger mt-2 mb-0 py-1 px-2 small">
                            <strong>خطأ المزامنة الأخير:</strong> {{ $user->erpnext_sync_error }}
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- الأزرار --}}
            <div class="col-12 d-flex gap-3">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    حفظ التغييرات
                </button>

                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-lg px-4">
                    إلغاء
                </a>
            </div>

        </form>

    </div>
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
        $('.select2-search:visible').each(function() {
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

        $('#financialUserSwitch').on('change', function() {
            if ($(this).is(':checked')) {
                $('#email_required_marker').show();
            } else {
                $('#email_required_marker').hide();
            }
        });
    });
    </script>
@endsection