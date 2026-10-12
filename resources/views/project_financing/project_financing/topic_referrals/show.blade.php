<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل الإحالات الرسمي - {{ $topic->subject }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        /* إعدادات الطباعة الاحترافية */
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            background: #f4f7f6; 
            color: #2d3436;
            -webkit-print-color-adjust: exact;
        }

        .print-container { 
            background: #fff;
            width: 277mm; 
            margin: 20px auto; 
            padding: 15mm; 
            box-shadow: 0 0 30px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        /* ترويسة فخمة */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 4px solid #0984e3;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .main-title {
            font-size: 28px;
            font-weight: 900;
            color: #2d3436;
            margin-bottom: 5px;
        }

        .subject-box {
            background: #e1f5fe;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            color: #01579b;
            font-size: 1.1rem;
        }

        /* الجدول الاحترافي */
        .elegant-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px; /* مسافات بين الصفوف تعطي طابعاً عصرياً */
        }

        .elegant-table thead th {
            background-color: #0984e3;
            color: white;
            padding: 15px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
        }

        .elegant-table thead th:first-child { border-radius: 0 12px 12px 0; }
        .elegant-table thead th:last-child { border-radius: 12px 0 0 12px; }

        .elegant-table tbody tr {
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }

        .elegant-table td {
            background: #fff;
            padding: 20px 15px;
            vertical-align: middle;
            border-top: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .elegant-table td:first-child { border-right: 1px solid #edf2f7; border-radius: 0 12px 12px 0; }
        .elegant-table td:last-child { border-left: 1px solid #edf2f7; border-radius: 12px 0 0 12px; }

        /* تفاصيل الأعمدة */
        .ref-id { font-weight: 800; color: #0984e3; font-size: 1.2rem; text-align: center; }
        .user-info { font-size: 0.9rem; line-height: 1.4; }
        .dept-tag { font-size: 0.75rem; background: #f0f2f5; padding: 2px 8px; border-radius: 4px; color: #636e72; }
        
        .content-text { font-size: 0.95rem; color: #4b4b4b; line-height: 1.6; }
        
        .status-badge {
            display: block;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .status-replied { background: #ebfbee; color: #2ecc71; border-right: 4px solid #2ecc71; }
        .status-pending { background: #fff9db; color: #f1c40f; border-right: 4px solid #f1c40f; }

        .qr-code img { width: 85px; filter: grayscale(1); opacity: 0.8; }

        @media print {
            body { background: white; }
            .no-print { display: none; }
            .print-container { box-shadow: none; margin: 0; padding: 5mm; width: 100%; }
            .elegant-table { border-spacing: 0; } /* تحسين المسافات للطباعة */
            .elegant-table td { border: 1px solid #eee; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container no-print mt-4 text-center">
        <button onclick="window.print()" class="btn btn-dark btn-lg rounded-pill px-5 shadow-lg d-inline-flex align-items-center justify-content-center gap-2 mx-auto">
            <x-icon name="print" size="18" /> طباعة الوثيقة الرسمية
        </button>
    </div>

    <div class="print-container">
        <header class="report-header">
            <div>
                <h1 class="main-title">سجل حركة المعاملات والإحالات</h1>
                <span class="subject-box">الموضوع: {{ $topic->subject }}</span>
            </div>
            <div class="text-start">
                <div class="qr-code">
                    <img src="{{ $qrCodeData }}" alt="QR">
                </div>
                <small class="text-muted d-block mt-1">رمز التحقق الرقمي</small>
            </div>
        </header>

        <table class="elegant-table">
            <thead>
                <tr>
                    <th width="8%">الرقم</th>
                    <th width="12%">التاريخ والوقت</th>
                    <th width="20%">مسار الإحالة</th>
                    <th width="30%">نص الإحالة</th>
                    <th width="30%">الرد والنتيجة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topic->activities as $activity)
                <tr>
                    <td class="ref-id">#{{ $activity->referral_number }}</td>
                    <td class="text-center">
                        <div class="fw-bold">{{ $activity->referral_date->format('Y-m-d') }}</div>
                        <div class="text-muted small">{{ $activity->referral_date->format('H:i A') }}</div>
                    </td>
                    <td>
                        <div class="user-info">
                            <span class="text-primary small">مرسل:</span> <strong>{{ $activity->fromUser->name }}</strong><br>
                            <span class="dept-tag">{{ $activity->fromDepartment->name }}</span>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <span class="text-danger small">مستلم:</span> <strong>{{ $activity->toDepartment->name }}</strong>
                        </div>
                    </td>
                    <td class="content-text">
                        {{ $activity->referral_text }}
                    </td>
                    <td>
                        @if($activity->response_text)
                            <div class="status-badge status-replied">
                                <strong>{{ $activity->response_text }}</strong>
                                <div class="mt-2 pt-1 border-top border-2" style="font-size: 0.75rem">
                                    المجيب: {{ $activity->responder->name ?? 'غير معروف' }} 
                                    <span class="mx-1">|</span> 
                                    {{ $activity->response_date->format('Y-m-d H:i') }}
                                </div>
                            </div>
                        @else
                            <div class="status-badge status-pending text-center d-flex align-items-center justify-content-center gap-1">
                                <x-icon name="clock" size="14" /> قيد المعالجة
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <footer class="mt-5 d-flex justify-content-between text-muted small border-top pt-3">
            <div>نظام الإدارة الذكي &copy; {{ date('Y') }}</div>
            <div>تم استخراج التقرير بواسطة: {{ Auth::user()->name }}</div>
            <div>تاريخ الاستخراج: {{ now()->format('Y-m-d H:i') }}</div>
        </footer>
    </div>
</body>
</html>