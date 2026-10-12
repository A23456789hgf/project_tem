<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>وثيقة طلب مشروع | {{ $projectRequest->project_name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<x-letterhead-styles />
    <style>
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: 400;
            src: url('https://fonts.gstatic.com/s/cairo/v28/SLXGc1nY6HkvangtZmpcMw.woff2') format('woff2');
        }

        :root {
            --primary: #0C5B47;
            --primary-dark: #084234;
            --secondary: #1E3A8A;
            --accent: #B19446;
            --bg-light: #F8FAFC;
            --border: #E2E8F0;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            --radius-lg: 15px;
            --radius-md: 8px;
            --danger: #DC2626;
            --success: #059669;
            --warning: #D97706;
        }

        @page {
            size: A4;
            margin-top: 15mm;
            margin-bottom: 20mm;
            margin-left: 15mm;
            margin-right: 15mm;
            margin-header: 10mm;
            margin-footer: 10mm;
        }

        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: var(--text-dark);
            line-height: 1.5;
            direction: rtl;
            font-size: 11pt;
            text-align: right;
        }

        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .w-100 { width: 100%; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            margin-bottom: 15px;
        }

        .modern-table {
            border: 1px solid #000;
        }

        .modern-table th {
            background-color: #f3f4f6;
            color: #000;
            padding: 8px;
            border: 1px solid #000;
            text-align: center;
        }

        .modern-table td {
            background-color: #fff;
            padding: 8px;
            border: 1px solid #000;
            vertical-align: middle;
        }

        .section-title {
            color: var(--primary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 5px;
            margin: 20px 0 10px;
            font-size: 13pt;
            font-weight: 800;
        }

        .card {
            border: 1px solid #000;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .card-head {
            background: #f3f4f6;
            padding: 8px 12px;
            font-weight: 700;
            border-bottom: 1px solid #000;
        }

        .cover-page {
            text-align: center;
            border: 3px double #000;
            padding: 30px;
            height: 260mm;
        }

        .project-identity {
            border: 2px solid #000;
            padding: 30px;
            margin: 40px auto;
            width: 85%;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 9pt;
            font-weight: bold;
            border: 1px solid #000;
            border-radius: 4px;
        }

        @media print {
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body>
<div class="no-print p-3"><x-letterhead-selector /></div>

    <!-- Cover Page -->
    <div class="cover-page page-break">
        <x-letterhead-header hideBorder="true" />

        <div style="margin-top: 50px;">
            <h1 style="font-size: 26pt; color: #000;">وثيقة طلب مشروع مقترح</h1>
            <div style="width: 120px; height: 3px; background: var(--accent); margin: 15px auto;"></div>
        </div>

        <div class="project-identity">
            <div style="font-size: 14pt; margin-bottom: 20px; color: var(--text-muted);">عنوان المشروع المقترح</div>
            <h2 style="font-size: 22pt; color: var(--primary); margin: 20px 0; line-height: 1.4;">{{ $projectRequest->project_name }}</h2>

            <div style="margin-top: 40px;">
                @if($qrCodeBase64)
                    <img src="{{ $qrCodeBase64 }}" style="width: 130px; height: 130px; border: 1px solid #ddd; padding: 5px;">
                @endif
                <div style="font-size: 10pt; margin-top: 10px; font-weight: bold;">كود التحقق الرقمي الموحد</div>
            </div>
        </div>

        <div style="margin-top: 60px;">
            <table style="width: 100%; border-top: 2px solid #000; padding-top: 20px;">
                <tr>
                    <td class="text-center" style="border: none;">
                        <div style="font-size: 11pt; color: #555;">رقم الطلب المرجعي</div>
                        <div class="font-bold" style="font-size: 13pt;">{{ $projectRequest->request_number }}</div>
                    </td>
                    <td class="text-center" style="border: none;">
                        <div style="font-size: 11pt; color: #555;">تاريخ الإصدار</div>
                        <div class="font-bold" style="font-size: 13pt;">{{ $printDate }}</div>
                    </td>
                    <td class="text-center" style="border: none;">
                        <div style="font-size: 11pt; color: #555;">التكلفة التقديرية</div>
                        <div class="font-bold" style="font-size: 13pt; color: var(--primary);">{{ number_format($totalProjectCost) }} ريال</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Main Content -->
    <div class="page-container">
        <h2 class="section-title">١. البيانات الأساسية والسياق العام</h2>
        <table class="modern-table">
            <tbody>
                <tr><td width="30%" class="font-bold">البرنامج الرئيسي</td><td>{{ optional($projectRequest->program)->name ?? '-' }}</td></tr>
                <tr><td class="font-bold">المجال / المجال الفرعي</td><td>{{ optional($projectRequest->domain)->name ?? '-' }} / {{ optional($projectRequest->subdomain)->name ?? '-' }}</td></tr>
                <tr><td class="font-bold">نوع التدخل</td><td>{{ optional($projectRequest->intervention)->name ?? '-' }}</td></tr>
                <tr><td class="font-bold">الأولوية</td><td>{{ optional($projectRequest->priority)->priority ?? '-' }}</td></tr>
                <tr><td class="font-bold">عدد المستفيدين المقدر</td><td>{{ number_format($projectRequest->number_of_beneficiaries) }}</td></tr>
                <tr><td class="font-bold">التكلفة التقديرية الكلية</td><td class="font-bold" style="color: var(--primary);">{{ number_format($totalProjectCost) }} ريال يمني</td></tr>
                <tr><td class="font-bold">الحالة الحالية للطلب</td><td>{{ $projectRequest->status_label }}</td></tr>
                <tr><td class="font-bold">مقدم الطلب</td><td>{{ optional($projectRequest->createdBy)->name }} ({{ $projectRequest->creator_entity_name }})</td></tr>
            </tbody>
        </table>

        <h2 class="section-title">٢. مبررات المشروع وأهدافه</h2>
        @php $data = $projectRequest->project_data ?? []; @endphp
        
        <div class="card">
            <div class="card-head">ملخص المشروع</div>
            <div style="padding: 10px;">{{ $data['project_summary'] ?? '-' }}</div>
        </div>

        <div class="card">
            <div class="card-head">المشكلة والمبررات</div>
            <div style="padding: 10px;">{{ $data['problem_and_justification'] ?? '-' }}</div>
        </div>

        <h3 style="font-size: 11pt; margin-bottom: 5px;">الأهداف العامة:</h3>
        <table class="modern-table">
            <thead><tr><th width="40">م</th><th>الهدف العام</th></tr></thead>
            <tbody>
                @forelse($projectRequest->mainObjectives as $idx => $obj)
                    <tr><td class="text-center">{{ $idx + 1 }}</td><td>{{ $obj->objective }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-center">لم يتم إضافة أهداف عامة</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2 class="section-title">٣. النطاق الجغرافي</h2>
        <table class="modern-table">
            <thead>
                <tr>
                    <th width="40">م</th>
                    <th>المحافظة</th>
                    <th>المديرية</th>
                    <th>العزلة / القرية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projectRequest->locations as $idx => $loc)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ optional($loc->governorate)->name }}</td>
                        <td>{{ optional($loc->directorate)->name }}</td>
                        <td>{{ optional($loc->subArea)->name }} / {{ optional($loc->village)->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">لم يتم تحديد مواقع</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2 class="section-title">٤. المخاطر والافتراضات</h2>
        <table class="modern-table">
            <thead>
                <tr>
                    <th width="40">م</th>
                    <th>وصف الخطر</th>
                    <th width="100">المستوى</th>
                    <th>إجراءات التخفيف</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projectRequest->risks as $idx => $risk)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $risk->risk }}</td>
                        <td class="text-center">{{ $risk->risk_rate }}/10</td>
                        <td>{{ $risk->proposed_solution }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">لا يوجد مخاطر مسجلة</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2 class="section-title">٥. الجهات ذات العلاقة</h2>
        <table class="modern-table">
            <thead>
                <tr>
                    <th width="150">الدور</th>
                    <th>اسم الجهة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($projectRequest->implementingEntities as $entity)
                    <tr><td>جهة منفذة</td><td>{{ optional($entity->authority)->name }}</td></tr>
                @endforeach
                @foreach($projectRequest->supervisingAuthorities as $entity)
                    <tr><td>جهة مشرفة</td><td>{{ optional($entity->authority)->name }}</td></tr>
                @endforeach
                @foreach($projectRequest->financings as $fin)
                    <tr><td>جهة ممولة</td><td>{{ optional($fin->authority)->name }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <h2 class="section-title">٦. ميزانية الأنشطة (ملخص)</h2>
        <table class="modern-table">
            <thead>
                <tr>
                    <th>البند</th>
                    <th width="150">التكلفة (ريال)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>إجمالي الأنشطة التمهيدية</td><td class="text-center">{{ number_format($preliminaryTotal) }}</td></tr>
                <tr><td>إجمالي الأنشطة التنفيذية</td><td class="text-center">{{ number_format($executiveTotal) }}</td></tr>
            </tbody>
            <tfoot>
                <tr><td class="font-bold">الإجمالي الكلي</td><td class="text-center font-bold" style="color: var(--danger);">{{ number_format($totalProjectCost) }}</td></tr>
            </tfoot>
        </table>

        <!-- Signatures Area -->
        <div style="margin-top: 50px; page-break-inside: avoid;">
            <table style="border: none;">
                <tr>
                    <td width="33%" class="text-center" style="border: none;">
                        <p class="font-bold">معد الطلب</p>
                        <br><br>
                        ................................
                    </td>
                    <td width="33%" class="text-center" style="border: none;">
                        <p class="font-bold">المراجعة والتدقيق</p>
                        <br><br>
                        ................................
                    </td>
                    <td width="33%" class="text-center" style="border: none;">
                        <p class="font-bold">الاعتماد النهائي</p>
                        <br><br>
                        ................................
                    </td>
                </tr>
            </table>
        <x-letterhead-footer documentName="وثيقة طلب مشروع مقترح" />
    </div></div></body>
</html>

