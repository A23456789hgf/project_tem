@props([
    'letterhead' => null,
    'customFooterText' => null,
    'documentName' => null,
    'fixedBottom' => false,
])

@php
    if (!$letterhead) {
        $letterhead = app(\App\Services\LetterheadAccessService::class)
            ->resolveEffectiveLetterhead(auth()->user(), request('letterhead_id'))
            ->letterhead;
    }

    $footer = $letterhead ? $letterhead->getFooterSettings() : [
        'show_footer' => true,
        'show_separator' => true,
        'separator_style' => 'solid',
        'separator_thickness' => 1,
        'separator_color' => '#0C5B47',
        'separator_width' => 100,
        'separator_top_margin' => 15,
        'separator_bottom_margin' => 8,
        'show_page_numbers' => true,
        'page_number_format' => 'page_number',
        'page_number_position' => 'left',
        'show_printed_by' => true,
        'show_user_organization' => true,
        'show_date' => true,
        'show_time' => true,
        'date_format' => 'Y-m-d',
        'time_format' => 'h:i A',
        'show_document_name' => false,
        'custom_footer_text' => 'صنعاء - الجمهورية اليمنية | هاتف: 01-282900 | البريد الإلكتروني: info@agriculture.gov.ye',
        'footer_font_family' => 'Tajawal',
        'footer_font_size' => 9,
        'footer_font_color' => '#64748b',
        'footer_top_margin' => 20,
    ];

    if (empty($footer['show_footer'])) {
        return;
    }

    $currentUser = auth()->user();
    $userName = $currentUser ? $currentUser->name : 'مستخدم النظام';
    $userOrg = $currentUser ? ($currentUser->entity?->name ?? ($currentUser->authority?->name ?? '')) : '';
    $printDate = date($footer['date_format'] ?? 'Y-m-d');
    $printTime = date($footer['time_format'] ?? 'h:i A');
@endphp

<div class="official-letterhead-footer avoid-break {{ $fixedBottom ? 'fixed-print-footer' : '' }}" 
     style="margin-top: {{ $footer['footer_top_margin'] ?? 20 }}px; font-family: '{{ $footer['footer_font_family'] ?? 'Tajawal' }}', sans-serif; font-size: {{ $footer['footer_font_size'] ?? 9 }}pt; color: {{ $footer['footer_font_color'] ?? '#64748b' }}; direction: rtl;">
    
    @if(!empty($footer['show_separator']))
        <div style="display: flex; justify-content: center; margin-top: {{ $footer['separator_top_margin'] ?? 15 }}px; margin-bottom: {{ $footer['separator_bottom_margin'] ?? 8 }}px;">
            <hr style="width: {{ $footer['separator_width'] ?? 100 }}%; border-top: {{ $footer['separator_thickness'] ?? 1 }}px {{ $footer['separator_style'] ?? 'solid' }} {{ $footer['separator_color'] ?? '#0C5B47' }}; opacity: 0.8; margin: 0;">
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <!-- Right Section: Printed By & System Metadata -->
        <div style="text-align: right; flex: 1; min-width: 200px;">
            @if(!empty($footer['show_printed_by']))
                <span>
                    <i class="fas fa-user-circle me-1"></i> طُبع بواسطة: <strong>{{ $userName }}</strong>
                </span>
            @endif

            @if(!empty($footer['show_user_organization']) && $userOrg)
                <span style="margin-right: 8px;">
                    ({{ $userOrg }})
                </span>
            @endif

            @if(!empty($footer['show_date']))
                <span style="margin-right: 10px;">
                    <i class="far fa-calendar-alt me-1"></i> {{ $printDate }}
                </span>
            @endif

            @if(!empty($footer['show_time']))
                <span style="margin-right: 6px;">
                    <i class="far fa-clock me-1"></i> {{ $printTime }}
                </span>
            @endif

            @if(!empty($footer['show_document_name']) && $documentName)
                <span style="margin-right: 10px;">
                    | {{ $documentName }}
                </span>
            @endif
        </div>

        <!-- Center Section: Custom text / Official address -->
        @if($customFooterText || !empty($footer['custom_footer_text']))
            <div style="text-align: center; flex: 2; min-width: 250px; font-weight: 500;">
                {{ $customFooterText ?? $footer['custom_footer_text'] }}
            </div>
        @endif

        <!-- Left Section: Page Numbering -->
        @if(!empty($footer['show_page_numbers']))
            <div style="text-align: {{ $footer['page_number_position'] ?? 'left' }}; flex: 1; min-width: 100px;" class="print-page-number-container">
                <span class="page-number-display">
                    @if(($footer['page_number_format'] ?? 'page_number') === 'page_prefix')
                        صفحة <span class="current-page-num">1</span>
                    @elseif(($footer['page_number_format'] ?? 'page_number') === 'page_of_total')
                        صفحة <span class="current-page-num">1</span> من <span class="total-page-num">1</span>
                    @else
                        <span class="current-page-num">1</span>
                    @endif
                </span>
            </div>
        @endif
    </div>
</div>
