<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة المشروع السابق | {{ $project->project_name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --main-color: #1e3a8a;
            --sec-color: #3b82f6;
            --dark-text: #1f2937;
            --light-text: #6b7280;
        }

        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: #e5e7eb;
            margin: 0;
            padding: 20px 0;
            direction: rtl;
            text-align: right;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color: #1f2937;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 10mm auto;
            padding: 20mm;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            page-break-after: always;
            page-break-inside: avoid;
        }

        /* Print Controls */
        .print-controls {
            position: fixed;
            top: 20px;
            left: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 230px;
        }

        .btn-control {
            background: var(--main-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
            display: block;
        }

        .btn-control:hover {
            background: #172e6e;
            color: white;
        }

        .btn-control.secondary {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-control.secondary:hover {
            background: #cbd5e1;
        }

        /* Header */
        .doc-header {
            border-bottom: 3px solid var(--main-color);
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 18px;
            font-weight: 800;
            color: var(--dark-text);
            line-height: 1.4;
        }

        .header-title h2 {
            font-size: 14px;
            font-weight: 600;
            color: var(--light-text);
        }

        .logo-img {
            max-height: 80px;
            max-width: 120px;
        }

        .badge-old {
            background-color: #f59e0b;
            color: white;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }

        .project-main-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--main-color);
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .meta-info {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 25px;
        }

        /* Section Titles */
        .section-header {
            background: #f1f5f9;
            border-right: 5px solid var(--main-color);
            padding: 10px 15px;
            margin: 25px 0 15px 0;
            border-radius: 4px;
        }

        .section-header h3 {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }

        /* Financial Box */
        .financial-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .fin-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            background: #fafafa;
        }

        .fin-box.total {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .fin-box.spent {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .fin-box.remaining {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .fin-label {
            font-size: 12.5px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 8px;
        }

        .fin-value {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }

        .fin-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            font-size: 13.5px;
            text-align: right;
            vertical-align: middle;
        }

        .data-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 700;
            width: 30%;
        }

        .data-table td {
            color: #0f172a;
            font-weight: 600;
        }

        /* Details text */
        .detail-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 13.5px;
            line-height: 1.8;
            white-space: pre-line;
            color: #334155;
        }

        .detail-title {
            font-weight: 800;
            color: var(--main-color);
            margin-bottom: 8px;
            font-size: 14px;
        }

        /* Footer */
        .doc-footer {
            margin-top: auto;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-title {
            font-size: 14px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 40px;
        }

        .signature-line {
            border-bottom: 1px dashed #94a3b8;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .page {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
            }

            .print-controls {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- لوحة تحكم الطباعة -->
    <div class="print-controls">
        <button onclick="window.print()" class="btn-control">🖨️ طباعة التقرير</button>
        <a href="{{ route('projects.show', $project->id) }}" class="btn-control secondary">↩ العودة لتفاصيل المشروع</a>
    </div>

    <!-- الوثيقة -->
    <div class="page">
        <!-- الترويسة الرسمية -->
        <div class="doc-header">
            <div class="header-title">
                <h1>الجمهورية اليمنية</h1>
                <h1>وزارة الزراعة والثروة السمكية والموارد المائية</h1>
                <h2>وثيقة بيانات مشروع سابق (قديم)</h2>
            </div>
            <div>
                @if(!empty($logoBase64))
                <img src="{{ $logoBase64 }}" alt="الشعار الرسمي" class="logo-img">
                @endif
            </div>
        </div>

        <!-- عنوان المشروع -->
        <div>
            <span class="badge-old">مشروع سابق (مكتمل / قديم)</span>
            <div class="project-main-title">{{ $project->project_name }}</div>
            <div class="meta-info">
                <span><strong>رقم النموذج:</strong> {{ $project->form_number ?? 'غير محدد' }}</span> | 
                <span><strong>تاريخ الطباعة:</strong> {{ $printDate }}</span>
            </div>
        </div>

        <!-- أولاً: ملخص التكاليف والأرصدة المالية -->
        <div class="section-header">
            <h3>أولاً: ملخص التكاليف والأرصدة المالية المعتمدة</h3>
        </div>

        @php
            $totalCost = $project->cost->total_cost ?? 0;
            $spentAmount = $project->cost->spent_amount ?? 0;
            $remainingAmount = $project->cost->remaining_amount ?? 0;
            $spentPerc = ($totalCost > 0 && $spentAmount > 0) ? min(100, round(($spentAmount / $totalCost) * 100, 1)) : 0;
        @endphp

        <div class="financial-summary">
            <div class="fin-box total">
                <div class="fin-label">إجمالي تكلفة المشروع</div>
                <div class="fin-value">{{ number_format($totalCost, 2) }} ريال</div>
                @if(!empty($project->cost->hijri_year))
                <div class="fin-sub">سنة التمويل: {{ $project->cost->hijri_year }} هـ</div>
                @endif
            </div>
            <div class="fin-box spent">
                <div class="fin-label">المبلغ المصروف الفعلي</div>
                <div class="fin-value">{{ number_format($spentAmount, 2) }} ريال</div>
                <div class="fin-sub">نسبة الصرف: {{ $spentPerc }}%</div>
            </div>
            <div class="fin-box remaining">
                <div class="fin-label">المبلغ المتبقي</div>
                <div class="fin-value">{{ number_format($remainingAmount, 2) }} ريال</div>
                <div class="fin-sub">{{ $remainingAmount >= 0 ? 'رصيد متاح' : 'تجاوز في الصرف' }}</div>
            </div>
        </div>

        <!-- ثانياً: البيانات التصنيفية والأساسية -->
        <div class="section-header">
            <h3>ثانياً: البيانات الأساسية والتصنيف</h3>
        </div>

        <table class="data-table">
            <tbody>
                <tr>
                    <th>البرنامج الرئيسي</th>
                    <td>{{ $project->program->name ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>المجال الرئيسي</th>
                    <td>{{ $project->domain->name ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>المجال الفرعي</th>
                    <td>{{ $project->subdomain->name ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>نوع التدخل</th>
                    <td>{{ $project->intervention->name ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>أولوية المشروع</th>
                    <td>{{ is_object($project->priority) ? ($project->priority->name ?? 'غير محدد') : ($project->priority ?? 'غير محدد') }}</td>
                </tr>
                <tr>
                    <th>هل المشروع جزء من خطة معتمدة؟</th>
                    <td>
                        @if(($project->detail->is_part_of_plan ?? null) === 1 || ($project->detail->is_part_of_plan ?? null) === true)
                            نعم، المشروع مدرج ضمن الخطة المعتمدة
                        @else
                            غير محدد / مشروع مستقل
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ثالثاً: الفترة الزمنية والمستفيدون -->
        <div class="section-header">
            <h3>ثالثاً: الفترة الزمنية والمستفيدون</h3>
        </div>

        <table class="data-table">
            <tbody>
                <tr>
                    <th>تاريخ بداية المشروع</th>
                    <td>
                        {{ $project->start_date_gregorian ? \Carbon\Carbon::parse($project->start_date_gregorian)->format('Y-m-d') . ' م' : 'غير محدد' }}
                        @if($project->start_date_hijri)
                        <small style="color:#64748b;"> (الموافق {{ $project->start_date_hijri }} هـ)</small>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>تاريخ انتهاء المشروع</th>
                    <td>
                        {{ $project->end_date_gregorian ? \Carbon\Carbon::parse($project->end_date_gregorian)->format('Y-m-d') . ' م' : 'غير محدد' }}
                        @if($project->end_date_hijri)
                        <small style="color:#64748b;"> (الموافق {{ $project->end_date_hijri }} هـ)</small>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>إجمالي عدد المستفيدين</th>
                    <td>{{ $project->number_of_beneficiaries ? number_format($project->number_of_beneficiaries) . ' مستفيد' : 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>الفئات المستهدفة</th>
                    <td>
                        {{ $project->targetCategory->name ?? 'غير محدد' }}
                        @if($project->main_directives)
                        <br><small style="color:#64748b;">التوجيهات: {{ $project->main_directives }}</small>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- رابعاً: المؤسسات والجهات ذات العلاقة -->
        <div class="section-header">
            <h3>رابعاً: المؤسسات والجهات ذات العلاقة</h3>
        </div>

        <table class="data-table">
            <tbody>
                <tr>
                    <th>الجهات المنفذة</th>
                    <td>
                        @if($project->implementingEntities->isNotEmpty())
                            {{ $project->implementingEntities->map(fn($e) => $e->authority->agency_name ?? ($e->internalEntity->name ?? 'جهة منفذة'))->implode('، ') }}
                        @else
                            غير محدد
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>الجهات الممولة</th>
                    <td>
                        @if($project->financings->isNotEmpty())
                            @foreach($project->financings as $financing)
                                <div>
                                    • {{ $financing->authority->agency_name ?? ($financing->fundingSource->name ?? 'جهة ممولة') }}
                                    @if($financing->amount)
                                    ({{ number_format($financing->amount, 2) }} ريال)
                                    @endif
                                </div>
                            @endforeach
                        @else
                            غير محدد
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>الجهات المشرفة</th>
                    <td>
                        @if($project->supervisingAuthorities->isNotEmpty())
                            {{ $project->supervisingAuthorities->map(fn($s) => $s->authority->agency_name ?? ($s->internalEntity->name ?? 'جهة مشرفة'))->implode('، ') }}
                        @else
                            غير محدد
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- خامساً: التفاصيل الفنية والمبررات (إن وجدت) -->
        @if($project->detail && ($project->detail->project_summary || $project->detail->project_introduction || $project->detail->problem_and_justification || $project->detail->project_components || $project->detail->expected_impact))
        <div class="section-header">
            <h3>خامساً: التفاصيل والمبررات والمكونات</h3>
        </div>

        @if($project->detail->project_summary)
        <div class="detail-box">
            <div class="detail-title">ملخص المشروع:</div>
            {{ $project->detail->project_summary }}
        </div>
        @endif

        @if($project->detail->problem_and_justification)
        <div class="detail-box">
            <div class="detail-title">المشكلة ومبررات المشروع:</div>
            {{ $project->detail->problem_and_justification }}
        </div>
        @endif

        @if($project->detail->project_components)
        <div class="detail-box">
            <div class="detail-title">مكونات المشروع وأنشطته الرئيسية:</div>
            {{ $project->detail->project_components }}
        </div>
        @endif

        @if($project->detail->expected_impact)
        <div class="detail-box">
            <div class="detail-title">الأثر المتوقع:</div>
            {{ $project->detail->expected_impact }}
        </div>
        @endif
        @endif

        <!-- التذييل والاعتمادات -->
        <div class="doc-footer">
            <div>
                @if(!empty($qrCodeBase64))
                <img src="{{ $qrCodeBase64 }}" alt="QR Code" style="width: 85px; height: 85px; border: 1px solid #cbd5e1; padding: 4px;">
                @endif
            </div>
            <div class="signature-box">
                <div class="signature-title">اعتماد الجهة المختصة</div>
                <div class="signature-line"></div>
            </div>
        </div>
    </div>

</body>

</html>
