<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مراسلة | {{ $correspondence->correspondence_number ?? '............' }}</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<x-letterhead-styles :letterhead="$letterhead ?? null" />
<style>
@page {
    margin: 10mm;
}

@page {
    margin: 10mm;
}

@media print {
    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .no-print { display: none !important; }
    tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
}

body {
    font-family: 'cairo', sans-serif;
    margin: 0;
    padding: 0;
    direction: rtl;
    background: #fff;
    color: #1a1a1a;
}

.print-container { padding: 5px; }

.header-table {
    width: 100%;
    border-bottom: 2px solid #0C5B47;
    margin-bottom: 10px;
    border-collapse: collapse;
}

.header-table td {
    vertical-align: middle;
    padding-bottom: 8px;
}

.header-right {
    width: 25%;
    text-align: right;
    font-weight: 700;
    font-size: 11pt;
    line-height: 1.6;
}

.header-left {
    width: 25%;
    text-align: left;
}

.header-center {
    width: 50%;
    text-align: center;
}

.logo { max-height: 70px; margin-bottom: 5px; }

h1 {
    font-size: 16pt;
    color: #0C5B47;
    margin: 5px 0;
    font-weight: 800;
}

.number-badge {
    background: #e6eeec;
    color: #0C5B47;
    padding: 4px 15px;
    font-weight: 700;
    display: inline-block;
    margin-top: 5px;
    border: 1px solid #0C5B47;
}

.batch-info {
    font-size: 10pt;
    font-weight: 700;
    color: #555;
}

.qr-code-img {
    width: 70px;
    height: 70px;
    border: 1px solid #ddd;
    padding: 2px;
    background: #fff;
}

.info-grid {
    width: 100%;
    border: 1px solid #222;
    margin-bottom: 8px;
}

.info-grid td {
    padding: 4px;
    border: 1px solid #222;
    text-align: center;
    width: 25%;
}

.info-label {
    display: block;
    font-size: 9pt;
    color: #555;
    font-weight: 600;
    margin-bottom: 3px;
}

.info-value {
    display: block;
    font-size: 10.5pt;
    font-weight: 700;
}

/* --- Document Body --- */
.document-body {
    margin-bottom: 10px;
    padding: 5px;
    border: 0;
}

.addressing {
    font-size: 12pt;
    font-weight: 700;
    margin-bottom: 10px;
}

.subject-line {
    background-color: #e6eeec;
    border-right: 5px solid #0C5B47;
    padding: 8px;
    margin: 10px 0;
    font-weight: 800;
    font-size: 11.5pt;
    color: #0C5B47;
}

.content-area {
    font-size: 11pt;
    text-align: justify;
    line-height: 1.7;
}

/* --- Tables --- */
table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

th {
    background-color: #0C5B47;
    color: #fff;
    border: 1px solid #222;
    padding: 8px 2px;
    font-size: 8.5pt;
    text-align: center;
}

td {
    border: 1px solid #222;
    padding: 5px;
    font-size: 8pt;
    text-align: center;
    vertical-align: middle;
}

/* --- Signatures --- */
.sig-section { margin-top: 10px; }
.sig-section table { border: 0; }
.sig-section td { border: 0; text-align: center; width: 33%; padding: 2px; }

.sig-space {
    margin-top: 25px;
    border-top: 1px solid #222;
    padding-top: 2px;
    font-weight: 700;
    margin-left: 10px;
    margin-right: 10px;
}

.footer-info {
    margin-top: 10px;
    text-align: center;
    font-size: 7.5pt;
    color: #777;
    border-top: 1px solid #eee;
    padding-top: 5px;
}

/* --- Referrals Separate Page --- */
.referrals-page {
    page-break-before: always;
    break-before: page;
    padding-top: 5px;
}

.referrals-title {
    font-size: 13pt;
    color: #0C5B47;
    font-weight: 800;
    border-bottom: 2px solid #0C5B47;
    padding-bottom: 5px;
    margin-bottom: 8px;
}

@media print {
    .print-container {
        height: auto;
        overflow: hidden;
        page-break-after: avoid;
    }
    .no-print { display: none !important; }
}

