@extends('layouts.app')

@section('title', 'هيكل الجهات الداخلية')

@section('content')
<div class="container-fluid" dir="rtl">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h1 class="h3 mb-0 text-primary fw-bold">
                    <i class="fas fa-sitemap me-2"></i> هيكل الجهات الداخلية
                </h1>
                <div class="btn-group shadow-sm" role="group">
                    <a href="{{ route('internal-entities.index') }}" class="btn btn-white border bg-white">
                        <i class="fas fa-list text-secondary"></i> عرض جدولي
                    </a>
                    @can('internal-entities.create')
                    <a href="{{ route('internal-entities.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إضافة جهة
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Tree Card -->
    <div class="card shadow-sm border-0 rounded-4">
        <!-- Toolbar (Search & Controls) -->
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="input-group input-group-lg shadow-sm rounded-3">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="treeSearch" class="form-control border-start-0 ps-0" placeholder="ابحث عن جهة داخلية...">
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <button class="btn btn-light border shadow-sm me-2 fw-semibold" id="expandAll">
                        <i class="fas fa-expand-alt text-primary"></i> توسيع الكل
                    </button>
                    <button class="btn btn-light border shadow-sm fw-semibold" id="collapseAll">
                        <i class="fas fa-compress-alt text-secondary"></i> طي الكل
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            @if($rootEntities->count() > 0)
                <div class="tree-container">
                    <ul class="tree-list" id="mainTree">
                        @foreach($rootEntities as $entity)
                            @include('configuration.internal_entities.partials.tree-node', ['entity' => $entity, 'level' => 0])
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="display-1 text-muted mb-3"><i class="fas fa-folder-open text-light"></i></div>
                    <h5 class="text-muted">لا توجد جهات داخلية حالياً</h5>
                    <p class="text-muted small">قم بإضافة جهتك الأولى لبدء بناء الهيكل التنظيمي.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
/* CSS Variables for elegant UI */
:root {
    --tree-line-color: #e2e8f0;
    --tree-hover-bg: #f8fafc;
    --tree-active-border: #3b82f6;
}

.tree-container {
    padding: 10px;
    border-radius: 12px;
    overflow-x: auto;
    direction: rtl;
}

.tree-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tree-item {
    margin: 8px 0;
    position: relative;
}

/* Node Styling */
.tree-node {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    transition: all 0.2s ease-in-out;
    position: relative;
    z-index: 2;
}

