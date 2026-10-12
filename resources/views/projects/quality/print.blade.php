<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الجودة - طباعة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #fff; color: #000; }
        .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .kpi-box { border: 1px solid #000; padding: 15px; text-align: center; font-weight: bold; }
        .chart-box { max-width: 500px; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background: #f0f0f0; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print text-center mb-4">
            <button class="btn btn-primary" onclick="window.print()">طباعة الآن</button>
        </div>

        <div class="print-header">
            <h2>تقرير مراقبة الجودة الشامل</h2>
            <p>تاريخ الإصدار: {{ date('Y-m-d') }}</p>
        </div>

        <div class="row mb-4">
            <div class="col-3"><div class="kpi-box">إجمالي المشاريع<br>{{ $kpis['total_projects'] }}</div></div>
            <div class="col-3"><div class="kpi-box">المكتملة<br>{{ $kpis['completed_projects'] }}</div></div>
            <div class="col-3"><div class="kpi-box">قيد التنفيذ<br>{{ $kpis['in_progress_projects'] }}</div></div>
            <div class="col-3"><div class="kpi-box">متوسط الجودة<br>{{ $kpis['average_score'] }}%</div></div>
        </div>

        <div class="row page-break">
            <div class="col-6 text-center">
                <h4>توزيع مستويات الجودة</h4>
                <div class="chart-box">
                    <canvas id="qualityDistributionChart"></canvas>
                </div>
            </div>
            <div class="col-6 text-center">
                <h4>متوسط الجودة حسب الجهة</h4>
                <div class="chart-box">
                    <canvas id="qualityByEntityChart"></canvas>
                </div>
            </div>
        </div>

        <h4 class="mt-5 mb-3">تفاصيل المشاريع</h4>
        <table>
            <thead>
                <tr>
                    <th>المشروع</th>
                    <th>الجهة</th>
                    <th>الموقع</th>
                    <th>الحالة</th>
                    <th>جودة الوقت</th>
                    <th>الجودة المالية</th>
                    <th>التقييم العام</th>
                </tr>
            </thead>
            <tbody>
                @foreach($projects_data as $data)
                <tr>
                    <td>{{ $data['project']->project_name }}</td>
                    <td>{{ $data['entity_name'] }}</td>
                    <td>{{ $data['location_name'] }}</td>
                    <td>{{ $data['status_name'] }}</td>
                    <td>{{ $data['statistics']['time_positive_percentage'] }}%</td>
                    <td>{{ $data['statistics']['financial_positive_percentage'] }}%</td>
                    <td>{{ $data['statistics']['overall_quality_score'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Script that uses the local chart.min.js that was downloaded earlier -->
    <script src="/js/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($charts);
            
            Chart.defaults.animation = false; // Disable animations for print

            new Chart(document.getElementById('qualityDistributionChart'), {
                type: 'doughnut',
                data: {
                    labels: chartData.quality_distribution.labels,
                    datasets: [{
                        data: chartData.quality_distribution.data,
                        backgroundColor: ['#10b981', '#34d399', '#f59e0b', '#f97316', '#ef4444'],
                    }]
                }
            });

            new Chart(document.getElementById('qualityByEntityChart'), {
                type: 'bar',
                data: {
                    labels: chartData.quality_by_entity.labels,
                    datasets: [{
                        label: 'متوسط الجودة %',
                        data: chartData.quality_by_entity.data,
                        backgroundColor: '#08214c',
                    }]
                }
            });

            // Auto-trigger print after charts render
            setTimeout(() => {
                // window.print();
            }, 1000);
        });
    </script>
</body>
</html>
