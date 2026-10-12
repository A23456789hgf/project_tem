<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بطاقة المشروع | {{ $project->project_name ?? 'اعتماد رسمي' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    @php
        $cardLetterhead = $letterhead ?? null;
        $primaryColor = $cardLetterhead ? ($cardLetterhead->getTypographySettings()['primary_color'] ?? '#2c5f2d') : '#2c5f2d';
        $secondaryColor = $cardLetterhead ? ($cardLetterhead->getTypographySettings()['secondary_color'] ?? '#97bc62') : '#97bc62';
        $resolvedLogoUrl = $cardLetterhead ? $cardLetterhead->getLogoUrl() : asset('images/logo.png');
        $rightSettings = $cardLetterhead ? $cardLetterhead->getHeaderRightSettings() : [];
        $headerRightText = !empty($rightSettings['text']) ? $rightSettings['text'] : "الجمهورية اليمنية\nوزارة الزراعة والثروة السمكية والموارد المائية";
    @endphp
    @if($cardLetterhead)
        <x-letterhead-styles :letterhead="$cardLetterhead" />
    @endif
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --main-color: {{ $primaryColor }};
            --sec-color: {{ $secondaryColor }};
            --dark-text: #1f2937;
            --light-text: #6b7280;
        }

        @page {
            size: A4;
            margin: 0;
        }

        html {
            direction: rtl;
        }

        body {
            font-family: 'Tajawal', 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #e5e7eb;
            margin: 0;
            padding: 20px 0;
            direction: rtl;
            text-align: right;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 10mm auto;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            page-break-after: always;
            page-break-inside: avoid;
            direction: rtl;
            overflow: hidden;
        }

        .internal-padding {
            padding: 40px 40px 60px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            direction: rtl;
        }

        /* ========== صفحة الغلاف ========== */
        .cover-header-bg {
            background-color: var(--main-color);
            height: 55%;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
            z-index: 0;
        }

        .cover-content {
            position: relative;
            z-index: 1;
            padding: 60px 40px;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
            direction: rtl;
        }

        .gov-title {
            color: white;
            font-size: 20px;
            opacity: 0.95;
            margin-bottom: 8px;
            font-weight: 500;
            direction: rtl;
        }

        .logo-container {
            margin-top: 50px;
            margin-bottom: 35px;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .logo-wrapper {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 350px;
            height: 350px;
            background-color: white;
            border-radius: 50%;
            padding: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            border: 2px solid rgba(255, 255, 255, 0.4);
            position: relative;
            overflow: hidden;
        }

        .logo-wrapper::before {
            content: '';
            position: absolute;
            top: -10%;
            left: -10%;
            right: -10%;
            bottom: -10%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.5) 80%);
            border-radius: 50%;
            z-index: 1;
        }

        .logo-wrapper img {
            width: 280px;
            height: auto;
            display: block;
            position: relative;
            z-index: 2;
        }

        .doc-badge {
            background-color: var(--sec-color);
            color: var(--main-color);
            padding: 8px 35px;
            border-radius: 40px;
            font-weight: 800;
            font-size: 16px;
            margin-bottom: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .project-details-bottom {
            margin-top: auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 20px;
        }

        .project-name-main {
            font-size: 28px;
            color: var(--dark-text);
            font-weight: 800;
            margin: 0 0 25px 0;
            line-height: 1.4;
            max-width: 90%;
            text-align: center;
            direction: rtl;
        }

        .applicant-info-card {
            background: #fff;
            border-right: 12px solid var(--main-color);
            border-left: none;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.05);
            padding: 20px 35px;
            border-radius: 12px;
            width: 85%;
            text-align: center;
            border: 1px solid #eef2e6;
        }

        .app-label {
            font-size: 14px;
            color: var(--light-text);
            display: block;
            margin-bottom: 5px;
        }

        .app-value {
            display: block;
            text-align: center;
            font-size: 18px;
            color: var(--main-color);
            font-weight: bold;
        }

        .cover-footer {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 20px 0 10px;
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
        }

        /* ===== الأنماط الداخلية ===== */
        .internal-title {
            border-bottom: 3px solid var(--main-color);
            padding-bottom: 12px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            direction: rtl;
        }

        .internal-title h3 {
            font-size: 20px;
            color: var(--main-color);
            font-weight: 800;
            margin: 0;
            text-align: right;
        }

        .internal-title .project-code-light {
            font-size: 13px;
            color: #8ba888;
            text-align: left;
        }

        /* ===== توحيد تنسيق جميع الجداول ===== */
        .unified-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12.5px;
            background: #fff;
            border: 1px solid #b8c5b8;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            direction: rtl;
        }

        .unified-table th {
            background: linear-gradient(180deg, #2c5f2d 0%, #3a7a3b 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 12.5px;
            padding: 9px 10px;
            text-align: center;
            border: 1px solid #245025;
            letter-spacing: 0.2px;
        }

        .unified-table td {
            border: 1px solid #d1d9d1;
            padding: 7px 10px;
            text-align: right;
            vertical-align: middle;
            font-size: 12.5px;
            color: #2d3748;
            line-height: 1.5;
        }

        .unified-table tbody tr:nth-child(even) {
            background-color: #f7faf5;
        }

        .section-header {
            margin-top: 18px;
            margin-bottom: 14px;
            border-right: 5px solid var(--main-color);
            border-left: none;
            padding-right: 15px;
            padding-left: 0;
            direction: rtl;
        }

        .section-header h2 {
            font-size: 20px;
            color: #1f2937;
            text-align: right;
        }

        /* ===== البيانات الأساسية في صف واحد ===== */
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 12px;
            margin-bottom: 15px;
            background-color: #f9fbf8;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            direction: rtl;
            text-align: right;
        }

        .data-item {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #edf2f7;
            padding: 10px 5px;
            min-height: 38px;
            direction: rtl;
        }

        .data-item:last-child {
            border-bottom: none;
        }

        .data-label {
            font-size: 13px;
            color: #5b6e8c;
            font-weight: 700;
            min-width: 160px;
            flex-shrink: 0;
            text-align: right;
        }

        .data-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e2a3e;
            text-align: left;
            flex: 1;
        }

        .summary-card {
            background-color: #f9fbf8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
            direction: rtl;
        }

        .summary-card h4,
        .summary-card h6,
        .summary-card p {
            margin: 0;
            padding: 0;
            line-height: 1.7;
            text-align: right;
            direction: rtl;
        }

        .summary-card h4 {
            color: var(--main-color);
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 8px;
        }

        .summary-card p {
            color: #2d3748;
            font-size: 13.5px;
            text-align: justify;
            text-align-last: right;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            display: inline-block;
        }

        .status-final {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .status-draft {
            background-color: #feebc8;
            color: #744210;
        }

        .hierarchy-label {
            font-weight: 800;
            color: var(--main-color);
            margin-bottom: 8px;
            font-size: 16px;
            text-align: right;
        }

        .page-number-footer {
            position: absolute;
            bottom: 15px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 12px;
            color: #5b6e8c;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-weight: 500;
            z-index: 10;
        }

        .compact-table {
            font-size: 11.5px;
        }

        .compact-table th,
        .compact-table td {
            padding: 6px 7px;
            font-size: 11.5px;
        }

        .detail-block {
            background-color: #f9fbf8;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
            border-right: 4px solid var(--main-color);
            border-left: none;
            direction: rtl;
        }

        .detail-block h6 {
            font-size: 14px;
            color: var(--main-color);
            font-weight: 700;
            margin-bottom: 6px;
            text-align: right;
        }

        .detail-block p {
            font-size: 13.5px;
            color: #2d3748;
            line-height: 1.8;
            text-align: justify;
            text-align-last: right;
            margin: 0;
            direction: rtl;
        }

        .basic-data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 10px;
            direction: rtl;
        }

        .basic-data-item {
            padding: 4px 6px;
            display: flex;
            flex-direction: column;
            text-align: right;
            line-height: 1.4;
        }

        .basic-data-label {
            font-size: 14px;
            color: #666;
        }

        .basic-data-value {
            font-size: 14px;
            font-weight: 500;
            color: #222;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .page {
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }

            .cover-header-bg {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .unified-table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    @if($cardLetterhead)
        <div class="no-print" style="max-width: 210mm; margin: 15px auto 5px; padding: 0 10px;">
            <x-letterhead-selector :currentLetterhead="$cardLetterhead" />
        </div>
    @endif

    <!-- ========== الصفحة 1: الغلاف ========== -->
    <div class="page">
        <div class="cover-header-bg"></div>
        <div class="cover-content">
            @foreach(explode("\n", trim($headerRightText)) as $titleLine)
                @if(trim($titleLine))
                    <div class="gov-title">{{ trim($titleLine) }}</div>
                @endif
            @endforeach
            <div class="logo-container">
                <div class="logo-wrapper">
                    <img src="{{ $resolvedLogoUrl }}" alt="الشعار الرسمي">
                </div>
            </div>
            <span class="doc-badge">بطاقة المشروع</span>
            <div class="project-details-bottom">
                <div class="project-name-main">{{ $project->project_name ?? '' }}</div>
                <div class="applicant-info-card">
                    <span class="app-label">الجهة المقدمة للمشروع</span>
                    <span class="app-value">{{ $project->creator_entity_name ?? '' }}</span>
                </div>
                <div class="cover-footer">
                    <div style="text-align: right;">
                        <div style="font-size: 14px; color: #4a5b6e; font-weight: bold;">الرقم المرجعي</div>
                        <div style="font-weight: 800; font-size: 28px; color: var(--main-color);">
                            {{ $project->form_number ?? '' }}
                        </div>
                    </div>
                    <div class="qr-container"
                        style="padding: 8px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        @if(isset($qrCodeBase64))
                            <img src="{{ $qrCodeBase64 }}" alt="QR Code" style="width:95px; height:95px; display:block;">
                        @else
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode(route('projects.show', $project->id)) }}" alt="QR Code" style="width:95px; height:95px; display:block;">
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== الصفحة 2: البيانات الأساسية ========== -->
    <div class="page internal-padding">
        <div class="internal-title">
            <h3>بيانات المشروع الأساسية</h3>
            <span class="project-code-light">{{ $project->form_number ?? '' }}</span>
        </div>

        <div class="section-header">
            <h2>أولاً: البيانات الأساسية</h2>
        </div>

        <div style="background:#f9fbf8;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;margin-bottom:20px;">
            <div class="basic-data-grid">
                <div class="basic-data-item">
                    <span class="basic-data-label">البرنامج</span>
                    <div class="basic-data-value">{{ $project->program->name ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">المجال</span>
                    <div class="basic-data-value">{{ $project->domain->name ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">المجال الفرعي</span>
                    <div class="basic-data-value">{{ $project->subdomain->name ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">نوع التدخل</span>
                    <div class="basic-data-value">{{ $project->intervention->name ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">الأولوية</span>
                    <div class="basic-data-value">{{ $project->priority->name ?? $project->priority ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">المدة الزمنية (أيام)</span>
                    <div class="basic-data-value">{{ $project->project_duration ?? 'غير محدد' }}</div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">تاريخ البداية (ميلادي)</span>
                    <div class="basic-data-value">
                        {{ $project->start_date_gregorian ? \Carbon\Carbon::parse($project->start_date_gregorian)->format('Y-m-d') : 'غير محدد' }}
                    </div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">تاريخ النهاية (ميلادي)</span>
                    <div class="basic-data-value">
                        {{ $project->end_date_gregorian ? \Carbon\Carbon::parse($project->end_date_gregorian)->format('Y-m-d') : 'غير محدد' }}
                    </div>
                </div>
                <div class="basic-data-item">
                    <span class="basic-data-label">حالة المشروع</span>
                    <div class="basic-data-value">
                        <span class="status-badge {{ $project->status === 'final' ? 'status-final' : 'status-draft' }}">
                            {{ $project->status === 'final' ? 'معتمد' : 'مسودة' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2>ثانياً: ملخص المشروع</h2>
        </div>
        <div class="detail-block">
            <p>{{ $project->detail?->project_summary ?? 'لم يتم تحديد ملخص للمشروع' }}</p>
        </div>

        <div class="section-header">
            <h2>ثالثاً: المشاكل والتبريرات</h2>
        </div>
        <div class="detail-block">
            <p>{{ $project->detail?->problem_and_justification ?? 'لم يتم تحديد مشاكل أو تبريرات' }}</p>
        </div>
    </div>

    <!-- ========== الصفحة 3: النطاق الجغرافي وجهات التنفيذ والمخاطر ========== -->
    <div class="page internal-padding">
        <div class="internal-title">
            <h3>النطاق الجغرافي والجهات والمخاطر</h3>
            <span class="project-code-light">{{ $project->form_number ?? '' }}</span>
        </div>

        @if($project->locations && $project->locations->count() > 0)
            <div class="section-header">
                <h2>رابعاً: النطاق الجغرافي للمشروع</h2>
            </div>
            <table class="unified-table">
                <thead>
                    <tr>
                        <th style="width:40px;">م</th>
                        <th>المحافظة</th>
                        <th>المديرية</th>
                        <th>العزلة</th>
                        <th>القرية</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->locations as $index => $loc)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td>{{ $loc->governorate->name ?? 'جميع المحافظات' }}</td>
                            <td>{{ $loc->directorate->name ?? 'جميع المديريات' }}</td>
                            <td>{{ $loc->subArea->name ?? $loc->area ?? 'جميع العزل' }}</td>
                            <td>{{ $loc->village->name ?? 'جميع القرى' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($project->implementingEntities && $project->implementingEntities->count() > 0)
            <div class="section-header">
                <h2>خامساً: جهات التنفيذ</h2>
            </div>
            <table class="unified-table">
                <thead>
                    <tr>
                        <th>نوع الجهة</th>
                        <th>اسم الجهة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->implementingEntities as $entity)
                        <tr>
                            <td>{{ ($entity->authority_type ?? $entity->entity_type) === 'internal' ? 'داخلية' : 'خارجية' }}</td>
                            <td>
                                @if(($entity->authority_type ?? $entity->entity_type) === 'internal')
                                    {{ $entity->internal_entity_id ?? $entity->authority_id }}
                                @else
                                    {{ $entity->authority?->agency_name ?? 'غير محدد' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($project->participatingEntities && $project->participatingEntities->count() > 0)
            <div class="section-header">
                <h2>سادساً: الجهات المشاركة</h2>
            </div>
            <table class="unified-table">
                <thead>
                    <tr>
                        <th>نوع الجهة</th>
                        <th>اسم الجهة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->participatingEntities as $entity)
                        <tr>
                            <td>{{ ($entity->authority_type ?? $entity->entity_type) === 'internal' ? 'داخلية' : 'خارجية' }}</td>
                            <td>
                                @if(($entity->authority_type ?? $entity->entity_type) === 'internal')
                                    {{ optional($entity->internalEntity)->name ?? $entity->internal_entity_id }}
                                @else
                                    {{ $entity->authority?->agency_name ?? 'غير محدد' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($project->supervisingAuthorities && $project->supervisingAuthorities->count() > 0)
            <div class="section-header">
                <h2>الجهات الإشرافية</h2>
            </div>
            <table class="unified-table">
                <thead>
                    <tr>
                        <th>نوع الجهة</th>
                        <th>اسم الجهة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->supervisingAuthorities as $entity)
                        <tr>
                            <td>{{ ($entity->authority_type ?? $entity->entity_type) === 'internal' ? 'داخلية' : 'خارجية' }}</td>
                            <td>
                                @if(($entity->authority_type ?? $entity->entity_type) === 'internal')
                                    {{ optional($entity->internalEntity)->name ?? $entity->internal_entity_id }}
                                @else
                                    {{ $entity->authority?->agency_name ?? 'غير محدد' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($project->beneficiaryEntities && $project->beneficiaryEntities->count() > 0)
            <div class="section-header">
                <h2>الجهات المستفيدة</h2>
            </div>
            <table class="unified-table">
                <thead>
                    <tr>
                        <th>نوع الجهة</th>
                        <th>اسم الجهة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->beneficiaryEntities as $entity)
                        <tr>
                            <td>{{ ($entity->authority_type ?? $entity->entity_type) === 'internal' ? 'داخلية' : 'خارجية' }}</td>
                            <td>
                                @if(($entity->authority_type ?? $entity->entity_type) === 'internal')
                                    {{ optional($entity->internalEntity)->name ?? $entity->internal_entity_id }}
                                @else
                                    {{ $entity->authority?->agency_name ?? 'غير محدد' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(($project->cost && $project->cost->total_cost) || ($project->financings && $project->financings->count() > 0))
            <div class="section-header">
                <h2>موازنة ومصادر التمويل</h2>
            </div>
            @if($project->cost)
                <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 12px; font-weight: bold; border-radius: 6px;">
                    إجمالي تكلفة المشروع: {{ number_format($project->cost->total_cost ?? 0, 2) }} {{ $project->cost->currency ?? 'ريال' }}
                </div>
            @endif
            @if($project->financings && $project->financings->count() > 0)
                <table class="unified-table">
                    <thead>
                        <tr>
                            <th>مصدر التمويل</th>
                            <th>جهة التمويل</th>
                            <th>نوع التمويل</th>
                            <th>مبلغ التمويل</th>
                            <th>النسبة %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->financings as $fin)
                            <tr>
                                <td>{{ optional($fin->fundingSource)->name ?? '-' }}</td>
                                <td>{{ optional($fin->authority)->agency_name ?? '-' }}</td>
                                <td>{{ optional($fin->financingType)->name ?? '-' }}</td>
                                <td>{{ number_format($fin->financing_amount ?? 0, 2) }}</td>
                                <td>{{ $fin->financing_percentage ? number_format($fin->financing_percentage, 1).'%' : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif

        @if($project->risks && $project->risks->count() > 0)
            <div class="section-header">
                <h2>سابعاً: المخاطر والافتراضات</h2>
            </div>
            <table class="unified-table">
                <thead>
                    <tr>
                        <th style="width:40px;">م</th>
                        <th>نوع الخطر</th>
                        <th>مستوى الخطر</th>
                        <th>إجراءات الحد من المخاطر</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->risks as $index => $risk)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td>{{ $risk->risk }}</td>
                            <td>{{ $risk->risk_rate ?? 'عام' }}</td>
                            <td>{{ $risk->proposed_solution }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- ========== الصفحة 4: الأهداف ========== -->
    <div class="page internal-padding">
        <div class="internal-title">
            <h3>الأهداف والمؤشرات</h3>
            <span class="project-code-light">{{ $project->form_number ?? '' }}</span>
        </div>
        <div class="section-header">
            <h2>ثامناً: الأهداف العامة</h2>
        </div>
        @if(($project->mainObjectives ?? [])->count())
            <div style="background:#fef9e6; padding:16px; border-right:5px solid #2c5f2d; margin-bottom:24px; border-radius:6px; direction:rtl;">
                <span class="hierarchy-label">الهدف العام:</span>
                @foreach($project->mainObjectives as $obj)
                    <div style="font-weight:700; margin-top:6px; text-align:right;">- {{ $obj->objective }}</div>
                @endforeach
            </div>
        @endif

        @if($project->specialObjectives && $project->specialObjectives->count() > 0)
            <div class="section-header">
                <h2>تاسعاً: الأهداف الخاصة والنتائج والمخرجات</h2>
            </div>
            @forelse($project->specialObjectives ?? [] as $i => $goal)
                @php
                    $allResults = $goal->results ?? collect();
                    $goalRowSpan = 0;

                    foreach ($allResults as $r) {
                        $goalRowSpan += max(($r->outputs ?? collect())->count(), 1);
                    }

                    if ($goalRowSpan === 0)
                        $goalRowSpan = 1;
                @endphp

                <div style="margin-bottom:18px; border:1px solid #b8c5b8; border-radius:6px; page-break-inside:avoid; font-family:Tahoma, Arial;">
                    {{-- الهدف --}}
                    <div style="background:linear-gradient(180deg,#2c5f2d,#3a7a3b); color:#fff; padding:8px 12px; font-size:12px; line-height:1.5; text-align:center;">
                        <strong>الهدف الخاص {{ $i + 1 }}:</strong>
                        {{ $goal->objective }}

                        <div style="font-size:11px; margin-top:4px; opacity:0.95; text-align:center;">
                            الوزن: {{ $goal->objective_weight ?? '-' }}%
                            | قيمة المؤشر: {{ $goal->indicator_type ?? $goal->indicator ?? '-' }}
                            | القيمة المستهدفة: {{ $goal->target_value ?? '-' }}
                            | وحدة القياس: {{ $goal->measurement_unit ?? '-' }}
                        </div>
                    </div>

                    {{-- الجدول --}}
                    <table style="width:100%; border-collapse:collapse; font-size:11px; table-layout:fixed; text-align:center;">
                        <thead>
                            <tr style="background:#f2f2f2;">
                                <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">النتيجة</th>
                                <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">القيمة المستهدفة</th>
                                <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">نوع المؤشر</th>
                                <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">وحدة المؤشر</th>
                                <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">المخرج</th>
                                <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">القيمة المستهدفة</th>
                                <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">نوع المؤشر</th>
                                <th style="border:1px solid #ccc; padding:6px; text-align:center; vertical-align:middle;">وحدة المؤشر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allResults as $result)
                                @php
                                    $outputs = $result->outputs ?? collect();
                                    $resultRowSpan = max($outputs->count(), 1);
                                @endphp

                                @forelse($outputs as $oIndex => $output)
                                    <tr>
                                        @if($oIndex === 0)
                                            <td rowspan="{{ $resultRowSpan }}" style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $result->result_name }}</td>
                                            <td rowspan="{{ $resultRowSpan }}" style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $result->target_value ?? '-' }}</td>
                                            <td rowspan="{{ $resultRowSpan }}" style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $result->indicator_type ?? '-' }}</td>
                                            <td rowspan="{{ $resultRowSpan }}" style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $result->indicator_unit ?? '-' }}</td>
                                        @endif
                                        <td style="border:1px solid #ccc; padding:5px; text-align:right; vertical-align:middle;">{{ $output->output }}</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $output->target_value ?? '-' }}</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $output->indicator_type ?? '-' }}</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $output->indicator_unit ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $result->result_name }}</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $result->target_value ?? '-' }}</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $result->indicator_type ?? '-' }}</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">{{ $result->indicator_unit ?? '-' }}</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">-</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">-</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">-</td>
                                        <td style="border:1px solid #ccc; padding:5px; text-align:center; vertical-align:middle;">-</td>
                                    </tr>
                                @endforelse
                            @empty
                                <tr>
                                    <td colspan="8" style="border:1px solid #ccc; padding:5px; text-align:center;">لا توجد نتائج مسجلة</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @empty
            @endforelse
        @endif
    </div>

    <!-- الأنشطة التمهيدية -->
    @if($project->preliminaryActivities && $project->preliminaryActivities->count() > 0)
        <div class="page internal-padding">
            <div class="internal-title">
                <h3>الأنشطة التمهيدية والإجراءات والتكاليف</h3>
                <span class="project-code-light">{{ $project->form_number ?? '' }}</span>
            </div>

            <div class="section-header">
                <h2>الأنشطة التمهيدية والإجراءات والتكاليف</h2>
            </div>

            @foreach($project->preliminaryActivities as $activity)
                <div style="margin-bottom: 20px; border: 1px solid #b8c5b8; border-radius: 6px; overflow: hidden; page-break-inside: avoid;">
                    <div style="background-color: #f1f5f1; padding: 10px 15px; font-weight: 700; border-bottom: 1px solid #b8c5b8; color: var(--main-color);">
                        {{ $activity->name }}
                    </div>
                    <table class="unified-table" style="font-size: 11px;">
                        <thead>
                            <tr style="background-color: #fafafa;">
                                <th>الإجراء التمهيدي</th>
                                <th>البند المالي</th>
                                <th>الوحدة</th>
                                <th style="text-align: center;">الكمية</th>
                                <th style="text-align: center;">السعر</th>
                                <th style="text-align: center;">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activity->procedures as $procedure)
                                @php $costCount = $procedure->costs->count(); @endphp
                                @if($costCount > 0)
                                    @foreach($procedure->costs as $index => $cost)
                                        <tr>
                                            @if($index === 0)
                                                <td rowspan="{{ $costCount }}">{{ $procedure->procedure ?? 'غير محدد' }}</td>
                                            @endif
                                            <td>{{ $cost->financialItem?->name ?? '-' }}</td>
                                            <td>{{ $cost->unit?->unit_name ?? $cost->unit ?? '-' }}</td>
                                            <td style="text-align: center;">{{ $cost->quantity ?? 0 }}</td>
                                            <td style="text-align: center;">{{ number_format($cost->amount ?? 0, 2) }}</td>
                                            <td style="text-align: center; font-weight: 700;">{{ number_format($cost->total ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>{{ $procedure->procedure ?? 'غير محدد' }}</td>
                                        <td colspan="5" style="text-align: center; color: #999;">لا توجد تكاليف مسجلة</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @endif

    <!-- ========== الصفحة 5: أنشطة التنفيذ ========== -->
    @if($project->executiveActivities && $project->executiveActivities->count() > 0)
        <div class="page internal-padding">
            <div class="internal-title">
                <h3>أنشطة التنفيذ والإجراءات والتكاليف</h3>
                <span class="project-code-light">{{ $project->form_number ?? '' }}</span>
            </div>

            <div class="section-header">
                <h2>عاشراً: أنشطة التنفيذ والإجراءات والتكاليف</h2>
            </div>

            @foreach($project->executiveActivities as $activity)
                <div style="margin-bottom: 20px; border: 1px solid #b8c5b8; border-radius: 6px; overflow: hidden; page-break-inside: avoid;">
                    <div style="background-color: #f1f5f1; padding: 10px 15px; font-weight: 700; border-bottom: 1px solid #b8c5b8; color: var(--main-color);">
                        {{ $activity->name }}
                    </div>
                    <table class="unified-table" style="margin-bottom: 0; border: none; border-radius: 0;">
                        <thead>
                            <tr>
                                <th>الإجراء التنفيذي</th>
                                <th>المنفذين</th>
                                <th>البند المالي</th>
                                <th>الوحدة</th>
                                <th style="text-align: center;">الكمية</th>
                                <th style="text-align: center;">السعر</th>
                                <th style="text-align: center;">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activity->actions as $action)
                                @php $costCount = $action->costs->count(); @endphp
                                @if($costCount > 0)
                                    @foreach($action->costs as $index => $cost)
                                        <tr>
                                            @if($index === 0)
                                                <td rowspan="{{ $costCount }}">
                                                    {{ $action->action ?? 'غير محدد' }}
                                                    <div style="font-size: 10.5px; color: #666; margin-top: 6px;">
                                                        <strong>وسيلة التحقق:</strong> {{ $action->verification_means ?? '-' }}<br>
                                                        <strong>الفترة:</strong>
                                                        {{ $action->start_date ? \Carbon\Carbon::parse($action->start_date)->format('Y-m-d') : '-' }}
                                                        →
                                                        {{ $action->end_date ? \Carbon\Carbon::parse($action->end_date)->format('Y-m-d') : '-' }}
                                                    </div>
                                                </td>
                                                <td rowspan="{{ $costCount }}">
                                                    @foreach($action->assignedEntities as $entity)
                                                        <div>- {{ $entity->name ?? $entity->agency_name ?? '-' }}</div>
                                                    @endforeach
                                                </td>
                                            @endif
                                            <td>{{ $cost->financialItem?->name ?? '-' }}</td>
                                            <td>{{ $cost->unit?->unit_name ?? $cost->unit ?? '-' }}</td>
                                            <td style="text-align: center;">{{ $cost->quantity ?? 0 }}</td>
                                            <td style="text-align: center;">{{ number_format($cost->amount ?? 0, 2) }}</td>
                                            <td style="text-align: center; font-weight: 700; color: var(--main-color);">
                                                {{ number_format($cost->total ?? 0, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>
                                            {{ $action->action ?? 'غير محدد' }}
                                            <div style="font-size: 10.5px; color: #666; margin-top: 6px;">
                                                <strong>الفترة:</strong>
                                                {{ $action->start_date ? \Carbon\Carbon::parse($action->start_date)->format('Y-m-d') : '-' }}
                                                →
                                                {{ $action->end_date ? \Carbon\Carbon::parse($action->end_date)->format('Y-m-d') : '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            @foreach($action->assignedEntities as $entity)
                                                <div>- {{ $entity->name ?? $entity->agency_name ?? '-' }}</div>
                                            @endforeach
                                        </td>
                                        <td colspan="5" style="text-align: center; color: #999;">لا توجد تكاليف مسجلة</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot style="background-color: #f8fafc;">
                            <tr>
                                <td colspan="6" style="padding: 10px; text-align: left; font-weight: 800; border-top: 1px solid #b8c5b8;">إجمالي النشاط التنفيذي:</td>
                                <td style="padding: 10px; text-align: center; font-weight: 800; color: #b91c1c; font-size: 14px; border-top: 1px solid #b8c5b8;">
                                    {{ number_format($activity->actions->flatMap->costs->sum('total'), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endforeach
        </div>
    @endif

    <script>
        window.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                window.print();
            }, 500);
        });
    </script>
</body>

</html>
