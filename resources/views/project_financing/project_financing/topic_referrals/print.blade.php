<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إحالات الموضوع - {{ $topic->subject }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        /* إعدادات الصفحة الأساسية للطباعة */
        @page {
            size: A4;
            margin: 20mm; /* الهوامش القياسية للمستندات الرسمية */
        }

        body { 
            font-family: 'Amiri', 'Traditional Arabic', 'Segoe UI', serif; 
            background: #f5f5f5; 
            color: #333;
        }

        .print-container { 
            background: #fff;
            width: 210mm; /* عرض A4 */
            min-height: 297mm; /* طول A4 */
            margin: 20px auto; 
            padding: 25mm; /* مساحة داخلية إضافية للأناقة */
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            position: relative;
        }

        /* الترويسة الأنيقة */
        .official-header {
            border-bottom: 3px double #333;
            margin-bottom: 40px;
            padding-bottom: 20px;
        }

        .report-title {
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .subject-badge {
            display: inline-block;
            background: #f0f0f0;
            padding: 5px 15px;
            border-radius: 4px;
            font-size: 1rem;
            border-right: 4px solid #333;
        }

        .qr-code img { 
            width: 100px; 
            height: 100px; 
            border: 1px solid #eee;
            padding: 5px;
        }

        /* قسم الإحالات */
        .referral-section {
            position: relative;
            padding-right: 20px;
            border-right: 2px solid #e0e0e0;
        }

        .referral-number {
            font-weight: bold;
            color: #2c3e50;
            background: #f8f9fa;
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .content-box {
            background: #fff;
            border: 1px inset #eee;
            padding: 15px;
            font-size: 1.1rem;
            line-height: 1.8;
            margin-top: 10px;
        }

        .response-box {
            background: #fdfdfd;
            border-right: 4px solid #198754 !important;
            margin-top: 15px;
            padding: 15px;
            font-style: italic;
        }

        .footer { 
            position: absolute;
            bottom: 25mm;
            left: 25mm;
            right: 25mm;
            border-top: 1px solid #eee; 
            padding-top: 15px; 
            font-size: 0.75rem; 
            color: #888; 
        }

        /* تحسينات وضع الطباعة */
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .print-container { 
                box-shadow: none; 
                margin: 0; 
                width: 100%; 
                padding: 0; /* تعتمد على هامش @page */
            }
            .referral-section { page-break-inside: avoid; } /* منع انقسام الإحالة الواحدة بين صفحتين */
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container no-print mt-4 mb-4 text-center">
        <div class="btn-group shadow-sm">
            <button onclick="window.print()" class="btn btn-dark btn-lg d-inline-flex align-items-center gap-2">
                <x-icon name="print" size="18" /> طباعة المستند
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-lg">إغلاق</button>
        </div>
    </div>

    <div class="print-container">
        <div class="official-header row align-items-center">
            <div class="col-8">
                <div class="report-title">تقرير إحالات وحركة المستند</div>
                <div class="subject-badge">الموضوع: {{ $topic->subject }}</div>
            </div>
            <div class="col-4 text-start qr-code">
                <img src="{{ $qrCodeData }}" alt="QR Code">
            </div>
        </div>

        @foreach($topic->activities as $activity)
            <div class="referral-section mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="referral-number">إحالة رقم: {{ $activity->referral_number }}</span>
                    <small class="text-muted">{{ $activity->referral_date->format('Y-m-d') }} | {{ $activity->referral_date->format('H:i') }}</small>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="small text-muted">الجهة المرسلة:</div>
                        <strong>{{ $activity->fromUser->name }}</strong> 
                        <span class="text-secondary">({{ $activity->fromDepartment->name }})</span>
                    </div>
                    <div class="col-6 border-start">
                        <div class="small text-muted">الجهة المستقبلة:</div>
                        <strong>{{ $activity->toDepartment->name }}</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="content-box">
                        {{ $activity->referral_text }}
                    </div>
                </div>

                @if($activity->response_text)
                    <div class="response-box shadow-sm">
                        <div class="small text-success fw-bold mb-1">الرد الرسمي:</div>
                        {{ $activity->response_text }}
                        <div class="mt-2" style="font-size: 0.8rem; color: #666;">
                            تم الرد بواسطة: {{ $activity->responder->name ?? 'غير معروف' }} في {{ $activity->response_date ? $activity->response_date->format('Y-m-d H:i') : '-' }}
                        </div>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="footer">
            <div class="row">
                <div class="col-6">نظام إدارة المشاريع الذكي</div>
                <div class="col-6 text-start">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>
</body>
</html>