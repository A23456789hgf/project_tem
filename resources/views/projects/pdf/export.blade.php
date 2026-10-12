<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $project->project_name }}</title>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
      <style>
        @page :first {
            margin: 1cm;
        }
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Almarai', 'DejaVu Sans', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-family: 'calibri', 'DejaVu Sans', sans-serif;
        }
        td, th {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
            font-family: 'calibri', 'DejaVu Sans', sans-serif;
        }
        .no-border td {
            border: none;
        }
        .header-table td {
            border: none;
            text-align: center;
            vertical-align: middle;
        }
        .section-title {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 5px;
            border: 1px solid #000;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        .label {
            width: 25%;
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10pt;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
    </style>
    @if(isset($engine) && $engine === 'mpdf')
    <style>
         body, td, th, .arabic-text, .arabic-title {
            font-family: 'calibri' !important;
        }
    </style>
    @endif
</head>
<body>
 <!-- Define Footer -->
  <htmlpagefooter name="myFooter">
    <table style="width: 100%; border-top: 1px solid #000; font-size: 10pt; padding-top: 5px;">
        <tr>
            <td style="border: none; text-align: right; width: 33%;">
                طبع بواسطة: {{ $user->name ?? 'غير محدد' }}
            </td>
            <td style="border: none; text-align: center; width: 33%;">
                صفحة {PAGENO} من {nbpg}
            </td>
            <td style="border: none; text-align: left; width: 33%;">
                تاريخ الطباعة: {{ now()->format('Y-m-d') }}
            </td>
        </tr>
    </table>
</htmlpagefooter>

<!-- Cover Page -->
<div style="position: relative; width: 21cm; height: 29.7cm; border: 4px solid #000; box-sizing: border-box;">

    <!-- Content Wrapper -->
    <div style="width: 100%; height: 100%; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 2cm 0; box-sizing: border-box;">

        @php
            $pdfLetterhead = $letterhead ?? null;
            $rightSettings = $pdfLetterhead ? $pdfLetterhead->getHeaderRightSettings() : [];
            $headerRightLines = !empty($rightSettings['text'])
                ? explode("\n", trim($rightSettings['text']))
                : ['الجمهورية اليمنية', 'وزارة الزراعة والثروة السمكية والموارد المائية'];
            $pdfLogoSrc = !empty($logoBase64) ? $logoBase64 : ($pdfLetterhead ? $pdfLetterhead->getLogoBase64() : '');
            if (empty($pdfLogoSrc)) {
                $defaultLogoPath = public_path('images/logo.png');
                $pdfLogoSrc = file_exists($defaultLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($defaultLogoPath)) : '';
            }
        @endphp

        <!-- Header -->
        <div style="margin-bottom: 2cm;">
            @foreach($headerRightLines as $hLine)
                @if(trim($hLine))
                    <h2 style="margin: 0; font-size: 16pt; font-weight: bold; line-height: 1.4;">{{ trim($hLine) }}</h2>
                @endif
            @endforeach

            <!-- Logo -->
            @if($pdfLogoSrc)
                <img src="{{ $pdfLogoSrc }}" style="width: 120px; height: auto; display: block; margin: 15px auto;">
            @else
                <div style="width: 120px; height: 120px; border: 1px dashed #000; line-height: 120px; margin: 15px auto;">الشعار</div>
            @endif
        </div>

        <!-- Title -->
        <div style="margin-bottom: 2cm;">
            <h1 style="font-size: 28pt; font-weight: bold; margin: 0;">وثيقة مشروع</h1>
            <div style="font-size: 16pt; margin-top: 10px;">{{ $project->project_name ?? '' }}</div>
        </div>

        <!-- QR Code -->
        <div style="margin-bottom: 2cm;">
            @if(!empty($qrCodeBase64) && strpos($qrCodeBase64, 'data:image') === 0)
                <img src="{{ $qrCodeBase64 }}" style="width: 120px; height: 120px; display: block; margin: 0 auto;">
            @endif
        </div>

        <!-- Footer -->
        <div style="position: absolute; bottom: 2cm; width: 100%; text-align: center; font-size: 14pt;">
            <div>رقم المشروع: {{ $project->form_number ?? '-' }}</div>
            @php
                $statusLabels = [
                    'draft' => 'قيد التنفيذ',
                    'progress_in' => 'قيد التقدم',
                    'in_progress' => 'قيد التقدم',
                    'final' => 'نهائي',
                ];
                $rawStatus = strtolower(trim($project->status ?? ''));
                $statusLabel = $statusLabels[$rawStatus] ?? ($project->status ?? '-');
            @endphp
            <div>حالة المستند: {{ $statusLabel }}</div>
        </div>

    </div>
</div>


    <!-- Page Break with Margins for Content Pages -->
    <pagebreak resetpagenum="1" margin-top="35" margin-bottom="25" margin-left="15" margin-right="15" header="myHeader" footer="myFooter" />

    {{-- Enable footer from this page onward (so cover remains footer-less) --}}
    <sethtmlpagefooter name="myFooter" value="on" />

    <!-- Define Header (Previously using table, now defining as htmlpageheader for consistency) -->
    <htmlpageheader name="myHeader">
        <table class="header-table" style="border-bottom: 2px solid {{ $pdfLetterhead ? ($pdfLetterhead->getTypographySettings()['primary_color'] ?? '#000') : '#000' }}; margin-bottom: 0;">
            <tr>
                <td width="35%" style="text-align: right; border: none; font-size: 10pt; line-height: 1.4;">
                    @foreach($headerRightLines as $hLine)
                        @if(trim($hLine))
                            <div><strong>{{ trim($hLine) }}</strong></div>
                        @endif
                    @endforeach
                </td>
                <td width="30%" style="border: none; text-align: center;">
                    @if($pdfLogoSrc)
                        <img src="{{ $pdfLogoSrc }}" width="65" height="auto">
                    @endif
                </td>
                <td width="35%" style="text-align: left; border: none; font-size: 10pt; line-height: 1.4;">
                    التاريخ: {{ now()->format('Y/m/d') }}<br>
                    رقم المشروع: <span dir="ltr">{{ $project->form_number ?? '-' }}</span>
                </td>
            </tr>
        </table>
    </htmlpageheader>




        <!-- Set Footer -->
    {{-- Disable global footer activation so footer is applied only where pagebreak/footer attributes request it (avoids footer on cover) --}}
    <!-- sethtmlpagefooter name="myFooter" value="on" -->

    <!-- Content Header (starts on next page) -->
    <table class="header-table" style="border-bottom: 1px solid #000; margin-bottom: 20px; margin-top: 20px;">
        <tr>
            <td width="30%" style="text-align: right;">
                <strong>الجمهورية اليمنية</strong><br>
                اللجنة الزراعية والسمكية العليا<br>
                الإدارة العامة للمشاريع
            </td>
            <td width="40%">
                @php
                    $logoPath = public_path('images/logo.png');
                    $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
                @endphp
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" width="80" height="auto">
                @else
                    <div style="border:1px solid #000; width:80px; height:80px; margin:0 auto;">شعار</div>
                @endif
            </td>
            <td width="30%" style="text-align: left;">
                التاريخ: {{ now()->format('Y/m/d') }}<br>
                رقم المشروع: {{ $project->form_number ?? '-' }}
            </td>
        </tr>
    </table>

    <!-- <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="border: 2px solid #000; padding: 10px; display: inline-block; background: #eee;">وثيقة مشروع</h1>
        <h3>{{ $project->project_name ?? '' }}</h3>
    </div> -->

    <!-- 1. Basic Info -->
    <div class="section-title">1. البيانات الأساسية</div>
    <table>
        <tr>
            <td class="label">اسم المشروع</td>
            <td>{{ $project->project_name ?? '-' }}</td>
            <td class="label">البرنامج</td>
            <td>{{ $project->program->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">المجال</td>
            <td>{{ $project->domain->name ?? '-' }}</td>
            <td class="label">المجال الفرعي</td>
            <td>{{ $project->subdomain->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">نوع التدخل</td>
            <td>{{ $project->intervention->name ?? '-' }}</td>
            <td class="label">الأولوية</td>
            <td>{{ $project->priority->name ?? $project->priority->priority ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">مدة المشروع</td>
            <td>{{ $project->project_duration ?? '-' }} يوم</td>
            <td class="label">عدد المستفيدين</td>
            <td>{{ number_format($project->number_of_beneficiaries ?? 0) }}</td>
        </tr>
        <tr>
            <td class="label">تاريخ البداية</td>
            @php
                $startRaw = $project->start_date_gregorian ?? $project->start_date ?? $project->start_date_hijri ?? null;
                try {
                    $startFormatted = $startRaw ? \Carbon\Carbon::parse($startRaw)->format('Y-m-d') : '-';
                } catch (\Exception $e) {
                    $startFormatted = $startRaw ?? '-';
                }
            @endphp
            <td>{{ $startFormatted }}</td>
            <td class="label">تاريخ النهاية</td>
            @php
                $endRaw = $project->end_date_gregorian ?? $project->end_date ?? $project->end_date_hijri ?? null;
                try {
                    $endFormatted = $endRaw ? \Carbon\Carbon::parse($endRaw)->format('Y-m-d') : '-';
                } catch (\Exception $e) {
                    $endFormatted = $endRaw ?? '-';
                }
            @endphp
            <td>{{ $endFormatted }}</td>
        </tr>
    </table>

    <!-- 2. Project Details -->
    @if($project->detail)
    <div class="section-title">2. تفاصيل المشروع</div>
    <table>
        <tr>
            <td class="label">ملخص المشروع</td>
            <td>{{ $project->detail->project_summary ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">المشكلة والمبررات</td>
            <td>{{ $project->detail->problem_and_justification ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">مكونات المشروع</td>
            <td>{{ $project->detail->project_components ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">الأثر المتوقع</td>
            <td>{{ $project->detail->expected_impact ?? '-' }}</td>
        </tr>
    </table>
    @endif

    <!-- 3. Risk Analysis (New Section) -->
    @if($project->risks->count() > 0)
    <div class="section-title">3. المخاطر والافتراضات</div>
    <table>
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th width="40%">الخطر المحتمل</th>
                <th width="15%">مستوى الخطر</th>
                <th width="45%">إجراءات الحد من المخاطر</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->risks as $risk)
            <tr>
                <td>{{ $risk->risk }}</td>
                <td style="text-align: center;">{{ $risk->risk_rate }}/10</td>
                <td>{{ $risk->proposed_solution }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- 4. Locations -->
    @if($project->locations->count() > 0)
    <div class="section-title">4. مواقع التنفيذ</div>
    <table>
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th width="5%">م</th>
                <th>المحافظة</th>
                <th>المديرية</th>
                <th>المنطقة/العزلة</th>
                <th>القرية</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->locations as $index => $loc)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $loc->governorate->name ?? '-' }}</td>
                <td>{{ $loc->directorate->name ?? '-' }}</td>
                <td>{{ $loc->subArea->name ?? '-' }}</td>
                <td>{{ $loc->village->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- 5. Objectives & Results (New Section) -->
    <div class="section-title">5. الأهداف والنتائج الاستراتيجية</div>

    @if($project->mainObjectives->count() > 0)
    <div style="margin-bottom: 10px;">
        <strong>الأهداف العامة:</strong>
        <ul>
            @foreach($project->mainObjectives as $obj)
                <li>{{ $obj->objective }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @foreach($project->specialObjectives as $index => $specObj)
    <div style="margin-top: 10px; border: 1px solid #ccc; padding: 5px;">
        <div style="background-color: #f9f9f9; padding: 5px; font-weight: bold;">
            الهدف الخاص {{ $index + 1 }}: {{ $specObj->objective }}
        </div>

        @foreach($specObj->results as $resIndex => $result)
        <div style="margin: 5px 15px;">
            <div style="font-weight: bold; margin-bottom: 3px;">النتيجة {{ $index + 1 }}.{{ $resIndex + 1 }}: {{ $result->result_name ?? $result->result_text ?? '-' }}</div>
            <table style="font-size: 10pt;">
                <thead>
                    <tr style="background-color: #eee;">
                        <th>اسم المخرج</th>
                        <th>القيمة المستهدفة</th>
                        <th>نوع المؤشر</th>
                        <th>وحدة المؤشر</th>

                    </tr>
                </thead>
                <tbody>
                    @php
                        $indicatorMap = [
                            'relative' => 'نسبي',
                            'qualitative' => 'نوعي',
                            'quantitative' => 'كمّي',
                            'percentage' => 'نسبة',
                            'binary' => 'ثنائي',
                            'count' => 'عددي',
                        ];
                    @endphp
                    @foreach($result->outputs as $out)
                    <tr>
                        <td>{{ $out->output ?? $out->output_text ?? '-' }}</td>
                        <td class="text-center">{{ $out->target_value ?? $out->target ?? '-' }}</td>
                        <td>
                            @php
                                $rawType = strtolower(trim($out->indicator_type ?? $out->indicator ?? ''));
                            @endphp
                            {{ $indicatorMap[$rawType] ?? ($out->indicator_type ?? $out->indicator ?? '-') }}
                        </td>
                        <td>{{ $out->indicator_unit ?? $out->measurement_unit ?? '-' }}</td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
    @endforeach

    <!-- 6. Project Activities Plan (New Section) -->
    <div class="section-title">6. خطة الأنشطة والإطار المنطقي</div>

    <!-- Preliminary Activities -->
    @if($project->preliminaryActivities->count() > 0)
    <h4>أولاً: الأنشطة التمهيدية</h4>
    @foreach($project->preliminaryActivities as $activity)
    <div style="margin-bottom: 10px; border: 1px solid #000;">
        <div style="background-color: #e0e0e0; padding: 5px; font-weight: bold;">{{ $activity->name }}</div>
        <table style="margin: 0; border: none;">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th>الإجراء</th>
                    <th>البند المالي</th>
                    <th>الوحدة</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activity->procedures as $proc)
                    @foreach($proc->costs as $cost)
                    @php $costTotal = ($cost->quantity ?? 0) * ($cost->amount ?? 0); @endphp
                    <tr>
                        <td>{{ $proc->procedure_name }}</td>
                        <td>{{ $cost->financialItem->name ?? '-' }}</td>
                        <td>{{ $cost->unit->name ?? '-' }}</td>
                        <td class="text-center">{{ $cost->quantity }}</td>
                        <td class="text-center" dir="ltr">{{ number_format($cost->amount) }}</td>
                        <td class="text-center" dir="ltr">{{ number_format($costTotal) }}</td>
                    </tr>
                    @endforeach
                @endforeach
                 <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="5" style="text-align: left;">إجمالي النشاط:</td>
                    @php
                        $activityTotal = $activity->procedures->flatMap->costs->sum(function($c){ return ($c->quantity ?? 0) * ($c->amount ?? 0); });
                    @endphp
                    <td class="text-center" dir="ltr">{{ number_format($activityTotal) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach
    @endif

    <!-- Executive Activities -->
    @if($project->executiveActivities->count() > 0)
    <h4>ثانياً: الأنشطة التنفيذية</h4>
    @foreach($project->executiveActivities as $activity)
    <div style="margin-bottom: 10px; border: 1px solid #000;">
        <div style="background-color: #e0e0e0; padding: 5px; font-weight: bold;">{{ $activity->name }}</div>
        <table style="margin: 0; border: none;">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th width="25%">الإجراء التنفيذي</th>
                    <th width="20%">الجهة المنفذة</th>
                    <th>البند المالي</th>
                    <th width="15%">التكلفة (كمية × سعر)</th>
                    <th width="15%">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activity->actions as $action)
                    @foreach($action->costs as $idx => $cost)
                    @php $costTotal = ($cost->quantity ?? 0) * ($cost->amount ?? 0); @endphp
                    <tr>
                        @if($idx === 0)
                        <td rowspan="{{ $action->costs->count() }}">{{ $action->action_name }}</td>
                        <td rowspan="{{ $action->costs->count() }}">
                            @foreach($action->assignedEntities as $entity)
                                <div>- {{ $entity->agency_name ?? $entity->name ?? '' }}</div>
                            @endforeach
                        </td>
                        @endif
                        <td>{{ $cost->financialItem->name ?? '-' }}</td>
                        <td class="text-center" dir="ltr">{{ $cost->quantity }} × {{ number_format($cost->amount) }}</td>
                        <td class="text-center" dir="ltr">{{ number_format($costTotal) }}</td>
                    </tr>
                    @endforeach
                @endforeach
                <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="4" style="text-align: left;">إجمالي النشاط التنفيذي:</td>
                    @php
                        $execTotal = $activity->actions->flatMap->costs->sum(function($c){ return ($c->quantity ?? 0) * ($c->amount ?? 0); });
                    @endphp
                    <td class="text-center" dir="ltr">{{ number_format($execTotal) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach
    @endif

    <!-- 7. Reviewers/Stakeholders (Detailed) -->
    <div class="section-title">7. الهيكل الإشرافي والتنظيمي (الجهات ذات العلاقة)</div>

    <table style="margin-bottom: 10px;">
        <tr style="background-color: #f0f0f0;">
            <th width="25%">النوع</th>
            <th width="20%">الجهة</th>
            <th width="55%">اسم الجهة</th>
        </tr>
        <!-- Supervising -->
        @foreach($project->supervisingAuthorities as $auth)
        @php
            $name = optional($auth->authority)->agency_name ?? optional($auth->authority)->name ?? ($auth->authority_name ?? $auth->authority_id ?? '-');
            $typeLabel = ($auth->authority_type ?? null) === 'internal' ? 'داخلية' : (($auth->authority_type ?? null) === 'external' ? 'خارجية' : '-');
            $parentName = optional($auth->parent)->agency_name;
            $fullName = $parentName ? $name . ' / ' . $parentName : $name;
        @endphp
        <tr>
            <td>جهة إشرافية</td>
            <td class="text-center">{{ $typeLabel }}</td>
            <td>{{ $fullName }}</td>
        </tr>
        @endforeach
        <!-- Implementing -->
        @foreach($project->implementingEntities as $entity)
        @php
            $ename = optional($entity->authority)->agency_name ?? optional($entity->authority)->name ?? ($entity->authority_name ?? $entity->authority_id ?? '-');
            $etypeKey = $entity->authority_type ?? $entity->entity_type ?? null;
            $etype = $etypeKey === 'internal' ? 'داخلية' : ($etypeKey === 'external' ? 'خارجية' : '-');
            $eParent = optional($entity->parent)->agency_name;
            $efull = $eParent ? $ename . ' / ' . $eParent : $ename;
        @endphp
        <tr>
            <td>جهة منفذة</td>
            <td class="text-center">{{ $etype }}</td>
            <td>{{ $efull }}</td>
        </tr>
        @endforeach
        <!-- Participating -->
        @foreach($project->participatingEntities as $part)
        @php
            $pname = optional($part->authority)->agency_name ?? optional($part->authority)->name ?? ($part->authority_name ?? $part->authority_id ?? '-');
            $ptypeKey = $part->entity_type ?? null;
            $ptype = $ptypeKey === 'internal' ? 'داخلية' : ($ptypeKey === 'external' ? 'خارجية' : '-');
            $pparent = optional($part->parent)->agency_name;
            $pfull = $pparent ? $pname . ' / ' . $pparent : $pname;
        @endphp
        <tr>
            <td>جهة مشاركة</td>
            <td class="text-center">{{ $ptype }}</td>
            <td>{{ $pfull }}</td>
        </tr>
        @endforeach
        <!-- Beneficiary -->
        @foreach($project->beneficiaryEntities as $ben)
        @php
            $bname = optional($ben->authority)->agency_name ?? optional($ben->authority)->name ?? ($ben->authority_name ?? $ben->authority_id ?? '-');
            $btypeKey = $ben->entity_type ?? null;
            $btype = $btypeKey === 'internal' ? 'داخلية' : ($btypeKey === 'external' ? 'خارجية' : '-');
            $bparent = optional($ben->parent)->agency_name;
            $bfull = $bparent ? $bname . ' / ' . $bparent : $bname;
        @endphp
        <tr>
            <td>جهة مستفيدة</td>
            <td class="text-center">{{ $btype }}</td>
            <td>{{ $bfull }}</td>
        </tr>
        @endforeach
    </table>

    <!-- 8. Financials -->
    <div class="section-title">8. البيانات المالية والتمويل</div>
    <table>
        <tr>
            <td class="label">إجمالي التكلفة التقديرية</td>
            <td style="font-weight: bold; font-size: 14pt;">{{ number_format($totalProjectCost ?? 0) }} ريال</td>
        </tr>
        <tr>
            <td class="label">مصادر التمويل</td>
            <td>
                <table style="width: 100%; border: none; margin: 0;">
                    <tr style="background-color: #eee;">
                        <th>المصدر</th>
                        <th>الجهة</th>
                        <th>المبلغ</th>
                    </tr>
                    @foreach($project->financings as $finance)
                    <tr>
                        <td style="border: none;">{{ $finance->fundingSource->name ?? '-' }}</td>
                        <td style="border: none;">{{ $finance->authority->name ?? $finance->authority->agency_name ?? '-' }}</td>
                        <td style="border: none; font-weight: bold;">{{ number_format($finance->financing_amount) }}</td>
                    </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

</body>
</html>

