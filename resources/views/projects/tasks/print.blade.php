<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة المهمة | {{ $task->title }}</title>
    <x-letterhead-styles :letterhead="$letterhead ?? null" />
    <style>
        :root {
            --primary: {{ $letterhead?->primary_color ?? '#0C5B47' }};
            --primary-light: #e6eeec;
            --border-dark: #222;
            --text-dark: #1a1a1a;
            --text-muted: #555;
        }

        @page {
            size: A4 portrait;
            margin: 20mm 15mm 20mm 15mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background-color: white;
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
            background: #f5f5f5;
            color: var(--text-dark);
            line-height: 1.5;
        }

        .print-container {
            background: white;
            width: 190mm;
            margin: 0 auto;
            padding: 5px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header-right,
        .header-left {
            width: 30%;
        }

        .header-center {
            width: 40%;
            text-align: center;
        }

        .header-right {
            font-weight: 700;
            font-size: 11pt;
            line-height: 1.6;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            text-align: left;
        }

        .logo {
            max-height: 80px;
            margin-bottom: 5px;
        }

        h1, h3 {
            font-size: 16pt;
            color: var(--primary);
            margin: 5px 0;
            font-weight: 800;
        }

        .section-title {
            font-size: 14pt;
            color: var(--primary);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 15px;
            font-weight: 800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: var(--primary-light) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--border-dark);
            padding: 10px;
            font-size: 10pt;
            text-align: right;
            width: 25%;
        }

        td {
            border: 1px solid var(--border-dark);
            padding: 10px;
            font-size: 10pt;
            text-align: right;
            vertical-align: middle;
        }

        ul {
            list-style-type: disc;
            padding-right: 20px;
            margin: 0;
        }

        li {
            margin-bottom: 5px;
            font-size: 10pt;
        }

        .floating-actions {
            position: fixed;
            bottom: 25px;
            left: 25px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Cairo';
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: 0.3s;
        }

        .btn-print {
            background: var(--primary);
            color: white;
        }

        .btn-close {
            background: #555;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
    </style>
</head>

<body>

    <div class="print-container">
        <x-letterhead-selector :currentLetterhead="$letterhead ?? null" />
        @php
            $headerRightLines = !empty($letterhead?->header_right_text) ? explode("\n", $letterhead->header_right_text) : [
                'الجمهورية اليمنية',
                $letterhead?->organization_name ?? 'وزارة الزراعة والثروة السمكية والموارد المائية',
            ];
            $taskLogoUrl = !empty($letterhead?->logo_path) ? asset('storage/' . $letterhead->logo_path) : asset('images/logo.png');
        @endphp
        <div class="header-section">
            <div class="header-right">
                @foreach($headerRightLines as $line)
                    @if(trim($line))
                        {{ trim($line) }}<br>
                    @endif
                @endforeach
            </div>
            <div class="header-center">
                <img src="{{ $taskLogoUrl }}" alt="Logo" class="logo"><br>
                <h3>تفاصيل المهمة</h3>
            </div>
            <div class="header-left" style="align-items: center; justify-content: center;">
                <span style="font-size: 10pt; margin-top: 5px; font-weight: 600;">تاريخ الطباعة:<br>{{ now()->format('Y-m-d') }}</span>
            </div>
        </div>

        <div class="section-title">المعلومات الأساسية</div>
        <table>
            <tr>
                <th>عنوان المهمة</th>
                <td>{{ $task->title }}</td>
            </tr>
            <tr>
                <th>الوصف والتفاصيل</th>
                <td>{{ $task->description ?? 'لا يوجد' }}</td>
            </tr>
            <tr>
                <th>المشروع</th>
                <td>{{ $project->project_name ?? 'بدون مشروع' }}</td>
            </tr>
            <tr>
                <th>تاريخ الاستحقاق</th>
                <td>{{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') : 'غير محدد' }}</td>
            </tr>
            <tr>
                <th>تاريخ الإنجاز</th>
                <td>{{ $task->completed_at ? \Carbon\Carbon::parse($task->completed_at)->format('Y-m-d') : 'لم تكتمل بعد' }}</td>
            </tr>
            <tr>
                <th>الحالة</th>
                <td>
                    @php
                        $statuses = [
                            'todo' => 'قيد الانتظار',
                            'in_progress' => 'قيد التنفيذ',
                            'completed' => 'مكتملة',
                            'cancelled' => 'ملغاة'
                        ];
                    @endphp
                    {{ $statuses[$task->status] ?? $task->status }}
                </td>
            </tr>
            <tr>
                <th>الأولوية</th>
                <td>
                    @php
                        $priorities = [
                            'low' => 'منخفضة',
                            'medium' => 'متوسطة',
                            'high' => 'عالية',
                            'urgent' => 'عاجلة'
                        ];
                    @endphp
                    {{ $priorities[$task->priority] ?? $task->priority }}
                </td>
            </tr>
            <tr>
                <th>منشئ المهمة</th>
                <td>{{ $task->createdBy->name ?? 'غير معروف' }}</td>
            </tr>
        </table>

        @if($task->projectEntity || $task->executiveAction || $task->preliminaryActivity || $task->executiveActivity)
        <div class="section-title">الارتباطات</div>
        <table>
            @if($task->projectEntity)
            <tr>
                <th>الكيان المسند إليه</th>
                <td>{{ $task->projectEntity->entity_name }}</td>
            </tr>
            @endif
            
            @if($task->preliminaryActivity)
            <tr>
                <th>النشاط التمهيدي</th>
                <td>{{ $task->preliminaryActivity->name }}</td>
            </tr>
            @endif

            @if($task->executiveActivity)
            <tr>
                <th>النشاط التنفيذي</th>
                <td>{{ $task->executiveActivity->name }}</td>
            </tr>
            @endif

            @if($task->preliminaryProcedure)
            <tr>
                <th>الإجراء التمهيدي</th>
                <td>{{ $task->preliminaryProcedure->procedure_name }}</td>
            </tr>
            @endif

            @if($task->executiveAction)
            <tr>
                <th>الإجراء التنفيذي</th>
                <td>{{ $task->executiveAction->action }}</td>
            </tr>
            @endif
        </table>
        @endif

        <div class="section-title">المسند إليهم</div>
        @if($task->assignees->count() > 0)
        <table>
            <tr>
                <td style="padding: 15px;">
                    <ul>
                        @foreach($task->assignees as $assignee)
                            <li>{{ $assignee->name }} ({{ $assignee->entity->name ?? 'بدون جهة' }})</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        </table>
        @else
        <p style="text-align: center; color: var(--text-muted);">لا يوجد مسند إليهم لهذه المهمة.</p>
        @endif

        @if($task->discussions->count() > 0)
        <div class="section-title">المناقشات</div>
        <table>
            @foreach($task->discussions as $discussion)
            <tr>
                <td style="background-color: #fcfcfc; border-bottom: 0;">
                    <strong>{{ $discussion->user?->name ?? 'مستخدم' }}:</strong> 
                    <span style="font-size: 8pt; color: #888; float: left;">{{ \Carbon\Carbon::parse($discussion->created_at)->format('Y-m-d H:i') }}</span>
                </td>
            </tr>
            <tr>
                <td style="border-top: 0; padding-bottom: 15px; color: #444;">
                    {{ $discussion->message }}
                </td>
            </tr>
            @endforeach
        </table>
        @endif

        <div style="margin-top: 50px; text-align: center; font-size: 8pt; color: #777; border-top: 1px solid #eee; padding-top: 10px;">
            هذه الوثيقة صادرة عن النظام الإلكتروني لإدارة المشاريع والمهام - تاريخ الاستخراج: {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    <div class="floating-actions no-print">
        <button onclick="window.print()" class="btn btn-print">طباعة الوثيقة</button>
        <button onclick="window.close()" class="btn btn-close">إغلاق</button>
    </div>

</body>
</html>