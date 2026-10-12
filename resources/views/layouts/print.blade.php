<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('report_title', 'تقرير رسمي')</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Centralized Letterhead Styles -->
    <x-letterhead-styles />

    <style>
        body {
            background-color: #ffffff;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .official-print-header {
            margin-bottom: 20px;
        }

        .table-print {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            border-collapse: collapse;
        }

        .table-print th,
        .table-print td {
            padding: 0.75rem;
            vertical-align: top;
            border: 1px solid #dee2e6;
            font-size: 13px;
        }

        .table-print thead th {
            vertical-align: bottom;
            border-bottom: 2px solid var(--print-primary-color, #2c5f2d);
            background-color: #f8f9fa;
            font-weight: 700;
            color: var(--print-primary-color, #2c5f2d);
        }

        .text-primary-print {
            color: var(--print-primary-color, #2c5f2d) !important;
        }
        
        .bg-primary-print {
            background-color: var(--print-primary-color, #2c5f2d) !important;
            color: white !important;
        }

        @media print {
            body {
                background-color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
            .avoid-break {
                page-break-inside: avoid;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body onload="window.print()">

    <div class="container-fluid py-3">
        
        <!-- Controls for non-print view -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print gap-2 flex-wrap">
            <x-letterhead-selector />

            <div class="d-flex gap-2">
                <button onclick="window.history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i> العودة
                </button>
                <button onclick="window.print()" class="btn btn-success" style="background-color: var(--print-primary-color, #2c5f2d); border-color: var(--print-primary-color, #2c5f2d);">
                    <i class="fas fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>

        <!-- Official Print Header Component -->
        <x-letterhead-header 
            :documentSubject="View::hasSection('report_subject') ? trim($__env->yieldContent('report_subject')) : null"
            :documentDate="date('Y-m-d')"
        />

        @hasSection('report_title')
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 class="print-title" style="margin: 0;">@yield('report_title')</h2>
            </div>
        @endif

        <!-- Content -->
        @yield('content')
        
        <!-- Official Print Footer Component -->
        <x-letterhead-footer :documentName="View::hasSection('report_title') ? trim($__env->yieldContent('report_title')) : null" />

    </div>

    @stack('scripts')
</body>
</html>
