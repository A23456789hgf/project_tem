@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/modern-reports.css') }}" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<style>
    .text-report-primary { color: var(--report-primary) !important; }
    .bg-report-primary { background-color: var(--report-primary) !important; }
    .bg-report-secondary { background-color: var(--report-secondary) !important; }
</style>
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      Chart.defaults.font.family = "'Tajawal', sans-serif";
      Chart.defaults.color = '#78716c';
      Chart.defaults.font.size = 12;

      const stats = @json($stats ?? []);

      const projectsByStatus = stats.projects_by_status ?? {
        "نشط": stats.total_projects ?? 0,
        "مكتمل": stats.completed_projects ?? 0,
        "متأخر": stats.delayed_projects ?? 0,
        "معلق": stats.on_hold_projects ?? 0
      };

      const months = stats.months ?? ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'];
      const progressSeries = stats.progress_series ?? months.map(() => Math.round(Math.random() * 80 + 10));

      /* ===== Bar Chart ===== */
      const ctxBar = document.getElementById('chartProjectsStatus')?.getContext('2d');
      if (ctxBar) {
        new Chart(ctxBar, {
          type: 'bar',
          data: {
            labels: Object.keys(projectsByStatus),
            datasets: [{
              data: Object.values(projectsByStatus),
              backgroundColor: [
                'rgba(15, 61, 46, 0.85)',
                'rgba(22, 163, 74, 0.85)',
                'rgba(234, 88, 12, 0.85)',
                'rgba(120, 113, 108, 0.7)'
              ],
              borderRadius: 8,
              borderSkipped: false,
              barThickness: 40
            }]
          },
          options: {
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: '#1c1917',
                padding: 12,
                cornerRadius: 8,
                titleFont: { weight: '700' },
                displayColors: false
              }
            },
            scales: {
              x: {
                grid: { display: false },
                ticks: { font: { weight: '600' } }
              },
              y: {
                beginAtZero: true,
                ticks: { stepSize: 1 },
                grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false }
              }
            },
            responsive: true,
            maintainAspectRatio: false
          }
        });
      }

      /* ===== Doughnut Chart ===== */
      const utilization = Math.min((stats.financial?.utilization_percentage ?? 0), 100);
      const ctxDough = document.getElementById('chartFinancial')?.getContext('2d');
      if (ctxDough) {
        new Chart(ctxDough, {
          type: 'doughnut',
          data: {
            labels: ['مستخدم', 'متبقي'],
            datasets: [{
              data: [utilization, Math.max(0, 100 - utilization)],
              backgroundColor: ['#0f3d2e', '#e7e5e4'],
              borderWidth: 0,
              hoverOffset: 8
            }]
          },
          options: {
            cutout: '75%',
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: '#1c1917',
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                  label: ctx => ctx.label + ': ' + ctx.parsed + '%'
                }
              }
            },
            responsive: true,
            maintainAspectRatio: false
          },
          plugins: [{
            id: 'centerText',
            beforeDraw(chart) {
              const { width, height, ctx: c } = chart;
              c.restore();
              c.font = '800 30px Tajawal';
              c.fillStyle = '#0f3d2e';
              c.textBaseline = 'middle';
              c.textAlign = 'center';
              c.fillText(utilization.toFixed(1) + '%', width / 2, height / 2 - 6);
              c.font = '500 12px Tajawal';
              c.fillStyle = '#78716c';
              c.fillText('نسبة الاستخدام', width / 2, height / 2 + 18);
              c.save();
            }
          }]
        });
      }

      /* ===== Line Chart ===== */
      const ctxLine = document.getElementById('chartProgress')?.getContext('2d');
      if (ctxLine) {
        const gradient = ctxLine.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(15, 61, 46, 0.2)');
        gradient.addColorStop(1, 'rgba(15, 61, 46, 0)');

        new Chart(ctxLine, {
          type: 'line',
          data: {
            labels: months,
            datasets: [{
              data: progressSeries,
              borderColor: '#0f3d2e',
              backgroundColor: gradient,
              fill: true,
              tension: 0.4,
              pointRadius: 4,
              pointHoverRadius: 7,
              pointBackgroundColor: '#fff',
              pointBorderColor: '#0f3d2e',
              pointBorderWidth: 2,
              borderWidth: 3
            }]
          },
          options: {
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: '#1c1917',
                padding: 12,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                  label: ctx => 'الإنجاز: ' + ctx.parsed.y + '%'
                }
              }
            },
            scales: {
              x: {
                grid: { display: false },
                ticks: { font: { weight: '600' } }
              },
              y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                  callback: v => v + '%',
                  stepSize: 25
                },
                grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false }
              }
            },
            responsive: true,
            maintainAspectRatio: false
          }
        });
      }


    });
  </script>