.tree-node:hover {
    background: var(--tree-hover-bg);
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

/* Lines for children */
.tree-children {
    list-style: none;
    padding: 0;
    margin: 0 30px 0 0; /* RTL spacing */
    border-right: 2px dashed var(--tree-line-color);
    padding-right: 20px;
    display: none; /* Hidden by default for logic to handle */
}

.tree-children.active {
    display: block;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Toggle Button */
.tree-toggle-btn {
    background: none;
    border: none;
    color: #64748b;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-left: 10px;
    background: #f1f5f9;
}

.tree-toggle-btn:hover {
    background: #e2e8f0;
    color: var(--tree-active-border);
}

.tree-toggle-btn i {
    transition: transform 0.3s ease;
}

.tree-toggle-btn.expanded i {
    transform: rotate(-90deg); /* RTL rotation */
}

.tree-node-icon {
    margin-left: 12px;
    color: var(--tree-active-border);
    font-size: 1.1rem;
    background: #eff6ff;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.tree-node-content {
    flex-grow: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tree-node-name {
    font-weight: 600;
    color: #334155;
    font-size: 1rem;
}

.child-counter {
    font-size: 0.75rem;
    padding: 4px 10px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 20px;
    font-weight: 600;
}

/* Highlight for search */
.highlight {
    background-color: #fef08a;
    padding: 2px 4px;
    border-radius: 4px;
}

.d-none-search {
    display: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const STORAGE_KEY = 'tree_expanded_nodes';
    
    // 1. استرجاع الفروع المفتوحة من LocalStorage
    let expandedNodes = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];

    // 2. وظيفة التبديل (التوسيع / الطي)
    function toggleNode(nodeId, expand = null) {
        const item = document.querySelector(`.tree-item[data-id="${nodeId}"]`);
        if (!item) return;

        const childrenContainer = item.querySelector(':scope > .tree-children');
        const toggleBtn = item.querySelector(':scope > .tree-node .tree-toggle-btn');
        
        if (!childrenContainer || !toggleBtn) return;

        const isCurrentlyExpanded = toggleBtn.classList.contains('expanded');
        const shouldExpand = expand !== null ? expand : !isCurrentlyExpanded;

        if (shouldExpand) {
            childrenContainer.classList.add('active');
            toggleBtn.classList.add('expanded');
            if (!expandedNodes.includes(nodeId)) expandedNodes.push(nodeId);
        } else {
            childrenContainer.classList.remove('active');
            toggleBtn.classList.remove('expanded');
            expandedNodes = expandedNodes.filter(id => id !== nodeId);
        }

        localStorage.setItem(STORAGE_KEY, JSON.stringify(expandedNodes));
    }

    // 3. تطبيق الحالة المحفوظة
    expandedNodes.forEach(nodeId => {
        toggleNode(nodeId, true);
    });

    // 4. ربط حدث النقر بأزرار التبديل
    document.querySelectorAll('.tree-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const nodeId = this.closest('.tree-item').getAttribute('data-id');
            toggleNode(nodeId);
        });
    });

    // 5. توسيع الكل
    document.getElementById('expandAll').addEventListener('click', () => {
        document.querySelectorAll('.tree-item[data-id]').forEach(item => {
            toggleNode(item.getAttribute('data-id'), true);
        });
    });

    // 6. طي الكل
    document.getElementById('collapseAll').addEventListener('click', () => {
        document.querySelectorAll('.tree-item[data-id]').forEach(item => {
            toggleNode(item.getAttribute('data-id'), false);
        });
        localStorage.removeItem(STORAGE_KEY);
        expandedNodes = [];
    });

    // 7. ميزة البحث في الشجرة
    const searchInput = document.getElementById('treeSearch');
    searchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase().trim();
        const allItems = document.querySelectorAll('.tree-item');
        const allNames = document.querySelectorAll('.tree-node-name');

        allNames.forEach(nameEl => nameEl.innerHTML = nameEl.textContent);

        if (term === '') {
            allItems.forEach(item => item.classList.remove('d-none-search'));
            document.querySelectorAll('.tree-item[data-id]').forEach(item => {
                const id = item.getAttribute('data-id');
                toggleNode(id, expandedNodes.includes(id));
            });
            return;
        }

        allItems.forEach(item => item.classList.add('d-none-search'));

        allNames.forEach(nameEl => {
            const text = nameEl.textContent.toLowerCase();
            if (text.includes(term)) {
                const regex = new RegExp(`(${term})`, "gi");
                nameEl.innerHTML = nameEl.textContent.replace(regex, "<span class='highlight'>$1</span>");

                let currentItem = nameEl.closest('.tree-item');
                currentItem.classList.remove('d-none-search');

                let parentList = currentItem.parentElement.closest('.tree-item');
                while (parentList) {
                    parentList.classList.remove('d-none-search');
                    const parentId = parentList.getAttribute('data-id');
                    if(parentId) {
                        const childContainer = parentList.querySelector(':scope > .tree-children');
                        const btn = parentList.querySelector(':scope > .tree-node .tree-toggle-btn');
                        if(childContainer) childContainer.classList.add('active');
                        if(btn) btn.classList.add('expanded');
                    }
                    parentList = parentList.parentElement.closest('.tree-item');
                }
            }
        });
    });

    // 8. التعامل مع تبديل نوع الجهة
    document.querySelectorAll('.entity-type-switch').forEach(switchEl => {
        switchEl.addEventListener('change', function() {
            const entityId = this.getAttribute('data-id');
            const newType = this.checked ? 'Company' : 'Department';
            const newTitle = this.checked ? 'كيان رئيسي (Company)' : 'قسم/جهة تابعة (Department)';
            const originalState = !this.checked;
            const container = this.closest('.entity-switch-container');

            fetch(`/internal-entities/${entityId}/toggle-type`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ entity_type: newType })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Update successful
                    console.log(data.message);
                    if(container) {
                        container.setAttribute('title', newTitle);
                    }
                } else {
                    alert('فشل في تحديث نوع الجهة: ' + (data.message || 'خطأ غير معروف'));
                    this.checked = originalState;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء الاتصال بالخادم.');
                this.checked = originalState;
            });
        });
    });
});
</script>
@endsection