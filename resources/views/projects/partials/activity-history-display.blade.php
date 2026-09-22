{{-- Activity History Display Component (Table View) --}}
<div class="activity-history-section" style="margin-top: 2rem;">
    @if(!isset($is_print) || !$is_print)
    <h4 class="section-header" style="color: #003d7a; font-size: 1.3rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <i class="fas fa-history"></i>
        سجل نشاط المشروع
    </h4>
    @endif

    @php
        $activities = $project->activityHistory()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    @endphp

    @if($activities->isEmpty())
        <div style="padding: 3rem 2rem; text-align: center; background: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;">
            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; color: #adb5bd; opacity: 0.5;"></i>
            <h5 style="color: #6c757d; margin-bottom: 0.5rem;">لا توجد سجلات نشاط</h5>
            <p style="color: #adb5bd; font-size: 0.9rem;">لم يتم تسجيل أي نشاط على هذا المشروع بعد</p>
        </div>
    @else
        <div style="overflow-x: auto; border-radius: 8px; border: 1px solid #e9ecef; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <table style="width: 100%; border-collapse: collapse; background: white; min-width: 1000px; text-align: right;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white;">
                        <th style="padding: 1rem; text-align: center; width: 60px; font-weight: 600; border-bottom: 2px solid #0056b3;">#</th>
                        <th style="padding: 1rem; text-align: right; font-weight: 600; border-bottom: 2px solid #0056b3; width: 20%;">المرحلة / الإجراء</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #0056b3; width: 15%;">الحالة</th>
                        <th style="padding: 1rem; text-align: right; font-weight: 600; border-bottom: 2px solid #0056b3; width: 15%;">المستخدم</th>
                        <th style="padding: 1rem; text-align: right; font-weight: 600; border-bottom: 2px solid #0056b3; width: 30%;">الملاحظات والمعلومات</th>
                        <th style="padding: 1rem; text-align: right; font-weight: 600; border-bottom: 2px solid #0056b3; width: 15%;">التاريخ والوقت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                        @php
                            $actionType = $activity->action_type;
                            $badgeStyle = ['bg' => '#e2e3e5', 'color' => '#6c757d', 'border' => '#d3d4d5', 'icon' => 'fa-info-circle', 'label' => $activity->getActionDescription()];
                            
                            if (in_array($actionType, ['approved', 'completed', 'review_completed', 'finalized'])) {
                                $badgeStyle = ['bg' => '#d1f0e4', 'color' => '#20c997', 'border' => '#a8e6d2', 'icon' => 'fa-check-circle', 'label' => 'مكتمل / موافقة'];
                            } elseif (in_array($actionType, ['rejected'])) {
                                $badgeStyle = ['bg' => '#f8d7da', 'color' => '#dc3545', 'border' => '#f5c6cb', 'icon' => 'fa-times-circle', 'label' => 'مرفوض'];
                            } elseif (in_array($actionType, ['returned_for_revision', 'stage_regression'])) {
                                $badgeStyle = ['bg' => '#d1ecf1', 'color' => '#17a2b8', 'border' => '#bee5eb', 'icon' => 'fa-undo', 'label' => 'مرتجعة'];
                            } elseif (in_array($actionType, ['requires_action', 'need_action'])) {
                                $badgeStyle = ['bg' => '#fff3cd', 'color' => '#ffc107', 'border' => '#ffeeba', 'icon' => 'fa-exclamation-triangle', 'label' => 'يتطلب إجراء'];
                            } elseif (in_array($actionType, ['resubmitted'])) {
                                $badgeStyle = ['bg' => '#e2d9f3', 'color' => '#6f42c1', 'border' => '#d1c4e9', 'icon' => 'fa-paper-plane', 'label' => 'معاد تقديمها'];
                            } elseif (in_array($actionType, ['sent_for_review'])) {
                                $badgeStyle = ['bg' => '#d1ecf1', 'color' => '#17a2b8', 'border' => '#bee5eb', 'icon' => 'fa-search', 'label' => 'أرسل للمراجعة'];
                            }
                        @endphp
                        
                        <tr style="background: {{ $loop->index % 2 === 0 ? '#f8f9fa' : 'white' }}; border-bottom: 1px solid #e9ecef; transition: background-color 0.2s ease;">
                            
                            {{-- Index Column --}}
                            <td style="padding: 0.75rem; text-align: center; font-weight: 600; color: #495057; border-left: 1px solid #e9ecef;">
                                {{ $loop->iteration }}
                            </td>

                            {{-- Stage/Action Column --}}
                            <td style="padding: 0.75rem; font-weight: 600; color: #007bff; vertical-align: top;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-layer-group" style="color: #6c757d;"></i>
                                    {{ $activity->from_stage_name ?? $activity->to_stage_name ?? $activity->getActionDescription() }}
                                </div>
                                @if($activity->from_stage_name && $activity->to_stage_name && $activity->from_stage_name != $activity->to_stage_name)
                                    <div style="font-size: 0.8rem; color: #6c757d; margin-top: 4px; display: flex; align-items: center; gap: 0.25rem;">
                                        <i class="fas fa-arrow-left text-success"></i> إلى: {{ $activity->to_stage_name }}
                                    </div>
                                @endif
                            </td>

                            {{-- Status Badge Column --}}
                            <td style="padding: 0.75rem; text-align: center; vertical-align: top;">
                                <span style="padding: 0.35rem 0.75rem; border-radius: 20px; color: {{ $badgeStyle['color'] }}; background: {{ $badgeStyle['bg'] }}; border: 1px solid {{ $badgeStyle['border'] }}; font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                                    <i class="fas {{ $badgeStyle['icon'] }}"></i> {{ $badgeStyle['label'] }}
                                </span>
                            </td>

                            {{-- User Column --}}
                            <td style="padding: 0.75rem; color: #495057; font-size: 0.9rem; vertical-align: top;">
                                {{ $activity->user->name ?? 'مستخدم محذوف' }}
                            </td>

                            {{-- Notes & Metadata Column --}}
                            <td style="padding: 0.75rem; vertical-align: top;">
                                @if($activity->notes || $activity->action_details)
                                    <div style="padding: 0.5rem; background: #fef5e7; border-right: 3px solid #f39c12; border-radius: 4px; font-size: 0.85rem; color: #555; margin-bottom: 0.5rem; white-space: pre-wrap;">
                                        <i class="fas fa-sticky-note" style="color: #d68910;"></i> 
                                        {{ $activity->notes ?? $activity->action_details }}
                                    </div>
                                @endif

                                @php
                                    $meta = is_array($activity->metadata) 
                                        ? $activity->metadata 
                                        : (is_string($activity->metadata) ? json_decode($activity->metadata, true) : []);
                                @endphp

                                @if(is_array($meta) && count($meta) > 0)
                                    @if(isset($is_print) && $is_print)
                                        <div class="meta-details">
                                            <div style="color: #3498db; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem;">
                                                <i class="fas fa-info-circle"></i> معلومات إضافية
                                            </div>
                                            <div style="padding: 0.5rem; background: #f8f9fa; border-radius: 4px; font-size: 0.8rem; border: 1px solid #dee2e6;">
                                                @foreach($meta as $key => $value)
                                                    @if(!in_array($key, ['timestamp', 'user_name']))
                                                        <div style="margin-bottom: 0.25rem;">
                                                            <strong style="color: #555;">{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                            <span style="color: #333;">
                                                                @if(is_array($value))
                                                                    {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                                @elseif(is_bool($value))
                                                                    {{ $value ? 'نعم' : 'لا' }}
                                                                @else
                                                                    {{ $value }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <details class="meta-details">
                                            <summary style="cursor: pointer; color: #3498db; font-size: 0.85rem; font-weight: 600; user-select: none;">
                                                <i class="fas fa-info-circle"></i> معلومات إضافية
                                            </summary>
                                            <div style="margin-top: 0.5rem; padding: 0.5rem; background: #f8f9fa; border-radius: 4px; font-size: 0.8rem;">
                                                @foreach($meta as $key => $value)
                                                    @if(!in_array($key, ['timestamp', 'user_name']))
                                                        <div style="margin-bottom: 0.25rem;">
                                                            <strong style="color: #555;">{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                            <span style="color: #333;">
                                                                @if(is_array($value))
                                                                    {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                                @elseif(is_bool($value))
                                                                    {{ $value ? 'نعم' : 'لا' }}
                                                                @else
                                                                    {{ $value }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                @endif

                                @if(!($activity->notes || $activity->action_details) && !(is_array($meta) && count($meta) > 0))
                                    <span style="color: #adb5bd;">لا توجد ملاحظات</span>
                                @endif
                            </td>

                            {{-- Time Column --}}
                            <td style="padding: 0.75rem; color: #6c757d; font-size: 0.85rem; white-space: nowrap; vertical-align: top;">
                                <div style="font-weight: 600; color: #495057;">
                                    {{ $activity->created_at->format('Y-m-d H:i') }}
                                </div>
                                <div style="font-size: 0.75rem; color: #adb5bd; margin-top: 0.25rem;">
                                    {{ $activity->created_at->diffForHumans() }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Export/Filter Controls --}}
        @if(!isset($is_print) || !$is_print)
        <div style="margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="font-size: 0.9rem; color: #6c757d;">
                <i class="fas fa-chart-line"></i>
                <strong>إجمالي الأنشطة:</strong> {{ $activities->count() }}
            </div>
            
            @if(Route::has('projects.activity.export'))
                @can('projects.export')
                <a href="{{ route('projects.activity.export', $project) }}" 
                   class="btn btn-sm btn-outline-primary" 
                   style="padding: 0.4rem 1rem; border-radius: 6px; text-decoration: none;">
                    <i class="fas fa-download"></i> تصدير السجل
                </a>
                @endcan
            @endif
        </div>
        @endif
    @endif
</div>
