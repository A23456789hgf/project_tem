@php
    // ✅ حماية: إن لم يُمرَّر $letterhead من المستدعي، أنشئ كائنًا فارغًا لتجنّب Undefined variable
    $letterhead = $letterhead ?? new \App\Models\OrganizationLetterhead();

    $right      = data_get($letterhead->header_right, 'text', '');
    $center     = data_get($letterhead->header_center, 'text', '');
    $left       = data_get($letterhead->header_left, 'text', '');
    $footer     = data_get($letterhead->footer_settings, 'text', '');
    $fontFamily = data_get($letterhead->typography_settings, 'font_family', 'Arial');
    $fontSize   = (int) data_get($letterhead->typography_settings, 'font_size', 14);
@endphp

<div class="letterhead-preview bg-white border rounded p-3"
     dir="rtl"
     style="font-family: {{ $fontFamily }}; font-size: {{ $fontSize }}px;">

    <div class="row align-items-start border-bottom pb-3 mb-3">
        <div class="col-4 text-end">{{ $right }}</div>
        <div class="col-4 text-center fw-bold">{{ $center ?: $letterhead->name }}</div>
        <div class="col-4 text-start">{{ $left }}</div>
    </div>

    <div style="min-height: 180px" class="text-muted d-flex align-items-center justify-content-center">
        معاينة محتوى المستند
    </div>

    @if($footer)
        <div class="border-top pt-2 mt-3 text-center small">{{ $footer }}</div>
    @endif
</div>