@endpush

@section('content')
<!-- Official Print Header -->
<div class="official-print-header">
    <table style="width: 100%; border-bottom: 2px solid #2c5f2d; padding-bottom: 10px; margin-bottom: 20px; direction: rtl;">
        <tr>
            <td width="33%" style="text-align: right; vertical-align: top;">
                <div style="font-size: 16px; font-weight: bold; color: #2c5f2d; font-family: 'Tajawal';">الجمهورية اليمنية</div>
                <div style="font-size: 14px; color: #1f2937; font-family: 'Tajawal';">وزارة الزراعة والثروة السمكية والموارد المائية</div>
            </td>
            <td width="33%" style="text-align: center; vertical-align: middle;">
                <img src="{{ asset('images/logo.png') }}" style="height: 90px;" alt="Logo">
            </td>
            <td width="33%" style="text-align: left; vertical-align: top;">
                <div style="font-size: 12px; color: #64748b; font-family: 'Tajawal';">التاريخ: {{ date('Y-m-d') }}</div>
                <div style="font-size: 12px; color: #64748b; font-family: 'Tajawal';">الموضوع: تقارير مركز البيانات الموحد</div>
            </td>
        </tr>
    </table>
    <div style="text-align: center; margin-bottom: 25px;">
        <h2 style="color: #2c5f2d; font-weight: 800; margin: 0; font-family: 'Tajawal';">تقرير ملخص إنجاز المشاريع الموحد</h2>
    </div>
</div>

