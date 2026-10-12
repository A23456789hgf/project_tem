@props(['letterhead' => null])

@php
    if (!$letterhead) {
        $letterhead = app(\App\Services\LetterheadAccessService::class)
            ->resolveEffectiveLetterhead(auth()->user(), request('letterhead_id'))
            ->letterhead;
    }
    $typo = $letterhead ? $letterhead->getTypographySettings() : [];
    $page = $letterhead ? $letterhead->getPageSettings() : [];
    $footer = $letterhead ? $letterhead->getFooterSettings() : [];
@endphp

@if($letterhead)
<style>
    :root {
        {!! $letterhead->toCssVariablesString() !!}
    }

    @page {
        size: {{ $page['paper_size'] ?? 'A4' }} {{ $page['orientation'] ?? 'portrait' }};
        margin: {{ $page['margin_top'] ?? 15 }}mm {{ $page['margin_right'] ?? 15 }}mm {{ $page['margin_bottom'] ?? 15 }}mm {{ $page['margin_left'] ?? 15 }}mm;
    }

    body {
        font-family: var(--print-font-family, 'Tajawal', sans-serif) !important;
        color: var(--print-text-dark, #1f2937);
        line-height: {{ $typo['line_height'] ?? 1.6 }};
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-title {
        font-size: var(--print-title-size, 18pt) !important;
        color: var(--print-primary-color, #0C5B47) !important;
        font-weight: 800;
    }

    .print-heading {
        font-size: var(--print-heading-size, 15pt) !important;
        color: var(--print-primary-color, #0C5B47) !important;
        font-weight: 700;
    }

    .print-subheading {
        font-size: var(--print-subheading-size, 13pt) !important;
        color: var(--print-secondary-color, #2c5f2d) !important;
        font-weight: 600;
    }

    .print-body {
        font-size: var(--print-body-size, 11pt) !important;
    }

    .print-table th {
        font-size: var(--print-table-header-size, 10pt) !important;
        background-color: var(--print-primary-color, #0C5B47) !important;
        color: #ffffff !important;
    }

    .print-table td {
        font-size: var(--print-table-body-size, 9.5pt) !important;
    }

    .print-metadata {
        font-size: var(--print-metadata-size, 8.5pt) !important;
        color: var(--print-text-muted, #64748b) !important;
    }

    @media print {
        .no-print {
            display: none !important;
        }
        .page-break {
            page-break-before: always;
            break-before: page;
        }
        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        tr {
            page-break-inside: avoid;
        }
        thead {
            display: table-header-group;
        }
    }
</style>
@endif
