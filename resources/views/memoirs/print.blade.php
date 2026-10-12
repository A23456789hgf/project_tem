<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مذكرة إدارية | {{ $memoir->memoir_number ?? '' }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<x-letterhead-styles :letterhead="$letterhead ?? null" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');

        @page {
            margin: 15mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            direction: rtl;
            background: #fff;
            color: #1a1a1a;
        }

        .print-container {
            padding: 10px;
            max-width: 900px;
            margin: auto;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0C5B47;
            padding-bottom: 15px;
            margin-bottom: 10px;
        }

        .header-right {
            flex: 1;
            text-align: center;
            font-weight: 700;
            font-size: 11pt;
            line-height: 1.8;
        }

        .header-center {
            flex: 1;
            text-align: center;
        }

        .header-left {
            flex: 1;
            text-align: left;
            font-size: 10pt;
            line-height: 1.8;
        }

        .logo {
            max-height: 90px;
            width: auto;
        }

        .document-title {
            text-align: center;
            margin: 15px 0;
        }

        .document-title h1 {
            font-size: 18pt;
            color: #0C5B47;
            margin: 0;
            font-weight: 800;
        }

        .document-body {
            margin-top: 20px;
            padding: 0 10px;
        }

        .addressing {
            font-size: 12.5pt;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .subject-line {
            background-color: #f0f5f4;
            border-right: 5px solid #0C5B47;
            padding: 12px;
            margin: 20px 0;
            font-weight: 800;
            font-size: 12pt;
            color: #0C5B47;
        }

        .content-area {
            font-size: 12pt;
            text-align: justify;
            line-height: 1.9;
            min-height: 300px;
            white-space: pre-wrap;
        }

        /* تعديل منطقة التوقيع لتكون في اليسار مع تقليل الهوامش */
        .single-signature {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: fit-content;
            margin-right: auto;
            /* دفع العنصر لليسار */
            margin-left: 60px;
            /* إزاحة بسيطة عن الحافة اليسرى */
            text-align: center;
        }

        /* Signature row layout */
        .signatures-section {
            display: flex;
            flex-direction: row-reverse;
            /* In RTL, this puts the first element on the far left */
            justify-content: flex-start;
            align-items: flex-end;
            gap: 50px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .sig-wrapper {
            display: flex;
            justify-content: flex-start;
            /* يبقى يسار الصفحة */
            align-items: center;
            /* توسيط عمودي */
            min-height: 120px;
            /* مهم جداً */
        }

        .sig-block {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            min-width: 180px;
            margin-bottom: 20px;
        }

        .sig-label {
            font-weight: 800;
            font-size: 12pt;
            margin-bottom: 10px;
            min-height: 2.8em;
            display: block;
            line-height: 1.4;
        }

        .sig-image-container {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 10px;
        }

        .sig-image-container img {
            max-height: 70px;
            object-fit: contain;
        }

        .sig-name {
            font-weight: 800;
            font-size: 12pt;
            margin-top: 5px;
        }

        .footer-info {
            margin-top: 50px;
            text-align: center;
            font-size: 8pt;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .floating-actions {
            position: fixed;
            bottom: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 700;
            border: 0;
            border-radius: 6px;
            font-family: 'Cairo', sans-serif;
        }

        .btn-print {
            background: #0C5B47;
            color: #fff;
        }

        .btn-close {
            background: #555;
            color: #fff;
        }
    </style>
</head>

<body>

    <div class="print-container">

        <x-letterhead-selector :currentLetterhead="$letterhead ?? null" />
<x-letterhead-header :letterhead="$letterhead ?? null" :documentNumber="$memoir->memoir_number" :documentDate="$memoir->gregorian_date ?? now()->format('Y-m-d')" :customSubtitle="$memoir->hijri_date ? 'التاريخ الهجري: ' . $memoir->hijri_date : null" customTitle="مذكرة إدارية" />
        <div class="document-body">
            <div class="addressing">
                الأخ/ {{ $memoir->to }} <span style="margin-right:15pt;">المحترم</span>
            </div>

            <div style="font-weight:600; margin-bottom:5px;">بعد التحية والتقدير،،،</div>

            <div class="subject-line">
                الموضوع: {{ $memoir->subject }}
            </div>

            <div class="content-area">
                {!! nl2br(e($memoir->body)) !!}
            </div>

            <div style="text-align:center; margin-top:30px; font-weight:700;">
                وتقبلوا خالص التحية والتقدير؛؛؛
            </div>
        </div>

        <div class="signatures-section">
            <!-- Selected Officers -->
            @forelse($officers_selected as $off)
                <div class="sig-block">
                    <span class="sig-label">
                        {{ $off->job_title }}
                    </span>

                    <div class="sig-image-container">
                        <!-- Space for physical signature -->
                    </div>

                    <div class="sig-name">
                        {{ $off->admin_name }}
                    </div>
                </div>
            @empty
                <!-- Default Fallback Signature if no officers selected -->
                <div class="sig-block">
                    <span class="sig-label">
                        @if($headerType === 'ministry')
                            نائب وزير الزراعة والثروة السمكية والموارد المائية
                        @else
                            رئيس اللجنة الزراعية والسمكية العليا
                        @endif
                    </span>

                    <div class="sig-image-container">
                        <!-- Space for physical signature -->
                    </div>

                    <div class="sig-name">
                        إبراهيم حسن المداني
                    </div>
                </div>
            @endforelse

            <!-- Electronic Signatures from Checkboxes -->
            @if(isset($signatures) && count($signatures) > 0)
                @foreach($signatures as $sig)
                    <div class="sig-block">
                        <span class="sig-label">{{ $sig->job_title }}</span>
                        <div class="sig-image-container">
                            <img src="{{ asset('storage/' . $sig->signature_path) }}" alt="توقيع">
                        </div>
                        <div class="sig-name">{{ $sig->name }}</div>
                    </div>
                @endforeach
            @endif
        </div>

        <x-letterhead-footer :letterhead="$letterhead ?? null" documentName="مذكرة إدارية" />

    </div>

    <div class="floating-actions no-print">
        <button onclick="window.print()" class="btn btn-print">طباعة الوثيقة</button>
        <button onclick="window.history.back()" class="btn btn-close">إغلاق</button>
    </div>

</body>

</html>
