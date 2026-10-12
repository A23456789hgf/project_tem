{{-- resources/views/components/index-page.blade.php --}}
@props([
    'title',
    'icon' => 'list-alt',
    'paginator' => null,
])

<style>
    .modern-layout-card {
        border-radius: 12px;
    }
    .custom-per-page-select {
        background-size: 10px 10px !important;
        background-position: left 0.5rem center !important;
        padding-left: 1.5rem !important;
        padding-right: 0.5rem !important;
        border-radius: 6px !important;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        font-size: 0.75rem;
    }
    .custom-per-page-select:focus {
        background-color: #ffffff;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15) !important;
        border-color: #86b7fe !important;
    }
    .glass-stats-bar {
        background: #f8f9fa;
        border: 1px solid rgba(0, 0, 0, 0.04);
        border-radius: 8px;
    }
    .icon-wrapper {
        width: 32px;
        height: 32px;
        background-color: #fff9e6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(212, 175, 55, 0.2);
    }
    .compact-table-wrapper table {
        font-size: 0.75rem;
    }
    .compact-table-wrapper table th,
    .compact-table-wrapper table td {
        padding: 0.35rem 0.3rem;
        white-space: nowrap;
    }
    .compact-table-wrapper .btn-group-sm > .btn,
    .compact-table-wrapper .btn-sm {
        padding: 0.15rem 0.3rem;
        font-size: 0.7rem;
    }
</style>

<div class="container-fluid py-2" dir="rtl">
    {{-- رأس الصفحة --}}
    <div class="row align-items-center mb-2">
        <div class="col-md-6 col-12 mb-2 mb-md-0">
            <h5 class="mb-0 d-flex align-items-center gap-2 text-dark fw-bold">
                <div class="icon-wrapper shadow-sm">
                    <x-icon :name="$icon" size="16" class="text-gold" style="color: var(--gold-color, #d4af37);" />
                </div>
                {{ $title }}
            </h5>
        </div>
        @if(isset($headerActions))
            <div class="col-md-6 col-12 text-md-end">
                <div class="d-flex justify-content-md-end gap-2 flex-wrap">
                    {{ $headerActions }}
                </div>
            </div>
        @endif
    </div>

    {{-- البطاقة الأساسية --}}
    <div class="card shadow-sm border-0 modern-layout-card">
        <div class="card-body p-2 p-md-3 bg-white">
            {{-- Breadcrumb --}}
            @if(isset($breadcrumb))
                <div class="mb-2 text-muted small">{{ $breadcrumb }}</div>
            @endif

            {{-- فلتر / بحث --}}
            @if(isset($filters))
                <div class="mb-2 pb-2 border-bottom border-light">{{ $filters }}</div>
            @endif

            {{-- المحتوى الرئيسي --}}
            <div class="content-wrapper my-1">{{ $slot }}</div>

            {{-- شريط إحصائيات --}}
            @if(isset($stats))
                <div class="glass-stats-bar mt-2 mb-2 p-2 d-flex flex-wrap gap-2 align-items-center text-secondary small">
                    {{ $stats }}
                </div>
            @endif

            {{-- الجدول بدون تمرير أفقي --}}
            @if(isset($table))
                <div class="compact-table-wrapper rounded-2 mt-2 overflow-hidden">
                    {{ $table }}
                </div>
            @endif

            {{-- منطقة الترقيم --}}
            @php
                $paginatorInstance = $paginator ?? null;
                $paginationHtml = null;
                $totalCount = null;

                if (isset($pagination)) {
                    $paginationHtml = $pagination;
                } elseif ($paginatorInstance && method_exists($paginatorInstance, 'withQueryString') && method_exists($paginatorInstance, 'links')) {
                    $paginationHtml = $paginatorInstance->withQueryString()->links();
                    if (method_exists($paginatorInstance, 'total')) {
                        $totalCount = $paginatorInstance->total();
                    }
                }

                if (isset($total)) {
                    $totalCount = $total;
                } elseif ($paginatorInstance && method_exists($paginatorInstance, 'total') && !$totalCount) {
                    $totalCount = $paginatorInstance->total();
                }
            @endphp

            @if($paginationHtml || isset($paginatorInstance) || isset($total))
                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top flex-wrap gap-2">
                    <div class="pagination-container">
                        {!! $paginationHtml !!}
                    </div>

                    <div class="d-flex align-items-center gap-3 bg-light px-2 py-1 rounded-2 border" dir="rtl">
                        @if($totalCount !== null)
                            <span class="text-secondary small fw-bold" style="font-size: 0.8rem;">
                                الإجمالي: <span class="text-primary">{{ $totalCount }}</span>
                            </span>
                        @endif

                        <div class="d-flex align-items-center gap-1 border-end pe-2 border-secondary-subtle">
                            <span class="text-secondary small fw-bold" style="font-size: 0.8rem;">عرض:</span>
                            <form method="GET" action="{{ url()->current() }}" class="m-0 p-0">
                                @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $v)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                        @endforeach
                                    @elseif(!is_null($value))
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <select name="per_page" class="form-select form-select-sm fw-bold text-primary custom-per-page-select" style="cursor: pointer;" onchange="this.form.submit()">
                                    <option value="20" {{ request('per_page') == 20 ? 'selected' : (request('per_page') == null ? 'selected' : '') }}>20</option>
                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($footer))
                <div class="mt-2 pt-2 border-top">{{ $footer }}</div>
            @endif
        </div>

        @if(isset($cardFooter))
            <div class="card-footer bg-light border-top-0 py-2">{{ $cardFooter }}</div>
        @endif
    </div>
</div>