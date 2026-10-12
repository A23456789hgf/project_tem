@extends('layouts.app')

@section('styles')
<style>
    :root {
        --primary-dark: #08214c;
        --primary-light: #0d2d5e;
        --gold-color: #d4af37;
        --light-bg: #f8fafc;
        --dark-text: #1e293b;
        --transition: all 0.25s ease;
    }

    .quality-container {
        padding: 1.5rem;
        background: var(--light-bg);
        min-height: calc(100vh - 65px);
        margin-left: 0;
    }

    .quality-header {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        border-left: 5px solid var(--gold-color);
    }

    .quality-header h1 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--gold-color);
    }

    .quality-header .project-info {
        display: flex;
        gap: 2rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .quality-header .info-badge {
        background: rgba(212, 175, 55, 0.15);
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-size: 0.95rem;
        border: 1px solid rgba(212, 175, 55, 0.3);
        color: #f0f0f0;
    }

    .quality-header .info-badge i {
        color: var(--gold-color);
        margin-left: 0.5rem;
    }

    /* Statistics Cards */
    .statistics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.75rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border-left: 5px solid;
        border-top: 1px solid #e2e8f0;
        transition: var(--transition);
    }

    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }

    .stat-card.positive {
        border-left-color: #10b981;
        background: linear-gradient(135deg, white 0%, #f0fdf4 100%);
    }

    .stat-card.negative {
        border-left-color: #ef4444;
        background: linear-gradient(135deg, white 0%, #fef2f2 100%);
    }

    .stat-card.neutral {
        border-left-color: var(--gold-color);
        background: linear-gradient(135deg, white 0%, #fefbf3 100%);
    }

    .stat-card .stat-label {
        font-size: 0.95rem;
        color: #6b7280;
        margin-bottom: 0.75rem;
        font-weight: 500;
    }

    .stat-card .stat-value {
        font-size: 2.25rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 0.5rem;
    }

    .stat-card .stat-percentage {
        font-size: 0.9rem;
        color: #10b981;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    .stat-card.negative .stat-percentage {
        color: #ef4444;
    }

    /* Activity Sections */
    .activity-section {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-bottom: 3px solid var(--gold-color);
        padding-bottom: 0.75rem;
    }

    .section-title i {
        color: var(--gold-color);
        font-size: 1.3rem;
    }

    /* Activity Cards */
    .activity-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        overflow: hidden;
        background: #f9fafb;
    }

    .activity-header {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        border-bottom: 1px solid #d1d5db;
        padding: 1rem 1.5rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }

    .activity-header:hover {
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    }

    .activity-header .activity-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #003d7a;
    }

    .activity-header .activity-weight {
        background: #007bff;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
    }

    .activity-body {
        padding: 1.5rem;
        display: none;
    }

    .activity-body.show {
        display: block;
    }

    /* Procedure/Action Cards */
    .procedure-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 1rem;
        background: #fafafa;
        overflow: hidden;
        transition: var(--transition);
    }

    .procedure-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .procedure-header {
        padding: 1.25rem;
        background: white;
        border-bottom: 1px solid #e5e7eb;
    }

    .procedure-name {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 0.75rem;
        font-size: 1rem;
    }

    .procedure-dates {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.25rem;
        font-size: 0.9rem;
        color: #6b7280;
    }

    .procedure-body {
        padding: 1.25rem;
        background: #f9f9f9;
    }

    /* Quality Assessment */
    .quality-assessment {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1rem;
    }

    .quality-box {
        border: 2px solid;
        border-radius: 8px;
        padding: 1.25rem;
        transition: var(--transition);
    }

    .quality-box.positive {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .quality-box.negative {
        border-color: #ef4444;
        background: #fef2f2;
    }

    .quality-box.neutral {
        border-color: var(--gold-color);
        background: #fffbf0;
    }

    .quality-box:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .quality-title {
        font-weight: 700;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
    }

    .quality-title i {
        font-size: 1.25rem;
    }

    .quality-box.positive .quality-title {
        color: #047857;
    }

    .quality-box.negative .quality-title {
        color: #b91c1c;
    }

    .quality-box.neutral .quality-title {
        color: #92400e;
    }

    .quality-details {
        font-size: 0.9rem;
    }

    .quality-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .quality-row:last-child {
        border-bottom: none;
    }

    .quality-label {
        font-weight: 600;
    }

    .quality-value {
        text-align: left;
    }

    /* Status Badges */
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-badge.ahead {
        background: #10b981;
        color: white;
    }

    .status-badge.on-time {
        background: #0891b2;
        color: white;
    }

    .status-badge.late {
        background: #ef4444;
        color: white;
    }

    .status-badge.below-plan {
        background: #10b981;
        color: white;
    }

    .status-badge.matching-plan {
        background: #0891b2;
        color: white;
    }

    .status-badge.over-plan {
        background: #ef4444;
        color: white;
    }

    /* Solution Form */
    .solution-form {
        margin-top: 1.5rem;
        padding: 1.5rem;
        background: #fef2f2;
        border: 2px dashed #ef4444;
        border-radius: 8px;
    }

    .solution-form h6 {
        color: #b91c1c;
        font-weight: 700;
        margin-bottom: 1rem;
        font-size: 1rem;
    }

    .solution-form textarea {
        width: 100%;
        min-height: 120px;
        padding: 0.75rem;
        border: 1px solid #fca5a5;
        border-radius: 6px;
        font-size: 0.95rem;
        resize: vertical;
        font-family: inherit;
    }

    .solution-form textarea:focus {
        border-color: #ef4444;
        outline: none;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .solution-form .file-upload {
        margin-top: 1rem;
    }

    .solution-form .btn-save {
        margin-top: 1rem;
        background: var(--primary-dark);
        color: var(--gold-color);
        border: none;
        padding: 0.65rem 1.75rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        font-size: 0.95rem;
    }

    .solution-form .btn-save:hover {
        background: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* No Data */
    .no-data {
        text-align: center;
        padding: 3.5rem 2rem;
        color: #9ca3af;
        font-style: italic;
    }

    .no-data i {
        font-size: 4rem;
        margin-bottom: 1.5rem;
        display: block;
        color: #d1d5db;
        opacity: 0.7;
    }

    .no-data p {
        font-size: 1.1rem;
        margin: 0.5rem 0;
    }

    /* Back Button */
    .back-button-container {
        text-align: center;
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid #e5e7eb;
    }

    .back-button-container .btn {
        background: var(--primary-dark);
        color: var(--gold-color);
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
    }

    .back-button-container .btn:hover {
        background: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        color: var(--gold-color);
    }

    /* Responsive */
    @media (max-width: 992px) {
        .quality-container {
            padding: 1.25rem;
        }

        .quality-header {
            padding: 1.5rem;
        }

        .quality-header h1 {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .quality-container {
            padding: 1rem;
        }

        .quality-header {
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .quality-header h1 {
            font-size: 1.25rem;
        }

        .quality-header .project-info {
            flex-direction: column;
            gap: 0.75rem;
        }

        .quality-assessment {
            grid-template-columns: 1fr;
        }

        .statistics-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .stat-card {
            padding: 1.5rem;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
        }

        .procedure-dates {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .activity-section {
            padding: 1.5rem;
        }

        .section-title {
            font-size: 1.1rem;
        }
    }
</style>
@endsection

@section('content')
<div class="quality-container">
    <!-- Header -->
    <div class="quality-header d-flex justify-content-between align-items-start flex-wrap">
        <div>
            <h1><i class="fas fa-chart-line me-2"></i>إدارة جودة المشروع</h1>
            <div class="project-info">
                <div class="info-badge">
                    <i class="fas fa-hashtag me-1"></i>
                    رقم المشروع: {{ $project->form_number ?? 'غير محدد' }}
                </div>
                <div class="info-badge">
                    <i class="fas fa-project-diagram me-1"></i>
                    {{ $project->project_name ?? 'غير محدد' }}
                </div>
            </div>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="{{ route('quality.print', ['report_scope' => 'single', 'single_project_id' => $project->id]) }}" target="_blank" class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                <i class="fas fa-print"></i> طباعة التقرير
            </a>
            <a href="{{ route('quality.export', ['report_scope' => 'single', 'single_project_id' => $project->id]) }}" class="btn" style="background: var(--gold-color); color: var(--primary-dark); border: none; font-weight: bold;">
                <i class="fas fa-file-excel"></i> تصدير Excel
            </a>
        </div>
    </div>

    <!-- Statistics Summary -->
    <div class="statistics-grid">
        <div class="stat-card neutral">
            <div class="stat-label">إجمالي العناصر المقيّمة</div>
            <div class="stat-value">{{ $statistics['total_items'] }}</div>
        </div>
        
        <div class="stat-card positive">
            <div class="stat-label">الجودة الزمنية الإيجابية</div>
            <div class="stat-value">{{ $statistics['time_positive'] }}</div>
            <div class="stat-percentage">{{ $statistics['time_positive_percentage'] }}%</div>
        </div>
        
        <div class="stat-card negative">
            <div class="stat-label">الجودة الزمنية السلبية</div>
            <div class="stat-value">{{ $statistics['time_negative'] }}</div>
        </div>
        
        <div class="stat-card positive">
            <div class="stat-label">الجودة المالية الإيجابية</div>
            <div class="stat-value">{{ $statistics['financial_positive'] }}</div>
            <div class="stat-percentage">{{ $statistics['financial_positive_percentage'] }}%</div>
        </div>
        
        <div class="stat-card negative">
            <div class="stat-label">الجودة المالية السلبية</div>
            <div class="stat-value">{{ $statistics['financial_negative'] }}</div>
        </div>
        
        <div class="stat-card neutral">
            <div class="stat-label">درجة الجودة الإجمالية</div>
            <div class="stat-value">{{ $statistics['overall_quality_score'] }}%</div>
        </div>
    </div>

    <!-- Preliminary Activities -->
    @if(count($preliminaryQuality) > 0)
    <div class="activity-section">
        <div class="section-title">
            <i class="fas fa-tasks"></i>
            الأنشطة التمهيدية
        </div>

        @php
            $groupedPreliminary = collect($preliminaryQuality)->groupBy(function($item) {
                return $item['activity']->id;
            });
        @endphp

        @foreach($groupedPreliminary as $activityId => $procedures)
            @php
                $activity = $procedures->first()['activity'];
            @endphp
            
            <div class="activity-card">
                <div class="activity-header" onclick="toggleActivity(this)">
                    <div class="activity-name">
                        <i class="fas fa-chevron-down me-2"></i>
                        {{ $activity->name }}
                    </div>
                    <div class="activity-weight">الوزن: {{ $activity->weight }}%</div>
                </div>
                
                <div class="activity-body">
                    @foreach($procedures as $item)
                        @php
                            $procedure = $item['procedure'];
                            $execution = $item['execution'];
                            $timeQuality = $item['time_quality'];
                            $financialQuality = $item['financial_quality'];
                        @endphp
                        
                        <div class="procedure-card">
                            <div class="procedure-header">
                                <div class="procedure-name">
                                    <i class="fas fa-clipboard-check me-2"></i>
                                    {{ $procedure->procedure_name }}
                                </div>
                                <div class="procedure-dates">
                                    <div>
                                        <strong>تاريخ البداية المخطط:</strong> {{ $procedure->start_date ?? 'غير محدد' }}
                                    </div>
                                    <div>
                                        <strong>تاريخ النهاية المخطط:</strong> {{ $procedure->end_date ?? 'غير محدد' }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="procedure-body">
                                <div class="quality-assessment">
                                    <!-- Time Quality -->
                                    <div class="quality-box {{ $timeQuality['quality'] ?? 'neutral' }}">
                                        <div class="quality-title">
                                            <i class="fas fa-clock"></i>
                                            الجودة الزمنية
                                        </div>
                                        <div class="quality-details">
                                            @if($timeQuality['status'])
                                                <div class="quality-row">
                                                    <span class="quality-label">الحالة:</span>
                                                    <span class="quality-value">
                                                        <span class="status-badge {{ $timeQuality['status'] }}">
                                                            {{ $timeQuality['details'] }}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">البداية الفعلية:</span>
                                                    <span class="quality-value">{{ $timeQuality['actual_start'] ?? 'غير محدد' }}</span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">النهاية الفعلية:</span>
                                                    <span class="quality-value">{{ $timeQuality['actual_end'] ?? 'غير محدد' }}</span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">الفرق:</span>
                                                    <span class="quality-value">{{ abs($timeQuality['variance_days'] ?? 0) }} يوم</span>
                                                </div>
                                            @else
                                                <p class="text-muted">{{ $timeQuality['details'] }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Financial Quality -->
                                    <div class="quality-box {{ $financialQuality['quality'] ?? 'neutral' }}">
                                        <div class="quality-title">
                                            <i class="fas fa-dollar-sign"></i>
                                            الجودة المالية
                                        </div>
                                        <div class="quality-details">
                                            @if($financialQuality['status'])
                                                <div class="quality-row">
                                                    <span class="quality-label">الحالة:</span>
                                                    <span class="quality-value">
                                                        <span class="status-badge {{ $financialQuality['status'] }}">
                                                            {{ $financialQuality['details'] }}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">المبلغ المخطط:</span>
                                                    <span class="quality-value">{{ number_format($financialQuality['planned_amount'] ?? 0, 2) }} ريال</span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">المبلغ الفعلي:</span>
                                                    <span class="quality-value">{{ number_format($financialQuality['actual_amount'] ?? 0, 2) }} ريال</span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">الفرق:</span>
                                                    <span class="quality-value">{{ number_format(abs($financialQuality['variance_amount'] ?? 0), 2) }} ريال</span>
                                                </div>
                                            @else
                                                <p class="text-muted">{{ $financialQuality['details'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Solution Form for Negative Quality -->
                                @if(($timeQuality['quality'] ?? null) === 'negative' || ($financialQuality['quality'] ?? null) === 'negative')
                                    @can('quality.edit')
                                    <div class="solution-form">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>الحل المقترح للحالة السلبية</h6>
                                        <form class="quality-solution-form" data-type="preliminary" data-procedure-id="{{ $procedure->id }}" data-activity-id="{{ $activity->id }}">
                                            @csrf
                                            <textarea name="proposed_solution" placeholder="اكتب الحل المقترح هنا..."></textarea>
                                            
                                            <div class="file-upload">
                                                <label><i class="fas fa-paperclip me-2"></i>إرفاق ملفات داعمة (اختياري):</label>
                                                <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-control mt-2">
                                            </div>
                                            
                                            <button type="submit" class="btn-save">
                                                <i class="fas fa-save me-2"></i>حفظ الحل
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                    <div class="alert alert-info mt-3 py-2 border-0 bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-info-circle me-1"></i>يرجى التواصل مع الإدارة لإضافة الحلول المقترحة (لا تمتلك صلاحية التعديل).
                                    </div>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Executive Activities -->
    @if(count($executiveQuality) > 0)
    <div class="activity-section">
        <div class="section-title">
            <i class="fas fa-cogs"></i>
            الأنشطة التنفيذية
        </div>

        @php
            $groupedExecutive = collect($executiveQuality)->groupBy(function($item) {
                return $item['activity']->id;
            });
        @endphp

        @foreach($groupedExecutive as $activityId => $actions)
            @php
                $activity = $actions->first()['activity'];
            @endphp
            
            <div class="activity-card">
                <div class="activity-header" onclick="toggleActivity(this)">
                    <div class="activity-name">
                        <i class="fas fa-chevron-down me-2"></i>
                        {{ $activity->name }}
                    </div>
                    <div class="activity-weight">الوزن: {{ $activity->weight }}%</div>
                </div>
                
                <div class="activity-body">
                    @foreach($actions as $item)
                        @php
                            $action = $item['action'];
                            $execution = $item['execution'];
                            $timeQuality = $item['time_quality'];
                            $financialQuality = $item['financial_quality'];
                        @endphp
                        
                        <div class="procedure-card">
                            <div class="procedure-header">
                                <div class="procedure-name">
                                    <i class="fas fa-tasks me-2"></i>
                                    {{ $action->action }}
                                </div>
                                <div class="procedure-dates">
                                    <div>
                                        <strong>تاريخ البداية المخطط:</strong> {{ $action->start_date ?? 'غير محدد' }}
                                    </div>
                                    <div>
                                        <strong>تاريخ النهاية المخطط:</strong> {{ $action->end_date ?? 'غير محدد' }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="procedure-body">
                                <div class="quality-assessment">
                                    <!-- Time Quality -->
                                    <div class="quality-box {{ $timeQuality['quality'] ?? 'neutral' }}">
                                        <div class="quality-title">
                                            <i class="fas fa-clock"></i>
                                            الجودة الزمنية
                                        </div>
                                        <div class="quality-details">
                                            @if($timeQuality['status'])
                                                <div class="quality-row">
                                                    <span class="quality-label">الحالة:</span>
                                                    <span class="quality-value">
                                                        <span class="status-badge {{ $timeQuality['status'] }}">
                                                            {{ $timeQuality['details'] }}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">البداية الفعلية:</span>
                                                    <span class="quality-value">{{ $timeQuality['actual_start'] ?? 'غير محدد' }}</span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">النهاية الفعلية:</span>
                                                    <span class="quality-value">{{ $timeQuality['actual_end'] ?? 'غير محدد' }}</span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">الفرق:</span>
                                                    <span class="quality-value">{{ abs($timeQuality['variance_days'] ?? 0) }} يوم</span>
                                                </div>
                                            @else
                                                <p class="text-muted">{{ $timeQuality['details'] }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Financial Quality -->
                                    <div class="quality-box {{ $financialQuality['quality'] ?? 'neutral' }}">
                                        <div class="quality-title">
                                            <i class="fas fa-dollar-sign"></i>
                                            الجودة المالية
                                        </div>
                                        <div class="quality-details">
                                            @if($financialQuality['status'])
                                                <div class="quality-row">
                                                    <span class="quality-label">الحالة:</span>
                                                    <span class="quality-value">
                                                        <span class="status-badge {{ $financialQuality['status'] }}">
                                                            {{ $financialQuality['details'] }}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">المبلغ المخطط:</span>
                                                    <span class="quality-value">{{ number_format($financialQuality['planned_amount'] ?? 0, 2) }} ريال</span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">المبلغ الفعلي:</span>
                                                    <span class="quality-value">{{ number_format($financialQuality['actual_amount'] ?? 0, 2) }} ريال</span>
                                                </div>
                                                <div class="quality-row">
                                                    <span class="quality-label">الفرق:</span>
                                                    <span class="quality-value">{{ number_format(abs($financialQuality['variance_amount'] ?? 0), 2) }} ريال</span>
                                                </div>
                                            @else
                                                <p class="text-muted">{{ $financialQuality['details'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Solution Form for Negative Quality -->
                                @if(($timeQuality['quality'] ?? null) === 'negative' || ($financialQuality['quality'] ?? null) === 'negative')
                                    @can('quality.edit')
                                    <div class="solution-form">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>الحل المقترح للحالة السلبية</h6>
                                        <form class="quality-solution-form" data-type="executive" data-action-id="{{ $action->id }}" data-activity-id="{{ $activity->id }}">
                                            @csrf
                                            <textarea name="proposed_solution" placeholder="اكتب الحل المقترح هنا..."></textarea>
                                            
                                            <div class="file-upload">
                                                <label><i class="fas fa-paperclip me-2"></i>إرفاق ملفات داعمة (اختياري):</label>
                                                <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-control mt-2">
                                            </div>
                                            
                                            <button type="submit" class="btn-save">
                                                <i class="fas fa-save me-2"></i>حفظ الحل
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                    <div class="alert alert-info mt-3 py-2 border-0 bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-info-circle me-1"></i>يرجى التواصل مع الإدارة لإضافة الحلول المقترحة (لا تمتلك صلاحية التعديل).
                                    </div>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- No Data Message -->
    @if(count($preliminaryQuality) === 0 && count($executiveQuality) === 0)
        <div class="activity-section">
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <p>لا توجد بيانات تنفيذ متاحة لتقييم الجودة</p>
                <p class="text-muted" style="font-size: 0.9rem;">يرجى إضافة بيانات التنفيذ أولاً من صفحة التنفيذ</p>
            </div>
        </div>
    @endif

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="{{ route('projects.show', $project->id) }}" class="btn">
            <i class="fas fa-arrow-right"></i>العودة إلى المشروع
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Toggle Activity Accordion
    function toggleActivity(header) {
        const body = header.nextElementSibling;
        const icon = header.querySelector('i');
        
        body.classList.toggle('show');
        
        if (body.classList.contains('show')) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }

    // Handle Solution Form Submission
    document.querySelectorAll('.quality-solution-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const type = this.dataset.type;
            const activityId = this.dataset.activityId;
            const procedureOrActionId = type === 'preliminary' ? this.dataset.procedureId : this.dataset.actionId;
            
            // Add additional data
            formData.append('project_id', '{{ $project->id }}');
            formData.append('record_type', type);
            formData.append('activity_id', activityId);
            formData.append('procedure_or_action_id', procedureOrActionId);
            formData.append('quality_aspect', 'time'); // You can determine this based on which quality is negative
            formData.append('quality_status', 'negative');
            
            try {
                const response = await fetch('{{ route("projects.quality.store", $project->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('تم حفظ الحل المقترح بنجاح');
                    this.reset();
                } else {
                    alert('حدث خطأ: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ أثناء حفظ البيانات');
            }
        });
    });
</script>
@endsection
