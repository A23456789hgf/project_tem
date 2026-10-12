@php
    $level = $level ?? 0;

    $nodeName = data_get($node, 'name')
        ?? data_get($node, 'organization_name')
        ?? data_get($node, 'entity.name')
        ?? data_get($node, 'authority.agency_name')
        ?? 'جهة';

    $nodeType = data_get($node, 'type') ?? data_get($node, 'organization_type');
    $children = data_get($node, 'children', []);
    $nodeLetterheads = data_get($node, 'letterheads', []);

    if ($children instanceof \Illuminate\Support\Collection) {
        $children = $children->all();
    }
    if ($nodeLetterheads instanceof \Illuminate\Support\Collection) {
        $nodeLetterheads = $nodeLetterheads->all();
    }

    $typeLabel = match ($nodeType) {
        'internal'      => 'داخلية',
        'external'      => 'خارجية',
        'system_global' => 'عامة',
        default         => null,
    };
@endphp

<div class="tree-node" style="margin-right: {{ $level * 12 }}px">
    <div class="tree-node-header">
        <div class="tree-node-title">
            <span>▸</span>
            <span class="text-truncate-custom" title="{{ $nodeName }}">{{ $nodeName }}</span>
        </div>

        @if($typeLabel)
            <span class="badge-artistic badge-normal">{{ $typeLabel }}</span>
        @endif
    </div>

    @if(!empty($nodeLetterheads))
        <div class="tree-letterheads">
            @foreach($nodeLetterheads as $nodeLetterhead)
                @php
                    $isDefault = (bool) data_get($nodeLetterhead, 'is_default', false);
                    $isGlobal  = (bool) data_get($nodeLetterhead, 'is_global_default', false);
                    $badgeClass = ($isDefault || $isGlobal) ? 'badge-default' : 'badge-normal';
                @endphp
                <span class="badge-artistic {{ $badgeClass }}">
                    {{ data_get($nodeLetterhead, 'name', 'ترويسة') }}
                </span>
            @endforeach
        </div>
    @endif

    @if(!empty($children))
        <div class="tree-node-children">
            @foreach($children as $child)
                @include('letterheads.partials._tree_node', [
                    'node'  => $child,
                    'level' => $level + 1,
                ])
            @endforeach
        </div>
    @endif
</div>