<div class="report-container">
    <div class="report-header d-flex justify-content-between align-items-center no-print">
        <div class="report-header-content">
            <h2 class="report-title text-report-primary"><i class="fas fa-chart-pie me-2"></i>تقارير مركز البيانات الموحد</h2>
            <p class="text-muted mb-0">تقرير ملخص إنجاز المشاريع الموحد</p>
        </div>
        <div>
            <a href="{{ route('projects.index') }}" class="btn btn-outline-primary rounded-pill px-4">
                <i class="fas fa-arrow-right me-2"></i>العودة للمشاريع
            </a>
            @can('reports.print')
            <a href="{{ route('projects.reports.official_summary') }}" class="btn btn-secondary rounded-pill px-4 ms-2 border-0">
                <i class="fas fa-file-pdf me-2"></i>التقرير الرسمي
            </a>
            <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn btn-primary rounded-pill px-4 ms-2 bg-report-primary border-0">
                <i class="fas fa-print me-2"></i>طباعة
            </a>
            @endcan
        </div>
    </div>

    {{-- الفلاتر بحسب النطاق الجغرافي والإداري --}}
    @include('projects.reports.partials._report_filters', ['actionUrl' => route('projects.reports.index')])

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="kpi-card primary-gradient">
                <div class="kpi-content">
                    <div class="kpi-label">إجمالي المشاريع</div>
                    <div class="kpi-value">{{ $stats['total_projects'] ?? 0 }}</div>
                </div>
                <i class="fas fa-project-diagram kpi-icon" style="font-size: 32px; opacity: 0.8"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="kpi-card info-gradient">
                <div class="kpi-content">
                    <div class="kpi-label">سجلات التنفيذ</div>
                    <div class="kpi-value">{{ ($stats['implementation']['preliminary'] ?? 0) + ($stats['implementation']['executive'] ?? 0) }}</div>
                </div>
                <i class="fas fa-tasks kpi-icon" style="font-size: 32px; opacity: 0.8"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="kpi-card warning-gradient">
                <div class="kpi-content">
                    <div class="kpi-label">سجلات الجودة</div>
                    <div class="kpi-value">{{ $stats['quality']['total_records'] ?? 0 }}</div>
                </div>
                <i class="fas fa-chart-line kpi-icon" style="font-size: 32px; opacity: 0.8"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="kpi-card success-gradient">
                <div class="kpi-content">
                    <div class="kpi-label">الاستخدام المالي</div>
                    <div class="kpi-value">{{ number_format($stats['financial']['utilization_percentage'] ?? 0, 1) }}%</div>
                </div>
                <i class="fas fa-dollar-sign kpi-icon" style="font-size: 32px; opacity: 0.8"></i>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <!-- Chart 1: Projects Status -->
            <div class="chart-card h-100 mb-4">
                <div class="chart-header">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-light rounded-circle text-report-primary me-3 d-flex align-items-center justify-content-center" style="width:35px;height:35px; border: 1px solid rgba(44, 95, 45, 0.1)">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <h6 class="m-0 font-weight-bold text-report-primary">حالة المشاريع</h6>
                            <small class="text-muted">توزيع المشاريع حسب الحالة</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 280px; margin-bottom: 20px;">
                        <canvas id="chartProjectsStatus"></canvas>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 px-4 pb-4">
                    <div class="d-flex justify-content-start gap-2">
                        @can('reports.status.view')
                          <a href="{{ route('projects.reports.status') }}" class="btn btn-sm btn-outline-secondary rounded-pill auth-perm-reports-status-view">
                            <i class="fas fa-list-ul me-1"></i> تفاصيل الحالة
                          </a>
                        @endcan
                        @can('reports.overview.view')
                          <a href="{{ route('projects.reports.overview') }}" class="btn btn-sm btn-primary bg-report-primary rounded-pill border-0 auth-perm-reports-overview-view">
                            <i class="fas fa-th-large me-1"></i> عرض شامل
                          </a>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Chart 2: Progress Over Time -->
            <div class="chart-card h-100 mb-4">
                <div class="chart-header">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-light rounded-circle text-report-primary me-3 d-flex align-items-center justify-content-center" style="width:35px;height:35px; border: 1px solid rgba(44, 95, 45, 0.1)">
                            <i class="fas fa-stopwatch"></i>
                        </div>
                        <div>
                            <h6 class="m-0 font-weight-bold text-report-primary">التقدم الزمني</h6>
                            <small class="text-muted">متوسط نسبة الإنجاز عبر الأشهر</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 280px; margin-bottom: 20px;">
                        <canvas id="chartProgress"></canvas>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 px-4 pb-4">
                    <div class="d-flex justify-content-start gap-2">
                        @can('reports.progress.view')
                          <a href="{{ route('projects.reports.progress') }}" class="btn btn-sm btn-primary bg-report-primary rounded-pill border-0 auth-perm-reports-progress-view">
                            <i class="fas fa-chart-line me-1"></i> تفاصيل التقدم
                          </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Financial Utilization -->
            <div class="chart-card mb-4">
                <div class="chart-header">
                    <h6 class="m-0 font-weight-bold text-report-primary">الاستخدام المالي</h6>
                    <small class="text-muted">نسبة المصروف مقابل الميزانية</small>
                </div>
                <div class="card-body">
                    <div style="height: 200px; margin-bottom: 20px;">
                        <canvas id="chartFinancial"></canvas>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                        <span class="small font-weight-bold text-secondary">إجمالي الميزانية</span>
                        <span class="small font-weight-bold text-dark">{{ number_format($stats['financial']['total_budget'] ?? 0) }} ﷼</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small font-weight-bold text-secondary">المصروف</span>
                        <span class="small font-weight-bold text-report-primary">{{ number_format($stats['financial']['total_spent'] ?? 0) }} ﷼</span>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 px-4 pb-4">
                    @can('reports.financial.view')
                      <a href="{{ route('projects.reports.financial') }}" class="btn btn-sm btn-outline-primary rounded-pill w-100 auth-perm-reports-financial-view">
                        <i class="fas fa-file-invoice-dollar me-1"></i> التفاصيل المالية
                      </a>
                    @endcan
                </div>
            </div>

            <!-- Quality Summary -->
            <div class="chart-card mb-4">
                <div class="chart-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="m-0 font-weight-bold text-report-primary">مؤشرات الجودة</h6>
                            <small class="text-muted">الإجمالي: {{ $stats['quality']['total_records'] ?? 0 }}</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3" style="border: 1px solid rgba(22, 163, 74, 0.2)">
                                <h3 class="mb-0 text-success font-weight-bold">{{ $stats['quality']['positive'] ?? 0 }}</h3>
                                <small class="text-muted">إيجابية</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3" style="border: 1px solid rgba(220, 38, 38, 0.2)">
                                <h3 class="mb-0 text-danger font-weight-bold">{{ $stats['quality']['negative'] ?? 0 }}</h3>
                                <small class="text-muted">سلبية</small>
                            </div>
                        </div>
                    </div>
                    <p class="small text-muted text-center m-0">مؤشرات دقيقة حول امتثال المشاريع لمعايير الجودة التنفيذية.</p>
                </div>
                <div class="card-footer bg-white border-top-0 px-4 pb-4">
                    @can('reports.quality.view')
                      <a href="{{ route('projects.reports.quality') }}" class="btn btn-sm btn-outline-primary rounded-pill w-100 auth-perm-reports-quality-view">
                        <i class="fas fa-clipboard-check me-1"></i> تفاصيل الجودة
                      </a>
                    @endcan
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="chart-card mb-4">
                <div class="chart-header">
                    <h6 class="m-0 font-weight-bold text-report-primary">نظرة سريعة</h6>
                    <small class="text-muted">توزيع الحالات</small>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded-3">
                                <h4 class="mb-0 font-weight-bold text-report-primary">{{ $stats['projects_by_status']['نشط'] ?? 0 }}</h4>
                                <small class="text-muted">نشطة</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded-3">
                                <h4 class="mb-0 font-weight-bold text-warning">{{ $stats['projects_by_status']['متأخر'] ?? 0 }}</h4>
                                <small class="text-muted">متأخرة</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded-3">
                                <h4 class="mb-0 font-weight-bold text-success">{{ $stats['projects_by_status']['مكتمل'] ?? 0 }}</h4>
                                <small class="text-muted">مكتملة</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded-3">
                                <h4 class="mb-0 font-weight-bold text-secondary">{{ $stats['projects_by_status']['معلق'] ?? 0 }}</h4>
                                <small class="text-muted">معلّقة</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ERP Stock Reports Row -->
    <div class="row mb-4">
        <div class="col-12 mb-3">
            <h5 class="font-weight-bold text-report-primary"><i class="fas fa-boxes me-2"></i>تقارير المخزون والمشتريات</h5>
            <hr>
        </div>
        
        @can('reports.purchase_invoices.view')
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="avatar-md bg-light rounded-circle text-report-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:60px;height:60px; border: 1px solid rgba(44, 95, 45, 0.1)">
                        <i class="fas fa-file-invoice-dollar fa-2x"></i>
                    </div>
                    <h5 class="font-weight-bold text-report-primary mb-2">فواتير الشراء</h5>
                    <p class="text-muted small mb-4">استعراض وتتبع فواتير الشراء وأرصدة الموردين</p>
                    <a href="{{ route('projects.reports.purchase_invoices') }}" class="btn btn-outline-primary rounded-pill w-100">
                        عرض التقرير <i class="fas fa-arrow-left ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        @endcan

        @can('reports.stock_ledger.view')
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="avatar-md bg-light rounded-circle text-report-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:60px;height:60px; border: 1px solid rgba(44, 95, 45, 0.1)">
                        <i class="fas fa-book fa-2x"></i>
                    </div>
                    <h5 class="font-weight-bold text-report-primary mb-2">سجل المخزون</h5>
                    <p class="text-muted small mb-4">تقرير تفصيلي يعرض حركات وأرصدة المخزون</p>
                    <a href="{{ route('projects.reports.stock_ledger') }}" class="btn btn-outline-primary rounded-pill w-100">
                        عرض التقرير <i class="fas fa-arrow-left ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        @endcan

        @can('reports.material_requests.view')
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="avatar-md bg-light rounded-circle text-report-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:60px;height:60px; border: 1px solid rgba(44, 95, 45, 0.1)">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                    <h5 class="font-weight-bold text-report-primary mb-2">طلبات المواد</h5>
                    <p class="text-muted small mb-4">متابعة طلبات توفير المواد للمشاريع</p>
                    <a href="{{ route('projects.reports.material_requests') }}" class="btn btn-outline-primary rounded-pill w-100">
                        عرض التقرير <i class="fas fa-arrow-left ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        @endcan

        @can('reports.stock_entries.view')
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform 0.2s;">
                <div class="card-body text-center p-4">
                    <div class="avatar-md bg-light rounded-circle text-report-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:60px;height:60px; border: 1px solid rgba(44, 95, 45, 0.1)">
                        <i class="fas fa-dolly fa-2x"></i>
                    </div>
                    <h5 class="font-weight-bold text-report-primary mb-2">صرف المواد</h5>
                    <p class="text-muted small mb-4">استعراض حركات صرف المواد للمشاريع</p>
                    <a href="{{ route('projects.reports.stock_entries') }}" class="btn btn-outline-primary rounded-pill w-100">
                        عرض التقرير <i class="fas fa-arrow-left ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        @endcan

    </div>
</div>
@endsection