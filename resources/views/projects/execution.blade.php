@extends('layouts.app')

@section('styles')
{{-- استيراد خط Cairo من Google Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        /* لوحة ألوان حديثة (Modern Palette) */
        --primary-color: #4f46e5;       /* Indigo 600 */
        --primary-light: #eef2ff;       /* Indigo 50 */
        --text-dark: #1e293b;           /* Slate 800 */
        --text-muted: #64748b;          /* Slate 500 */
        --bg-body: #f1f5f9;             /* Slate 100 */
        --card-bg: #ffffff;
        
        --success-soft: #dcfce7;
        --success-text: #166534;
        
        --danger-soft: #fee2e2;
        --danger-text: #991b1b;
        
        --warning-soft: #fef3c7;
        --warning-text: #92400e;

        --radius-lg: 16px;
        --radius-md: 12px;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Cairo', sans-serif;
        color: var(--text-dark);
        line-height: 1.5;
        font-size: 0.9rem;
    }

    /* --- الهيدر (Header) --- */
    .dashboard-header {
        background: var(--card-bg);
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .breadcrumb-item a {
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.8rem;
        transition: color 0.2s;
    }
    .breadcrumb-item.active { color: var(--text-dark); font-weight: 600; }
    .breadcrumb-item a:hover { color: var(--primary-color); }

    .page-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-dark);
        margin-top: 0.5rem;
        letter-spacing: -0.5px;
    }

    /* --- الأزرار الحديثة (Modern Buttons) --- */
    .btn-modern {
        padding: 0.5rem 1rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        border: none;
    }
    .btn-modern-primary {
        background: var(--primary-color);
        color: #fff;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
    }
    .btn-modern-primary:hover {
        background: #4338ca;
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.3);
    }
    .btn-modern-outline {
        background: white;
        border: 1px solid #e2e8f0;
        color: var(--text-dark);
    }
    .btn-modern-outline:hover { background: #f8fafc; border-color: #cbd5e1; }

    /* --- شبكة الإحصائيات (Stats Grid) --- */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        border: 1px solid rgba(255,255,255,0.5);
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }

    .stat-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-bottom: 0.75rem;
    }
    .stat-icon.blue { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4338ca; }
    .stat-icon.red { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #b91c1c; }
    .stat-icon.orange { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #b45309; }
    .stat-icon.green { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #15803d; }

    .stat-label { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; }
    .stat-value { font-size: 1.5rem; font-weight: 800; color: var(--text-dark); margin-top: 0.25rem; }

    /* --- التبويبات الحديثة (Segmented Tabs) --- */
    .modern-tabs {
        background: #e2e8f0;
        padding: 0.3rem;
        border-radius: var(--radius-lg);
        display: inline-flex;
        gap: 0.4rem;
        margin-bottom: 1.5rem;
    }
    .modern-tabs .nav-link {
        border-radius: var(--radius-md);
        color: var(--text-muted);
        font-weight: 600;
        padding: 0.5rem 1.25rem;
        border: none;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    .modern-tabs .nav-link:hover { color: var(--text-dark); }
    .modern-tabs .nav-link.active {
        background: #fff;
        color: var(--primary-color);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    /* --- الكروت والجداول (Cards & Tables) --- */
    .content-card {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        border: none;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    
    .content-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
    }
    .content-card-title { font-size: 1rem; font-weight: 700; color: var(--text-dark); margin: 0; }

    .modern-table { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0; 
        font-size: 0.85rem;
    }
    .modern-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .modern-table tbody td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.85rem;
        vertical-align: middle;
        background: #fff;
        transition: background 0.1s;
    }
    .modern-table tbody tr:last-child td { border-bottom: none; }
    .modern-table tbody tr:hover td { background: #f8fafc; }

    /* --- Badges & Utility --- */
    .badge-soft {
        padding: 0.3em 0.6em;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-block;
    }
    .badge-soft-success { background: var(--success-soft); color: var(--success-text); }
    .badge-soft-danger { background: var(--danger-soft); color: var(--danger-text); }
    .badge-soft-warning { background: var(--warning-soft); color: var(--warning-text); }
    .badge-soft-info { background: #e0f2fe; color: #0369a1; }
    
    .font-num { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-weight: 600; }
    .text-link { color: var(--primary-color); text-decoration: none; font-weight: 600; font-size: 0.8rem; }
    .text-link:hover { text-decoration: underline; }

    /* تحسينات للعرض على شاشات صغيرة */
    @media (max-width: 1200px) {
        .container-fluid {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .modern-table thead th,
        .modern-table tbody td {
            padding: 0.65rem 0.75rem;
        }
    }

    @media (max-width: 992px) {
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .stat-value {
            font-size: 1.3rem;
        }
        
        .page-title {
            font-size: 1.3rem;
        }
    }

    @media (max-width: 768px) {
        .dashboard-header {
            padding: 0.75rem 1rem;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .modern-tabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding: 0.3rem;
        }
        
        .modern-tabs .nav-link {
            white-space: nowrap;
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .content-card-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .btn-modern {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .page-title {
            font-size: 1.2rem;
        }
        
        .modern-table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .modern-table thead th {
            font-size: 0.7rem;
            padding: 0.5rem 0.6rem;
        }
        
        .modern-table tbody td {
            font-size: 0.8rem;
            padding: 0.6rem 0.6rem;
        }
    }

    /* تحسين إمكانية القراءة للجداول */
    .modern-table tbody td .small {
        font-size: 0.8rem;
    }
    
    .modern-table tbody td .text-muted {
        font-size: 0.8rem;
    }

</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab Persistence
        const activeTab = localStorage.getItem('activeExecutionTab');
        if (activeTab) {
            const tabEl = document.querySelector(`button[data-bs-target="${activeTab}"]`);
            if (tabEl) {
                const tab = new bootstrap.Tab(tabEl);
                tab.show();
            }
        }

        const tabLinks = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabLinks.forEach(link => {
            link.addEventListener('shown.bs.tab', function (e) {
                localStorage.setItem('activeExecutionTab', e.target.getAttribute('data-bs-target'));
            });
        });

        // Ensure forms reload to the same tab
        window.activeExecutionTab = activeTab;

        // Assignment Logic
        const assignmentModal = new bootstrap.Modal(document.getElementById('assignmentModal'));
        
        // When clicking assign button
        $(document).on('click', '.btn-assign', function() {
            const assignableType = $(this).data('assignable-type');
            const assignableId = $(this).data('assignable-id');
            const assignableName = $(this).data('assignable-name');
            
            $('#modalAssignableType').val(assignableType);
            $('#modalAssignableId').val(assignableId);
            $('#modalAssignableName').text(assignableName);
            
            $('#modalAssignedTo').val('').trigger('change');
            $('#modalDueDate').val('');
            $('#modalNotes').val('');
            
            assignmentModal.show();
        });

        // Initialize select2 on the modal select
        if (typeof $.fn.select2 !== 'undefined') {
            $('#modalAssignedTo').select2({
                dropdownParent: $('#assignmentModal'),
                ajax: {
                    url: "{{ Route::has('projects.assignments.users') ? route('projects.assignments.users') : (Route::has('assignments.users') ? route('assignments.users') : '') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            project_id: "{{ $project->id }}"
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    text: item.name,
                                    id: item.id
                                }
                            })
                        };
                    },
                    cache: true
                },
                placeholder: '-- اختر مستخدم --',
                minimumInputLength: 0
            });
        }

        // On submitting assignment form
        $('#assignmentForm').on('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>جاري الحفظ...');
            
            $.ajax({
                url: "{{ route('projects.assignments.store', $project->id) }}",
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    submitBtn.prop('disabled', false).text('حفظ التكليف');
                    if (response.success) {
                        assignmentModal.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'نجاح',
                            text: response.message,
                            confirmButtonText: 'حسناً'
                        });
                        
                        const assignableType = $('#modalAssignableType').val();
                        const assignableId = $('#modalAssignableId').val();
                        const containerId = `#assigned-users-${assignableType}-${assignableId}`;
                        
                        if (response.assignments && response.assignments.length) {
                            response.assignments.forEach(function(assignment) {
                                let badgeHtml = `
                                    <span class="badge bg-soft-info text-info border d-inline-flex align-items-center gap-1" title="مكلف: ${assignment.user_name}" id="assignment-badge-${assignment.id}">
                                        <i class="fas fa-user-circle"></i>
                                        ${assignment.user_name}
                                        <a href="javascript:void(0)" class="text-danger ms-1 remove-assignment" data-id="${assignment.id}" style="font-size: 0.8em; text-decoration: none;">
                                            <i class="fas fa-times-circle"></i>
                                        </a>
                                    </span>
                                `;
                                $(containerId).append(badgeHtml);
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: response.message || 'حدث خطأ أثناء حفظ التكليف',
                            confirmButtonText: 'حسناً'
                        });
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).text('حفظ التكليف');
                    let errorMsg = 'حدث خطأ في النظام.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: errorMsg,
                        confirmButtonText: 'حسناً'
                    });
                }
            });
        });

        // When removing assignment
        $(document).on('click', '.remove-assignment', function() {
            const assignmentId = $(this).data('id');
            const badge = $(this).closest('.badge');
            
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: "سيتم إلغاء تكليف هذا المستخدم من المهمة.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، إلغاء التكليف',
                cancelButtonText: 'تراجع'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/assignments/${assignmentId}`,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                badge.fadeOut(300, function() {
                                    $(this).remove();
                                });
                                Swal.fire({
                                    icon: 'success',
                                    title: 'نجاح',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: 'فشل في إلغاء التكليف. يرجى المحاولة مرة أخرى.',
                                confirmButtonText: 'حسناً'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endsection

@section('content')

{{-- Header --}}
<div class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="#">المشاريع</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $project->project_name }}</li>
                </ol>
            </nav>
            <h1 class="page-title">
                لوحة متابعة التنفيذ
            </h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.execution.print', $project->id) }}" target="_blank" class="btn-modern btn-modern-outline">
                <i class="fas fa-print me-2"></i>طباعة
            </a>
            
            {{-- ERP Sync Status & Actions --}}
            <div class="d-flex gap-2">
                @can('projects.edit')
                <form action="{{ route('projects.bulk-sync-to-erp') }}" method="POST" class="d-inline">
                    @csrf
                    <!-- <button type="submit" class="btn-modern btn-modern-outline" onclick="return confirmAction(this, 'هل أنت متأكد من رغبتك في مزامنة جميع المشاريع المؤهلة مع ERPNext؟')">
                        <i class="fas fa-sync me-2"></i>مزامنة الكل مع ERP
                    </button> -->
                </form>
                @endcan

                <!-- <div class="dropdown">
                    <button class="btn-modern btn-modern-outline dropdown-toggle" type="button" id="erpStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        @if($project->erpnext_project_id)
                            <i class="fas fa-sync-alt me-2 text-success"></i>متزامن مع ERP
                        @elseif($project->sync_status === 'failed')
                            <i class="fas fa-exclamation-circle me-2 text-danger"></i>فشل المزامنة
                        @else
                            <i class="fas fa-sync me-2 text-muted"></i>غير متزامن
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="erpStatusDropdown" style="min-width: 250px;">
                        @if($project->erpnext_project_id)
                            <li>
                                <div class="mb-2">
                                    <small class="text-muted d-block mb-1">معرف المشروع في ERP:</small>
                                    <span class="badge bg-light text-dark font-num w-100 p-2 border">{{ $project->erpnext_project_id }}</span>
                                </div>
                                <div class="text-center pt-2 border-top">
                                    @can('projects.edit')
                                    <form action="{{ route('projects.sync-to-erp', $project->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-link text-primary text-decoration-none">
                                            <i class="fas fa-redo me-1"></i> إعادة المزامنة
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </li>
                        @else
                            @if($project->sync_status === 'failed')
                                <li class="mb-3">
                                    <div class="alert alert-danger p-2 mb-2" style="font-size: 0.8rem;">
                                        <i class="fas fa-exclamation-triangle me-1"></i> 
                                        <strong>خطأ:</strong> {{ $project->sync_error }}
                                    </div>
                                </li>
                            @endif
                            <li>
                                @can('projects.edit')
                                <form action="{{ route('projects.sync-to-erp', $project->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-cloud-upload-alt me-1"></i> مزامنة هذا المشروع الآن
                                    </button>
                                </form>
                                @endcan
                            </li>
                        @endif
                    </ul>
                </div> -->
            </div>
            
            <a href="{{ route('projects.show', $project->id) }}" class="btn-modern btn-modern-primary">
                <i class="fas fa-arrow-right me-2"></i>عودة للمشروع
            </a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    {{-- Stats Grid --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">إجمالي الأنشطة</div>
                    <div class="stat-value font-num">{{ $project->executiveActivities->count() + $project->preliminaryActivities->count() }}</div>
                </div>
                <div class="stat-icon blue">
                    <x-icon name="layer-group" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">تجاوز الميزانية</div>
                    <div class="stat-value font-num">-- <span class="fs-6 text-muted">﷼</span></div>
                </div>
                <div class="stat-icon green">
                    <x-icon name="dollar-sign" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">إجمالي التأخير</div>
                    <div class="stat-value font-num">-- <span class="fs-6 text-muted">يوم</span></div>
                </div>
                <div class="stat-icon orange">
                    <x-icon name="clock" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">نسبة الإنجاز</div>
                    <div class="stat-value font-num">-- <span class="fs-6 text-muted">%</span></div>
                </div>
                <div class="stat-icon purple">
                    <x-icon name="chart-pie" />
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs & Content --}}
    <div>
        {{-- Navigation --}}
        <ul class="nav modern-tabs" id="modernTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="preliminary-tab" data-bs-toggle="tab" data-bs-target="#preliminary" type="button" role="tab">
                    <i class="fas fa-clipboard-list me-2"></i>الأنشطة التحضيرية
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="executive-tab" data-bs-toggle="tab" data-bs-target="#executive" type="button" role="tab">
                    <i class="fas fa-tasks me-2"></i>الأنشطة التنفيذية
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab">
                    <i class="fas fa-file-invoice me-2"></i>المبررات المالية
                    @if(session('financial_count')) 
                        <span class="badge bg-danger rounded-pill ms-2" style="font-size: 0.7em;">{{ session('financial_count') }}</span> 
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="technical-tab" data-bs-toggle="tab" data-bs-target="#technical" type="button" role="tab">
                    <i class="fas fa-hourglass-half me-2"></i>التأخيرات الزمنية
                </button>
            </li>
        </ul>

        <div class="tab-content" id="modernTabContent">
            
            {{-- Tab 1: Preliminary --}}
            <div class="tab-pane fade show active" id="preliminary" role="tabpanel">
                <div class="content-card">
                    <div class="content-card-header">
                        <h5 class="content-card-title">الأنشطة الأولية (التحضيرية)</h5>
                    </div>
                    <div class="p-3">
                        @include('projects.partials.implementation.execution-preliminary')
                    </div>
                </div>
            </div>

            {{-- Tab 2: Executive --}}
            <div class="tab-pane fade" id="executive" role="tabpanel">
                <div class="content-card">
                    <div class="content-card-header">
                        <h5 class="content-card-title">الأنشطة التنفيذية</h5>
                    </div>
                    <div class="p-3">
                        @include('projects.partials.implementation.execution-executive')
                    </div>
                </div>
            </div>

            {{-- Tab 2: Financial --}}
            <div class="tab-pane fade" id="financial" role="tabpanel">
                <div class="content-card">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>النشاط / البند</th>
                                    <th>المخطط</th>
                                    <th>الفعلي</th>
                                    <th>التجاوز</th>
                                    <th style="width: 30%">شرح التجاوز</th>
                                    <th>المرفقات</th>
                                    <th class="text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $hasFin = false; @endphp
                                
                                {{-- Loop Preliminary --}}
                                @foreach($project->preliminaryActivities ?? [] as $act)
                                    @foreach($act->procedures ?? [] as $proc)
                                        @foreach($proc->budgetJustifications ?? [] as $just)
                                            @php $hasFin = true; @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $act->name }}</div>
                                                    <div class="small text-muted">{{ $proc->procedure_name }}</div>
                                                </td>
                                                <td class="font-num text-muted">{{ number_format($just->planned_amount, 2) }}</td>
                                                <td class="font-num text-dark fw-bold">{{ number_format($just->actual_amount, 2) }}</td>
                                                <td>
                                                    <span class="badge-soft badge-soft-danger font-num">
                                                        +{{ number_format($just->actual_amount - $just->planned_amount, 2) }}
                                                    </span>
                                                </td>
                                                <td class="text-muted small" style="line-height: 1.4;">{{ Str::limit($just->justification, 60) }}</td>
                                                <td>
                                                    @foreach($just->attachments ?? [] as $att)
                                                        <a href="{{ asset('storage/'.$att) }}" target="_blank" class="text-link d-inline-block me-2">
                                                            <x-icon name="file" class="me-1" size="14" />ملف
                                                        </a>
                                                    @endforeach
                                                </td>
                                                <td class="text-center">
                                                    @if($just->approval_status == 'approved') <span class="badge-soft badge-soft-success">معتمد</span>
                                                    @elseif($just->approval_status == 'rejected') <span class="badge-soft badge-soft-danger">مرفوض</span>
                                                    @else <span class="badge-soft badge-soft-warning">معلق</span> @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @endforeach

                                {{-- Loop Executive --}}
                                @foreach($project->executiveActivities ?? [] as $act)
                                    @foreach($act->actions ?? [] as $action)
                                        @if($action->budgetJustification)
                                            @php $just = $action->budgetJustification; $hasFin = true; @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $act->name }}</div>
                                                    <div class="small text-muted">{{ $action->action }}</div>
                                                </td>
                                                <td class="font-num text-muted">{{ number_format($just->planned_amount, 2) }}</td>
                                                <td class="font-num text-dark fw-bold">{{ number_format($just->actual_amount, 2) }}</td>
                                                <td>
                                                    <span class="badge-soft badge-soft-danger font-num">
                                                        +{{ number_format($just->actual_amount - $just->planned_amount, 2) }}
                                                    </span>
                                                </td>
                                                <td class="text-muted small">{{ Str::limit($just->justification, 60) }}</td>
                                                <td>
                                                    @foreach($just->attachments ?? [] as $att)
                                                        <a href="{{ asset('storage/'.$att) }}" target="_blank" class="text-link d-inline-block me-2">
                                                            <x-icon name="file" class="me-1" size="14" />ملف
                                                        </a>
                                                    @endforeach
                                                </td>
                                                <td class="text-center">
                                                    @if($just->approval_status == 'approved') <span class="badge-soft badge-soft-success">معتمد</span>
                                                    @elseif($just->approval_status == 'rejected') <span class="badge-soft badge-soft-danger">مرفوض</span>
                                                    @else <span class="badge-soft badge-soft-warning">معلق</span> @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endforeach

                                @if(!$hasFin)
                                    <tr><td colspan="7" class="text-center py-5 text-muted">لا توجد بيانات مالية للعرض</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Technical --}}
            <div class="tab-pane fade" id="technical" role="tabpanel">
                <div class="content-card">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>النشاط / المهمة</th>
                                    <th>تاريخ الانتهاء (المخطط)</th>
                                    <th>تاريخ الانتهاء (الفعلي)</th>
                                    <th>فترة التأخير</th>
                                    <th style="width: 35%">سبب التأخير</th>
                                    <th>المرفقات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $hasTech = false; @endphp
                                
                                {{-- Loop Preliminary --}}
                                @foreach($project->preliminaryActivities ?? [] as $act)
                                    @foreach($act->procedures ?? [] as $proc)
                                        @foreach($proc->technicalJustifications ?? [] as $just)
                                            @php $hasTech = true; @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $act->name }}</div>
                                                    <div class="small text-muted">{{ $proc->procedure_name }}</div>
                                                </td>
                                                <td class="font-num text-muted">{{ optional($proc->end_date)->format('Y-m-d') }}</td>
                                                <td class="font-num text-dark fw-bold">{{ optional($just->actual_end_date)->format('Y-m-d') }}</td>
                                                <td><span class="badge-soft badge-soft-warning font-num">{{ $just->delay_days }} يوم</span></td>
                                                <td class="text-muted small">{{ Str::limit($just->justification, 80) }}</td>
                                                <td>
                                                    @foreach($just->attachments ?? [] as $att)
                                                        <a href="{{ asset('storage/'.$att) }}" target="_blank" class="text-link"><x-icon name="paperclip" size="14" /></a>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @endforeach

                                {{-- Loop Executive --}}
                                @foreach($project->executiveActivities ?? [] as $act)
                                    @foreach($act->actions ?? [] as $action)
                                        @foreach($action->technicalJustifications ?? [] as $just)
                                            @php $hasTech = true; @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $act->name }}</div>
                                                    <div class="small text-muted">{{ $action->action }}</div>
                                                </td>
                                                <td class="font-num text-muted">{{ optional($action->end_date_gregorian)->format('Y-m-d') }}</td>
                                                <td class="font-num text-dark fw-bold">{{ optional($just->actual_end_date)->format('Y-m-d') }}</td>
                                                <td><span class="badge-soft badge-soft-warning font-num">{{ $just->delay_days }} يوم</span></td>
                                                <td class="text-muted small">{{ Str::limit($just->explanation, 80) }}</td>
                                                <td>
                                                    @foreach($just->attachments ?? [] as $att)
                                                        <a href="{{ asset('storage/'.$att) }}" target="_blank" class="text-link"><x-icon name="paperclip" size="14" /></a>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @endforeach

                                @if(!$hasTech)
                                    <tr><td colspan="6" class="text-center py-5 text-muted">لا توجد تأخيرات مسجلة</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="assignmentModalLabel">
                    <i class="fas fa-user-plus me-2"></i>تكليف مستخدم بمهمة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignmentForm">
                @csrf
                <input type="hidden" name="assignable_type" id="modalAssignableType">
                <input type="hidden" name="assignable_id" id="modalAssignableId">
                <div class="modal-body text-start" dir="rtl">
                    <div class="mb-3">
                        <label class="form-label fw-bold">المهمة/النشاط:</label>
                        <div id="modalAssignableName" class="form-control bg-light" readonly style="min-height: 38px; display: flex; align-items: center; justify-content: flex-start;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="modalAssignedTo" class="form-label fw-bold">اختر المستخدمين:</label>
                        <select name="assigned_to[]" id="modalAssignedTo" class="form-select select2-modal" multiple="multiple" style="width: 100%;">
                            @foreach($users ?? [] as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modalDueDate" class="form-label fw-bold">تاريخ الانتهاء المطلوب (اختياري):</label>
                        <input type="date" name="due_date" id="modalDueDate" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="modalNotes" class="form-label fw-bold">ملاحظات (اختياري):</label>
                        <textarea name="notes" id="modalNotes" class="form-control" rows="3" placeholder="اكتب أي ملاحظات أو توجيهات هنا..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التكليف</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection