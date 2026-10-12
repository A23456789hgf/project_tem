@php
    $isEdit = isset($letterhead) && $letterhead->exists;
    $selectedType = old('organization_type', $letterhead->organization_type ?? 'internal');
    $selectedOrgId = old('organization_id', $letterhead->organization_id ?? '');

    $safeValue = static function ($value, $key, $default = null) {
        if (is_array($value)) {
            return $value[$key] ?? $default;
        }

        if (is_object($value)) {
            return data_get($value, $key, $default);
        }

        return $default;
    };

    $headerRight = old('header_right', $letterhead->header_right ?? []);
    $headerCenter = old('header_center', $letterhead->header_center ?? []);
    $headerLeft = old('header_left', $letterhead->header_left ?? []);
    $footerSettings = old('footer_settings', $letterhead->footer_settings ?? []);
    $typographySettings = old('typography_settings', $letterhead->typography_settings ?? []);
    $pageSettings = old('page_settings', $letterhead->page_settings ?? []);
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-bold mb-2">يرجى تصحيح الأخطاء التالية:</div>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">اسم الترويسة <span class="text-danger">*</span></label>
        <input
            type="text"
            id="name"
            name="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $letterhead->name ?? '') }}"
            maxlength="255"
            required
        >
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label for="organization_type" class="form-label">نوع الجهة <span class="text-danger">*</span></label>
        <select id="organization_type" name="organization_type" class="form-select @error('organization_type') is-invalid @enderror" required>
            <option value="internal" @selected($selectedType === 'internal')>داخلية</option>
            <option value="external" @selected($selectedType === 'external')>خارجية</option>
            <option value="system_global" @selected($selectedType === 'system_global')>عامة للنظام</option>
        </select>
        @error('organization_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3" id="organization-wrapper">
        <label for="organization_id" class="form-label">الجهة</label>
        <select id="organization_id" name="organization_id" class="form-select @error('organization_id') is-invalid @enderror">
            <option value="">-- اختر الجهة --</option>
            @foreach (($scopedOrganizations ?? collect()) as $organization)
                @php
                    $orgType = data_get($organization, 'type')
                        ?? data_get($organization, 'organization_type')
                        ?? data_get($organization, 'entity_type');

                    $orgId = data_get($organization, 'id')
                        ?? data_get($organization, 'organization_id')
                        ?? data_get($organization, 'entity_id')
                        ?? data_get($organization, 'authority_id');

                    $orgName = data_get($organization, 'name')
                        ?? data_get($organization, 'organization_name')
                        ?? data_get($organization, 'entity_name')
                        ?? data_get($organization, 'agency_name')
                        ?? ('جهة #' . $orgId);

                    if (in_array($orgType, ['Company', 'Department'], true)) {
                        $orgType = 'internal';
                    }
                @endphp

                @if ($orgId)
                    <option
                        value="{{ $orgId }}"
                        data-org-type="{{ $orgType }}"
                        @selected((string) $selectedOrgId === (string) $orgId)
                    >
                        {{ $orgName }}
                    </option>
                @endif
            @endforeach
        </select>
        @error('organization_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">يُترك فارغًا عند اختيار "عامة للنظام".</div>
    </div>

    <div class="col-12"><hr class="my-1"></div>

    <div class="col-md-4">
        <label for="logo_file" class="form-label">الشعار الرئيسي</label>
        <input type="file" id="logo_file" name="logo_file" class="form-control @error('logo_file') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        @error('logo_file') <div class="invalid-feedback">{{ $message }}</div> @enderror

        @if ($isEdit && !empty($letterhead->logo_path))
            <div class="mt-2 d-flex align-items-center gap-2">
                <img src="{{ asset('storage/' . $letterhead->logo_path) }}" alt="الشعار الحالي" style="max-height:55px;max-width:120px;object-fit:contain">
                <label class="form-check-label text-danger">
                    <input type="checkbox" class="form-check-input" name="remove_logo" value="1">
                    حذف الشعار الحالي
                </label>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        <label for="second_logo_file" class="form-label">الشعار الثاني</label>
        <input type="file" id="second_logo_file" name="second_logo_file" class="form-control @error('second_logo_file') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        @error('second_logo_file') <div class="invalid-feedback">{{ $message }}</div> @enderror

        @if ($isEdit && !empty($letterhead->second_logo_path))
            <div class="mt-2 d-flex align-items-center gap-2">
                <img src="{{ asset('storage/' . $letterhead->second_logo_path) }}" alt="الشعار الثاني الحالي" style="max-height:55px;max-width:120px;object-fit:contain">
                <label class="form-check-label text-danger">
                    <input type="checkbox" class="form-check-input" name="remove_second_logo" value="1">
                    حذف الشعار الثاني
                </label>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        <label for="watermark_file" class="form-label">العلامة المائية</label>
        <input type="file" id="watermark_file" name="watermark_file" class="form-control @error('watermark_file') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        @error('watermark_file') <div class="invalid-feedback">{{ $message }}</div> @enderror

        @if ($isEdit && !empty($letterhead->watermark_path))
            <div class="mt-2 d-flex align-items-center gap-2">
                <img src="{{ asset('storage/' . $letterhead->watermark_path) }}" alt="العلامة المائية الحالية" style="max-height:55px;max-width:120px;object-fit:contain">
                <label class="form-check-label text-danger">
                    <input type="checkbox" class="form-check-input" name="remove_watermark" value="1">
                    حذف العلامة المائية
                </label>
            </div>
        @endif
    </div>

    <div class="col-12"><hr class="my-1"></div>

    <div class="col-md-4">
        <label class="form-label">نص يمين الترويسة</label>
        <textarea name="header_right[text]" class="form-control" rows="3">{{ $safeValue($headerRight, 'text', '') }}</textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label">نص وسط الترويسة</label>
        <textarea name="header_center[text]" class="form-control" rows="3">{{ $safeValue($headerCenter, 'text', '') }}</textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label">نص يسار الترويسة</label>
        <textarea name="header_left[text]" class="form-control" rows="3">{{ $safeValue($headerLeft, 'text', '') }}</textarea>
    </div>

    <div class="col-md-6">
        <label class="form-label">نص التذييل</label>
        <textarea name="footer_settings[text]" class="form-control" rows="3">{{ $safeValue($footerSettings, 'text', '') }}</textarea>
    </div>

    <div class="col-md-3">
        <label class="form-label">الخط</label>
        <input type="text" name="typography_settings[font_family]" class="form-control" value="{{ $safeValue($typographySettings, 'font_family', 'Arial') }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">حجم الخط</label>
        <input type="number" min="8" max="72" name="typography_settings[font_size]" class="form-control" value="{{ $safeValue($typographySettings, 'font_size', 14) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">اتجاه الصفحة</label>
        @php($orientation = $safeValue($pageSettings, 'orientation', 'portrait'))
        <select name="page_settings[orientation]" class="form-select">
            <option value="portrait" @selected($orientation === 'portrait')>عمودي</option>
            <option value="landscape" @selected($orientation === 'landscape')>أفقي</option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">الهامش العلوي (mm)</label>
        <input type="number" min="0" max="100" step="0.5" name="page_settings[margin_top]" class="form-control" value="{{ $safeValue($pageSettings, 'margin_top', 15) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">الهامش الأيمن (mm)</label>
        <input type="number" min="0" max="100" step="0.5" name="page_settings[margin_right]" class="form-control" value="{{ $safeValue($pageSettings, 'margin_right', 15) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">الهامش الأيسر (mm)</label>
        <input type="number" min="0" max="100" step="0.5" name="page_settings[margin_left]" class="form-control" value="{{ $safeValue($pageSettings, 'margin_left', 15) }}">
    </div>

    <div class="col-md-4">
        <label for="inheritance_mode" class="form-label">وضع الوراثة</label>
        @php($inheritanceMode = old('inheritance_mode', $letterhead->inheritance_mode ?? 'own'))
        <select id="inheritance_mode" name="inheritance_mode" class="form-select">
            <option value="own" @selected($inheritanceMode === 'own')>ترويسة خاصة</option>
            <option value="inherit" @selected($inheritanceMode === 'inherit')>وراثة من الجهة الأعلى</option>
        </select>
    </div>

    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="is_default" value="0">
            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" @checked(old('is_default', $letterhead->is_default ?? true))>
            <label class="form-check-label" for="is_default">تعيين كترويسة افتراضية</label>
        </div>
    </div>

    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $letterhead->is_active ?? true))>
            <label class="form-check-label" for="is_active">نشطة</label>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('organization_type');
        const orgSelect = document.getElementById('organization_id');
        const orgWrapper = document.getElementById('organization-wrapper');
        if (!typeSelect || !orgSelect || !orgWrapper) return;

        const refreshOrganizations = function () {
            const selectedType = typeSelect.value;
            const isGlobal = selectedType === 'system_global';
            orgWrapper.style.display = isGlobal ? 'none' : '';

            if (isGlobal) {
                orgSelect.value = '';
                orgSelect.disabled = true;
                return;
            }

            orgSelect.disabled = false;
            Array.from(orgSelect.options).forEach(function (option, index) {
                if (index === 0) return;
                const optionType = option.dataset.orgType || '';
                option.hidden = optionType !== '' && optionType !== selectedType;
                option.disabled = option.hidden;
            });

            const selected = orgSelect.options[orgSelect.selectedIndex];
            if (selected && selected.hidden) {
                orgSelect.value = '';
            }
        };

        typeSelect.addEventListener('change', refreshOrganizations);
        refreshOrganizations();
    });
</script>
@endpush
