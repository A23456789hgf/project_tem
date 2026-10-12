@props([
    'letterhead' => null,
    'documentNumber' => null,
    'documentDate' => null,
    'documentSubject' => null,
    'attachments' => null,
    'qrCodeData' => null,
    'customTitle' => null,
    'customSubtitle' => null,
    'hideBorder' => false,
])

@php
    if (!$letterhead) {
        $letterhead = app(\App\Services\LetterheadAccessService::class)
            ->resolveEffectiveLetterhead(auth()->user(), request('letterhead_id'))
            ->letterhead;
    }

    $right = $letterhead ? $letterhead->getHeaderRightSettings() : [
        'show' => true,
        'text' => "الجمهورية اليمنية\nوزارة الزراعة والثروة السمكية والموارد المائية",
        'font_family' => 'Tajawal',
        'font_size' => 12,
        'font_color' => '#0C5B47',
        'font_weight' => '700',
        'is_italic' => false,
        'text_align' => 'right',
        'line_height' => 1.5,
        'width_percent' => 33,
    ];

    $center = $letterhead ? $letterhead->getHeaderCenterSettings() : [
        'show_logo' => true,
        'logo_position' => 'center',
        'logo_width' => 90,
        'logo_height' => 90,
        'preserve_ratio' => true,
        'show_title' => true,
        'title_text' => 'تقرير رسمي',
        'title_font_family' => 'Tajawal',
        'title_font_size' => 16,
        'title_font_color' => '#0C5B47',
        'title_font_weight' => '800',
        'show_subtitle' => false,
        'subtitle_text' => '',
        'width_percent' => 34,
    ];

    $left = $letterhead ? $letterhead->getHeaderLeftSettings() : [
        'show' => true,
        'text' => "Republic of Yemen\nMinistry of Agriculture",
        'font_family' => 'Tajawal',
        'font_size' => 11,
        'font_color' => '#64748b',
        'font_weight' => '600',
        'is_italic' => false,
        'text_align' => 'left',
        'line_height' => 1.4,
        'width_percent' => 33,
    ];

    $logoUrl = $letterhead ? $letterhead->getLogoUrl() : asset('images/logo.png');
    $pageSettings = $letterhead ? $letterhead->getPageSettings() : [];
    $bottomGap = $pageSettings['header_bottom_margin'] ?? 20;
    $primaryColor = $letterhead ? ($letterhead->getTypographySettings()['primary_color'] ?? '#0C5B47') : '#0C5B47';
@endphp

