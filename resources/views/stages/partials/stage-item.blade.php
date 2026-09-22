<li class="list-group-item">
    <strong>{{ $stage->name_ar }}</strong> ({{ $stage->code }})
    <span class="float-end">
        @can('stages.edit')
        <a href="{{ route('stages.edit', $stage->id) }}" class="btn btn-sm btn-warning">تعديل</a>
        @endcan
    </span>

    @if($stage->children->count() > 0)
        <ul class="list-group mt-2">
            @foreach($stage->children as $child)
                @include('stages.partials.stage-item', ['stage' => $child])
            @endforeach
        </ul>
    @endif
</li>
