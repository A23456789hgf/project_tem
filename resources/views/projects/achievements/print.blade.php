<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير إنجازات المشروع | {{ $project->project_name }}</title>
    <x-letterhead-styles :letterhead="$letterhead ?? null" />
    <style>
        :root {
            --primary: {{ $letterhead?->primary_color ?? '#1e3a5f' }};
            --primary-light: #e8f0f8;
            --gold: #c9a961;
            --gold-dark: #a8843f;
            --border: #d1d9e0;
            --text: #1a1a2e;
            --muted: #64748b;
            --success: #059669;
            --danger: #dc2626;
            --warning: #d97706;
        }

        @page {
            size: A4 portrait;
            margin: 15mm 12mm 18mm 12mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: white;
            }
            .no-print { display: none !important; }
            .achievement-block { page-break-inside: avoid; }
            .page-break { page-break-before: always; }
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background: #f5f7fa;
            color: var(--text);
            line-height: 1.5;
            font-size: 10pt;
        }

        .print-container {
            background: white;
            width: 190mm;
            margin: 0 auto;
            padding: 6px;
        }

        /* ===== REPORT HEADER ===== */
        .report-header {
            border-bottom: 3px solid var(--primary);
            padding-bottom: 12px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-center { text-align: center; flex: 1; }
        .report-title {
            font-size: 16pt;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .report-subtitle { font-size: 11pt; color: var(--gold-dark); font-weight: 700; }
        .report-meta { font-size: 8pt; color: var(--muted); margin-top: 4px; }
        .header-logo { width: 70px; text-align: left; }
        .header-info { width: 70px; text-align: right; font-size: 8.5pt; line-height: 1.7; }
        .stamp-box {
            border: 2px solid var(--border);
            border-radius: 6px;
            padding: 4px 10px;
            text-align: center;
            font-size: 8pt;
            color: var(--muted);
        }

        /* ===== PROJECT INFO TABLE ===== */
        .project-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 9.5pt;
        }
        .project-info-table td {
            padding: 5px 8px;
            border: 1px solid var(--border);
        }
        .project-info-table .lbl {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 700;
            width: 22%;
            white-space: nowrap;
        }
        .project-info-table .val { font-weight: 600; }

        /* ===== STATS SUMMARY ===== */
        .stats-row {
            display: flex;
            gap: 0;
            border: 2px solid var(--primary);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 18px;
        }
        .stat-cell {
            flex: 1;
            text-align: center;
            padding: 10px 6px;
            border-left: 1px solid var(--border);
        }
        .stat-cell:first-child { border-left: none; }
        .stat-cell:nth-child(odd) { background: var(--primary-light); }
        .stat-num { font-size: 15pt; font-weight: 800; color: var(--primary); }
        .stat-lbl { font-size: 8pt; color: var(--muted); margin-top: 2px; }

        /* ===== SECTION TITLE ===== */
        .section-title {
            font-size: 12pt;
            font-weight: 800;
            color: var(--primary);
            border-right: 4px solid var(--gold);
            padding-right: 8px;
            margin-bottom: 10px;
            margin-top: 18px;
        }

        /* ===== ACHIEVEMENT BLOCK ===== */
        .achievement-block {
            border: 1.5px solid var(--border);
            border-radius: 8px;
            margin-bottom: 14px;
            overflow: hidden;
        }
        .achievement-header {
            background: var(--primary);
            color: white;
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .achievement-header-right { font-size: 10.5pt; font-weight: 700; }
        .achievement-header-left { font-size: 9pt; opacity: 0.9; }
        .achievement-body { padding: 10px 12px; }

        /* Progress display */
        .progress-display {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--primary-light);
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        .progress-num {
            font-size: 20pt;
            font-weight: 800;
            color: var(--gold-dark);
            min-width: 70px;
            text-align: center;
        }
        .progress-bar-wrap { flex: 1; }
        .progress-bar-track {
            height: 12px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            border-radius: 20px;
        }
        .progress-meta { font-size: 8.5pt; color: var(--muted); margin-top: 3px; }

        /* Info grid inside achievement */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 10px;
        }
        .info-cell {
            background: #f8fafc;
            border: 1px solid #e8edf3;
            border-radius: 5px;
            padding: 5px 7px;
        }
        .info-cell-lbl { font-size: 7.5pt; color: var(--muted); font-weight: 700; }
        .info-cell-val { font-size: 9.5pt; color: var(--text); font-weight: 700; margin-top: 1px; }

        /* Text boxes */
        .text-box {
            background: #f8fafc;
            border: 1px solid #e8edf3;
            border-radius: 5px;
            padding: 7px 10px;
            margin-bottom: 8px;
            font-size: 9pt;
        }
        .text-box-label {
            font-size: 8pt;
            color: var(--gold-dark);
            font-weight: 800;
            margin-bottom: 3px;
        }
        .text-box-content { color: var(--text); line-height: 1.6; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }

        /* Funding table */
        .funding-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        .funding-table th {
            background: var(--primary);
            color: white;
            padding: 5px 6px;
            text-align: center;
            font-weight: 700;
            border: 1px solid #2d4a7a;
        }
        .funding-table th:first-child { text-align: right; }
        .funding-table td {
            padding: 4px 6px;
            border: 1px solid var(--border);
            text-align: center;
        }
        .funding-table td:first-child { text-align: right; font-weight: 600; }
        .funding-table tr:nth-child(even) td { background: #f8fafc; }
        .funding-table .total-row td {
            background: var(--primary-light);
            font-weight: 800;
            color: var(--primary);
            border-top: 2px solid var(--primary);
        }

        /* Documents */
        .docs-list { list-style: none; padding: 0; margin: 0; }
        .docs-list li {
            padding: 3px 0;
            font-size: 8.5pt;
            color: var(--muted);
            border-bottom: 1px dotted #e2e8f0;
        }
        .docs-list li:last-child { border-bottom: none; }

        /* Percentage badge */
        .pct-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: 700;
            color: white;
        }

        /* Signature area */
        .signature-row {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 2px solid var(--border);
        }
        .signature-cell { text-align: center; width: 28%; }
        .signature-line {
            border-bottom: 1px solid var(--text);
            margin: 30px auto 5px;
            width: 80%;
        }
        .signature-label { font-size: 8.5pt; font-weight: 700; color: var(--muted); }

        /* Report footer */
        .report-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 2px solid var(--border);
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            color: var(--muted);
        }

        /* Floating print button */
        .floating-actions {
            position: fixed;
            bottom: 25px;
            left: 25px;
            display: flex;
            gap: 10px;
            z-index: 999;
        }
        .btn-print {
            background: linear-gradient(135deg, var(--primary), #2d5a8f);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 12px;
            font-family: 'Cairo', sans-serif;
            font-size: 11pt;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(30,58,95,0.4);
            transition: all 0.2s;
        }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(30,58,95,0.5); }
        .btn-back {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 12px 22px;
            border-radius: 12px;
            font-family: 'Cairo', sans-serif;
            font-size: 11pt;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="print-container">
    <x-letterhead-selector :currentLetterhead="$letterhead ?? null" />

    {{-- ===== REPORT HEADER ===== --}}
    <div class="report-header">
        <div class="header-info">
            <div>التاريخ: {{ now()->format('Y/m/d') }}</div>
            <div>الوقت: {{ now()->format('H:i') }}</div>
            <div class="stamp-box mt-1">نسخة رسمية</div>
        </div>
        <div class="header-center">
            <div class="report-title">تقرير إنجازات المشروع</div>
            <div class="report-subtitle">{{ $project->project_name }}</div>
            <div class="report-meta">
                {{ $letterhead?->organization_name ?? 'الجمهورية اليمنية' }} &nbsp;|&nbsp;
                رقم النموذج: {{ $project->form_number ?? 'ـ' }} &nbsp;|&nbsp;
                تاريخ التقرير: {{ now()->format('Y/m/d') }} م
            </div>
        </div>
        <div class="header-logo">
            @php
                $achievementLogoUrl = !empty($letterhead?->logo_path) ? asset('storage/' . $letterhead->logo_path) : asset('images/logo.png');
            @endphp
            <img src="{{ $achievementLogoUrl }}" alt="Logo" style="max-width:70px;max-height:70px;object-fit:contain;">
        </div>
    </div>

    {{-- ===== PROJECT INFO ===== --}}
    <div class="section-title">بيانات المشروع</div>
    <table class="project-info-table">
        <tr>
            <td class="lbl">اسم المشروع</td>
            <td class="val" colspan="3">{{ $project->project_name }}</td>
        </tr>
        <tr>
            <td class="lbl">رقم المشروع</td>
            <td class="val">{{ $project->form_number ?? 'ـ' }}</td>
            <td class="lbl">نوع المشروع</td>
            <td class="val">{{ $project->project_type === 'old' ? 'مشروع سابق (قديم)' : 'مشروع جديد' }}</td>
        </tr>
        <tr>
            <td class="lbl">المحافظة</td>
            <td class="val">{{ $governorates }}</td>
            <td class="lbl">المديرية</td>
            <td class="val">{{ $directorates }}</td>
        </tr>
        <tr>
            <td class="lbl">جهة التنفيذ</td>
            <td class="val" colspan="3">{{ $implementingEntitiesList }}</td>
        </tr>
    </table>

    {{-- ===== SUMMARY STATS ===== --}}
    @if($achievements->count())
    <div class="stats-row">
        <div class="stat-cell">
            <div class="stat-num">{{ $achievements->count() }}</div>
            <div class="stat-lbl">عدد تقارير الإنجاز</div>
        </div>
        <div class="stat-cell">
            <div class="stat-num" style="color:var(--gold-dark);">{{ number_format($latestAchievement, 1) }}%</div>
            <div class="stat-lbl">نسبة الإنجاز الأخيرة</div>
        </div>
        <div class="stat-cell">
            <div class="stat-num" style="color:var(--success);font-size:11pt;">{{ number_format($achievements->sum(fn($a) => $a->fundings->sum('new_disbursement')), 0) }}</div>
            <div class="stat-lbl">إجمالي الصرف (ريال)</div>
        </div>
        <div class="stat-cell">
            <div class="stat-num" style="color:#7c3aed;">{{ number_format($achievements->sum('number_of_beneficiaries')) }}</div>
            <div class="stat-lbl">إجمالي المستفيدين</div>
        </div>
    </div>
    @endif

    {{-- ===== ACHIEVEMENTS ===== --}}
    <div class="section-title">سجل الإنجازات التفصيلي</div>

    @forelse($achievements as $index => $achievement)
        @php $pct = (float)$achievement->new_achievement; @endphp

        @if($index > 0 && $index % 2 === 0)
        <div class="page-break"></div>
        @endif

        <div class="achievement-block">
            {{-- Header --}}
            <div class="achievement-header">
                <div class="achievement-header-right">
                    <span style="background:var(--gold);color:white;border-radius:50%;padding:2px 8px;font-size:9pt;margin-left:8px;">{{ $achievements->count() - $index }}</span>
                    {{ optional($achievement->reportType)->name ?? 'تقرير إنجاز' }}
                </div>
                <div class="achievement-header-left">
                    من: {{ $achievement->start_date_gregorian?->format('Y/m/d') ?? 'ـ' }}
                    &nbsp;→&nbsp;
                    إلى: {{ $achievement->end_date_gregorian?->format('Y/m/d') ?? 'ـ' }}
                    @if($achievement->duration)
                        &nbsp;|&nbsp; المدة: {{ $achievement->duration }}
                    @endif
                </div>
            </div>

            {{-- Body --}}
            <div class="achievement-body">
                {{-- Progress --}}
                <div class="progress-display">
                    <div class="progress-num">{{ number_format($pct, 1) }}%</div>
                    <div class="progress-bar-wrap">
                        <div style="display:flex;justify-content:space-between;font-size:8pt;color:var(--muted);margin-bottom:3px;">
                            <span>الإنجاز السابق: {{ number_format($achievement->previous_achievement, 1) }}%</span>
                            <span>الإنجاز الجديد: {{ number_format($pct, 1) }}%</span>
                        </div>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill" style="width:{{ $pct }}%;"></div>
                        </div>
                        <div class="progress-meta">نسبة الإنجاز الإجمالية المحققة حتى هذا التقرير</div>
                    </div>
                </div>

                {{-- Info Grid --}}
                <div class="info-grid">
                    <div class="info-cell">
                        <div class="info-cell-lbl">من (ميلادي)</div>
                        <div class="info-cell-val">{{ $achievement->start_date_gregorian?->format('Y/m/d') ?? 'ـ' }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="info-cell-lbl">من (هجري)</div>
                        <div class="info-cell-val">{{ $achievement->start_date_hijri ?? 'ـ' }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="info-cell-lbl">إلى (ميلادي)</div>
                        <div class="info-cell-val">{{ $achievement->end_date_gregorian?->format('Y/m/d') ?? 'ـ' }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="info-cell-lbl">إلى (هجري)</div>
                        <div class="info-cell-val">{{ $achievement->end_date_hijri ?? 'ـ' }}</div>
                    </div>
                </div>

                {{-- Outputs & Indicators --}}
                <div class="two-col">
                    <div class="text-box">
                        <div class="text-box-label">▸ المخرجات المحققة</div>
                        <div class="text-box-content">{{ $achievement->achieved_outputs }}</div>
                    </div>
                    <div class="text-box">
                        <div class="text-box-label">▸ المؤشرات المحققة</div>
                        <div class="text-box-content">{{ $achievement->achieved_indicators }}</div>
                    </div>
                </div>

                {{-- Beneficiaries --}}
                <div class="info-cell" style="margin-bottom:8px;">
                    <div class="info-cell-lbl">عدد المستفيدين: <strong style="color:var(--primary);font-size:10pt;">{{ number_format($achievement->number_of_beneficiaries) }}</strong></div>
                    <div class="text-box-content" style="font-size:8.5pt;margin-top:3px;">{{ $achievement->notes_on_beneficiaries }}</div>
                </div>

                @if($achievement->comments)
                <div class="text-box" style="border-right:3px solid var(--gold);">
                    <div class="text-box-label">▸ ملاحظات إضافية</div>
                    <div class="text-box-content">{{ $achievement->comments }}</div>
                </div>
                @endif

                {{-- Funding Table --}}
                @if($achievement->fundings->count())
                <div style="margin-top:8px;">
                    <div style="font-size:8.5pt;font-weight:800;color:var(--gold-dark);margin-bottom:4px;">▸ بيانات الصرف والتمويل</div>
                    <table class="funding-table">
                        <thead>
                            <tr>
                                <th>جهة التمويل</th>
                                <th>إجمالي التمويل</th>
                                <th>الصرف السابق</th>
                                <th>المتبقي السابق</th>
                                <th>الصرف الجديد</th>
                                <th>نسبة الصرف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($achievement->fundings as $f)
                            @php $dp = (float)$f->disbursement_percentage; @endphp
                            <tr>
                                <td>{{ $f->funding_entity }}</td>
                                <td>{{ number_format($f->total_funding, 2) }}</td>
                                <td>{{ number_format($f->previous_disbursement, 2) }}</td>
                                <td>{{ number_format($f->previous_remaining_disbursement, 2) }}</td>
                                <td style="font-weight:800;color:var(--success);">{{ number_format($f->new_disbursement, 2) }}</td>
                                <td>
                                    <span class="pct-badge" style="background:{{ $dp > 90 ? '#dc2626' : ($dp > 60 ? '#d97706' : '#059669') }};">
                                        {{ number_format($dp, 1) }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                            <tr class="total-row">
                                <td>الإجمالي</td>
                                <td>{{ number_format($achievement->fundings->sum('total_funding'), 2) }}</td>
                                <td>{{ number_format($achievement->fundings->sum('previous_disbursement'), 2) }}</td>
                                <td>{{ number_format($achievement->fundings->sum('previous_remaining_disbursement'), 2) }}</td>
                                <td>{{ number_format($achievement->fundings->sum('new_disbursement'), 2) }}</td>
                                <td>ـ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Documents --}}
                @if($achievement->documents->count())
                <div style="margin-top:6px;font-size:8.5pt;">
                    <strong style="color:var(--gold-dark);">▸ المستندات المرفقة ({{ $achievement->documents->count() }}):</strong>
                    <ul class="docs-list" style="margin-top:4px;">
                        @foreach($achievement->documents as $doc)
                        <li>📎 {{ $doc->file_name }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Meta --}}
                <div style="margin-top:8px;padding-top:6px;border-top:1px dotted var(--border);font-size:7.5pt;color:var(--muted);display:flex;justify-content:space-between;">
                    <span>سُجِّل بواسطة: {{ optional($achievement->creator)->name ?? 'غير محدد' }}</span>
                    <span>تاريخ التسجيل: {{ $achievement->created_at?->format('Y/m/d H:i') }}</span>
                </div>
            </div>
        </div>
    @empty
        <div style="text-align:center;padding:40px;color:var(--muted);border:1px dashed var(--border);border-radius:8px;">
            لا توجد إنجازات مسجّلة لهذا المشروع بعد.
        </div>
    @endforelse

    {{-- ===== SIGNATURE ===== --}}
    <div class="signature-row no-print" style="display:none;"></div>
    @if($achievements->count())
    <div class="signature-row" style="margin-top:35px;">
        <div class="signature-cell">
            <div class="signature-line"></div>
            <div class="signature-label">مُعِدّ التقرير</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line"></div>
            <div class="signature-label">المدير المباشر</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line"></div>
            <div class="signature-label">مدير الإدارة</div>
        </div>
    </div>
    @endif

    {{-- ===== FOOTER ===== --}}
    <div class="report-footer">
        <span>تقرير إنجازات المشروع — {{ $project->project_name }}</span>
        <span>طُبع بتاريخ: {{ now()->format('Y/m/d H:i') }}</span>
    </div>

</div>

{{-- Floating print button --}}
<div class="floating-actions no-print">
    <button onclick="window.print()" class="btn-print">🖨️ طباعة التقرير</button>
    <a href="{{ route('projects.achievements.index', $project->id) }}" class="btn-back">← العودة</a>
</div>

</body>
</html>
