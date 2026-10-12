<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة إنجاز | {{ $project->project_name }}</title>
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
        }

        @page { size: A4 portrait; margin: 15mm 12mm 18mm 12mm; }

        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; background: white; }
            .no-print { display: none !important; }
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

        .print-container { background: white; width: 190mm; margin: 0 auto; padding: 6px; }

        /* Header */
        .report-header {
            border-bottom: 3px solid var(--primary);
            padding-bottom: 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-center { text-align: center; flex: 1; }
        .report-title { font-size: 15pt; font-weight: 800; color: var(--primary); margin-bottom: 3px; }
        .report-subtitle { font-size: 11pt; color: var(--gold-dark); font-weight: 700; }
        .report-meta { font-size: 8pt; color: var(--muted); margin-top: 3px; }
        .header-side { width: 65px; font-size: 8pt; }
        .stamp-box { border: 2px solid var(--border); border-radius: 5px; padding: 4px 8px; text-align: center; font-size: 7.5pt; color: var(--muted); margin-top: 5px; }

        /* Project info */
        .project-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 9pt; }
        .project-table td { padding: 5px 8px; border: 1px solid var(--border); }
        .project-table .lbl { background: var(--primary-light); color: var(--primary); font-weight: 700; width: 22%; }

        /* Section title */
        .section-title {
            font-size: 11pt; font-weight: 800; color: var(--primary);
            border-right: 4px solid var(--gold); padding-right: 8px;
            margin: 14px 0 8px;
        }

        /* Progress display */
        .progress-display {
            display: flex; align-items: center; gap: 14px;
            background: var(--primary-light); border-radius: 8px;
            padding: 10px 14px; margin-bottom: 12px;
        }
        .progress-num { font-size: 22pt; font-weight: 800; color: var(--gold-dark); min-width: 75px; text-align: center; }
        .progress-bar-wrap { flex: 1; }
        .progress-track { height: 13px; background: #e2e8f0; border-radius: 20px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, var(--gold), var(--gold-dark)); border-radius: 20px; }
        .progress-meta { font-size: 8pt; color: var(--muted); margin-top: 3px; }
        .prev-badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            background: #64748b; color: white; font-size: 8.5pt; font-weight: 700; margin-top: 5px;
        }

        /* Info grid */
        .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 10px; }
        .info-cell { background: #f8fafc; border: 1px solid #e8edf3; border-radius: 5px; padding: 6px 8px; }
        .info-cell-lbl { font-size: 7.5pt; color: var(--muted); font-weight: 700; }
        .info-cell-val { font-size: 9.5pt; font-weight: 700; color: var(--text); margin-top: 2px; }

        /* Text boxes */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }
        .text-box { background: #f8fafc; border: 1px solid #e8edf3; border-radius: 5px; padding: 7px 10px; }
        .text-box-label { font-size: 8pt; color: var(--gold-dark); font-weight: 800; margin-bottom: 3px; }
        .text-box-content { font-size: 9pt; color: var(--text); line-height: 1.6; }

        /* Funding table */
        .funding-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8.5pt; }
        .funding-table th { background: var(--primary); color: white; padding: 5px 6px; text-align: center; font-weight: 700; border: 1px solid #2d4a7a; }
        .funding-table th:first-child { text-align: right; }
        .funding-table td { padding: 4px 6px; border: 1px solid var(--border); text-align: center; }
        .funding-table td:first-child { text-align: right; font-weight: 600; }
        .funding-table tr:nth-child(even) td { background: #f8fafc; }
        .funding-table .total-row td { background: var(--primary-light); font-weight: 800; color: var(--primary); border-top: 2px solid var(--primary); }

        /* Documents */
        .docs-list { list-style: none; padding: 0; margin: 0; }
        .docs-list li { padding: 3px 0; font-size: 8.5pt; color: var(--muted); border-bottom: 1px dotted #e2e8f0; }
        .docs-list li:last-child { border-bottom: none; }

        /* Badge */
        .pct-badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 8pt; font-weight: 700; color: white; }

        /* Signature */
        .signature-row { display: flex; justify-content: space-around; margin-top: 30px; padding-top: 10px; border-top: 2px solid var(--border); }
        .signature-cell { text-align: center; width: 28%; }
        .signature-line { border-bottom: 1px solid var(--text); margin: 30px auto 5px; width: 80%; }
        .signature-label { font-size: 8.5pt; font-weight: 700; color: var(--muted); }

        /* Footer */
        .report-footer { margin-top: 18px; padding-top: 8px; border-top: 2px solid var(--border); display: flex; justify-content: space-between; font-size: 7.5pt; color: var(--muted); }

        /* Floating buttons */
        .floating-actions { position: fixed; bottom: 25px; left: 25px; display: flex; gap: 10px; z-index: 999; }
        .btn-print {
            background: linear-gradient(135deg, var(--primary), #2d5a8f);
            color: white; border: none; padding: 12px 28px; border-radius: 12px;
            font-family: 'Cairo', sans-serif; font-size: 11pt; font-weight: 700;
            cursor: pointer; box-shadow: 0 4px 15px rgba(30,58,95,0.4); transition: all 0.2s;
        }
        .btn-print:hover { transform: translateY(-2px); }
        .btn-back {
            background: white; color: var(--primary); border: 2px solid var(--primary);
            padding: 12px 22px; border-radius: 12px;
            font-family: 'Cairo', sans-serif; font-size: 11pt; font-weight: 700;
            cursor: pointer; text-decoration: none;
        }
    </style>
</head>
<body>
<div class="print-container">
    <x-letterhead-selector :currentLetterhead="$letterhead ?? null" />

    {{-- Header --}}
    <div class="report-header">
        <div class="header-side" style="text-align:right;">
            <div>التاريخ: {{ now()->format('Y/m/d') }}</div>
            <div>الوقت: {{ now()->format('H:i') }}</div>
            <div class="stamp-box mt-1">نسخة رسمية</div>
        </div>
        <div class="header-center">
            <div class="report-title">تقرير إنجاز المشروع</div>
            <div class="report-subtitle">{{ $project->project_name }}</div>
            <div class="report-meta">
                {{ $letterhead?->organization_name ?? 'الجمهورية اليمنية' }} &nbsp;|&nbsp;
                رقم النموذج: {{ $project->form_number ?? 'ـ' }}
                &nbsp;|&nbsp; نوع التقرير: {{ optional($achievement)->reportType?->name ?? 'ـ' }}
            </div>
        </div>
        <div class="header-side" style="text-align:left;">
            @php
                $achievementLogoUrl = !empty($letterhead?->logo_path) ? asset('storage/' . $letterhead->logo_path) : asset('images/logo.png');
            @endphp
            <img src="{{ $achievementLogoUrl }}" alt="Logo" style="max-width:58px;max-height:58px;object-fit:contain;">
        </div>
    </div>

    {{-- Project Info --}}
    <div class="section-title">بيانات المشروع</div>
    <table class="project-table">
        <tr>
            <td class="lbl">اسم المشروع</td>
            <td colspan="3" style="font-weight:700;">{{ $project->project_name }}</td>
        </tr>
        <tr>
            <td class="lbl">رقم المشروع</td>
            <td>{{ $project->form_number ?? 'ـ' }}</td>
            <td class="lbl">نوع المشروع</td>
            <td>{{ $project->project_type === 'old' ? 'مشروع سابق (قديم)' : 'مشروع جديد' }}</td>
        </tr>
        <tr>
            <td class="lbl">المحافظة</td>
            <td>{{ $governorates }}</td>
            <td class="lbl">المديرية</td>
            <td>{{ $directorates }}</td>
        </tr>
        <tr>
            <td class="lbl">جهة التنفيذ</td>
            <td colspan="3">{{ $implementingEntitiesList }}</td>
        </tr>
    </table>

    {{-- Achievement Period --}}
    <div class="section-title">الفترة الزمنية للتقرير</div>
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

    {{-- Progress --}}
    <div class="section-title">نسبة الإنجاز</div>
    @php $pct = (float)$achievement->new_achievement; @endphp
    <div class="progress-display">
        <div class="progress-num">{{ number_format($pct, 1) }}%</div>
        <div class="progress-bar-wrap">
            <div style="display:flex;justify-content:space-between;font-size:8pt;color:var(--muted);margin-bottom:4px;">
                <span>الإنجاز السابق</span>
                <span>الإنجاز الحالي</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" style="width:{{ $pct }}%;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:3px;">
                <span class="prev-badge">{{ number_format($achievement->previous_achievement, 1) }}%</span>
                <span class="progress-meta">المدة: {{ $achievement->duration ?? 'ـ' }}</span>
            </div>
        </div>
    </div>

    {{-- Outputs & Indicators --}}
    <div class="section-title">المخرجات والمؤشرات</div>
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
    <div class="section-title">المستفيدون</div>
    <div class="text-box" style="margin-bottom:8px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;">
            <span style="font-size:14pt;font-weight:800;color:var(--primary);">{{ number_format($achievement->number_of_beneficiaries) }}</span>
            <span class="text-box-label">إجمالي المستفيدين</span>
        </div>
        <div class="text-box-content">{{ $achievement->notes_on_beneficiaries }}</div>
    </div>

    @if($achievement->comments)
    <div class="text-box" style="border-right:3px solid var(--gold);margin-bottom:8px;">
        <div class="text-box-label">▸ ملاحظات إضافية</div>
        <div class="text-box-content">{{ $achievement->comments }}</div>
    </div>
    @endif

    {{-- Funding --}}
    @if($achievement->fundings->count())
    <div class="section-title">بيانات الصرف والتمويل</div>
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
    @endif

    {{-- Documents --}}
    @if($achievement->documents->count())
    <div class="section-title">المستندات المرفقة</div>
    <ul class="docs-list">
        @foreach($achievement->documents as $doc)
        <li>📎 {{ $doc->file_name }}</li>
        @endforeach
    </ul>
    @endif

    {{-- Signature --}}
    <div class="signature-row">
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

    {{-- Footer --}}
    <div class="report-footer">
        <span>سُجِّل بواسطة: {{ optional($achievement->creator)->name ?? 'غير محدد' }} — {{ $achievement->created_at?->format('Y/m/d H:i') }}</span>
        <span>طُبع بتاريخ: {{ now()->format('Y/m/d H:i') }}</span>
    </div>

</div>

{{-- Print button --}}
<div class="floating-actions no-print">
    <button onclick="window.print()" class="btn-print">🖨️ طباعة</button>
    <a href="{{ route('projects.achievements.show', [$project->id, $achievement->id]) }}" class="btn-back">← العودة</a>
</div>
</body>
</html>
