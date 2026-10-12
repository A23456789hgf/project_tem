@php
    $modelClass = class_basename($model);
    
    // Find modifications for this specific item
    $itemMods = isset($itemModifications) ? $itemModifications->filter(function($m) use ($modelClass, $model) {
        $meta = $m->metadata;
        return isset($meta['related_model']) && $meta['related_model'] === $modelClass 
            && isset($meta['related_id']) && $meta['related_id'] == $model->id;
    }) : collect([]);

    // Find notes for this specific item
    $notesList = isset($itemNotes) ? $itemNotes->filter(function($n) use ($modelClass, $model) {
        $meta = $n->metadata;
        return isset($meta['related_model']) && $meta['related_model'] === $modelClass 
            && isset($meta['related_id']) && $meta['related_id'] == $model->id;
    }) : collect([]);
@endphp

@if($itemMods->count() > 0)
    <div class="mt-2 mb-2 p-2 bg-warning-subtle border border-warning rounded">
        <strong class="text-warning-emphasis small"><i class="fas fa-exclamation-triangle me-1"></i>تم تعديل البيانات:</strong>
        <ul class="mb-0 small text-muted ps-3">
            @foreach($itemMods as $mod)
                @if(isset($mod->metadata['changes']) && is_array($mod->metadata['changes']))
                    @foreach($mod->metadata['changes'] as $field => $change)
                        <li>تغير الحقل <b>{{ $field }}</b> من <code>{{ $change['old'] ?? 'فارغ' }}</code> إلى <code>{{ $change['new'] ?? 'فارغ' }}</code> <span class="text-muted" style="font-size: 0.75rem;">({{ $mod->created_at->format('Y-m-d H:i') }} - بواسطة {{ $mod->user->name ?? 'مجهول' }})</span></li>
                    @endforeach
                @endif
            @endforeach
        </ul>
    </div>
@endif

@if($notesList->count() > 0)
    <div class="mt-2 mb-2">
        <strong class="text-info small"><i class="fas fa-comments me-1"></i>الملاحظات السابقة:</strong>
        @foreach($notesList as $note)
            <div class="p-2 mb-1 bg-light border rounded small">
                <span class="text-primary fw-bold">{{ $note->user->name ?? 'مجهول' }}</span>
                <span class="text-muted" style="font-size: 0.75rem;">({{ $note->created_at->format('Y-m-d H:i') }}):</span>
                <span class="text-dark">{{ $note->notes }}</span>
            </div>
        @endforeach
    </div>
@endif

<div class="mt-2">
    <label class="form-label small text-muted mb-1"><i class="fas fa-comment-medical me-1"></i>إضافة ملاحظة على هذا البند:</label>
    <textarea name="item_notes[{{ $modelClass }}][{{ $model->id }}]" class="form-control form-control-sm" rows="1" placeholder="اكتب ملاحظتك هنا..."></textarea>
</div>
