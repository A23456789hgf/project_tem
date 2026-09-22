<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الشجرة التنظيمية الديناميكية للجهات</title>
    <!-- إضافة Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- إضافة Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- إضافة Bootstrap Treeview CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-treeview/1.2.0/bootstrap-treeview.min.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .treeview .list-group-item {
            cursor: pointer;
            text-align: right;
            border: 1px solid #dee2e6;
            margin-bottom: 2px;
            border-radius: 5px;
            transition: all 0.3s ease;
            padding: 10px 15px !important;
        }

        .treeview .list-group-item:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
        }

        .treeview span.indent {
            margin-left: 0px;
            margin-right: 10px;
        }

        .treeview .node-disabled {
            color: silver;
            cursor: not-allowed;
        }

        #treeview-container {
            max-height: 70vh;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .node-selected {
            background-color: #007bff !important;
            color: white !important;
        }

        .authority-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .action-buttons {
            position: absolute;
            top: 15px;
            left: 15px;
        }

        .tree-node-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 10px;
        }

        .node-left-section {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .node-right-section {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        /* نوع الجهة - Badge */
        .type-entity-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            color: white;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .type-entity-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        .type-entity-badge i {
            font-size: 0.9em;
        }

        .type-entity-badge .type-name {
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .node-name {
            font-weight: 600;
            color: #2c3e50;
            white-space: nowrap;
        }

        .node-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .node-badge {
            font-size: 0.75em;
            padding: 4px 8px;
            border-radius: 12px;
            white-space: nowrap;
        }

        .hierarchy-level-0 { 
            font-weight: bold; 
            color: #2c3e50;
            background: linear-gradient(to right, #e8f4fd 0%, transparent 100%);
            padding: 8px 12px;
            border-radius: 6px;
        }

        .hierarchy-level-1 { 
            font-weight: 600; 
            color: #34495e;
            background: linear-gradient(to right, #fef7e0 0%, transparent 100%);
            padding: 6px 12px;
            border-radius: 6px;
        }

        .hierarchy-level-2 { 
            color: #7f8c8d;
            background: linear-gradient(to right, #f0f9f0 0%, transparent 100%);
            padding: 4px 12px;
            border-radius: 6px;
        }

        .hierarchy-level-3 { 
            color: #95a5a6; 
            font-style: italic;
            background: linear-gradient(to right, #fafafa 0%, transparent 100%);
            padding: 4px 12px;
            border-radius: 6px;
        }

        /* Treeview custom SVG mapping */
        .treeview span.icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            vertical-align: middle;
        }

        .treeview span.icon::before {
            content: "" !important;
            display: none !important;
        }

        .treeview .fa-plus-circle {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="%23198754"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 17.7-10.7 24-24 24s-24-10.7-24-24z"/></svg>') !important;
        }

        .treeview .fa-minus-circle {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="%23dc3545"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM184 232H328c13.3 0 24 10.7 24 24s-10.7 24-24 24H184c-13.3 0-24-10.7-24-24s10.7-24 24-24z"/></svg>') !important;
        }

        .treeview .fa-folder {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="%23ffc107"><path d="M64 480c-35.3 0-64-28.7-64-64V96C0 60.7 28.7 32 64 32H192c17.7 0 32 14.3 32 32v16H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64z"/></svg>') !important;
        }

        .treeview .fa-sitemap {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="%230d6efd"><path d="M256 0a48 48 0 1 1 0 96 48 48 0 1 1 0-96zm0 128c-17.7 0-32 14.3-32 32v48H64c-17.7 0-32 14.3-32 32v64H0v48c0 17.7 14.3 32 32 32h96c17.7 0 32-14.3 32-32V304H96V240H352v64H320v48c0 17.7 14.3 32 32 32h96c17.7 0 32-14.3 32-32V304h32v-64c0-17.7-14.3-32-32-32H288V160c0-17.7-14.3-32-32-32zM32 352a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm320 0a48 48 0 1 1 96 0 48 48 0 1 1 -96 0z"/></svg>') !important;
        }

        /* تحسينات للشاشات الصغيرة */
        @media (max-width: 768px) {
            .tree-node-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .node-left-section {
                width: 100%;
                flex-wrap: wrap;
            }

            .node-right-section {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .type-entity-badge .type-name {
                max-width: 80px;
            }
        }

        /* تأثيرات حركية */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .treeview .list-group-item {
            animation: fadeIn 0.3s ease;
        }

        /* فلتر نوع الجهة */
        .type-filter-btn {
            transition: all 0.3s ease;
        }

        .type-filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .type-filter-btn.active {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-sitemap me-2"></i> الشجرة التنظيمية الديناميكية للجهات</h4>
                        <div class="action-buttons d-flex align-items-center gap-1">
                            <button id="btn-refresh" class="btn btn-sm btn-light d-flex align-items-center justify-content-center" title="تحديث البيانات" style="width: 28px; height: 28px; padding: 0;">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button id="btn-expand-all" class="btn btn-sm btn-light d-flex align-items-center justify-content-center" title="توسيع الكل" style="width: 28px; height: 28px; padding: 0;">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button id="btn-collapse-all" class="btn btn-sm btn-light d-flex align-items-center justify-content-center" title="طي الكل" style="width: 28px; height: 28px; padding: 0;">
                                <i class="fas fa-compress"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- معلومات إحصائية -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card d-flex flex-column align-items-center">
                                    <i class="fas fa-building text-primary mb-2" style="font-size: 32px;"></i>
                                    <h5 id="total-agencies">0</h5>
                                    <small class="text-muted">إجمالي الجهات</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card d-flex flex-column align-items-center">
                                    <i class="fas fa-layer-group text-success mb-2" style="font-size: 32px;"></i>
                                    <h5 id="main-agencies">0</h5>
                                    <small class="text-muted">الجهات الرئيسية</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card d-flex flex-column align-items-center">
                                    <i class="fas fa-code-branch text-info mb-2" style="font-size: 32px;"></i>
                                    <h5 id="sub-agencies">0</h5>
                                    <small class="text-muted">الجهات الفرعية</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card d-flex flex-column align-items-center">
                                    <i class="fas fa-project-diagram text-warning mb-2" style="font-size: 32px;"></i>
                                    <h5 id="tree-levels">0</h5>
                                    <small class="text-muted">مستويات التدرج</small>
                                </div>
                            </div>
                        </div>

                        <!-- شريط البحث والإجراءات -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" id="search-box" class="form-control" placeholder="ابحث في اسم الجهة أو الوصف...">
                                    <button class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center" type="button" id="btn-search" style="width: 40px;">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" type="button" id="btn-clear-search" title="مسح البحث" style="width: 40px;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-start">
                                <div class="btn-group">
                                    @can('authorities.create')
                                    <a href="{{ route('authorities.create') }}" class="btn btn-success d-inline-flex align-items-center gap-1" title="إضافة جهة جديدة">
                                        <i class="fas fa-plus"></i>إضافة جهة
                                    </a>
                                    @endcan
                                    <a href="{{ route('authorities.index', ['view' => 'list']) }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" title="عرض القائمة">
                                        <i class="fas fa-list"></i>عرض القائمة
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- فلتر حسب النوع -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="btn-group flex-wrap" role="group" id="type-filters">
                                    <button type="button" class="btn btn-outline-primary active filter-btn type-filter-btn d-inline-flex align-items-center gap-1" data-filter="all">
                                        <i class="fas fa-globe"></i>جميع الجهات
                                    </button>
                                    <button type="button" class="btn btn-outline-warning filter-btn type-filter-btn d-inline-flex align-items-center gap-1" data-filter="main">
                                        <i class="fas fa-star"></i>رئيسية فقط
                                    </button>
                                </div>
                                <!-- سيتم إضافة أزرار أنواع الجهات ديناميكياً -->
                            </div>
                        </div>

                        <!-- منطقة عرض الشجرة -->
                        <div id="treeview-container" class="bg-white">
                            <!-- سيتم تعبئتها ديناميكياً -->
                        </div>

                        <!-- حالة التحميل -->
                        <div id="loading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">جار التحميل...</span>
                            </div>
                            <p class="text-muted mt-2">جار تحميل الشجرة التنظيمية...</p>
                        </div>

                        <!-- معلومات الجهة المحددة -->
                        <div id="agency-details" class="mt-4" style="display: none;">
                            <div class="authority-info">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 id="selected-agency-name" class="d-flex align-items-center">
                                            <i class="fas fa-building me-2" style="width: 24px; height: 24px;"></i>
                                        </h4>
                                        <p id="selected-agency-description" class="mb-1"></p>
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <small class="d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-tag" style="width: 14px; height: 14px;"></i> 
                                                    النوع: <span id="selected-agency-type"></span>
                                                </small>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-map-marker-alt" style="width: 14px; height: 14px;"></i> 
                                                    المحافظة: <span id="selected-agency-governorate"></span>
                                                </small>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-map-pin" style="width: 14px; height: 14px;"></i> 
                                                    المديرية: <span id="selected-agency-directorate"></span>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-4">
                                                <small class="d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-level-up-alt" style="width: 14px; height: 14px;"></i> 
                                                    المستوى: <span id="selected-agency-level"></span>
                                                </small>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-network-wired" style="width: 14px; height: 14px;"></i> 
                                                    الجهات التابعة: <span id="selected-agency-children"></span>
                                                </small>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="d-inline-flex align-items-center gap-1">
                                                    <i class="fas fa-calendar" style="width: 14px; height: 14px;"></i> 
                                                    تاريخ الإنشاء: <span id="selected-agency-created"></span>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-start">
                                        <div class="btn-group-vertical">
                                            <a href="#" id="btn-edit-agency" class="btn btn-light btn-sm mb-2 d-inline-flex align-items-center gap-1">
                                                <i class="fas fa-edit"></i>تعديل
                                            </a>
                                            <a href="#" id="btn-view-agency" class="btn btn-light btn-sm mb-2 d-inline-flex align-items-center gap-1">
                                                <i class="fas fa-eye"></i>عرض التفاصيل
                                            </a>
                                            <button id="btn-add-child" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="fas fa-plus"></i>إضافة جهة تابعة
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Treeview JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-treeview/1.2.0/bootstrap-treeview.min.js"></script>

    <script>
    $(document).ready(function() {
        // متغيرات التطبيق
        let treeData = [];
        let filteredTreeData = [];
        let currentFilter = 'all';
        let selectedNode = null;
        let typeEntities = []; // لتخزين أنواع الجهات

        // تحميل الشجرة عند فتح الصفحة
        loadTreeView();

        // دالة تحميل الشجرة
        function loadTreeView(searchTerm = '') {
            $('#loading').show();
            $('#treeview-container').hide();
            $('#agency-details').hide();

            $.ajax({
                url: '{{ route("authorities.tree.getData") }}',
                method: 'GET',
                data: { 
                    search: searchTerm,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        treeData = response.data;
                        filteredTreeData = treeData;
                        
                        // استخراج أنواع الجهات من البيانات
                        extractTypeEntities(treeData);
                        buildTypeFilters();
                        
                        initTreeView(filteredTreeData);
                        updateStatistics(treeData);
                    } else {
                        $('#treeview-container').html(
                            '<div class="alert alert-info text-center">' +
                            '<i class="fas fa-info-circle me-2"></i>لا توجد جهات لعرضها' +
                            '</div>'
                        ).show();
                        updateStatistics([]);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading tree data:', error);
                    $('#treeview-container').html(
                        '<div class="alert alert-danger text-center">' +
                        '<i class="fas fa-exclamation-triangle me-2"></i>حدث خطأ في تحميل البيانات' +
                        '</div>'
                    ).show();
                },
                complete: function() {
                    $('#loading').hide();
                    $('#treeview-container').show();
                }
            });
        }

        // دالة استخراج أنواع الجهات من البيانات
        function extractTypeEntities(nodes) {
            typeEntities = [];
            const typeMap = new Map();
            
            function traverse(nodeList) {
                nodeList.forEach(node => {
                    if (node.data && node.data.type_entity) {
                        const typeEntity = node.data.type_entity;
                        if (!typeMap.has(typeEntity.id)) {
                            typeMap.set(typeEntity.id, {
                                id: typeEntity.id,
                                name: typeEntity.name,
                                color: typeEntity.color || '#6c757d'
                            });
                        }
                    }
                    if (node.nodes && node.nodes.length > 0) {
                        traverse(node.nodes);
                    }
                });
            }
            
            traverse(nodes);
            typeEntities = Array.from(typeMap.values());
        }

        // دالة بناء أزرار الفلترة حسب نوع الجهة
        function buildTypeFilters() {
            const filtersContainer = $('#type-filters');
            
            // إزالة الأزرار القديمة (ما عدا الأزرار الأساسية)
            filtersContainer.find('.type-entity-filter').remove();
            
            // إضافة أزرار أنواع الجهات
            typeEntities.forEach(type => {
                const button = `
                    <button type="button" class="btn btn-outline-secondary type-filter-btn type-entity-filter d-inline-flex align-items-center gap-1" 
                            data-filter="type-${type.id}" 
                            data-type-id="${type.id}"
                            style="border-color: ${type.color}; color: ${type.color};">
                        <i class="fas fa-tag"></i>${type.name}
                    </button>
                `;
                filtersContainer.append(button);
            });

            // إضافة event listeners للأزرار الجديدة
            $('.type-entity-filter').on('click', function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                currentFilter = $(this).data('filter');
                filterTreeData(currentFilter);
            });
        }

        // دالة تهيئة الشجرة
        function initTreeView(data) {
            $('#treeview-container').treeview({
                data: data,
                levels: 2,
                expandIcon: 'fas fa-plus-circle text-success',
                collapseIcon: 'fas fa-minus-circle text-danger',
                emptyIcon: 'fas fa-folder text-warning',
                nodeIcon: 'fas fa-sitemap text-primary',
                showTags: true,
                onNodeSelected: function(event, node) {
                    selectedNode = node;
                    showAgencyDetails(node);
                },
                onNodeUnselected: function(event, node) {
                    $('#agency-details').hide();
                    selectedNode = null;
                },
                highlightSearchResults: true,
                searchResultColor: '#ffffff',
                searchResultBackColor: '#007bff',
            });

            // تخصيص عرض العقد بعد التهيئة
            customizeNodeDisplay();
        }

        // دالة تخصيص عرض العقد
        function customizeNodeDisplay() {
            $('#treeview-container .list-group-item').each(function() {
                const nodeId = $(this).data('nodeid');
                const node = findNodeById(treeData, nodeId);
                
                if (node && node.data) {
                    const nodeContent = $(this).find('.node-content');
                    if (nodeContent.length === 0) {
                        // بناء محتوى العقدة المخصص
                        const customContent = buildCustomNodeContent(node);
                        $(this).html(customContent);
                    }
                }
            });
        }

        // دالة بناء محتوى العقدة المخصص
        function buildCustomNodeContent(node) {
            const levelClass = `hierarchy-level-${node.data.level || 0}`;
            
            // نوع الجهة
            let typeEntityHTML = '';
            if (node.data.type_entity) {
                const typeColor = node.data.type_entity.color || '#6c757d';
                typeEntityHTML = `
                    <span class="type-entity-badge" style="background: linear-gradient(135deg, ${typeColor} 0%, ${adjustColor(typeColor, -20)} 100%);" 
                          title="${node.data.type_entity.name}">
                        <i class="fas fa-tag"></i>
                        <span class="type-name">${node.data.type_entity.name}</span>
                    </span>
                `;
            } else {
                typeEntityHTML = `
                    <span class="type-entity-badge" style="background: linear-gradient(135deg, #a8a8a8 0%, #6c757d 100%);" title="بدون نوع">
                        <i class="fas fa-question-circle"></i>
                    </span>
                `;
            }
            
            // المحافظة والمديرية
            let locationHTML = '';
            if (node.data.governorate) {
                locationHTML += `
                    <span class="badge bg-light text-dark border node-badge" title="المحافظة">
                        <i class="fas fa-map-marker-alt text-danger"></i>
                        ${node.data.governorate.name}
                    </span>
                `;
            }
            if (node.data.directorate) {
                locationHTML += `
                    <span class="badge bg-light text-dark border node-badge" title="المديرية">
                        <i class="fas fa-map-pin text-primary"></i>
                        ${node.data.directorate.name}
                    </span>
                `;
            }
            
            // الحالة
            const statusBadge = node.data.is_active 
                ? '<span class="badge bg-success node-badge">نشط</span>'
                : '<span class="badge bg-danger node-badge">غير نشط</span>';
            
            // عدد الجهات التابعة
            const childrenBadge = node.data.children_count > 0
                ? `<span class="badge bg-info node-badge"><i class="fas fa-sitemap"></i> ${node.data.children_count}</span>`
                : '';
            
            return `
                <div class="tree-node-content ${levelClass}">
                    <div class="node-left-section">
                        ${typeEntityHTML}
                        <span class="node-name">${node.data.agency_name || node.text}</span>
                    </div>
                    <div class="node-right-section">
                        <div class="node-meta">
                            ${locationHTML}
                            ${statusBadge}
                            ${childrenBadge}
                        </div>
                    </div>
                </div>
            `;
        }

        // دالة مساعدة لتعديل اللون
        function adjustColor(color, percent) {
            const num = parseInt(color.replace("#", ""), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255))
                .toString(16).slice(1);
        }

        // دالة البحث عن عقدة بالمعرف
        function findNodeById(nodes, id) {
            for (let node of nodes) {
                if (node.id == id) {
                    return node;
                }
                if (node.nodes && node.nodes.length > 0) {
                    const found = findNodeById(node.nodes, id);
                    if (found) return found;
                }
            }
            return null;
        }

        // دالة عرض تفاصيل الجهة المحددة
        function showAgencyDetails(node) {
            $('#selected-agency-name').html(`<i class="fas fa-building me-2"></i>${node.data.agency_name || node.text}`);
            
            // نوع الجهة
            if (node.data.type_entity) {
                $('#selected-agency-type').html(`
                    <span class="badge" style="background-color: ${node.data.type_entity.color};">${node.data.type_entity.name}</span>
                `);
            } else {
                $('#selected-agency-type').text('غير محدد');
            }
            
            // المحافظة والمديرية
            $('#selected-agency-governorate').text(node.data.governorate ? node.data.governorate.name : 'غير محدد');
            $('#selected-agency-directorate').text(node.data.directorate ? node.data.directorate.name : 'غير محدد');
            
            $('#selected-agency-level').text((node.data.level || 0) + 1);
            $('#selected-agency-children').text(node.data.children_count || 0);
            $('#selected-agency-created').text(node.data.created_at || 'غير محدد');
            
            // تحديث روابط الإجراءات
            if (node.a_attr && node.a_attr.href) {
                $('#btn-edit-agency').attr('href', node.a_attr.href.replace('/show', '/edit'));
                $('#btn-view-agency').attr('href', node.a_attr.href);
                $('#btn-add-child').attr('href', '{{ route("authorities.create") }}?parent_id=' + node.id);
            }
            
            $('#agency-details').slideDown();
        }

        // دالة تحديث الإحصائيات
        function updateStatistics(data) {
            let totalAgencies = countNodes(data);
            let mainAgencies = data.length;
            let subAgencies = totalAgencies - mainAgencies;
            let maxLevel = findMaxLevel(data);
            
            $('#total-agencies').text(totalAgencies);
            $('#main-agencies').text(mainAgencies);
            $('#sub-agencies').text(subAgencies);
            $('#tree-levels').text(maxLevel + 1);
        }

        // دالة عد جميع العقد
        function countNodes(nodes) {
            let count = 0;
            nodes.forEach(node => {
                count++;
                if (node.nodes && node.nodes.length > 0) {
                    count += countNodes(node.nodes);
                }
            });
            return count;
        }

        // دالة إيجاد أقصى مستوى في الشجرة
        function findMaxLevel(nodes, currentLevel = 0) {
            let maxLevel = currentLevel;
            nodes.forEach(node => {
                let nodeLevel = currentLevel;
                if (node.nodes && node.nodes.length > 0) {
                    let childMaxLevel = findMaxLevel(node.nodes, currentLevel + 1);
                    maxLevel = Math.max(maxLevel, childMaxLevel);
                }
            });
            return maxLevel;
        }

        // دالة تصفية البيانات حسب النوع
        function filterTreeData(filterType) {
            if (filterType === 'all') {
                filteredTreeData = treeData;
            } else if (filterType === 'main') {
                filteredTreeData = treeData.filter(node => !node.data.parent_id);
            } else if (filterType.startsWith('type-')) {
                const typeId = parseInt(filterType.replace('type-', ''));
                filteredTreeData = filterNodesByType(treeData, typeId);
            }
            
            $('#treeview-container').treeview('remove');
            initTreeView(filteredTreeData);
        }

        // دالة تصفية العقد حسب نوع الجهة
        function filterNodesByType(nodes, typeId) {
            return nodes.filter(node => {
                return node.data && node.data.type_entity && node.data.type_entity.id === typeId;
            }).map(node => {
                let newNode = {...node};
                if (newNode.nodes && newNode.nodes.length > 0) {
                    newNode.nodes = filterNodesByType(newNode.nodes, typeId);
                }
                return newNode;
            });
        }

        // البحث في الشجرة
        $('#btn-search').on('click', function() {
            const searchTerm = $('#search-box').val();
            if (searchTerm.trim() !== '') {
                loadTreeView(searchTerm);
            }
        });

        // مسح البحث
        $('#btn-clear-search').on('click', function() {
            $('#search-box').val('');
            loadTreeView();
        });

        // البحث عند الضغط على Enter
        $('#search-box').on('keypress', function(e) {
            if (e.which === 13) {
                $('#btn-search').click();
            }
        });

        // تصفية حسب النوع
        $(document).on('click', '.filter-btn', function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            currentFilter = $(this).data('filter');
            filterTreeData(currentFilter);
        });

        // توسيع الكل
        $('#btn-expand-all').on('click', function() {
            $('#treeview-container').treeview('expandAll', { levels: 10 });
            customizeNodeDisplay();
        });

        // طي الكل
        $('#btn-collapse-all').on('click', function() {
            $('#treeview-container').treeview('collapseAll');
        });

        // تحديث البيانات
        $('#btn-refresh').on('click', function() {
            loadTreeView();
            $('#search-box').val('');
        });

        // إضافة جهة تابعة
        $('#btn-add-child').on('click', function(e) {
            if (!selectedNode) {
                e.preventDefault();
                alert('يرجى اختيار جهة أولاً لإضافة جهة تابعة لها');
            }
        });
    });
    </script>
</body>
</html>