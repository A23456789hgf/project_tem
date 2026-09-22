@extends('layouts.app')

@section('title', 'وحدة الاستشارات والإحالات')

@section('styles')
<style>
    .consultation-center-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 1rem;
        color: #fff;
        padding: 1.75rem 2rem;
        margin-bottom: 1.75rem;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.15);
    }
    
    .stat-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: all 0.25s ease;
    }
    .stat-card-link:hover {
        transform: translateY(-3px);
    }
    .stat-card {
        border-radius: 1rem;
        border: 2px solid transparent;
        transition: all 0.2s ease;
        background: #ffffff;
    }
    .stat-card.active-tab-card {
        border-color: #3b82f6;
        box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.2);
    }
    
    .nav-tabs-custom {
        border-bottom: 2px solid #e2e8f0;
        gap: 0.5rem;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #64748b;
        font-weight: 600;
        padding: 0.85rem 1.25rem;
        border-radius: 0.5rem 0.5rem 0 0;
        transition: all 0.2s ease;
    }
    .nav-tabs-custom .nav-link:hover {
        color: #1e293b;
        background-color: #f8fafc;
    }
    .nav-tabs-custom .nav-link.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
        background-color: #eff6ff;
    }

    /* Consultation Record Cards */
    .consultation-record-card {
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        transition: all 0.25s ease;
        background: #ffffff;
        position: relative;
    }
    .consultation-record-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 12px 28px -5px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }
    .consultation-record-card.card-pending {
        border-top: 4px solid #f59e0b;
    }
    .consultation-record-card.card-responded {
        border-top: 4px solid #10b981;
    }
    .consultation-record-card.card-returned {
        border-top: 4px solid #64748b;
    }
    .consultation-record-card.card-closed {
        border-top: 4px solid #1e293b;
    }

    .hover-primary:hover {
        color: #2563eb !important;
    }

    @media print {
        .btn, .nav-tabs, .alert, .stat-card-link, nav, .breadcrumb, .no-print, form, .modal, .pagination {
            display: none !important;
        }
        .consultation-center-header {
            background: none !important;
            color: #000 !important;
            box-shadow: none !important;
            padding: 0 0 1rem 0 !important;
            border-bottom: 2px solid #0f172a !important;
            margin-bottom: 1rem !important;
        }
        .consultation-center-header h3, .consultation-center-header p {
            color: #000 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table-responsive {
            overflow: visible !important;
        }
        table {
            width: 100% !important;
            font-size: 0.78rem !important;
            border: 1px solid #cbd5e1 !important;
        }
        th, td {
            border: 1px solid #cbd5e1 !important;
            padding: 6px 8px !important;
        }
        th:last-child, td:last-child {
            display: none !important;
        }
        .badge {
            border: 1px solid #94a3b8 !important;
            color: #000 !important;
            background: #fff !important;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    
    {{-- Top Banner Header --}}
    <div class="consultation-center-header">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2 text-white-50 small">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-white-50 text-decoration-none"><i class="fas fa-home me-1"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}" class="text-white-50 text-decoration-none">المشاريع</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">الاستشارات والإحالات</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-1 d-flex align-items-center gap-2">
                    <i class="fas fa-comments text-info"></i> الاستشارات والإحالات
                </h3>
                <p class="text-white-50 mb-0 small">
                    منصة متخصصة ومستقلة لإدارة طلبات الإفادة والاستشارات الفنية والتنسيقية بين الجهات دون التأثير على مسار الاعتماد الرئيسي
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" onclick="window.print()" class="btn btn-outline-light rounded-pill px-3 btn-sm fw-semibold shadow-sm">
                    <i class="fas fa-print me-1"></i> طباعة القائمة
                </button>
                <a href="{{ route('approvals.index') }}" class="btn btn-outline-light rounded-pill px-4 btn-sm fw-semibold">
                    <i class="fas fa-clipboard-check me-1"></i> مركز المراجعة والاعتمادات
                </a>
                <a href="{{ route('projects.index') }}" class="btn btn-primary rounded-pill px-4 btn-sm fw-semibold shadow-sm">
                    <i class="fas fa-list me-1"></i> قائمة المشاريع
                </a>
            </div>
        </div>
    </div>

    {{-- Notice Banner Directing to Projects Detail / Archive --}}
    <div class="alert alert-info border-0 shadow-sm rounded-4 d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 p-3 px-4">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-white text-info d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                <i class="fas fa-info-circle fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-dark">وحدة الاستشارات والإحالات مخصصة للمهام التي تتطلب إفادتك أو متابعتك الفنية</h6>
                <p class="mb-0 text-muted small">تفاصيل المشاريع وسجل الاستشارات التاريخي الكامل متاح دائماً داخل <a href="{{ route('projects.index') }}" class="fw-bold text-decoration-underline text-info">صفحة تفاصيل المشروع</a>.</p>
            </div>
        </div>
        <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-info rounded-pill px-3 fw-bold">
            <i class="fas fa-list me-1"></i> قائمة المشاريع
        </a>
    </div>

    @if(isset($isAdmin) && $isAdmin)
    {{-- Statistics Cards (5 Required Metrics) --}}
    <div class="row g-3 mb-4">
        {{-- Stat 1: Incoming --}}
        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('consultations.index', array_merge(request()->except('tab', 'page'), ['tab' => 'incoming'])) }}" class="stat-card-link">
                <div class="card stat-card h-100 shadow-sm p-3 {{ $activeTab === 'incoming' ? 'active-tab-card bg-primary bg-opacity-10' : '' }}">
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fw-semibold small d-block mb-1">الاستشارات الواردة</span>
                            <h3 class="fw-bold text-primary mb-0">{{ number_format($incomingCount) }}</h3>
                            <small class="text-muted">الموجهة إلى جهتك</small>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-inbox fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Stat 2: Sent --}}
        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('consultations.index', array_merge(request()->except('tab', 'page'), ['tab' => 'sent'])) }}" class="stat-card-link">
                <div class="card stat-card h-100 shadow-sm p-3 {{ $activeTab === 'sent' ? 'active-tab-card bg-info bg-opacity-10' : '' }}">
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fw-semibold small d-block mb-1">الاستشارات الصادرة</span>
                            <h3 class="fw-bold text-info mb-0">{{ number_format($sentCount) }}</h3>
                            <small class="text-muted">المرسلة من جهتك</small>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-paper-plane fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Stat 3: Pending Response --}}
        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('consultations.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}" class="stat-card-link">
                <div class="card stat-card h-100 shadow-sm p-3 {{ request('status') === 'pending' ? 'active-tab-card bg-warning bg-opacity-10' : '' }}">
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fw-semibold small d-block mb-1">بانتظار الرد</span>
                            <h3 class="fw-bold text-warning mb-0">{{ number_format($pendingCount) }}</h3>
                            <small class="text-muted">قيد المراجعة الفنية</small>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-clock fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Stat 4: Responded --}}
        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('consultations.index', array_merge(request()->except('status', 'page'), ['status' => 'responded'])) }}" class="stat-card-link">
                <div class="card stat-card h-100 shadow-sm p-3 {{ request('status') === 'responded' ? 'active-tab-card bg-success bg-opacity-10' : '' }}">
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fw-semibold small d-block mb-1">تم الرد</span>
                            <h3 class="fw-bold text-success mb-0">{{ number_format($respondedCount) }}</h3>
                            <small class="text-muted">تمت الإفادة الفنية</small>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-check-double fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Stat 5: Closed --}}
        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('consultations.index', array_merge(request()->except('status', 'page'), ['status' => 'closed'])) }}" class="stat-card-link">
                <div class="card stat-card h-100 shadow-sm p-3 {{ request('status') === 'closed' ? 'active-tab-card bg-dark bg-opacity-10' : '' }}">
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fw-semibold small d-block mb-1">المغلقة</span>
                            <h3 class="fw-bold text-dark mb-0">{{ number_format($closedCount) }}</h3>
                            <small class="text-muted">استشارات منتهية</small>
                        </div>
                        <div class="rounded-circle bg-dark bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-lock fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Tabs Navigation (3 Required Tabs: Incoming, Sent, Completed) --}}
    <ul class="nav nav-tabs nav-tabs-custom mb-4 overflow-auto flex-nowrap text-nowrap">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'incoming' ? 'active' : '' }}" href="{{ route('consultations.index', array_merge(request()->except('tab', 'page'), ['tab' => 'incoming'])) }}">
                <i class="fas fa-inbox me-1"></i> الواردة
                <span class="badge rounded-pill {{ $activeTab === 'incoming' ? 'bg-primary' : 'bg-secondary' }} ms-1">
                    {{ $incomingCount }}
                </span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'sent' ? 'active' : '' }}" href="{{ route('consultations.index', array_merge(request()->except('tab', 'page'), ['tab' => 'sent'])) }}">
                <i class="fas fa-paper-plane me-1"></i> الصادرة
                <span class="badge rounded-pill {{ $activeTab === 'sent' ? 'bg-info' : 'bg-secondary' }} ms-1">
                    {{ $sentCount }}
                </span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'completed' ? 'active' : '' }}" href="{{ route('consultations.index', array_merge(request()->except('tab', 'page'), ['tab' => 'completed'])) }}">
                <i class="fas fa-check-circle me-1"></i> المكتملة
                <span class="badge rounded-pill {{ $activeTab === 'completed' ? 'bg-success' : 'bg-secondary' }} ms-1">
                    {{ $respondedCount + $closedCount }}
                </span>
            </a>
        </li>
    </ul>

    {{-- Filter and Search Bar --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('consultations.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                
                {{-- Search --}}
                <div class="col-12 col-md-3">
                    <label class="form-label fw-bold small text-muted">البحث:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="اسم المشروع أو الرقم أو الموضوع...">
                    </div>
                </div>

                {{-- Entity Filter --}}
                <div class="col-12 col-md-3">
                    <label class="form-label fw-bold small text-muted">الجهة المعنية:</label>
                    <select name="entity_id" class="form-select bg-light">
                        <option value="">-- جميع الجهات --</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ request('entity_id') == $entity->id ? 'selected' : '' }}>
                                {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="col-12 col-md-2">
                    <label class="form-label fw-bold small text-muted">الحالة:</label>
                    <select name="status" class="form-select bg-light">
                        <option value="">-- كافة الحالات --</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>بانتظار الرد</option>
                        <option value="responded" {{ request('status') === 'responded' ? 'selected' : '' }}>تم الرد</option>
                        <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>تم الإرجاع</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>مغلقة</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold small text-muted">من تاريخ:</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control bg-light">
                </div>

                {{-- Date To --}}
                <div class="col-6 col-md-2">
                    <label class="form-label fw-bold small text-muted">إلى تاريخ:</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control bg-light">
                </div>

                {{-- Submit & Reset Buttons --}}
                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                    @if(request()->hasAny(['search', 'entity_id', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('consultations.index', ['tab' => $activeTab]) }}" class="btn btn-outline-secondary rounded-pill px-3" title="إعادة ضبط الفلاتر">
                            <i class="fas fa-times me-1"></i> إلغاء التصفية
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
    @endif


    {{-- Content Area: Table View --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3 py-3">رقم المشروع</th>
                            <th class="py-3">اسم المشروع</th>
                            <th class="py-3">الجهة المقدمة</th>
                            <th class="py-3">الجهة المُحيلة (من)</th>
                            <th class="py-3">الجهة المُستشارة (التي وصل إليها)</th>
                            <th class="py-3">الحالة</th>
                            <th class="py-3">مدة الانتظار</th>
                            <th class="px-3 py-3 text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referrals as $referral)
                            @php
                                $isPending = ($referral->status === 'pending');
                                $isResponded = ($referral->status === 'responded');
                                $isReturned = ($referral->status === 'returned');
                                $isClosed = ($referral->status === 'closed');
                                
                                $waitingDuration = '-';
                                if ($referral->created_at) {
                                    if ($referral->responded_at) {
                                        $waitingDuration = $referral->created_at->diffForHumans($referral->responded_at, true);
                                    } else {
                                        $waitingDuration = $referral->created_at->diffForHumans(null, true);
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="px-3 py-3 font-monospace small text-muted">
                                    {{ $referral->project?->form_number ?: 'PRJ-'.$referral->project_id }}
                                </td>
                                <td class="py-3">
                                    <h6 class="mb-0 fw-bold">
                                        <a href="{{ route('consultations.show', $referral) }}" class="text-decoration-none text-dark">
                                            {{ $referral->project?->project_name ?? 'مشروع #'.$referral->project_id }}
                                        </a>
                                    </h6>
                                </td>
                                <td class="py-3">
                                    <div class="fw-semibold text-dark">
                                        <i class="fas fa-building text-secondary me-1 small"></i>
                                        {{ $referral->project?->creator_entity_name ?? 'غير محدد' }}
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="fw-semibold text-dark">
                                        <i class="fas fa-paper-plane text-secondary me-1 small"></i>
                                        {{ $referral->referringEntity?->name ?? 'غير محدد' }}
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="fw-bold text-primary">
                                        <i class="fas fa-map-marker-alt text-primary me-1 small"></i>
                                        {{ $referral->referredEntity?->name ?? 'غير محدد' }}
                                    </div>
                                </td>
                                <td class="py-3">
                                    @if($isPending)
                                        <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill shadow-sm"><i class="fas fa-clock me-1"></i> بانتظار الرد</span>
                                    @elseif($isResponded)
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-3 py-1.5 rounded-pill"><i class="fas fa-check-circle me-1"></i> تم الرد</span>
                                    @elseif($isReturned)
                                        <span class="badge bg-secondary bg-opacity-15 text-secondary border border-secondary border-opacity-25 px-3 py-1.5 rounded-pill"><i class="fas fa-undo me-1"></i> تم الإرجاع</span>
                                    @elseif($isClosed)
                                        <span class="badge bg-dark px-3 py-1.5 rounded-pill"><i class="fas fa-lock me-1"></i> مغلقة</span>
                                    @endif
                                </td>
                                <td class="py-3 text-muted small fw-semibold">
                                    <i class="far fa-hourglass text-secondary me-1"></i> {{ $waitingDuration }}
                                </td>
                                <td class="px-3 py-3 text-end">
                                    <a href="{{ route('consultations.show', $referral) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                        <i class="fas fa-eye me-1"></i> التفاصيل
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-comments fs-2 mb-3 opacity-50"></i>
                                        <h5 class="fw-bold text-dark">لا توجد استشارات حالياً</h5>
                                        <p class="mb-0 small">لا توجد استشارات أو إحالات تتطلب إجراءك في الوقت الحالي.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4 d-flex justify-content-center">
        @if(isset($referrals) && $referrals instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $referrals->links() }}
        @endif
    </div>

</div>
@endsection