<div class="official-letterhead-header avoid-break" style="width: 100%; margin-bottom: {{ $bottomGap }}px; direction: rtl;">
    <table style="width: 100%; border-collapse: collapse; border: none; @if(!$hideBorder) border-bottom: 2px solid {{ $primaryColor }}; padding-bottom: 12px; margin-bottom: 15px; @endif">
        <tr>
            <!-- Right Area (RTL: First on the right) -->
            <td style="width: {{ $right['width_percent'] ?? 33 }}%; vertical-align: top; border: none; padding: {{ $right['padding_top'] ?? 0 }}px {{ $right['padding_right'] ?? 0 }}px {{ $right['padding_bottom'] ?? 0 }}px {{ $right['padding_left'] ?? 0 }}px; text-align: {{ $right['text_align'] ?? 'right' }};">
                @if(!empty($right['show']))
                    <div style="font-family: '{{ $right['font_family'] ?? 'Tajawal' }}', sans-serif; font-size: {{ $right['font_size'] ?? 12 }}pt; color: {{ $right['font_color'] ?? '#0C5B47' }}; font-weight: {{ $right['font_weight'] ?? '700' }}; font-style: {{ !empty($right['is_italic']) ? 'italic' : 'normal' }}; line-height: {{ $right['line_height'] ?? 1.5 }}; white-space: pre-line;">
                        {{ $right['text'] ?? '' }}
                    </div>
                @endif
            </td>

            <!-- Center Area: Logo and/or Document Main Title -->
            <td style="width: {{ $center['width_percent'] ?? 34 }}%; vertical-align: middle; border: none; padding: 0 10px; text-align: {{ $center['logo_position'] ?? 'center' }};">
                @if(!empty($center['show_logo']) && $logoUrl)
                    <div style="margin-bottom: 6px;">
                        <img src="{{ $logoUrl }}" 
                             alt="Logo" 
                             style="max-width: {{ $center['logo_width'] ?? 90 }}px; max-height: {{ $center['logo_height'] ?? 90 }}px; {{ !empty($center['preserve_ratio']) ? 'object-fit: contain;' : '' }} display: inline-block;">
                    </div>
                @endif

                @if(!empty($center['show_title']) || $customTitle)
                    <div style="font-family: '{{ $center['title_font_family'] ?? 'Tajawal' }}', sans-serif; font-size: {{ $center['title_font_size'] ?? 16 }}pt; color: {{ $center['title_font_color'] ?? '#0C5B47' }}; font-weight: {{ $center['title_font_weight'] ?? '800' }}; margin: 2px 0;">
                        {{ $customTitle ?? ($center['title_text'] ?? '') }}
                    </div>
                @endif

                @if(!empty($center['show_subtitle']) || $customSubtitle)
                    <div style="font-family: '{{ $center['title_font_family'] ?? 'Tajawal' }}', sans-serif; font-size: {{ ($center['title_font_size'] ?? 16) * 0.75 }}pt; color: {{ $center['title_font_color'] ?? '#64748b' }}; opacity: 0.85;">
                        {{ $customSubtitle ?? ($center['subtitle_text'] ?? '') }}
                    </div>
                @endif
            </td>

            <!-- Left Area (RTL: Far left) -->
            <td style="width: {{ $left['width_percent'] ?? 33 }}%; vertical-align: top; border: none; padding: {{ $left['padding_top'] ?? 0 }}px {{ $left['padding_right'] ?? 0 }}px {{ $left['padding_bottom'] ?? 0 }}px {{ $left['padding_left'] ?? 0 }}px; text-align: {{ $left['text_align'] ?? 'left' }};">
                @if($documentNumber || $documentDate || $documentSubject || $attachments || $qrCodeData)
                    <!-- Dynamic Document Metadata Left Block -->
                    <div style="display: flex; flex-direction: column; align-items: {{ ($left['text_align'] ?? 'left') === 'left' ? 'flex-start' : (($left['text_align'] ?? 'left') === 'center' ? 'center' : 'flex-end') }}; font-family: '{{ $left['font_family'] ?? 'Tajawal' }}', sans-serif; font-size: {{ $left['font_size'] ?? 10 }}pt; color: {{ $left['font_color'] ?? '#1f2937' }}; line-height: {{ $left['line_height'] ?? 1.5 }};">
                        @if($qrCodeData)
                            <div style="margin-bottom: 5px;">
                                <img src="{{ $qrCodeData }}" alt="QR" style="width: 65px; height: 65px; border: 1px solid #ddd; padding: 2px; background: #fff; display: inline-block;">
                            </div>
                        @endif
                        @if($documentNumber)
                            <div><strong>الرقم الإشاري:</strong> <span dir="ltr">{{ $documentNumber }}</span></div>
                        @endif
                        @if($documentDate)
                            <div><strong>التاريخ:</strong> {{ $documentDate }}</div>
                        @endif
                        @if($documentSubject)
                            <div><strong>الموضوع:</strong> {{ $documentSubject }}</div>
                        @endif
                        @if($attachments)
                            <div><strong>المرفقات:</strong> {{ $attachments }}</div>
                        @endif
                    </div>
                @elseif(!empty($left['show']))
                    <!-- Standard Configured Text Left Block -->
                    <div style="font-family: '{{ $left['font_family'] ?? 'Tajawal' }}', sans-serif; font-size: {{ $left['font_size'] ?? 11 }}pt; color: {{ $left['font_color'] ?? '#64748b' }}; font-weight: {{ $left['font_weight'] ?? '600' }}; font-style: {{ !empty($left['is_italic']) ? 'italic' : 'normal' }}; line-height: {{ $left['line_height'] ?? 1.4 }}; white-space: pre-line;">
                        {{ $left['text'] ?? '' }}
                    </div>
                @endif
            </td>
        </tr>
    </table>
</div>
