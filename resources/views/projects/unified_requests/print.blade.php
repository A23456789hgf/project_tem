<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>
        @if($unifiedRequest->request_type === 'clearance_payment')
            طلب إخلاء عهدة وصرف دفعة | {{ $unifiedRequest->request_number }}
        @elseif($unifiedRequest->request_type === 'clearance_closure')
            طلب إخلاء عهدة وإغلاق مشروع | {{ $unifiedRequest->request_number }}
        @elseif($unifiedRequest->request_type === 'clearance')
            طلب إخلاء عهدة مالية | {{ $unifiedRequest->request_number }}
        @else
            طلب صرف دفعة مالية | {{ $unifiedRequest->request_number }}
        @endif
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <x-letterhead-styles :letterhead="$letterhead" />

    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }

        body {
            font-family: 'Cairo', 'Tajawal', sans-serif;
            background: #ffffff;
            color: #1e293b;
            line-height: 1.6;
            direction: rtl;
            font-size: 11pt;
            text-align: right;
            margin: 0;
            padding: 0;
        }

        .document-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            padding: 10px 20px;
        }

        .doc-title-box {
            text-align: center;
            border-top: 2px solid #0f766e;
            border-bottom: 2px solid #0f766e;
            padding: 10px 0;
            margin: 15px 0 20px;
            background-color: #f8fafc;
        }

        .doc-title-box h1 {
            font-size: 18pt;
            font-weight: 800;
            color: #0f766e;
            margin: 0;
        }

        .meta-strip {
            display: flex;
            justify-content: space-between;
            background-color: #f1f5f9;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 10pt;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .official-recipient {
            font-size: 12pt;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1e293b;
        }

        .official-body-text {
            text-align: justify;
            font-size: 11pt;
            line-height: 1.8;
            margin-bottom: 20px;
            color: #334155;
        }

        .section-header {
            font-size: 12pt;
            font-weight: 800;
            color: #0f766e;
            border-right: 4px solid #0f766e;
            padding-right: 10px;
            margin: 15px 0 8px;
            background-color: #f8fafc;
            padding-top: 4px;
            padding-bottom: 4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10pt;
        }

        .info-table th {
            background-color: #f1f5f9;
            color: #0f766e;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            padding: 7px 10px;
            width: 25%;
            text-align: right;
        }

        .info-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 10px;
            color: #1e293b;
        }

        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10pt;
            text-align: center;
        }

        .financial-table th {
            background-color: #0f766e;
            color: #ffffff;
            border: 1px solid #0f766e;
            padding: 8px;
            font-weight: 700;
        }

        .financial-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            background-color: #ffffff;
        }

        .highlight-amount {
            font-weight: 800;
            color: #0f766e;
            font-size: 11.5pt;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .signature-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            background-color: #f8fafc;
        }

        .signature-title {
            font-weight: 800;
            font-size: 10.5pt;
            color: #0f766e;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .signature-name {
            font-weight: 700;
            font-size: 10pt;
            color: #1e293b;
            min-height: 20px;
            margin-bottom: 40px;
        }

        .signature-line {
            font-size: 9pt;
            color: #64748b;
            border-top: 1px dotted #94a3b8;
            padding-top: 5px;
        }

        .barcode-strip {
            margin-top: 25px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8.5pt;
            color: #64748b;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
            .document-container { max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print p-3 bg-light border-bottom mb-3 text-center">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <x-letterhead-selector :currentLetterhead="$letterhead" />
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-print me-1"></i> طباعة الوثيقة (Print A4)
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 ms-2">
                إغلاق
            </button>
        </div>
    </div>
</div>

<div class="document-container">

    {{-- Official Organization Letterhead Header --}}
    <x-letterhead-header :letterhead="$letterhead" />

    {{-- Metadata Strip --}}
    <div class="meta-strip">
        <div>
            <strong>رقم الطلب المرجعي:</strong>
            <span style="font-family: monospace; font-size: 11pt; color: #0f766e; font-weight: bold;">
                {{ $unifiedRequest->request_number }}
            </span>
        </div>
        <div>
            <strong>تاريخ الطلب:</strong>
            <span>{{ $unifiedRequest->request_date?->format('Y-m-d') }}</span>
        </div>
        <div>
            <strong>حالة الطلب:</strong>
            <span>{{ $unifiedRequest->statusLabel() }}</span>
        </div>
    </div>

    {{-- Document Heading --}}
    <div class="doc-title-box">
        @if($unifiedRequest->request_type === 'clearance_payment')
            <h1>طلب إخلاء عهدة وصرف دفعة للمشروع</h1>
        @elseif($unifiedRequest->request_type === 'clearance_closure')
            <h1>طلب إخلاء عهدة وإغلاق المشروع</h1>
        @elseif($unifiedRequest->request_type === 'clearance')
            <h1>طلب إخلاء عهدة مالية</h1>
        @else
            <h1>طلب صرف دفعة مالية للمشروع</h1>
        @endif
    </div>

    {{-- Official Recipient --}}
    <div class="official-recipient">
        الأخ / وزير الزراعة والثروة السمكية والموارد المائية &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; المحترم<br>
        <span style="font-size: 10pt; font-weight: normal; color: #475569;">
            نسخة للأخ / مدير عام الشؤون المالية &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; المحترم
        </span>
    </div>

    {{-- Formal Arabic Request Wording --}}
    <div class="official-body-text">
        <p style="margin-bottom: 6px; font-weight: bold;">تحية طيبة وبعد ،،،</p>

        @if($unifiedRequest->request_type === 'clearance_payment')
            <p><strong>الموضوع: طلب إخلاء عهدة وصرف دفعة للمشروع</strong></p>
            <p>
                نتقدم إليكم بطلب إخلاء العهدة المالية المتعلقة بالمشروع الموضح بياناته أدناه، وذلك وفقًا للمصروفات الفعلية والمستندات المؤيدة المرفقة، كما نرجو التكرم بالنظر في اعتماد صرف دفعة جديدة للمشروع، وفقًا للموازنة المعتمدة والضوابط والإجراءات المالية المنظمة.
            </p>
            <p>
                ونؤكد أن البيانات والمبالغ الموضحة في هذا الطلب تستند إلى السجلات والمستندات المالية الخاصة بالمشروع، راجين اتخاذ ما يلزم من إجراءات المراجعة والاعتماد.
            </p>
        @elseif($unifiedRequest->request_type === 'clearance_closure')
            <p><strong>الموضوع: طلب إخلاء عهدة وإغلاق المشروع</strong></p>
            <p>
                نتقدم إليكم بطلب استكمال إجراءات إخلاء العهد المالية المتعلقة بالمشروع الموضح بياناته أدناه، والنظر في استكمال إجراءات الإغلاق المالي والإداري للمشروع، وذلك بعد مراجعة المصروفات والمستندات المؤيدة والتحقق من التسويات المالية والالتزامات القائمة.
            </p>
            <p>
                وعليه، نرجو التكرم باتخاذ الإجراءات اللازمة للمراجعة والتسوية والاعتماد، تمهيدًا لإغلاق المشروع وفقًا للوائح والضوابط المعتمدة.
            </p>
        @elseif($unifiedRequest->request_type === 'clearance')
            <p><strong>الموضوع: طلب إخلاء عهدة مالية</strong></p>
            <p>
                نتقدم إليكم بطلب إخلاء العهدة المالية المتعلقة بالمشروع الموضح بياناته أدناه، والتي تم استخدامها لتغطية المصروفات المرتبطة بتنفيذ أنشطة المشروع وفقًا للأغراض المعتمدة.
            </p>
            <p>
                ونرفق لكم تفاصيل المصروفات والمستندات المؤيدة، راجين التكرم بمراجعتها واتخاذ الإجراءات اللازمة لإخلاء العهدة وتسوية الرصيد وفقًا للأنظمة والضوابط المالية المعمول بها.
            </p>
        @else
            <p><strong>الموضوع: طلب صرف دفعة مالية للمشروع</strong></p>
            <p>
                نتقدم إليكم بطلب الموافقة على صرف الدفعة المالية الموضحة بياناتها أدناه، وذلك لتغطية الاحتياجات المالية المرتبطة بتنفيذ أنشطة المشروع، وفقًا للموازنة المعتمدة وخطة التنفيذ والضوابط المالية المنظمة.
            </p>
            <p>
                ونرجو التكرم بمراجعة الطلب واتخاذ ما يلزم من إجراءات الاعتماد والصرف، مع مراعاة الأرصدة المالية والدفعات السابقة والالتزامات القائمة.
            </p>
        @endif
    </div>

    {{-- Submitting Entity & Submitting User (Mandatory in all documents) --}}
    <div class="section-header">
        <i class="fas fa-id-card me-1"></i> بيانات الجهة والمستخدم مقدم الطلب
    </div>
    <table class="info-table">
        <tr>
            <th>الجهة المقدمة للطلب:</th>
            <td style="font-weight: bold; color: #0f766e;">{{ $unifiedRequest->submitting_organization_name }}</td>
            <th>نوع الجهة:</th>
            <td>{{ $unifiedRequest->submitting_type === 'external' ? 'جهة خارجية' : 'جهة داخلية' }}</td>
        </tr>
        <tr>
            <th>المستخدم مقدم الطلب:</th>
            <td style="font-weight: bold;">{{ $unifiedRequest->submitting_user_name }}</td>
            <th>الصفة الوظيفية:</th>
            <td>{{ $unifiedRequest->submittingUser?->work ?: ($unifiedRequest->submittingUser?->department ?: 'مسؤول الطلب') }}</td>
        </tr>
    </table>

    {{-- Project Information --}}
    <div class="section-header">
        <i class="fas fa-folder-open me-1"></i> بيانات المشروع
    </div>
    <table class="info-table">
        <tr>
            <th>اسم المشروع:</th>
            <td colspan="3" style="font-weight: bold;">{{ $unifiedRequest->project?->project_name }}</td>
        </tr>
        <tr>
            <th>رقم / رمز المشروع:</th>
            <td>{{ $unifiedRequest->project?->form_number ?: ('#'.$unifiedRequest->project_id) }}</td>
            <th>موازنة المشروع المعتمدة:</th>
            <td class="highlight-amount">{{ number_format($unifiedRequest->project?->total_cost ?: 0, 2) }} ريال يمني</td>
        </tr>
        <tr>
            <th>إجمالي المنصرف حتى الآن:</th>
            <td>{{ number_format($unifiedRequest->project?->total_actually_disbursed ?: 0, 2) }} ريال يمني</td>
            <th>المتبقي من الموازنة:</th>
            <td>{{ number_format(max(0, ($unifiedRequest->project?->total_cost ?: 0) - ($unifiedRequest->project?->total_actually_disbursed ?: 0)), 2) }} ريال يمني</td>
        </tr>
    </table>

    {{-- Clearance Section (If Applicable) --}}
    @if($unifiedRequest->hasClearanceComponent())
        <div class="section-header">
            <i class="fas fa-clipboard-check me-1"></i> بيانات إخلاء العهدة المالية السابقة
        </div>
        <table class="financial-table">
            <thead>
                <tr>
                    <th>الدفعة المعنية</th>
                    <th>مستلم العهدة</th>
                    <th>مبلغ العهدة المستلم</th>
                    <th>المبلغ المقدم للإخلاء</th>
                    <th>المبلغ المتبقي</th>
                    <th>نسبة الإنجاز الفني</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>دفعة رقم {{ $unifiedRequest->clearanceInstallment?->sequence_number ?: '1' }}</td>
                    <td>{{ $unifiedRequest->advance_recipient ?: ($unifiedRequest->submitting_user_name) }}</td>
                    <td>{{ number_format($unifiedRequest->original_advance_amount, 2) }} ريال</td>
                    <td class="highlight-amount">{{ number_format($unifiedRequest->cleared_amount, 2) }} ريال</td>
                    <td style="font-weight: bold;">{{ number_format($unifiedRequest->remaining_advance_amount, 2) }} ريال</td>
                    <td><strong>{{ $unifiedRequest->technical_progress_percentage }}%</strong></td>
                </tr>
            </tbody>
        </table>

        @if($arabicClearedAmount)
            <div style="font-size: 9.5pt; color: #475569; margin-top: -8px; margin-bottom: 12px; padding: 4px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
                <strong>المبلغ المخلى كتابةً:</strong> {{ $arabicClearedAmount }}
            </div>
        @endif
    @endif

    {{-- Payment Section (If Applicable) --}}
    @if($unifiedRequest->hasPaymentComponent())
        <div class="section-header">
            <i class="fas fa-hand-holding-usd me-1"></i> بيانات الدفعة المالية المطلوب صرفها
        </div>
        <table class="financial-table">
            <thead>
                <tr>
                    <th>رقم الدفعة</th>
                    <th>المبلغ المطلوب صرفه</th>
                    <th>مستلم الدفعة</th>
                    <th>تاريخ الإخلاء المتوقع</th>
                    <th>مبررات الصرف</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>دفعة رقم {{ $unifiedRequest->payment_number ?: ($unifiedRequest->paymentInstallment?->sequence_number ?: '2') }}</td>
                    <td class="highlight-amount">{{ number_format($unifiedRequest->requested_payment_amount, 2) }} ريال</td>
                    <td>{{ $unifiedRequest->payment_recipient ?: ($unifiedRequest->submitting_organization_name) }}</td>
                    <td>{{ $unifiedRequest->expected_clearance_date?->format('Y-m-d') ?: '—' }}</td>
                    <td style="text-align: right; font-size: 9pt;">{{ Str::limit($unifiedRequest->payment_justification ?: 'تغطية احتياجات تنفيذ أنشطة المشروع المعتمدة', 60) }}</td>
                </tr>
            </tbody>
        </table>

        @if($arabicPaymentAmount)
            <div style="font-size: 9.5pt; color: #475569; margin-top: -8px; margin-bottom: 12px; padding: 4px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
                <strong>المبلغ المطلوب صرفه كتابةً:</strong> {{ $arabicPaymentAmount }}
            </div>
        @endif
    @endif

    {{-- Closure Section (If Applicable) --}}
    @if($unifiedRequest->hasClosureComponent())
        <div class="section-header">
            <i class="fas fa-lock me-1"></i> بيانات التسوية الختامية وإغلاق المشروع
        </div>
        <table class="info-table">
            <tr>
                <th>تاريخ اكتمال الأنشطة:</th>
                <td>{{ $unifiedRequest->project_completion_date?->format('Y-m-d') ?: date('Y-m-d') }}</td>
                <th>حالة العهد السابقة:</th>
                <td style="color: #059669; font-weight: bold;">تم إخلاء وتصفية كافة العهد المالية بالكامل</td>
            </tr>
            <tr>
                <th>مبررات الإغلاق:</th>
                <td colspan="3">{{ $unifiedRequest->closure_justification ?: 'تم استكمال كافة أهداف وأنشطة المشروع وتصفية المستحقات المالية.' }}</td>
            </tr>
        </table>
    @endif

    {{-- Official Signatures Grid --}}
    @if($unifiedRequest->approvalSteps && $unifiedRequest->approvalSteps->isNotEmpty())
        <div class="signatures-grid" style="grid-template-columns: repeat({{ min(4, max(2, $unifiedRequest->approvalSteps->count())) }}, 1fr);">
            @foreach($unifiedRequest->approvalSteps as $step)
                <div class="signature-box">
                    <div class="signature-title">{{ $step->stage_name ?: ('مرحلة ' . $step->step_order) }}</div>
                    <div class="signature-name">
                        @if($step->reviewedByUser)
                            {{ $step->reviewedByUser->name }}<br>
                            <span style="font-size: 8.5pt; color: #64748b;">{{ $step->entity?->name ?: ($step->authority?->name ?: 'جهة المراجعة') }}</span>
                        @elseif($step->assignedUser)
                            {{ $step->assignedUser->name }}<br>
                            <span style="font-size: 8.5pt; color: #64748b;">(مكلف بالمراجعة)</span>
                        @else
                            {{ $step->entity?->name ?: ($step->authority?->name ?: 'صاحب الصلاحية') }}<br>
                            <span style="font-size: 8.5pt; color: #64748b;">المختص بالاعتماد</span>
                        @endif
                    </div>
                    <div class="signature-line">
                        @if($step->status === 'approved')
                            <span style="color: #059669; font-weight: bold;">
                                <i class="fas fa-check-circle me-1"></i> معتمد ({{ $step->reviewed_at?->format('Y-m-d') }})
                            </span>
                        @elseif($step->status === 'rejected')
                            <span style="color: #dc2626; font-weight: bold;">
                                <i class="fas fa-times-circle me-1"></i> مرفوض ({{ $step->reviewed_at?->format('Y-m-d') }})
                            </span>
                        @elseif($step->status === 'pending')
                            <span style="color: #d97706; font-weight: bold;">
                                قيد الانتظار والمراجعة
                            </span>
                        @else
                            التوقيع والاعتماد: .......................
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="signatures-grid">
            <div class="signature-box">
                <div class="signature-title">المستخدم مقدم الطلب</div>
                <div class="signature-name">
                    {{ $unifiedRequest->submitting_user_name }}<br>
                    <span style="font-size: 8.5pt; color: #64748b;">{{ $unifiedRequest->submitting_organization_name }}</span>
                </div>
                <div class="signature-line">التوقيع والختم: .......................</div>
            </div>

            <div class="signature-box">
                <div class="signature-title">مراجعة الشؤون المالية</div>
                <div class="signature-name">
                    الإدارة المالية العامة<br>
                    <span style="font-size: 8.5pt; color: #64748b;">المراجعة والتدقيق المحاسبي</span>
                </div>
                <div class="signature-line">التوقيع والتاريخ: .......................</div>
            </div>

            <div class="signature-box">
                <div class="signature-title">اعتماد الصرف / الإخلاء</div>
                <div class="signature-name">
                    {{ $unifiedRequest->approvedBy?->name ?: 'وزير الزراعة والثروة السمكية' }}<br>
                    <span style="font-size: 8.5pt; color: #64748b;">
                        {{ $unifiedRequest->isApproved() ? 'مُعتمد رسمياً في النظام' : 'صاحب الصلاحية للاعتماد' }}
                    </span>
                </div>
                <div class="signature-line">
                    @if($unifiedRequest->isApproved())
                        <span style="color: #059669; font-weight: bold;"><i class="fas fa-stamp me-1"></i> معتمد إلكترونياً ({{ $unifiedRequest->approved_at?->format('Y-m-d') }})</span>
                    @else
                        التوقيع والاعتماد: .......................
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Official Footer & Verification Barcode --}}
    <div class="barcode-strip">
        <div>
            كود التحقق الرقمي الموحد: <strong>{{ md5($unifiedRequest->request_number . '-' . $unifiedRequest->created_at) }}</strong>
        </div>
        <div>
            تاريخ الطباعة: {{ $printDate }} &nbsp;|&nbsp; نظام إدارة المشاريع الموحد
        </div>
    </div>

    {{-- Official Letterhead Footer --}}
    <x-letterhead-footer :letterhead="$letterhead" />

</div>

</body>
</html>
