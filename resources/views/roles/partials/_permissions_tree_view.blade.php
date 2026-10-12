{{-- _permissions_tree_view.blade.php --}}

<div class="tree-view" id="treeView">
    <div class="tree-layout-container">
        
        {{-- Column 1: Main Modules --}}
        <div class="tree-column main-modules-col">
            <div class="column-header">
                <h5><i class="fas fa-boxes text-primary me-2"></i>الوحدات الأساسية</h5>
                <div class="search-wrap">
                    <input type="text" class="form-control form-control-sm tree-search" data-target=".module-item" placeholder="بحث عن وحدة...">
                </div>
            </div>
            <div class="column-body">
                <div class="list-group list-group-flush" id="mainModulesList">
                    @foreach($subModulesMap as $mainModuleKey => $pages)
                        @php
                            $mainLabel = $moduleTranslations[$mainModuleKey] ?? $mainModuleKey;
                            $pagesCount = count($pages);
                        @endphp
                        <button type="button" class="list-group-item list-group-item-action module-item" data-module="{{ $mainModuleKey }}">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <span class="fw-bold module-name">{{ $mainLabel }}</span>
                                <span class="badge bg-secondary rounded-pill">{{ $pagesCount }}</span>
                            </div>
                            <div class="module-stats small text-muted mt-1">
                                <span class="active-count">0</span> مفعلة
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Column 2: Pages (Sub Modules) --}}
        <div class="tree-column pages-col">
            <div class="column-header">
                <h5><i class="fas fa-file-alt text-success me-2"></i>الصفحات</h5>
                <div class="search-wrap">
                    <input type="text" class="form-control form-control-sm tree-search" data-target=".page-item" placeholder="بحث عن صفحة...">
                </div>
            </div>
            <div class="column-body">
                <div class="list-group list-group-flush" id="pagesList">
                    <div class="empty-state text-center text-muted p-4 mt-5">
                        <i class="fas fa-arrow-right fs-1 mb-3 text-light"></i>
                        <p>الرجاء اختيار وحدة لعرض الصفحات</p>
                    </div>
                    
                    @foreach($subModulesMap as $mainModuleKey => $pages)
                        <div class="pages-group" data-main-module="{{ $mainModuleKey }}" style="display: none;">
                            @foreach($pages as $pageKey)
                                @if(in_array($pageKey, $allModuleNames->toArray()))
                                    @php
                                        $pageLabel = $moduleTranslations[$pageKey] ?? $pageKey;
                                        $pagePerms = $modulePermsMap[$pageKey]['perms'] ?? [];
                                    @endphp
                                    <button type="button" class="list-group-item list-group-item-action page-item" data-page="{{ $pageKey }}">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <span class="fw-bold page-name">{{ $pageLabel }}</span>
                                            <span class="badge bg-info rounded-pill">{{ count($pagePerms) }}</span>
                                        </div>
                                        <div class="page-stats small text-muted mt-1">
                                            <span class="active-count">0</span> / {{ count($pagePerms) }}
                                        </div>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Column 3: Operations (Permissions) --}}
        <div class="tree-column operations-col">
            <div class="column-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-key text-warning me-2"></i>العمليات</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary select-all-ops" style="display:none;">تحديد الكل</button>
                </div>
            </div>
            <div class="column-body p-3 bg-light" id="operationsList">
                <div class="empty-state text-center text-muted p-4 mt-5">
                    <i class="fas fa-mouse-pointer fs-1 mb-3 text-light"></i>
                    <p>الرجاء اختيار صفحة لعرض العمليات</p>
                </div>

                @foreach($allModuleNames as $moduleName)
                    @php
                        $moduleKey = strtolower($moduleName);
                        $moduleLabel = $moduleTranslations[$moduleKey] ?? $moduleName;
                        $moduleConfig = $moduleConfigs[$moduleKey] ?? [];
                        $allPerms = $modulePermsMap[$moduleName]['perms'] ?? [];
                        
                        $savedScope = isset($role) && is_array($role->module_scopes) ? ($role->module_scopes[$moduleKey] ?? 'own') : 'own';
                        $savedGeo = isset($role) && is_array($role->module_geo_scopes) ? ($role->module_geo_scopes[$moduleKey] ?? 'none') : 'none';
                        $hasDedicated = $moduleConfig['has_dedicated_scope'] ?? false;
                        $isReadOnly = (isset($viewOnly) && $viewOnly === true);
                    @endphp
                    <div class="ops-group" data-page="{{ $moduleKey }}" style="display: none;">
                        <h6 class="mb-3 text-primary border-bottom pb-2">{{ $moduleLabel }}</h6>
                        <div class="ops-grid">
                            @foreach($allPerms as $perm)
                                @php
                                    $isChecked = isset($rolePermsLookup[$perm->id]);
                                    $isDisabled = $isReadOnly && !$isChecked && !($role->full_access ?? false);
                                @endphp
                                <label class="op-card {{ $isChecked ? 'checked' : '' }}">
                                    <input type="checkbox" class="tree-perm-checkbox" 
                                           value="{{ $perm->id }}"
                                           data-page="{{ $moduleKey }}"
                                           {{ $isChecked ? 'checked' : '' }} 
                                           {{ $isDisabled ? 'disabled' : '' }}>
                                    <span class="op-label">{{ $slugArabicMap[explode('.', $perm->slug)[1] ?? $perm->slug] ?? $perm->slug }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="scopes-section">
                            <h6 class="mb-3 text-secondary border-bottom pb-2">النطاقات (Scopes)</h6>
                            <div class="row g-3">
                                @if(!$hasDedicated && !in_array($moduleKey, ['governorates', 'directorates']))
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">نطاق إداري</label>
                                    <select class="form-select form-select-sm tree-scope-select"
                                            data-name="module_scopes[{{ $moduleKey }}]"
                                            data-page="{{ $moduleKey }}"
                                            {{ $isReadOnly ? 'disabled' : '' }}>
                                        <option value="none"   {{ $savedScope === 'none'   ? 'selected' : '' }}>🚫 حجب</option>
                                        <option value="own"    {{ $savedScope === 'own'    ? 'selected' : '' }}>🏢 جهتي</option>
                                        <option value="parent" {{ $savedScope === 'parent' ? 'selected' : '' }}>📂 عامة</option>
                                        <option value="all"    {{ $savedScope === 'all'    ? 'selected' : '' }}>🏛️ الكل</option>
                                    </select>
                                </div>
                                @endif

                                @if(!$hasDedicated && !in_array($moduleKey, ['users', 'roles', 'audit-logs']))
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">نطاق جغرافي</label>
                                    <select class="form-select form-select-sm tree-geo-select"
                                            data-name="module_geo_scopes[{{ $moduleKey }}]"
                                            data-page="{{ $moduleKey }}"
                                            {{ $isReadOnly ? 'disabled' : '' }}>
                                        <option value="none" {{ $savedGeo === 'none' ? 'selected' : '' }}>🚫 جغرافي</option>
                                        <option value="all"  {{ $savedGeo === 'all'  ? 'selected' : '' }}>🌐 الكل</option>
                                        @if(in_array($moduleKey, ['projects', 'correspondence', 'plans', 'governorates', 'directorates']))
                                            <option value="gov" {{ $savedGeo === 'same_governorate' ? 'selected' : '' }}>🏙️ المحافظة</option>
                                            <option value="dir" {{ $savedGeo === 'same_directorate' ? 'selected' : '' }}>🏢 المديرية</option>
                                        @endif
                                    </select>
                                </div>
                                @endif

                                @if($hasDedicated)
                                <div class="col-12 mt-2">
                                    <span class="badge bg-warning text-dark"><i class="fas fa-info-circle me-1"></i> يتم ضبط النطاقات الخاصة من العرض التقليدي للمصفوفة</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

<style>
.tree-view {
    display: none;
    animation: fadeIn 0.4s ease;
}
.tree-view.active {
    display: block;
}
.tree-layout-container {
    display: flex;
    gap: 20px;
    height: 600px;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    overflow: hidden;
}
.tree-column {
    flex: 1;
    display: flex;
    flex-direction: column;
    border-left: 1px solid #e2e8f0;
}
.tree-column:last-child {
    border-left: none;
    flex: 1.5; /* Give operations column a bit more space */
}
.column-header {
    padding: 15px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.column-header h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
}
.search-wrap {
    margin-top: 10px;
}
.column-body {
    flex: 1;
    overflow-y: auto;
}
.list-group-item {
    border: none;
    border-bottom: 1px solid #f1f5f9;
    padding: 12px 15px;
    cursor: pointer;
    transition: all 0.2s;
}
.list-group-item:hover {
    background-color: #f8fafc;
}
.list-group-item.active {
    background-color: #eff6ff;
    color: #1e3a8a;
    border-right: 4px solid #3b82f6;
}
.list-group-item.active .text-muted {
    color: #60a5fa !important;
}
.ops-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 15px;
}
.op-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.2s;
}
.op-card:hover {
    border-color: #94a3b8;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.op-card input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.op-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    user-select: none;
    margin: 0;
    cursor: pointer;
}
.op-card.checked {
    background: #f0fdf4;
    border-color: #86efac;
}
.scopes-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px dashed #cbd5e1;
}
</style>


