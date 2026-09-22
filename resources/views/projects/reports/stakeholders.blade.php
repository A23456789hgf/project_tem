@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">تقرير أصحاب المصلحة</h2>
                    <p class="text-muted">{{ $project->project_name }}</p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>طباعة
                    </button>
                    @can('projects.stakeholders.export')
                    <a href="{{ route('projects.stakeholders.export', $project->id) }}" class="btn btn-outline-success">
                        <i class="fas fa-download me-2"></i>تصدير CSV
                    </a>
                    @endcan
                    <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>العودة للمشروع
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Executive Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>الملخص التنفيذي
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-primary">{{ $totalEntities }}</h3>
                                <p class="mb-0">إجمالي الجهات</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-success">{{ $executingEntities }}</h3>
                                <p class="mb-0">الجهات المنفذة</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-warning">{{ $supervisingEntities }}</h3>
                                <p class="mb-0">الجهات المشرفة</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-danger">{{ $fundingEntities }}</h3>
                                <p class="mb-0">الجهات الممولة</p>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-info">{{ $totalTasks }}</h4>
                                <p class="mb-0">إجمالي المهام</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-success">{{ $completedTasks }}</h4>
                                <p class="mb-0">المهام المكتملة</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-primary">{{ round($overallProgress, 1) }}%</h4>
                                <p class="mb-0">معدل الإنجاز العام</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Stakeholders Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>تفاصيل أصحاب المصلحة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-standard table-report table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>اسم الجهة</th>
                                        <th>النوع</th>
                                        <th>المهام الرئيسية</th>
                                        <th>عدد الفرق</th>
                                        <th>المهام المكتملة</th>
                                        <th>معدل الإنجاز</th>
                                        <th>مستوى المخاطر</th>
                                        <th>الشخص المسؤول</th>
                                        <th>معلومات الاتصال</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($entities->where('is_sub_entity', false) as $entity)
                                    @php
                                        $totalTasks = $entity->teamAssignments->count();
                                        $completedTasks = $entity->teamAssignments->where('status', 'completed')->count();
                                        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
                                        
                                        // Risk assessment
                                        $riskLevel = 'منخفض';
                                        $riskColor = 'success';
                                        if($entity->entity_type == 'executing' && $completionRate < 50) {
                                            $riskLevel = 'عالي';
                                            $riskColor = 'danger';
                                        } elseif($completionRate < 25) {
                                            $riskLevel = 'عالي';
                                            $riskColor = 'danger';
                                        } elseif($completionRate < 75) {
                                            $riskLevel = 'متوسط';
                                            $riskColor = 'warning';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $entity->entity_name }}</strong>
                                            @if($entity->subEntities->count() > 0)
                                                <br><small class="text-muted">
                                                    {{ $entity->subEntities->count() }} جهة فرعية
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="table-badge bg-{{ $entity->entity_type == 'executing' ? 'success' : ($entity->entity_type == 'supervising' ? 'warning' : ($entity->entity_type == 'funding' ? 'danger' : 'info')) }}">
                                                @switch($entity->entity_type)
                                                    @case('executing')
                                                        منفذة
                                                        @break
                                                    @case('supervising')
                                                        مشرفة
                                                        @break
                                                    @case('funding')
                                                        ممولة
                                                        @break
                                                    @default
                                                        مشاركة
                                                @endswitch
                                            </span>
                                        </td>
                                        <td>{{ Str::limit($entity->entity_task, 80) }}</td>
                                        <td class="text-center">{{ $totalTasks }}</td>
                                        <td class="text-center">{{ $completedTasks }}</td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 20px; width: 80px;">
                                                <div class="progress-bar bg-{{ $completionRate >= 75 ? 'success' : ($completionRate >= 50 ? 'warning' : 'danger') }}" 
                                                     style="width: {{ $completionRate }}%">
                                                    {{ round($completionRate) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="table-badge bg-{{ $riskColor }}">{{ $riskLevel }}</span>
                                        </td>
                                        <td>{{ $entity->contact_person ?? 'غير محدد' }}</td>
                                        <td>
                                            @if($entity->contact_phone)
                                                <div><i class="fas fa-phone text-muted"></i> {{ $entity->contact_phone }}</div>
                                            @endif
                                            @if($entity->contact_email)
                                                <div><i class="fas fa-envelope text-muted"></i> {{ $entity->contact_email }}</div>
                                            @endif
                                            @if(!$entity->contact_phone && !$entity->contact_email)
                                                <span class="text-muted">غير متوفر</span>
                                            @endif
                                        </td>
                                    </tr>
                                    
                                    <!-- Sub-entities -->
                                    @foreach($entity->subEntities as $subEntity)
                                    @php
                                        $subTotalTasks = $subEntity->teamAssignments->count();
                                        $subCompletedTasks = $subEntity->teamAssignments->where('status', 'completed')->count();
                                        $subCompletionRate = $subTotalTasks > 0 ? ($subCompletedTasks / $subTotalTasks) * 100 : 0;
                                    @endphp
                                    <tr class="table-secondary">
                                        <td>{{ $loop->parent->iteration }}.{{ $loop->iteration }}</td>
                                        <td class="ps-4">
                                            <i class="fas fa-level-up-alt fa-rotate-90 text-muted me-2"></i>
                                            {{ $subEntity->entity_name }}
                                        </td>
                                        <td><span class="table-badge bg-light text-dark">فرعية</span></td>
                                        <td>{{ Str::limit($subEntity->entity_task, 60) }}</td>
                                        <td class="text-center">{{ $subTotalTasks }}</td>
                                        <td class="text-center">{{ $subCompletedTasks }}</td>
                                        <td class="text-center">{{ round($subCompletionRate) }}%</td>
                                        <td class="text-center"><span class="table-badge bg-info">تابعة</span></td>
                                        <td>{{ $subEntity->contact_person ?? 'غير محدد' }}</td>
                                        <td>
                                            @if($subEntity->contact_phone || $subEntity->contact_email)
                                                @if($subEntity->contact_phone)
                                                    <div>{{ $subEntity->contact_phone }}</div>
                                                @endif
                                                @if($subEntity->contact_email)
                                                    <div>{{ $subEntity->contact_email }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted">غير متوفر</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Analysis -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>تحليل المخاطر
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-standard table-report table-sm">
                                <thead>
                                    <tr>
                                        <th>الجهة</th>
                                        <th>نوع المخاطر</th>
                                        <th>مستوى الخطورة</th>
                                        <th>التأثير المحتمل</th>
                                        <th>الإجراءات المقترحة</th>
                                        <th>الأولوية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($entities->where('entity_type', 'executing') as $entity)
                                    @php
                                        $totalTasks = $entity->teamAssignments->count();
                                        $completedTasks = $entity->teamAssignments->where('status', 'completed')->count();
                                        $progress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
                                        
                                        if($progress < 25) {
                                            $riskType = 'تأخير شديد في التنفيذ';
                                            $riskLevel = 'عالي';
                                            $riskColor = 'danger';
                                            $impact = 'تأثير كبير على الجدول الزمني';
                                            $actions = 'مراجعة فورية للخطة وإعادة توزيع الموارد';
                                            $priority = 'عاجل';
                                        } elseif($progress < 50) {
                                            $riskType = 'بطء في معدل الإنجاز';
                                            $riskLevel = 'متوسط';
                                            $riskColor = 'warning';
                                            $impact = 'احتمالية تأخير في المواعيد';
                                            $actions = 'تعزيز الفرق وتحسين التنسيق';
                                            $priority = 'مهم';
                                        } elseif($progress < 75) {
                                            $riskType = 'تحديات في التنفيذ';
                                            $riskLevel = 'منخفض';
                                            $riskColor = 'info';
                                            $impact = 'تأثير محدود';
                                            $actions = 'متابعة دورية ودعم إضافي';
                                            $priority = 'عادي';
                                        } else {
                                            $riskType = 'أداء ممتاز';
                                            $riskLevel = 'آمن';
                                            $riskColor = 'success';
                                            $impact = 'لا يوجد تأثير سلبي';
                                            $actions = 'الحفاظ على الأداء الحالي';
                                            $priority = 'منخفض';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $entity->entity_name }}</td>
                                        <td>{{ $riskType }}</td>
                                        <td class="text-center">
                                            <span class="table-badge bg-{{ $riskColor }}">{{ $riskLevel }}</span>
                                        </td>
                                        <td>{{ $impact }}</td>
                                        <td>{{ $actions }}</td>
                                        <td class="text-center">
                                            <span class="table-badge bg-{{ $priority == 'عاجل' ? 'danger' : ($priority == 'مهم' ? 'warning' : 'info') }}">
                                                {{ $priority }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Footer -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <p class="mb-1"><strong>تاريخ إنشاء التقرير:</strong> {{ now()->format('Y-m-d H:i') }}</p>
                    <p class="mb-0 text-muted">تم إنشاء هذا التقرير تلقائياً من نظام متابعة المشاريع</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn-group {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    
    .card-header {
        background: #f0f0f0 !important;
        color: #000 !important;
        border-bottom: 1px solid #000 !important;
    }
    
    .table-badge {
        border: 1px solid #000 !important;
        background: #f0f0f0 !important;
        color: #000 !important;
    }
    
    .progress {
        border: 1px solid #000 !important;
    }
    
    .progress-bar {
        background: #000 !important;
    }
}
</style>
@endsection