.floating-actions {
    position: fixed;
    bottom: 20px;
    left: 20px;
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 16px;
    cursor: pointer;
    font-weight: 700;
    border: 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.btn-print { background: #0C5B47; color: #fff; }
.btn-close  { background: #555; color: #fff; }
</style>
</head>
<body>

<div class="print-container">

<x-letterhead-selector :currentLetterhead="$letterhead ?? null" />

<x-letterhead-header 
    :letterhead="$letterhead ?? null"
    :documentNumber="$correspondence->correspondence_number" 
    :documentDate="$correspondence->correspondence_date" 
    :qrCodeData="$qrCodeData ?? null" 
    customTitle="مذكرة مراسلة رسمية" 
/>

<table class="info-grid">
    <tr>
        <td>
            <span class="info-label">نوع المراسلة</span>
            <span class="info-value">{{ $correspondence->type_label ?? 'رسمية' }}</span>
        </td>
        <td>
            <span class="info-label">الجهة المرسلة</span>
            <span class="info-value">{{ $correspondence->senderEntity?->name ?? '---' }}</span>
        </td>
        <td>
            <span class="info-label">تاريخ الإصدار</span>
            <span class="info-value">{{ now()->format('Y-m-d') }}</span>
        </td>
        <td>
            <span class="info-label">المرفقات</span>
            <span class="info-value">{{ !empty($correspondence->attachments) ? 'يوجد مرفقات' : 'لا يوجد' }}</span>
        </td>
    </tr>
</table>

<div class="document-body">
    <div class="addressing">
        الأخ/ {{ $correspondence->recipientEntity?->name ?? 'اسم المستلم' }} <span style="margin-right:15pt;">المحترم</span>
    </div>

    <div style="font-weight:600; margin-bottom:10px;">بعد التحية والتقدير،،،</div>

    <div class="subject-line">
        الموضوع: {{ $correspondence->subject }}
    </div>

    <div class="content-area">
        {!! nl2br(e($correspondence->message_body)) !!}
    </div>

    <div style="text-align:center; margin-top:20px; font-weight:700;">
        وتقبلوا خالص التحية والتقدير؛؛؛
    </div>
</div>

@if(isset($movements) && count($movements) > 0)
<h2 style="font-size:12pt; color:var(--primary); border-bottom:1.5px solid var(--primary); padding-bottom:3px; margin-bottom:5px;">
    سجل حركة المراسلة (مسار الإجراءات)
</h2>
<table>
    <thead>
        <tr>
            <th width="15%">التاريخ</th>
            <th width="15%">نوع الإجراء</th>
            <th width="45%">الوصف المسجل</th>
            <th width="25%">بواسطة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($movements as $movement)
        <tr>
            <td>{{ $movement->action_date->format('Y-m-d H:i') }}</td>
            <td><strong>{{ $movement->action_type_label }}</strong></td>
            <td style="text-align:right;">
                {{ $movement->action_description }}
                @if($movement->from_entity || $movement->to_entity)
                    <div style="font-size:8pt; color:var(--text-muted); margin-top:4px;">
                        @if($movement->from_entity) [مـن: {{ $movement->from_entity }}] @endif
                        @if($movement->to_entity) [إلى: {{ $movement->to_entity }}] @endif
                    </div>
                @endif

                {{-- نص الإحالة --}}
                @if($movement->action_type === 'referral' && !empty($movement->action_details['referral_text']))
                    <div style="margin-top:5px; padding:4px 8px; background:#e8f4fd; border-right:3px solid #2196F3; border-radius:3px; font-size:8.5pt; color:#1a3a5c;">
                        <strong>نص الإحالة:</strong> {{ $movement->action_details['referral_text'] }}
                    </div>
                @endif

                {{-- سبب الإغلاق --}}
                @if($movement->action_type === 'close' && !empty($movement->action_details['reason']))
                    <div style="margin-top:5px; padding:4px 8px; background:#fff3e0; border-right:3px solid #FF9800; border-radius:3px; font-size:8.5pt; color:#5c3a1a;">
                        <strong>سبب الإغلاق:</strong> {{ $movement->action_details['reason'] }}
                    </div>
                @endif

                {{-- نص الرد --}}
                @if($movement->action_type === 'reply' && !empty($movement->action_details['reply_text']))
                    <div style="margin-top:5px; padding:4px 8px; background:#e8f5e9; border-right:3px solid #4CAF50; border-radius:3px; font-size:8.5pt; color:#1a5c2a;">
                        <strong>نص الرد:</strong> {{ $movement->action_details['reply_text'] }}
                    </div>
                @endif
            </td>
            <td>{{ $movement->user->name ?? 'النظام' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="sig-section">
    <table>
        <tr>
            <td>
                <span>إعداد / المسؤول المختص</span>
                <div class="sig-space">{{ auth()->user()->name ?? '......................' }}</div>
            </td>
            <td>
                <span>مراجعة / مدير الإدارة</span>
                <div class="sig-space">......................</div>
            </td>
            <td>
                <span>اعتماد / رئيس الجهة</span>
                <div class="sig-space">ختم وتوقيع رسمي</div>
            </td>
        </tr>
    </table>
<x-letterhead-footer :letterhead="$letterhead ?? null" documentName="مذكرة مراسلة رسمية" />

</div>

@if(isset($correspondence->referrals) && $correspondence->referrals->count() > 0)
<div class="referrals-page">
    <h2 class="referrals-title">
        جدول الإحالات المرتبطة بالمراسلة {{ $correspondence->correspondence_number }}
    </h2>
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="20%">الجهة المحالة إليها</th>
                <th width="15%">بواسطة (المحيل)</th>
                <th width="28%">نص الإحالة / التعليمات</th>
                <th width="12%">تاريخ الإحالة</th>
                <th width="10%">الموعد النهائي</th>
                <th width="10%">الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($correspondence->referrals as $referral)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $referral->referredToEntity?->name ?? '---' }}</td>
                <td>{{ $referral->referredByUser?->name ?? 'النظام' }}</td>
                <td style="text-align:right;">{{ $referral->referral_text }}</td>
                <td>{{ $referral->referred_at ? $referral->referred_at->format('Y-m-d H:i') : $referral->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $referral->deadline ? $referral->deadline->format('Y-m-d') : '---' }}</td>
                <td>{{ $referral->status_label }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer-info">
        إجمالي الإحالات المرتبطة بهذه المراسلة: {{ $correspondence->referrals->count() }} إحالة
    </div>
</div>
@endif

<div class="floating-actions no-print">
    <button onclick="window.print()" class="btn btn-print">طباعة الوثيقة</button>
    <button onclick="window.history.back()" class="btn btn-close">إغلاق</button>
</div>

</body>
</html>