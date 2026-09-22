<li data-id="{{ $authority->id }}" data-level="{{ $level }}" class="{{ $authority->children_count > 0 ? '' : 'no-children' }}">
    <div class="tree-node">
        @if($authority->children_count > 0)
            <button class="tree-toggle">
                <i class="fas fa-chevron-right"></i>
            </button>
        @else
            <button class="tree-toggle" style="visibility: hidden;">
                <i class="fas fa-chevron-right"></i>
            </button>
        @endif
        
        <div class="node-content" style="display: flex; align-items: center; gap: 8px; flex: 1;">
            @can('authorities.bulk-edit')
            <input type="checkbox" class="form-check-input authority-checkbox shadow-sm" value="{{ $authority->id }}" onclick="event.stopPropagation();">
            @endcan
            <span class="folder-icon">
                <i class="fas fa-folder"></i>
            </span>
            
            <span class="node-name" style="flex: 1;">{{ $authority->agency_name }}</span>
            
            <span class="node-meta" style="display: flex; gap: 8px; align-items: center;">
                <span class="badge bg-{{ $authority->is_active ? 'success' : 'danger' }}">
                    {{ $authority->is_active ? 'نشط' : 'غير نشط' }}
                </span>
                
                @if($authority->is_funded)
                    <span class="badge bg-success bg-opacity-75">
                        ممولة
                    </span>
                @endif
                
                @if($authority->children_count > 0)
                    <span class="badge bg-info">
                        {{ $authority->children_count }} جهة تابعة
                    </span>
                @endif
            </span>
            
            <div class="node-actions" style="display: flex; gap: 4px; align-items: center;">
                <a href="{{ route('authorities.show', $authority->id) }}" 
                   class="btn btn-sm" title="عرض التفاصيل">
                    <i class="fas fa-eye"></i>
                </a>
                @can('authorities.edit')
                <a href="{{ route('authorities.edit', $authority->id) }}" 
                   class="btn btn-sm" title="تعديل">
                    <i class="fas fa-edit"></i>
                </a>
                @endcan
                @can('authorities.create')
                <a href="{{ route('authorities.create', ['parent_id' => $authority->id]) }}" 
                   class="btn btn-sm" title="إضافة جهة تابعة">
                    <i class="fas fa-plus"></i>
                </a>
                @endcan
                @can('authorities.delete')
                <form action="{{ route('authorities.destroy', $authority->id) }}" 
                      method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm" 
                            title="حذف" 
                            onclick="return confirmAction(this, 'هل أنت متأكد من حذف الجهة {{ $authority->agency_name }}؟')">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </div>
    
    @if($authority->children_count > 0)
        <ul style="display: none;">
            {{-- سيتم تحميل الأطفال ديناميكياً via AJAX --}}
        </ul>
    @endif
</li>

<style>
.authority-item {
    margin-bottom: 4px;
}

.tree-node {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background-color: #fafafa;
    transition: all 0.2s ease;
}

.tree-node:hover {
    background-color: #f0f8ff;
    border-color: #cce7ff;
}

/* الجزء الأيسر - يحتوي على جميع الأزرار والأيقونات */
.node-left-section {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.tree-toggle {
    background: none;
    border: none;
    cursor: pointer;
    transition: transform 0.2s;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #6c757d;
}

.tree-toggle.expanded {
    transform: rotate(90deg);
}

.tree-toggle:hover {
    color: #2c3e50;
    background-color: #e9ecef;
    border-radius: 4px;
}

.folder-icon {
    color: #ffc107;
    font-size: 1.1em;
    flex-shrink: 0;
}

.node-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}

.node-actions .btn {
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s ease;
    font-size: 0.8em;
}

.node-actions .btn:hover {
    transform: translateY(-1px);
}

/* الجزء الأيمن - يحتوي على اسم الجهة والمعلومات */
.node-right-section {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    margin-left: auto;
}

.node-name {
    font-weight: 600;
    color: #2c3e50;
    white-space: nowrap;
    text-align: right;
}

.node-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

.badge {
    font-size: 0.75em;
    padding: 4px 8px;
    border-radius: 12px;
    white-space: nowrap;
}

.children-list {
    list-style: none;
    padding-right: 30px;
    margin-top: 4px;
}

/* تحسينات للشاشات الصغيرة */
@media (max-width: 768px) {
    .tree-node {
        padding: 6px 8px;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .node-left-section {
        width: 100%;
        justify-content: space-between;
        order: 2;
    }
    
    .node-right-section {
        width: 100%;
        justify-content: space-between;
        order: 1;
        margin-left: 0;
    }
    
    .node-actions {
        gap: 2px;
    }
    
    .node-actions .btn {
        padding: 3px 6px;
    }
    
    .node-meta {
        gap: 4px;
    }
    
    .badge {
        font-size: 0.7em;
        padding: 3px 6px;
    }
}

/* تحسينات إضافية للشاشات الصغيرة جداً */
@media (max-width: 480px) {
    .node-left-section {
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .node-actions {
        width: 100%;
        justify-content: center;
        margin-top: 4px;
    }
    
    .node-right-section {
        flex-direction: column;
        gap: 6px;
        align-items: flex-end;
    }
}

/* تنسيق خاص للجهات الفرعية بمستويات مختلفة */
.authority-item[data-level="0"] .tree-node {
    background-color: #e8f4fd;
    border-color: #b8daff;
}

.authority-item[data-level="1"] .tree-node {
    background-color: #fef7e0;
    border-color: #ffeaa7;
}

.authority-item[data-level="2"] .tree-node {
    background-color: #f0f9f0;
    border-color: #c8e6c9;
}

.authority-item[data-level="3"] .tree-node {
    background-color: #fafafa;
    border-color: #e0e0e0;
}

/* تحسينات للشجرة */
.tree-toggle {
    margin-left: 0;
}

.node-name {
    margin-left: 0;
}
</style>