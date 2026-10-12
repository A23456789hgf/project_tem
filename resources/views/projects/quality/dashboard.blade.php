@extends('layouts.app')

@section('title', 'تقرير الجودة الشامل')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    :root {
        --navy: #08214c;
        --navy-light: #0d2d5e;
        --gold: #d4af37;
        --gold-soft: #e6c558;
        --bg: #f1f5f9;
        --card: #ffffff;
        --text: #1e293b;
        --muted: #64748b;
        --border: #e2e8f0;
        --success: #10b981;
        --warn: #f59e0b;
        --danger: #ef4444;
        --info: #0ea5e9;
        --radius: 14px;
        --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06);
        --shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
        --transition: all 0.2s ease;
    }

    .q-wrap { padding: 1.25rem 1.5rem 3rem; background: var(--bg); min-height: 100vh; }

    /* ============ Hero ============ */
    .hero {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
        color: #fff;
        padding: 1.75rem 2rem;
        border-radius: var(--radius);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }
    .hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 90% 20%, rgba(212,175,55,0.18), transparent 60%);
        pointer-events: none;
    }
    .hero-title { font-size: 1.55rem; font-weight: 800; margin: 0 0 0.35rem; color: var(--gold); }
    .hero-sub { color: rgba(255,255,255,0.75); margin: 0; font-size: 0.9rem; }
    .hero-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; z-index: 1; }
    .hero-actions .btn { font-weight: 600; border-radius: 10px; padding: 0.55rem 1rem; font-size: 0.9rem; }
    .btn-gold { background: var(--gold); color: var(--navy); border: none; }
    .btn-gold:hover { background: var(--gold-soft); color: var(--navy); }

    /* ============ KPI ============ */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1rem;
        margin-top: 1.25rem;
    }
    .kpi {
        background: var(--card);
        border-radius: var(--radius);
        padding: 1.1rem 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
        transition: var(--transition);
    }
    .kpi:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
    .kpi::after {
        content: '';
        position: absolute;
        inset: auto 0 0 0;
        height: 3px;
        background: var(--c, var(--navy));
    }
    .kpi-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: grid; place-items: center;
        font-size: 1.25rem;
        background: color-mix(in srgb, var(--c, var(--navy)) 12%, transparent);
        color: var(--c, var(--navy));
    }
    .kpi-label { font-size: 0.8rem; color: var(--muted); font-weight: 600; margin-bottom: 0.15rem; }
    .kpi-value { font-size: 1.6rem; font-weight: 800; color: var(--text); line-height: 1; }
    .kpi-value small { font-size: 0.85rem; color: var(--muted); font-weight: 600; }

    /* ============ Filters ============ */
    .filters {
        background: var(--card);
        border-radius: var(--radius);
        padding: 1rem 1.25rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
        margin-top: 1.25rem;
    }
    .filters-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 0.85rem;
        cursor: pointer;
        user-select: none;
    }
    .filters-head h6 { margin: 0; font-weight: 700; color: var(--navy); display: flex; gap: 0.5rem; align-items: center; }
    .filters-head .toggle-icon { transition: transform 0.25s; color: var(--muted); }
    .filters.collapsed .toggle-icon { transform: rotate(-90deg); }
    .filters.collapsed .filters-body { display: none; }

    .filters-body .form-label {
        font-size: 0.8rem; font-weight: 700; color: var(--muted);
        margin-bottom: 0.3rem; display: block;
    }
    .filters-body .form-control,
    .filters-body .form-select {
        border-radius: 10px;
        border: 1px solid var(--border);
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
        min-height: 40px;
    }
    .filters-body .form-control:focus,
    .filters-body .form-select:focus {
        border-color: var(--navy);
        box-shadow: 0 0 0 3px rgba(8,33,76,0.1);
    }

    .active-filters {
        display: flex; flex-wrap: wrap; gap: 0.4rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px dashed var(--border);
    }
    .filter-chip {
        background: rgba(8,33,76,0.06);
        color: var(--navy);
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        display: inline-flex; align-items: center; gap: 0.4rem;
    }
    .filter-chip i { cursor: pointer; opacity: 0.7; }
    .filter-chip i:hover { opacity: 1; color: var(--danger); }

    /* ============ Charts ============ */
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        gap: 1rem;
        margin-top: 1.25rem;
    }
    .panel {
        background: var(--card);
        border-radius: var(--radius);
        padding: 1.25rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
    }
    .panel-title {
        font-size: 0.95rem; font-weight: 700; color: var(--navy);
        margin: 0 0 1rem;
        display: flex; align-items: center; gap: 0.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border);
    }
    .panel-title i { color: var(--gold); }

    /* ============ Table ============ */
    .table-panel {
        background: var(--card);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
        margin-top: 1.25rem;
        overflow: hidden;
    }
    .table-panel-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 0.75rem;
    }
    .table-panel-head h6 { margin: 0; font-weight: 700; color: var(--navy); font-size: 1rem; }
    .table-panel-head .count { color: var(--muted); font-size: 0.85rem; font-weight: 500; }

    .q-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .q-table thead th {
        background: #f8fafc;
        color: var(--muted);
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
        user-select: none;
    }
    .q-table thead th.sortable { cursor: pointer; }
    .q-table thead th.sortable:hover { color: var(--navy); }
    .q-table thead th .sort-icon { margin-right: 0.35rem; opacity: 0.4; font-size: 0.7rem; }
    .q-table thead th.active-sort .sort-icon { opacity: 1; color: var(--navy); }

    .q-table tbody tr {
        border-bottom: 1px solid var(--border);
        transition: var(--transition);
    }
    .q-table tbody tr:hover { background: #f8fafc; }
    .q-table tbody td { padding: 0.9rem 1rem; vertical-align: middle; }

    .project-name {
        color: var(--navy); font-weight: 700; text-decoration: none;
        display: inline-flex; align-items: center; gap: 0.4rem;
    }
    .project-name:hover { color: var(--gold); }

    .badge-soft {
        padding: 0.3rem 0.65rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-block;
    }
    .badge-soft.gray  { background: #f1f5f9; color: #475569; }
    .badge-soft.green { background: #d1fae5; color: #065f46; }
    .badge-soft.blue  { background: #dbeafe; color: #1e40af; }
    .badge-soft.warn  { background: #fef3c7; color: #92400e; }
    .badge-soft.red   { background: #fee2e2; color: #991b1b; }

    .score-ring {
        width: 52px; height: 52px;
        position: relative;
        display: inline-grid; place-items: center;
    }
    .score-ring svg { transform: rotate(-90deg); }
    .score-ring .val {
        position: absolute;
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--navy);
    }

    .mini-bar {
        height: 6px; border-radius: 999px;
        background: #f1f5f9;
        overflow: hidden;
        margin-bottom: 0.3rem;
    }
    .mini-bar .fill { height: 100%; border-radius: 999px; transition: width 0.5s ease; }

    .row-actions { display: flex; gap: 0.35rem; }
    .icon-btn {
        width: 32px; height: 32px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: #fff;
        display: grid; place-items: center;
        cursor: pointer;
        color: var(--muted);
        transition: var(--transition);
        font-size: 0.8rem;
    }
    .icon-btn:hover { background: var(--navy); color: #fff; border-color: var(--navy); }

    /* Select2 tweaks */
    .select2-container--default .select2-selection--multiple {
        min-height: 40px;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 0.15rem 0.3rem;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: var(--navy);
        box-shadow: 0 0 0 3px rgba(8,33,76,0.1);
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: var(--navy);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 0.1rem 0.5rem;
        font-size: 0.78rem;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff;
        margin-left: 0.4rem;
    }

    .empty-state { padding: 3rem 1rem; text-align: center; color: var(--muted); }
    .empty-state i { font-size: 2.5rem; color: var(--border); margin-bottom: 0.75rem; display: block; }
</style>
@endsection

@section('content')
<div class="q-wrap">

    {{-- ================= Hero ================= --}}
    <div class="hero">
        <div>
            <h1 class="hero-title"><i class="fas fa-medal me-2"></i> التقرير العام لجودة المشاريع</h1>
            <p class="hero-sub">نظرة شاملة على الأداء الزمني والمالي مع إمكانية الفرز والتصفية المتقدمة</p>
        </div>
        <div class="hero-actions">
            <button class="btn btn-light" onclick="printReport('all')">
                <i class="fas fa-print me-1"></i> طباعة
            </button>
            <button class="btn btn-gold" onclick="exportReport('all')">
                <i class="fas fa-file-excel me-1"></i> تصدير Excel
            </button>
        </div>
    </div>

    {{-- ================= KPIs ================= --}}
    <div class="kpi-grid">
        <div class="kpi" style="--c: #0ea5e9;">
            <div class="kpi-icon"><i class="fas fa-project-diagram"></i></div>
            <div>
                <div class="kpi-label">إجمالي المشاريع</div>
                <div class="kpi-value">{{ number_format($kpis['total_projects']) }}</div>
            </div>
        </div>
        <div class="kpi" style="--c: #10b981;">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="kpi-label">مشاريع مكتملة</div>
                <div class="kpi-value">{{ number_format($kpis['completed_projects']) }}</div>
            </div>
        </div>
        <div class="kpi" style="--c: #f59e0b;">
            <div class="kpi-icon"><i class="fas fa-spinner"></i></div>
            <div>
                <div class="kpi-label">قيد التنفيذ</div>
                <div class="kpi-value">{{ number_format($kpis['in_progress_projects']) }}</div>
            </div>
        </div>
        <div class="kpi" style="--c: #d4af37;">
            <div class="kpi-icon"><i class="fas fa-star"></i></div>
            <div>
                <div class="kpi-label">متوسط الجودة</div>
                <div class="kpi-value">{{ $kpis['average_score'] }}<small>%</small></div>
            </div>
        </div>
    </div>

    {{-- ================= Filters ================= --}}
    <form id="filter-form" method="GET" action="{{ route('projects.quality.index') }}">
        <input type="hidden" name="report_scope" value="all" id="report_scope">
        <input type="hidden" name="project_ids" id="filter_project_ids">
        <input type="hidden" name="sort_by"  id="sort_by"  value="{{ request('sort_by', '') }}">
        <input type="hidden" name="sort_dir" id="sort_dir" value="{{ request('sort_dir', 'desc') }}">

        <div class="filters" id="filtersBox">
            <div class="filters-head" onclick="document.getElementById('filtersBox').classList.toggle('collapsed')">
                <h6><i class="fas fa-sliders-h"></i> الفلاتر والفرز المتقدم</h6>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>

            <div class="filters-body">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">المحافظات</label>
                        <select name="governorate_ids[]" class="form-select multi-select" multiple>
                            @foreach($filterData['governorates'] as $gov)
                                <option value="{{ $gov->id }}"
                                    {{ in_array($gov->id, (array) request('governorate_ids', [])) ? 'selected' : '' }}>
                                    {{ $gov->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">الجهات</label>
                        <select name="entity_ids[]" class="form-select multi-select" multiple>
                            @foreach($filterData['entities'] as $ent)
                                <option value="{{ $ent->id }}"
                                    {{ in_array($ent->id, (array) request('entity_ids', [])) ? 'selected' : '' }}>
                                    {{ $ent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">الحالة</label>
                        <select name="status_ids[]" class="form-select multi-select" multiple>
                            @foreach($filterData['statuses'] as $key => $label)
                                <option value="{{ $key }}"
                                    {{ in_array($key, (array) request('status_ids', [])) ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3">
                        <label class="form-label">من تاريخ</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>

                    <div class="col-lg-2 col-md-3">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">ترتيب حسب</label>
                        <select name="sort_by" class="form-select">
                            <option value="creator_entity_id" {{ request('sort_by') == 'creator_entity_id' ? 'selected' : '' }}>الجهة</option>
                            <option value="project_name"       {{ request('sort_by') == 'project_name' ? 'selected' : '' }}>اسم المشروع</option>
                            <option value="start_date_gregorian" {{ request('sort_by') == 'start_date_gregorian' ? 'selected' : '' }}>تاريخ البدء</option>
                            <option value="created_at"         {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>الأحدث</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">الاتجاه</label>
                        <select name="sort_dir" class="form-select">
                            <option value="desc" {{ request('sort_dir', 'desc') == 'desc' ? 'selected' : '' }}>تنازلي</option>
                            <option value="asc"  {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>تصاعدي</option>
                        </select>
                    </div>

                    <div class="col-lg-7 col-md-12 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-filter me-1"></i> تطبيق
                        </button>
                        <a href="{{ route('projects.quality.index') }}" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-undo me-1"></i> إعادة تعيين
                        </a>
                    </div>
                </div>

                {{-- Active Filter Chips --}}
                @php
                    $activeChips = [];
                    if (request('governorate_ids')) {
                        foreach (request('governorate_ids') as $id) {
                            $g = collect($filterData['governorates'])->firstWhere('id', $id);
                            if ($g) $activeChips[] = ['type' => 'gov', 'label' => $g->name];
                        }
                    }
                    if (request('entity_ids')) {
                        foreach (request('entity_ids') as $id) {
                            $e = collect($filterData['entities'])->firstWhere('id', $id);
                            if ($e) $activeChips[] = ['type' => 'ent', 'label' => $e->name];
                        }
                    }
                    if (request('status_ids')) {
                        foreach (request('status_ids') as $s) {
                            if (isset($filterData['statuses'][$s])) {
                                $activeChips[] = ['type' => 'st', 'label' => $filterData['statuses'][$s]];
                            }
                        }
                    }
                @endphp

                @if(count($activeChips) || request('from_date') || request('to_date'))
                <div class="active-filters">
                    <span class="text-muted small align-self-center me-2">
                        <i class="fas fa-tag"></i> فلاتر مطبّقة:
                    </span>
                    @foreach($activeChips as $chip)
                        <span class="filter-chip">{{ $chip['label'] }}</span>
                    @endforeach
                    @if(request('from_date'))
                        <span class="filter-chip">من: {{ request('from_date') }}</span>
                    @endif
                    @if(request('to_date'))
                        <span class="filter-chip">إلى: {{ request('to_date') }}</span>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </form>

    {{-- ================= Charts ================= --}}
    <div class="charts-grid">
        <div class="panel">
            <h3 class="panel-title"><i class="fas fa-chart-pie"></i> توزيع مستويات الجودة</h3>
            <canvas id="qualityDistributionChart" height="220"></canvas>
        </div>
        <div class="panel">
            <h3 class="panel-title"><i class="fas fa-chart-bar"></i> متوسط الجودة حسب الجهة</h3>
            <canvas id="qualityByEntityChart" height="220"></canvas>
        </div>
        <div class="panel">
            <h3 class="panel-title"><i class="fas fa-chart-line"></i> اتجاه الجودة الزمني</h3>
            <canvas id="qualityTrendChart" height="220"></canvas>
        </div>
    </div>

    {{-- ================= Table ================= --}}
    <div class="table-panel">
        <div class="table-panel-head">
            <h6><i class="fas fa-table me-2"></i> تفاصيل المشاريع</h6>
            <span class="count">{{ count($projects) }} مشروع</span>
        </div>

        <div class="table-responsive">
            <table class="q-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th class="sortable" data-sort="project_name">
                            المشروع <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th>الجهة</th>
                        <th>الموقع</th>
                        <th>الحالة</th>
                        <th>جودة الوقت</th>
                        <th>الجودة المالية</th>
                        <th class="text-center">التقييم العام</th>
                        <th style="width: 100px;" class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $data)
                    @php
                        $score = $data['statistics']['overall_quality_score'];
                        $circ  = 2 * 3.14159 * 22;
                        $dash  = ($score / 100) * $circ;
                        $ringColor = $score >= 80 ? '#10b981' : ($score >= 60 ? '#f59e0b' : '#ef4444');
                        $statusClass = match($data['status_name']) {
                            'مكتمل', 'completed' => 'green',
                            'قيد التنفيذ', 'in_progress' => 'blue',
                            'معلّق', 'pending' => 'warn',
                            default => 'gray',
                        };
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input project-checkbox" value="{{ $data['project']->id }}">
                        </td>
                        <td>
                            <a href="{{ route('projects.quality.show', $data['project']->id) }}" class="project-name">
                                {{ \Illuminate\Support\Str::limit($data['project']->project_name, 45) }}
                            </a>
                        </td>
                        <td>{{ $data['entity_name'] }}</td>
                        <td class="text-muted">{{ $data['location_name'] }}</td>
                        <td><span class="badge-soft {{ $statusClass }}">{{ $data['status_name'] }}</span></td>
                        <td>
                            <div class="mini-bar">
                                <div class="fill" style="width: {{ $data['statistics']['time_positive_percentage'] }}%; background:#0ea5e9;"></div>
                            </div>
                            <small class="text-muted">{{ $data['statistics']['time_positive_percentage'] }}%</small>
                        </td>
                        <td>
                            <div class="mini-bar">
                                <div class="fill" style="width: {{ $data['statistics']['financial_positive_percentage'] }}%; background:#10b981;"></div>
                            </div>
                            <small class="text-muted">{{ $data['statistics']['financial_positive_percentage'] }}%</small>
                        </td>
                        <td class="text-center">
                            <div class="score-ring" title="{{ $score }}%">
                                <svg width="52" height="52">
                                    <circle cx="26" cy="26" r="22" stroke="#f1f5f9" stroke-width="5" fill="none"/>
                                    <circle cx="26" cy="26" r="22" stroke="{{ $ringColor }}" stroke-width="5" fill="none"
                                            stroke-linecap="round"
                                            stroke-dasharray="{{ $dash }} {{ $circ }}"/>
                                </svg>
                                <span class="val">{{ round($score) }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="row-actions justify-content-center">
                                <button class="icon-btn" onclick="printSingle({{ $data['project']->id }})" title="طباعة">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="icon-btn" onclick="exportSingle({{ $data['project']->id }})" title="تصدير">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <div class="fw-bold">لا توجد مشاريع مطابقة للفلاتر</div>
                                <div class="small mt-1">جرّب تغيير معايير البحث</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/chart.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ================= Multi-Select =================
    $('.multi-select').select2({
        placeholder: 'اختر...',
        allowClear: true,
        width: '100%',
        dir: 'rtl',
        language: { noResults: () => 'لا توجد نتائج' },
    });

    // ================= Sortable headers =================
    document.querySelectorAll('.q-table th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.sort;
            const current = document.getElementById('sort_by').value;
            const dir = document.getElementById('sort_dir');
            document.getElementById('sort_by').value = col;
            dir.value = (current === col && dir.value === 'asc') ? 'desc' : 'asc';
            document.getElementById('filter-form').submit();
        });
    });

    // ================= Selection =================
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.project-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    // ================= Charts =================
    const chartData = @json($charts);

    new Chart(document.getElementById('qualityDistributionChart'), {
        type: 'doughnut',
        data: {
            labels: chartData.quality_distribution.labels,
            datasets: [{
                data: chartData.quality_distribution.data,
                backgroundColor: ['#10b981', '#34d399', '#f59e0b', '#f97316', '#ef4444'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, boxWidth: 12 } }
            }
        }
    });

    new Chart(document.getElementById('qualityByEntityChart'), {
        type: 'bar',
        data: {
            labels: chartData.quality_by_entity.labels,
            datasets: [{
                label: 'متوسط الجودة %',
                data: chartData.quality_by_entity.data,
                backgroundColor: '#0d2d5e',
                borderRadius: 6,
                barThickness: 28,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('qualityTrendChart'), {
        type: 'line',
        data: {
            labels: chartData.quality_trend.labels,
            datasets: [{
                label: 'متوسط الجودة',
                data: chartData.quality_trend.data,
                borderColor: '#d4af37',
                backgroundColor: 'rgba(212,175,55,0.12)',
                fill: true, tension: 0.35,
                pointRadius: 4, pointBackgroundColor: '#08214c',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });
});

// ================= Actions =================
function getSelectedIds() {
    return Array.from(document.querySelectorAll('.project-checkbox:checked')).map(cb => cb.value).join(',');
}

function buildActionUrl(baseUrl, scope, id = null) {
    const formData = new FormData(document.getElementById('filter-form'));
    const params = new URLSearchParams(formData);
    params.set('report_scope', scope);
    if (scope === 'selected') params.set('project_ids', getSelectedIds());
    if (scope === 'single' && id) params.set('single_project_id', id);
    return baseUrl + '?' + params.toString();
}

function printReport(scope)  { window.open(buildActionUrl('{{ route("projects.quality.print") }}',  scope), '_blank'); }
function exportReport(scope) { window.location.href = buildActionUrl('{{ route("projects.quality.export") }}', scope); }
function printSingle(id)     { window.open(buildActionUrl('{{ route("projects.quality.print") }}',  'single', id), '_blank'); }
function exportSingle(id)    { window.location.href = buildActionUrl('{{ route("projects.quality.export") }}', 'single', id); }
</script>
@endsection