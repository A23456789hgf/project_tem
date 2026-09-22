<li class="tree-item" data-id="{{ $entity->id }}">
    <div class="tree-node shadow-sm">
        
        @if($entity->children->count() > 0)
            <button class="tree-toggle-btn" aria-label="Toggle">
                <i class="fas fa-chevron-left"></i>
            </button>
        @else
            <div style="width: 38px; margin-left: 10px;"></div>
        @endif

        <div class="tree-node-icon">
            <i class="fas {{ $level == 0 ? 'fa-city' : ($entity->children->count() > 0 ? 'fa-network-wired' : 'fa-building') }}"></i>
        </div>
        
        <div class="tree-node-content">
            <span class="tree-node-name">{{ $entity->name }}</span>
            
            @if($entity->children->count() > 0)
                <span class="child-counter" title="عدد الفروع التابعة">
                    {{ $entity->children->count() }}
                </span>
            @endif
        </div>

        <div class="tree-node-actions d-flex align-items-center gap-3">
            <div class="form-check form-switch m-0 entity-switch-container" title="{{ $entity->entity_type === 'Company' ? 'كيان رئيسي (Company)' : 'قسم/جهة تابعة (Department)' }}">
                <input class="form-check-input entity-type-switch" type="checkbox" data-id="{{ $entity->id }}" 
                    style="cursor: pointer; width: 2em; height: 1em;"
                    {{ $entity->entity_type === 'Company' ? 'checked' : '' }}>
            </div>
            <div class="btn-group gap-2">
                @can('internal-entities.edit')
                <a href="{{ route('internal-entities.edit', $entity->id) }}" class="btn btn-sm btn-outline-secondary rounded" title="تعديل">
                    <i class="fas fa-edit"></i>
                </a>
                @endcan
                <a href="{{ route('internal-entities.show', $entity->id) }}" class="btn btn-sm btn-outline-info rounded" title="التفاصيل">
                    <i class="fas fa-eye"></i>
                </a>
            </div>
        </div>
    </div>

    @if($entity->children->count() > 0)
        <ul class="tree-children">
            @foreach($entity->children as $child)
                @include('configuration.internal_entities.partials.tree-node', [
                    'entity' => $child, 
                    'level' => $level + 1
                ])
            @endforeach
        </ul>
    @endif
</li>