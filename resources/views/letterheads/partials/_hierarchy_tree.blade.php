<div class="tree-wrapper">
    @forelse(($hierarchyTree ?? []) as $node)
        @include('letterheads.partials._tree_node', ['node' => $node])
    @empty
        <div class="text-center text-muted py-4">لا توجد بيانات هرمية.</div>
    @endforelse
</div>
