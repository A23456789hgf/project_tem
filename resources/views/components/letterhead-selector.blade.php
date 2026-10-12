@props([
    'currentLetterhead' => null,
])

@php
    $user = auth()->user();
    $service = app(\App\Services\LetterheadAccessService::class);
    $resolution = $service->resolveEffectiveLetterhead($user, request('letterhead_id'));
    $effectiveLetterhead = $currentLetterhead ?? $resolution->letterhead;
    $canSelectOther = $user && ($user->can('letterheads.select-other') || $user->is_super_admin || $user->hasRole('admin'));
    $allowedLetterheads = $canSelectOther ? $service->getAllowedLetterheadsForUser($user) : collect();
@endphp

<div class="no-print d-flex align-items-center gap-2 mb-3 bg-light p-2 rounded-3 border">
    <div class="d-flex align-items-center gap-2">
        <i class="fas fa-heading text-success fs-5"></i>
        <span class="fw-bold small text-muted">الترويسة النشطة:</span>
    </div>

    @if($canSelectOther && $allowedLetterheads->count() > 1)
        <select class="form-select form-select-sm" style="max-width: 320px;" onchange="switchPrintLetterhead(this.value)">
            @foreach($allowedLetterheads as $lh)
                <option value="{{ $lh->id }}" {{ $effectiveLetterhead && $effectiveLetterhead->id === $lh->id ? 'selected' : '' }}>
                    {{ $lh->name }} ({{ $lh->organization_name }})
                    @if($lh->is_default) [افتراضي] @endif
                </option>
            @endforeach
        </select>
        <script>
            function switchPrintLetterhead(id) {
                const url = new URL(window.location.href);
                url.searchParams.set('letterhead_id', id);
                window.location.href = url.toString();
            }
        </script>
    @else
        <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3 fw-bold">
            <i class="fas fa-shield-alt me-1"></i>
            {{ $effectiveLetterhead ? $effectiveLetterhead->name . ' (' . $effectiveLetterhead->organization_name . ')' : 'الترويسة الرسمية الافتراضية' }}
        </span>
        @if($resolution && $resolution->isInherited)
            <span class="badge bg-info-subtle text-info border border-info-subtle py-2 px-2" title="{{ $resolution->explanation }}">
                <i class="fas fa-sitemap me-1"></i> موروثة
            </span>
        @endif
    @endif
</div>
