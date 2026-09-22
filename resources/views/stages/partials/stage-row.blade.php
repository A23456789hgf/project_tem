@php
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
    $childrenCount = $stage->children->count();
@endphp

<tr>
    <td>{{ $stage->id }}</td>
    <td>{!! $indent !!}{{ $stage->name_ar }}</td>
    <td>{{ $stage->name_en }}</td>
    <td>{{ $stage->code }}</td>
    <td>{{ $stage->description_ar ? Str::limit($stage->description_ar, 30) : '-' }}</td>
    <td>{{ $stage->description_en ? Str::limit($stage->description_en, 30) : '-' }}</td>
    <td>
        <span class="badge bg-info">{{ $stage->type ?? '-' }}</span>
    </td>
    <td>{{ $stage->order }}</td>
    <td>
        @if($stage->is_active)
            <span class="badge bg-success">نعم</span>
        @else
            <span class="badge bg-danger">لا</span>
        @endif
    </td>
    <td>
        @if($stage->is_system)
            <span class="badge bg-warning">نعم</span>
        @else
            <span class="badge bg-secondary">لا</span>
        @endif
    </td>
    <td>{{ $stage->parent?->name_ar ?? '-' }}</td>
    <td>
        @if($childrenCount > 0)
            <span class="badge bg-primary">{{ $childrenCount }}</span>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
    <td>
        @can('stages.edit')
        <a href="{{ route('stages.edit', $stage->id) }}" class="btn btn-sm btn-warning">تعديل</a>
        @endcan
        @can('stages.delete')
        <form action="{{ route('stages.destroy', $stage->id) }}" method="POST" style="display:inline;" onsubmit="return confirmAction(this, 'هل تأكد من حذف هذه المرحلة؟')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">حذف</button>
        </form>
        @endcan
    </td>
</tr>

@foreach($stage->children as $child)
    @include('stages.partials.stage-row', ['stage' => $child, 'level' => $level + 1])
@endforeach
