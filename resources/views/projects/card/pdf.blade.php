<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بطاقة المشروع - {{ $project->project_name }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: 'dejavusans', 'xbriyaz', sans-serif;
            margin: 0;
            padding: 0;
            background: #e5e5e5;
            -webkit-print-color-adjust: exact !important;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 10mm auto;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            page-break-after: always;
        }

        .internal-padding {
            padding: 40px;
        }

        /* ========== رأس الصفحة ========== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #2c5f2d;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .header-qr-box {
            width: 70px;
            height: 70px;
            padding: 8px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .header-qr-box img {
            width: 100%;
            height: auto;
        }

        /* ========== عناوين الأقسام ========== */
        .section-header {
            margin-top: 40px;
            margin-bottom: 20px;
            border-right: 5px solid #2c5f2d;
            padding-right: 15px;
        }

        .section-header h2 {
            margin: 0;
            font-size: 22px;
            color: #1f2937;
        }

        .section-header h3 {
            margin: 0;
            font-size: 18px;
            color: #1f2937;
        }

        /* ========== البطاقات الملخصة ========== */
        .summary-card {
            background-color: #f7fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        /* شبكة البيانات (عمودين) */
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .data-item {
            border-bottom: 1px solid #edf2f7;
            padding: 8px 0;
        }

        .data-label {
            font-size: 13px;
            color: #6b7280;
            display: block;
        }

        .data-value {
            font-size: 15px;
            color: #1f2937;
            font-weight: 600;
        }

        /* ========== الجداول الحديثة ========== */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .modern-table th {
            background: #f1f5f1;
            color: #2c5f2d;
            padding: 12px;
            text-align: right;
            border: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .modern-table td {
            padding: 12px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            color: #1f2937;
        }

        /* ========== تنسيقات الأهداف والنتائج (الهرمية) ========== */
        .hierarchy-label {
            font-weight: 700;
            color: #2c5f2d;
            margin-bottom: 4px;
            display: block;
        }

        /* ========== شارات الحالة ========== */
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-final {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .status-draft {
            background-color: #feebc8;
            color: #744210;
        }

        /* ========== الطباعة ========== */
        @media print {
            body {
                background: white;
            }

            .page {
                margin: 0;
                box-shadow: none;
                min-height: 297mm;
            }

            .no-print {
                display: none;
            }
        }

        /* ========== تنسيقات إضافية للجداول الكبيرة ========== */
        .table-wrapper {
            overflow-x: auto;
        }
    </style>
</head>

<body>

    <div class="page internal-padding">
        @php
            $cardPdfLetterhead = $letterhead ?? null;
            $cardLogoSrc = $cardPdfLetterhead ? $cardPdfLetterhead->getLogoBase64() : null;
            if (empty($cardLogoSrc)) {
                $defaultLogo = public_path('images/logo.png');
                $cardLogoSrc = file_exists($defaultLogo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($defaultLogo)) : null;
            }
            $cardPrimaryColor = $cardPdfLetterhead ? ($cardPdfLetterhead->getTypographySettings()['primary_color'] ?? '#2c5f2d') : '#2c5f2d';
        @endphp
        <!-- رأس الصفحة مع QR واسم المشروع والترويسة الرسمية -->
        <div class="page-header" style="border-bottom-color: {{ $cardPrimaryColor }};">
            <div style="display: flex; align-items: center; gap: 15px;">
                @if($cardLogoSrc)
                    <img src="{{ $cardLogoSrc }}" alt="Logo" style="height: 60px; width: auto; object-fit: contain;">
                @endif
                <div>
                    <div style="font-size: 13px; color: #4b5563; font-weight: bold; margin-bottom: 2px;">
                        {{ $cardPdfLetterhead ? $cardPdfLetterhead->organization_name : 'الجمهورية اليمنية - وزارة الزراعة والثروة السمكية والموارد المائية' }}
                    </div>
                    <div style="font-weight: 800; font-size: 18px; color: {{ $cardPrimaryColor }};">{{ $project->project_name }}</div>
                    <div style="font-size: 12px; color: #666;">كود المشروع الموحد: <span dir="ltr">{{ $project->form_number }}</span></div>
                </div>
            </div>
            <div class="header-qr-box">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode(route('projects.show', $project->id)) }}"
                    alt="QR Code">
            </div>
        </div>

        <!-- ========== البيانات الأساسية وجدول التنفيذ ========== -->
        <div class="section-header">
            <h2>أولاً: البيانات الأساسية وجدول التنفيذ</h2>
        </div>
        <div class="summary-card">
            <div class="data-grid">
                <div class="data-item">
                    <span class="data-label">البرنامج</span>
                    <span class="data-value">{{ $project->program->name ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <span class="data-label">المجال</span>
                    <span class="data-value">{{ $project->domain->name ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <span class="data-label">المجال الفرعي</span>
                    <span class="data-value">{{ $project->subdomain->name ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <span class="data-label">تاريخ البداية (ميلادي)</span>
                    <span
                        class="data-value">{{ $project->start_date_gregorian ? \Carbon\Carbon::parse($project->start_date_gregorian)->format('Y-m-d') : 'غير محدد' }}</span>
                </div>
                <div class="data-item">
                    <span class="data-label">تاريخ النهاية (ميلادي)</span>
                    <span
                        class="data-value">{{ $project->end_date_gregorian ? \Carbon\Carbon::parse($project->end_date_gregorian)->format('Y-m-d') : 'غير محدد' }}</span>
                </div>
                <div class="data-item">
                    <span class="data-label">المدة الزمنية (أيام)</span>
                    <span class="data-value">{{ $project->project_duration ?? 'غير محدد' }}</span>
                </div>
                <div class="data-item">
                    <span class="data-label">حالة المشروع</span>
                    <span class="status-badge {{ $project->status === 'final' ? 'status-final' : 'status-draft' }}">
                        {{ $project->status === 'final' ? 'معتمد' : 'مسودة' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ========== ملخص المشروع ========== -->
        <div class="section-header">
            <h2>ثانياً: ملخص المشروع</h2>
        </div>
        <div class="summary-card">
            <p style="font-size: 14px; line-height: 1.6; text-align: justify;">
                {{ $project->detail?->project_summary ?? 'لم يتم تحديد ملخص للمشروع' }}
            </p>
        </div>

        <!-- ========== المشاكل والتبريرات ========== -->
        <div class="section-header">
            <h2>ثالثاً: المشاكل والتبريرات</h2>
        </div>
        <div class="summary-card">
            <p style="font-size: 14px; line-height: 1.6; text-align: justify;">
                {{ $project->detail?->problem_and_justification ?? 'لم يتم تحديد مشاكل أو تبريرات' }}
            </p>
        </div>

        <!-- ========== المواقع الجغرافية ========== -->
        @if($project->locations && $project->locations->count() > 0)
            <div class="section-header">
                <h2>رابعاً: النطاق الجغرافي للمشروع</h2>
            </div>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th width="40">م</th>
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
                            <td class="location-highlight">{{ $loc->governorate->name ?? 'جميع المحافظات' }}</td>
                            <td>{{ $loc->directorate->name ?? 'جميع المديريات' }}</td>
                            <td>{{ $loc->subArea->name ?? $loc->area ?? 'جميع العزل' }}</td>
                            <td>{{ $loc->village->name ?? 'جميع القرى' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- ========== جهات التنفيذ ========== -->
        @if($project->implementingEntities && $project->implementingEntities->count() > 0)
            <div class="section-header">
                <h2>خامساً: جهات التنفيذ</h2>
            </div>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>نوع الجهة</th>
                        <th>اسم الجهة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->implementingEntities as $entity)
                        <tr>
                            <td>{{ ($entity->authority_type ?? $entity->entity_type) === 'internal' ? 'داخلية' : 'خارجية' }}
                            </td>
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

        <!-- ========== الجهات المشاركة ========== -->
        @if($project->participatingEntities && $project->participatingEntities->count() > 0)
            <div class="section-header">
                <h2>سادساً: الجهات المشاركة</h2>
            </div>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>نوع الجهة</th>
                        <th>اسم الجهة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->participatingEntities as $entity)
                        <tr>
                            <td>{{ ($entity->authority_type ?? $entity->entity_type) === 'internal' ? 'داخلية' : 'خارجية' }}
                            </td>
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

        <!-- ========== الجهات الإشرافية ========== -->
        @if($project->supervisingAuthorities && $project->supervisingAuthorities->count() > 0)
            <div class="section-header">
                <h2>الجهات الإشرافية</h2>
            </div>
            <table class="modern-table">
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

        <!-- ========== الجهات المستفيدة ========== -->
        @if($project->beneficiaryEntities && $project->beneficiaryEntities->count() > 0)
            <div class="section-header">
                <h2>الجهات المستفيدة</h2>
            </div>
            <table class="modern-table">
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

        <!-- ========== موازنة ومصادر التمويل ========== -->
        @if(($project->cost && $project->cost->total_cost) || ($project->financings && $project->financings->count() > 0))
            <div class="section-header">
                <h2>موازنة ومصادر التمويل</h2>
            </div>
            @if($project->cost)
                <div style="padding: 10px 15px; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 15px; font-weight: bold;">
                    إجمالي تكلفة المشروع: {{ number_format($project->cost->total_cost ?? 0, 2) }} {{ $project->cost->currency ?? 'ريال' }}
                </div>
            @endif
            @if($project->financings && $project->financings->count() > 0)
                <table class="modern-table">
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

        <!-- ========== المخاطر ========== -->
        @if($project->risks && $project->risks->count() > 0)
            <div class="section-header">
                <h2>سابعاً: المخاطر والافتراضات</h2>
            </div>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th width="50">م</th>
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

        <!-- ========== الأهداف الرئيسية ========== -->
        @if($project->mainObjectives && $project->mainObjectives->count() > 0)
            <div class="section-header">
                <h2>ثامناً: الأهداف العامة</h2>
            </div>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>الهدف العام</th>
                        <th>المؤشر</th>
                        <th>وحدة القياس</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->mainObjectives as $objective)
                        <tr>
                            <td>{{ $objective->objective ?? 'غير محدد' }}</td>
                            <td>{{ $objective->indicator ?? 'غير محدد' }}</td>
                            <td>{{ $objective->indicator_unit ?? 'غير محدد' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- ========== الأهداف الخاصة مع النتائج والمخرجات (تصميم هرمي) ========== -->
        @if($project->specialObjectives && $project->specialObjectives->count() > 0)
            <div class="section-header">
                <h2>تاسعاً: الأهداف الخاصة والنتائج والمخرجات</h2>
            </div>

            @php
                $indicatorTypesMapping = [
                    'quantitative' => 'كمي',
                    'relative' => 'نسبي',
                    'qualitative' => 'كيفي',
                ];
            @endphp

            @foreach($project->specialObjectives as $objIndex => $objective)
                <div
                    style="margin-bottom: 30px; border: 2px solid #2c5f2d; border-radius: 8px; overflow: hidden; page-break-inside: avoid;">
                    <!-- رأس الهدف الخاص -->
                    <div style="background-color: #2c5f2d; color: white; padding: 15px 20px;">
                        <div style="margin-bottom: 12px;">
                            <span style="font-weight: 800; font-size: 18px;">
                                الهدف الخاص {{ $objIndex + 1 }}:
                            </span>
                            <span style="font-size: 18px; font-weight: bold;">
                                {{ $objective->objective }}
                            </span>
                        </div>
                        <div
                            style="display: flex; gap: 20px; font-size: 14px; background: rgba(255,255,255,0.15); padding: 8px 15px; border-radius: 4px;">
                            <span>الوزن: <b
                                    style="background: white; color: #2c5f2d; padding: 2px 8px; border-radius: 4px; margin-right: 5px;">{{ $objective->objective_weight ?? 0 }}%</b></span>
                            <span>القيمة المستهدفة: <b>{{ $objective->target_value ?? '-' }}</b></span>
                            <span>وحدة القياس: <b>{{ $objective->measurement_unit ?? '-' }}</b></span>
                        </div>
                    </div>

                    <!-- النتائج والمخرجات -->
                    @if($objective->results && $objective->results->count() > 0)
                        <table style="width:100%; border-collapse:collapse; font-size:14px;">
                            <thead>
                                <tr style="background-color:#f1f5f1; color:#2c5f2d; border-bottom:2px solid #e2e8f0;">
                                    <th style="padding:12px; text-align:right; width:35%;">النتيجة المتوقعة</th>
                                    <th style="padding:12px; text-align:right; width:35%;">المخرج</th>
                                    <th style="padding:12px; text-align:center; width:10%;">المستهدف</th>
                                    <th style="padding:12px; text-align:center; width:10%;">نوع المؤشر</th>
                                    <th style="padding:12px; text-align:center; width:10%;">وحدة القياس</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($objective->results as $resIndex => $result)
                                    @php $outputsCount = max($result->outputs->count(), 1); @endphp
                                    @if($result->outputs && $result->outputs->count() > 0)
                                        @foreach($result->outputs as $outIndex => $output)
                                            <tr style="border-bottom:1px solid #e2e8f0;">
                                                @if($outIndex == 0)
                                                    <td rowspan="{{ $outputsCount }}"
                                                        style="padding:12px; vertical-align:top; background:#f8fafc; font-weight:bold; border-left:1px solid #e2e8f0;">
                                                        <span style="color:#97bc62; font-size:18px;">▪</span>
                                                        النتيجة {{ $objIndex + 1 }}.{{ $resIndex + 1 }}
                                                        <div style="margin-top:6px; color:#1f2937;">{{ $result->result_name }}</div>
                                                    </td>
                                                @endif
                                                <td style="padding:10px; color:#4b5563;">
                                                    <span style="color:#94a3b8;">↳</span> {{ $output->output }}
                                                </td>
                                                <td style="padding:10px; text-align:center; font-weight:bold; color:#2c5f2d;">
                                                    {{ $output->target_value ?? '-' }}
                                                </td>
                                                <td style="padding:10px; text-align:center; color:#64748b;">
                                                    {{ $indicatorTypesMapping[$output->indicator_type] ?? $output->indicator_type ?? '-' }}
                                                </td>
                                                <td style="padding:10px; text-align:center; color:#64748b;">
                                                    {{ $output->indicator_unit ?? '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr style="border-bottom:1px solid #e2e8f0;">
                                            <td style="padding:12px; background:#f8fafc; font-weight:bold;">
                                                <span style="color:#97bc62;">▪</span> النتيجة
                                                {{ $objIndex + 1 }}.{{ $resIndex + 1 }}
                                                <div style="margin-top:6px;">{{ $result->result_name }}</div>
                                            </td>
                                            <td style="padding:10px; text-align:center; color:#94a3b8;">لا توجد مخرجات</td>
                                            <td style="padding:10px; text-align:center;">-</td>
                                            <td style="padding:10px; text-align:center;">-</td>
                                            <td style="padding:10px; text-align:center;">-</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div style="text-align:center; color:#64748b; padding:20px;">لا توجد نتائج مسجلة لهذا الهدف</div>
                    @endif
                </div>
            @endforeach
        @endif

        <!-- ========== الأنشطة التمهيدية مع الإجراءات والتكاليف ========== -->
        @if($project->preliminaryActivities && $project->preliminaryActivities->count() > 0)
            <div class="section-header">
                <h2>الأنشطة التمهيدية والإجراءات والتكاليف</h2>
            </div>

            @foreach($project->preliminaryActivities as $activity)
                <div
                    style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; page-break-inside: avoid;">
                    <div
                        style="background-color: #f1f5f1; padding: 10px 15px; font-weight: 700; border-bottom: 1px solid #e2e8f0;">
                        {{ $activity->name }}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead style="background-color: #fafafa;">
                            <tr style="text-align: right; color: #555;">
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0;">الإجراء التمهيدي</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0;">البند المالي</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0;">الوحدة</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0; text-align: center;">الكمية</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0; text-align: center;">السعر</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0; text-align: center;">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activity->procedures as $procedure)
                                @php $costCount = $procedure->costs->count(); @endphp
                                @if($costCount > 0)
                                    @foreach($procedure->costs as $index => $cost)
                                        <tr style="border-bottom: 1px dotted #eee;">
                                            @if($index === 0)
                                                <td style="padding: 8px;" rowspan="{{ $costCount }}">{{ $procedure->procedure ?? 'غير محدد' }}</td>
                                            @endif
                                            <td style="padding: 8px;">{{ $cost->financialItem?->name ?? '-' }}</td>
                                            <td style="padding: 8px;">{{ $cost->unit?->unit_name ?? $cost->unit ?? '-' }}</td>
                                            <td style="padding: 8px; text-align: center;">{{ $cost->quantity ?? 0 }}</td>
                                            <td style="padding: 8px; text-align: center;">{{ number_format($cost->amount ?? 0, 2) }}</td>
                                            <td style="padding: 8px; text-align: center; font-weight: 700; color: #2c5f2d;">{{ number_format($cost->total ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr style="border-bottom: 1px dotted #eee;">
                                        <td style="padding: 8px;">{{ $procedure->procedure ?? 'غير محدد' }}</td>
                                        <td colspan="5" style="padding: 8px; text-align: center; color: #999;">لا توجد تكاليف مسجلة</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endif

        <!-- ========== أنشطة التنفيذ مع الإجراءات والتكاليف ========== -->
        @if($project->executiveActivities && $project->executiveActivities->count() > 0)
            <div class="section-header">
                <h2>عاشراً: أنشطة التنفيذ والإجراءات والتكاليف</h2>
            </div>

            @foreach($project->executiveActivities as $activity)
                <div
                    style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; page-break-inside: avoid;">
                    <div
                        style="background-color: #f1f5f1; padding: 10px 15px; font-weight: 700; border-bottom: 1px solid #e2e8f0;">
                        {{ $activity->name }}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead style="background-color: #fafafa;">
                            <tr style="text-align: right; color: #555;">
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0;">الإجراء التنفيذي</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0;">المنفذين</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0;">البند المالي</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0;">الوحدة</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0; text-align: center;">الكمية</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0; text-align: center;">السعر</th>
                                <th style="padding: 8px; border-bottom: 2px solid #e2e8f0; text-align: center;">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activity->actions as $action)
                                @php $costCount = $action->costs->count(); @endphp
                                @if($costCount > 0)
                                    @foreach($action->costs as $index => $cost)
                                        <tr style="border-bottom: 1px dotted #eee;">
                                            @if($index === 0)
                                                <td style="padding: 8px;" rowspan="{{ $costCount }}">
                                                    {{ $action->action ?? 'غير محدد' }}
                                                    <div style="font-size: 10px; color: #666; margin-top: 4px;">
                                                        <strong>وسيلة التحقق:</strong> {{ $action->verification_means ?? '-' }} |
                                                        <strong>الفترة:</strong>
                                                        {{ $action->start_date ? \Carbon\Carbon::parse($action->start_date)->format('Y-m-d') : '-' }}
                                                        →
                                                        {{ $action->end_date ? \Carbon\Carbon::parse($action->end_date)->format('Y-m-d') : '-' }}
                                                    </div>
                                                </td>
                                                <td style="padding: 8px;" rowspan="{{ $costCount }}">
                                                    @foreach($action->assignedEntities as $entity)
                                                        <div style="font-weight: 600;">- {{ $entity->name ?? $entity->agency_name ?? '-' }}</div>
                                                    @endforeach
                                                </td>
                                            @endif
                                            <td style="padding: 8px;">{{ $cost->financialItem?->name ?? '-' }}</td>
                                            <td style="padding: 8px;">{{ $cost->unit?->unit_name ?? $cost->unit ?? '-' }}</td>
                                            <td style="padding: 8px; text-align: center;">{{ $cost->quantity ?? 0 }}</td>
                                            <td style="padding: 8px; text-align: center;">{{ number_format($cost->amount ?? 0, 2) }}</td>
                                            <td style="padding: 8px; text-align: center; font-weight: 700; color: #2c5f2d;">
                                                {{ number_format($cost->total ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr style="border-bottom: 1px dotted #eee;">
                                        <td style="padding: 8px;">{{ $action->action ?? 'غير محدد' }}</td>
                                        <td style="padding: 8px;">
                                            @foreach($action->assignedEntities as $entity)
                                                <span>{{ $entity->name ?? $entity->agency_name ?? '-' }}</span>
                                            @endforeach
                                        </td>
                                        <td colspan="5" style="padding: 8px; text-align: center; color: #999;">لا توجد تكاليف مسجلة</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot style="background-color: #f8fafc;">
                            <tr>
                                <td colspan="6" style="padding: 10px; text-align: left; font-weight: 800;">إجمالي النشاط
                                    التنفيذي:</td>
                                <td
                                    style="padding: 10px; text-align: center; font-weight: 800; color: #b91c1c; font-size: 14px;">
                                    {{ number_format($activity->actions->flatMap->costs->sum('total'), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endforeach
        @endif

        <!-- ========== تذييل الصفحة ========== -->
        <div
            style="margin-top: 60px; text-align: center; font-size: 11px; color: #aaa; border-top: 1px solid #eee; padding-top: 15px;">
            صادر عن النظام الإلكتروني للجنة الزراعية والسمكية العليا - {{ date('Y') }} | تاريخ الطباعة:
            {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>

</body>

</html>