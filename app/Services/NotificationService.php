<?php

namespace App\Services;

use App\Models\Authority;
use App\Models\Correspondence;
use App\Models\ExecutionDelayExplanation;
use App\Models\InternalEntity;
use App\Models\Memoir;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectAchievement;
use App\Models\ProjectRequest;
use App\Models\RequestDescend;
use App\Models\Stage;
use App\Models\Suggestion;
use App\Models\Task;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Notifications\ProjectApprovalNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notify users when project moves to next stage (Approved)
     */
    public function notifyStageTransition(
        Project $project,
        Stage $fromStage,
        Stage $toStage,
        ?int $nextAuthorityId,
        string $approvedBy
    ): void {
        try {
            // Get users at the next stage
            $users = $this->getUsersForStage($toStage, $nextAuthorityId);

            if ($users->isEmpty()) {
                Log::warning('No users found for stage transition notification', [
                    'project_id' => $project->id,
                    'to_stage' => $toStage->name_ar,
                    'authority_id' => $nextAuthorityId,
                ]);

                return;
            }

            $data = [
                'type' => 'stage_transition',
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'form_number' => $project->form_number,
                'from_stage' => $fromStage->name_ar,
                'to_stage' => $toStage->name_ar,
                'approved_by' => $approvedBy,
                'message' => "تم نقل المشروع \"{$project->project_name}\" من مرحلة {$fromStage->name_ar} إلى مرحلة {$toStage->name_ar}",
                'action_url' => route('projects.show', $project->id),
                'icon' => 'fa-arrow-circle-left',
                'color' => 'success',
            ];

            foreach ($users as $user) {
                $user->notify(new ProjectApprovalNotification($data));
            }

            Log::info('Stage transition notifications sent', [
                'project_id' => $project->id,
                'users_count' => $users->count(),
                'to_stage' => $toStage->name_ar,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send stage transition notification', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify financial and technical reviewers
     */
    public function notifyReviewersAssigned(
        Project $project,
        Stage $currentStage,
        string $assignedBy
    ): void {
        try {
            $financialReviewers = $this->getFinancialReviewers();
            $technicalReviewers = $this->getTechnicalReviewers();

            $baseData = [
                'type' => 'review_assigned',
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'form_number' => $project->form_number,
                'stage' => $currentStage->name_ar,
                'assigned_by' => $assignedBy,
                'action_url' => route('projects.show', $project->id),
                'icon' => 'fa-search-dollar',
            ];

            // Notify financial reviewers
            if ($financialReviewers->isNotEmpty()) {
                $financialData = array_merge($baseData, [
                    'review_type' => 'financial',
                    'message' => "تم تعيينك كمراجع مالي للمشروع \"{$project->project_name}\" في مرحلة {$currentStage->name_ar}",
                    'color' => 'info',
                ]);

                foreach ($financialReviewers as $reviewer) {
                    $reviewer->notify(new ProjectApprovalNotification($financialData));
                }
            }

            // Notify technical reviewers
            if ($technicalReviewers->isNotEmpty()) {
                $technicalData = array_merge($baseData, [
                    'review_type' => 'technical',
                    'message' => "تم تعيينك كمراجع فني للمشروع \"{$project->project_name}\" في مرحلة {$currentStage->name_ar}",
                    'color' => 'purple',
                ]);

                foreach ($technicalReviewers as $reviewer) {
                    $reviewer->notify(new ProjectApprovalNotification($technicalData));
                }
            }

            Log::info('Review assignment notifications sent', [
                'project_id' => $project->id,
                'financial_reviewers' => $financialReviewers->count(),
                'technical_reviewers' => $technicalReviewers->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send reviewer assignment notification', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify when action is required (Need Action)
     */
    public function notifyActionRequired(
        Project $project,
        Stage $currentStage,
        Stage $previousStage,
        string $requiredAction,
        string $requestedBy
    ): void {
        try {
            // Get users from previous stage (where project is being returned to)
            $users = $this->getPreviousStageUsers($project, $previousStage);

            if ($users->isEmpty()) {
                Log::warning('No users found for action required notification', [
                    'project_id' => $project->id,
                    'previous_stage' => $previousStage->name_ar,
                ]);

                return;
            }

            $data = [
                'type' => 'action_required',
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'form_number' => $project->form_number,
                'current_stage' => $currentStage->name_ar,
                'returned_to_stage' => $previousStage->name_ar,
                'required_action' => $requiredAction,
                'requested_by' => $requestedBy,
                'message' => "يتطلب المشروع \"{$project->project_name}\" إجراءً منك. تم إرجاعه من مرحلة {$currentStage->name_ar} إلى {$previousStage->name_ar}",
                'action_url' => route('projects.show', $project->id),
                'icon' => 'fa-exclamation-circle',
                'color' => 'warning',
            ];

            foreach ($users as $user) {
                $user->notify(new ProjectApprovalNotification($data));
            }

            Log::info('Action required notifications sent', [
                'project_id' => $project->id,
                'users_count' => $users->count(),
                'returned_to' => $previousStage->name_ar,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send action required notification', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify when project is rejected
     */
    public function notifyRejection(
        Project $project,
        Stage $currentStage,
        Stage $previousStage,
        string $rejectionReason,
        string $rejectedBy
    ): void {
        try {
            // Get users from previous stage
            $users = $this->getPreviousStageUsers($project, $previousStage);

            if ($users->isEmpty()) {
                Log::warning('No users found for rejection notification', [
                    'project_id' => $project->id,
                    'previous_stage' => $previousStage->name_ar,
                ]);

                return;
            }

            $data = [
                'type' => 'rejected',
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'form_number' => $project->form_number,
                'current_stage' => $currentStage->name_ar,
                'returned_to_stage' => $previousStage->name_ar,
                'rejection_reason' => $rejectionReason,
                'rejected_by' => $rejectedBy,
                'message' => "تم رفض المشروع \"{$project->project_name}\" في مرحلة {$currentStage->name_ar} وإرجاعه إلى {$previousStage->name_ar}",
                'action_url' => route('projects.show', $project->id),
                'icon' => 'fa-times-circle',
                'color' => 'danger',
            ];

            foreach ($users as $user) {
                $user->notify(new ProjectApprovalNotification($data));
            }

            Log::info('Rejection notifications sent', [
                'project_id' => $project->id,
                'users_count' => $users->count(),
                'returned_to' => $previousStage->name_ar,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send rejection notification', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify when project is resubmitted
     */
    public function notifyResubmission(
        Project $project,
        Stage $currentStage,
        string $resubmittedBy,
        ?string $notes = null
    ): void {
        try {
            // Get users at current stage (reviewers)
            $users = $this->getUsersForCurrentStage($project, $currentStage);

            if ($users->isEmpty()) {
                Log::warning('No users found for resubmission notification', [
                    'project_id' => $project->id,
                    'current_stage' => $currentStage->name_ar,
                ]);

                return;
            }

            $data = [
                'type' => 'resubmitted',
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'form_number' => $project->form_number,
                'stage' => $currentStage->name_ar,
                'resubmitted_by' => $resubmittedBy,
                'notes' => $notes,
                'message' => "تم إعادة تقديم المشروع \"{$project->project_name}\" للمراجعة في مرحلة {$currentStage->name_ar}",
                'action_url' => route('projects.show', $project->id),
                'icon' => 'fa-redo',
                'color' => 'secondary',
            ];

            foreach ($users as $user) {
                $user->notify(new ProjectApprovalNotification($data));
            }

            Log::info('Resubmission notifications sent', [
                'project_id' => $project->id,
                'users_count' => $users->count(),
                'stage' => $currentStage->name_ar,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send resubmission notification', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get users for a specific stage and authority
     */
    private function getUsersForStage(Stage $stage, ?int $authorityId)
    {
        if (! $authorityId) {
            return collect();
        }

        // Get authority
        $authority = Authority::find($authorityId);

        if (! $authority) {
            return collect();
        }

        // Get users from this authority/entity
        return User::where('entity_id', $authorityId)
            ->where('status', 'active')
            ->whereHas('permissions', function ($query) {
                $query->where('slug', 'LIKE', 'approvals.%');
            })
            ->get();
    }

    /**
     * Get financial reviewers
     */
    private function getFinancialReviewers()
    {
        return User::where('status', 'active')
            ->where(function ($query) {
                $query->whereHas('permissions', function ($q) {
                    $q->where('slug', 'approvals.financial-review');
                })
                    ->orWhereHas('roleEntity', function ($q) {
                        $q->where('name', 'LIKE', '%مالي%')
                            ->orWhere('name', 'LIKE', '%Financial%');
                    });
            })
            ->get();
    }

    /**
     * Get technical reviewers
     */
    private function getTechnicalReviewers()
    {
        return User::where('status', 'active')
            ->where(function ($query) {
                $query->whereHas('permissions', function ($q) {
                    $q->where('slug', 'approvals.technical-review');
                })
                    ->orWhereHas('roleEntity', function ($q) {
                        $q->where('name', 'LIKE', '%فني%')
                            ->orWhere('name', 'LIKE', '%Technical%');
                    });
            })
            ->get();
    }

    /**
     * Get users from previous stage
     */
    private function getPreviousStageUsers(Project $project, Stage $previousStage)
    {
        // Get the project creator and users who worked on previous stage
        $users = collect();

        // Add project creator
        if ($project->created_by_user_id) {
            $creator = User::find($project->created_by_user_id);
            if ($creator && $creator->status === 'active') {
                $users->push($creator);
            }
        }

        // Add users from the entity/authority that created the project
        if ($project->creator_entity_id) {
            $entityUsers = User::where('entity_id', $project->creator_entity_id)
                ->where('status', 'active')
                ->get();
            $users = $users->merge($entityUsers);
        } elseif ($project->authority_id) {
            $authorityUsers = User::where('authority_id', $project->authority_id)
                ->where('status', 'active')
                ->get();
            $users = $users->merge($authorityUsers);
        } elseif ($project->created_by_entity) {
            $entityUsers = User::where('department', $project->created_by_entity)
                ->where('status', 'active')
                ->get();
            $users = $users->merge($entityUsers);
        }

        return $users->unique('id');
    }

    /**
     * Get users for current stage
     */
    private function getUsersForCurrentStage(Project $project, Stage $currentStage)
    {
        // Get users who have approval permissions at current stage
        return User::where('status', 'active')
            ->whereHas('permissions', function ($query) {
                $query->where('slug', 'LIKE', 'approvals.%');
            })
            ->get();
    }

    // =====================================================================
    //  إشعارات المشاريع القديمة
    // =====================================================================

    /**
     * إشعار عند تغيير الجهة المقدمة للمشروع القديم
     * يُرسل لمستخدمي الجهة الجديدة المُسندة إليها المشروع
     */
    public function notifyOldProjectEntityChanged(Project $project, InternalEntity $newEntity): void
    {
        try {
            // جلب مستخدمي الجهة الجديدة
            $users = User::where('entity_id', $newEntity->id)
                ->where('status', 'active')
                ->get();

            if ($users->isEmpty()) {
                Log::warning('notifyOldProjectEntityChanged: لا يوجد مستخدمون نشطون في الجهة', [
                    'project_id' => $project->id,
                    'entity_id' => $newEntity->id,
                ]);

                return;
            }

            $data = [
                'type' => 'old_project_assigned',
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'form_number' => $project->form_number,
                'entity_name' => $newEntity->name,
                'message' => "تم إسناد مشروع \"{$project->project_name}\" إلى جهتكم ({$newEntity->name}) للاستكمال.",
                'action_url' => route('projects.complete-data', $project->id),
                'icon' => 'fa-building',
                'color' => 'warning',
            ];

            foreach ($users as $user) {
                $user->notify(new ProjectApprovalNotification($data));
            }

            Log::info('notifyOldProjectEntityChanged: تم إرسال الإشعارات', [
                'project_id' => $project->id,
                'entity_id' => $newEntity->id,
                'users_count' => $users->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('notifyOldProjectEntityChanged: فشل الإرسال', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * إشعار عند اكتمال بيانات المشروع القديم
     * يُرسل للمستخدمين ذوي صلاحيات الاعتماد وللمنشئ الأصلي
     */
    public function notifyOldProjectDataCompleted(Project $project): void
    {
        try {
            $users = collect();

            // إضافة المنشئ الأصلي
            if ($project->created_by_user_id) {
                $creator = User::find($project->created_by_user_id);
                if ($creator && $creator->status === 'active') {
                    $users->push($creator);
                }
            }

            // إضافة المشرفين (لديهم صلاحية projects.view-details)
            $supervisors = User::where('status', 'active')
                ->whereHas('permissions', function ($q) {
                    $q->where('slug', 'projects.view-details');
                })
                ->get();
            $users = $users->merge($supervisors)->unique('id');

            if ($users->isEmpty()) {
                return;
            }

            $entityName = $project->created_by_entity ?? optional($project->creatorEntity)->name ?? '-';

            $data = [
                'type' => 'old_project_data_completed',
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'form_number' => $project->form_number,
                'entity_name' => $entityName,
                'message' => "تم استكمال بيانات المشروع القديم \"{$project->project_name}\" بواسطة جهة ({$entityName}) وهو جاهز لتسجيل الإنجاز.",
                'action_url' => route('projects.show', $project->id),
                'icon' => 'fa-check-circle',
                'color' => 'success',
            ];

            foreach ($users as $user) {
                $user->notify(new ProjectApprovalNotification($data));
            }

            Log::info('notifyOldProjectDataCompleted: تم إرسال الإشعارات', [
                'project_id' => $project->id,
                'users_count' => $users->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('notifyOldProjectDataCompleted: فشل الإرسال', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * إشعار عند تسجيل إنجاز جديد للمشروع القديم
     * يُرسل لمستخدمي الجهة المقدمة وللمشرفين
     */
    public function notifyOldProjectAchievementRecorded(Project $project, ProjectAchievement $achievement): void
    {
        try {
            $users = collect();

            // مستخدمو الجهة المقدمة
            if ($project->creator_entity_id) {
                $entityUsers = User::where('entity_id', $project->creator_entity_id)
                    ->whereIn('status', ['active', 'Active'])
                    ->get();
                $users = $users->merge($entityUsers);
            } elseif ($project->authority_id) {
                $authorityUsers = User::where('authority_id', $project->authority_id)
                    ->whereIn('status', ['active', 'Active'])
                    ->get();
                $users = $users->merge($authorityUsers);
            }

            // المنشئ الأصلي
            if ($project->created_by_user_id) {
                $creator = User::find($project->created_by_user_id);
                if ($creator && in_array($creator->status, ['active', 'Active'])) {
                    $users->push($creator);
                }
            }

            $users = $users->unique('id');

            if ($users->isEmpty()) {
                return;
            }

            $entityName = $project->created_by_entity ?? optional($project->creatorEntity)->name ?? '-';
            $percentage = $achievement->new_achievement ?? 0;

            $data = [
                'type' => 'old_project_achievement',
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'form_number' => $project->form_number,
                'entity_name' => $entityName,
                'achievement_pct' => $percentage,
                'message' => "تم تسجيل إنجاز جديد ({$percentage}%) للمشروع \"{$project->project_name}\" بواسطة جهة ({$entityName}).",
                'action_url' => url("/projects/{$project->id}"),
                'icon' => 'fa-trophy',
                'color' => 'info',
            ];

            foreach ($users as $user) {
                $user->notify(new ProjectApprovalNotification($data));
            }

            Log::info('notifyOldProjectAchievementRecorded: تم إرسال الإشعارات', [
                'project_id' => $project->id,
                'users_count' => $users->count(),
                'percentage' => $percentage,
            ]);

        } catch (\Exception $e) {
            Log::error('notifyOldProjectAchievementRecorded: فشل الإرسال', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =====================================================================
    //  نظام الإشعارات الشامل لكافة صفحات وعمليات النظام
    // =====================================================================

    /**
     * إرسال إشعار موحد لمستخدم أو قائمة مستخدمين
     */
    public function sendNotification($recipients, array $data): void
    {
        try {
            if (! $recipients) {
                return;
            }

            if ($recipients instanceof User) {
                $recipients = collect([$recipients]);
            } elseif (is_array($recipients)) {
                $recipients = collect($recipients);
            }

            $uniqueUsers = $recipients->filter(function ($user) {
                return $user instanceof User && in_array($user->status, ['active', 'Active']);
            })->unique('id');

            $currentUserId = auth()->id();
            $notification = new GeneralNotification($data);

            foreach ($uniqueUsers as $user) {
                // تجنب إرسال الإشعار للشخص الذي قام بالعملية نفسه إلا إذا كان مقصوداً
                if ($user->id === $currentUserId && ! ($data['notify_self'] ?? false)) {
                    continue;
                }
                $user->notify($notification);
            }
        } catch (\Exception $e) {
            Log::error('NotificationService@sendNotification Error: '.$e->getMessage(), [
                'data' => $data,
            ]);
        }
    }

    /**
     * إشعارات المراسلات الإدارية
     */
    public function notifyCorrespondence(Correspondence $correspondence, string $action, ?string $customMessage = null, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';
            $users = collect();

            $actionTitles = [
                'created' => 'مراسلة جديدة: '.$correspondence->correspondence_number,
                'reply' => 'رد جديد على المراسلة: '.$correspondence->correspondence_number,
                'referral' => 'إحالة جديدة للمراسلة: '.$correspondence->correspondence_number,
                'forward' => 'توجيه المراسلة: '.$correspondence->correspondence_number,
                'close' => 'إغلاق المراسلة: '.$correspondence->correspondence_number,
                'updated' => 'تحديث المراسلة: '.$correspondence->correspondence_number,
            ];

            $actionMessages = [
                'created' => "تم إنشاء مراسلة جديدة بعنوان \"{$correspondence->subject}\" برقم ({$correspondence->correspondence_number}) من قِبل {$causerName}.",
                'reply' => "قام {$causerName} بإضافة رد على المراسلة رقم ({$correspondence->correspondence_number}).",
                'referral' => "تمت إحالة المراسلة رقم ({$correspondence->correspondence_number}) بواسطة {$causerName}.",
                'forward' => "تم توجيه المراسلة رقم ({$correspondence->correspondence_number}) إليك بواسطة {$causerName}.",
                'close' => "تم إغلاق المراسلة رقم ({$correspondence->correspondence_number}) بنجاح بواسطة {$causerName}.",
                'updated' => "تم تحديث بيانات المراسلة رقم ({$correspondence->correspondence_number}) بواسطة {$causerName}.",
            ];

            $icons = [
                'created' => 'fas fa-paper-plane',
                'reply' => 'fas fa-reply',
                'referral' => 'fas fa-share',
                'forward' => 'fas fa-forward',
                'close' => 'fas fa-check-double',
                'updated' => 'fas fa-edit',
            ];

            // تحديد المستلمين حسب الإجراء
            if (in_array($action, ['created', 'forward'])) {
                if ($correspondence->recipient_entity_id) {
                    $entityUsers = User::where('entity_id', $correspondence->recipient_entity_id)->where('status', 'active')->get();
                    $users = $users->merge($entityUsers);
                }
                if ($correspondence->recipient_user_id) {
                    $recUser = User::find($correspondence->recipient_user_id);
                    if ($recUser) {
                        $users->push($recUser);
                    }
                }
            } elseif ($action === 'reply') {
                if ($correspondence->sender_user_id) {
                    $senderUser = User::find($correspondence->sender_user_id);
                    if ($senderUser) {
                        $users->push($senderUser);
                    }
                }
                if ($correspondence->sender_entity_id) {
                    $entityUsers = User::where('entity_id', $correspondence->sender_entity_id)->where('status', 'active')->get();
                    $users = $users->merge($entityUsers);
                }
            } elseif ($action === 'referral') {
                $latestReferral = $correspondence->latestReferral;
                if ($latestReferral && $latestReferral->referred_to_entity_id) {
                    $refUsers = User::where('entity_id', $latestReferral->referred_to_entity_id)->where('status', 'active')->get();
                    $users = $users->merge($refUsers);
                }
            } else {
                // close / updated -> notify both sides
                if ($correspondence->sender_user_id) {
                    $users->push(User::find($correspondence->sender_user_id));
                }
                if ($correspondence->recipient_entity_id) {
                    $users = $users->merge(User::where('entity_id', $correspondence->recipient_entity_id)->where('status', 'active')->get());
                }
            }

            $this->sendNotification($users, [
                'type' => 'correspondence',
                'action_type' => $action,
                'title' => $actionTitles[$action] ?? 'المراسلات الإدارية',
                'message' => $customMessage ?? ($actionMessages[$action] ?? "إجراء على المراسلة {$correspondence->correspondence_number}"),
                'page_name' => 'المراسلات الإدارية',
                'action_url' => route('correspondence.show', $correspondence->id),
                'icon' => $icons[$action] ?? 'fas fa-envelope',
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyCorrespondence Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات طلبات المشاريع (Project Requests)
     */
    public function notifyProjectRequest(ProjectRequest $request, string $action, ?string $customMessage = null, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';
            $users = collect();

            $titles = [
                'created' => 'طلب مشروع جديد: '.$request->request_number,
                'submitted' => 'تقديم طلب مشروع للاعتماد: '.$request->request_number,
                'approved' => 'الموافقة على طلب المشروع: '.$request->request_number,
                'rejected' => 'رفض طلب المشروع: '.$request->request_number,
                'transferred' => 'تحويل الطلب إلى مشروع رسمي: '.$request->request_number,
            ];

            $messages = [
                'created' => "تم إنشاء مسودة طلب مشروع جديد بعنوان \"{$request->project_name}\" برقم ({$request->request_number}).",
                'submitted' => "قام {$causerName} بتقديم طلب المشروع \"{$request->project_name}\" برقم ({$request->request_number}) للدراسة والاعتماد.",
                'approved' => "تمت الموافقة على طلب المشروع \"{$request->project_name}\" برقم ({$request->request_number}) بواسطة {$causerName}.",
                'rejected' => "تم رفض طلب المشروع \"{$request->project_name}\" برقم ({$request->request_number}) بواسطة {$causerName}.",
                'transferred' => "تم تحويل طلب المشروع \"{$request->project_name}\" إلى مشروع رسمي بنجاح برقم ({$request->assigned_project_number}).",
            ];

            $icons = [
                'created' => 'fas fa-file-signature',
                'submitted' => 'fas fa-paper-plane',
                'approved' => 'fas fa-check-circle',
                'rejected' => 'fas fa-times-circle',
                'transferred' => 'fas fa-rocket',
            ];

            if ($action === 'submitted' || $action === 'created') {
                // إشعار للمشرفين والمعتمدين
                $approvers = User::where('status', 'active')
                    ->whereHas('permissions', fn ($q) => $q->where('slug', 'LIKE', 'project-requests.approve%'))
                    ->get();
                $users = $users->merge($approvers);
            }

            if (in_array($action, ['approved', 'rejected', 'transferred', 'submitted'])) {
                // إشعار لمنشئ الطلب
                if ($request->created_by_user_id) {
                    $creator = User::find($request->created_by_user_id);
                    if ($creator) {
                        $users->push($creator);
                    }
                }
            }

            $url = $request->project_id ? route('projects.show', $request->project_id) : route('project-requests.show', $request->id);

            $this->sendNotification($users, [
                'type' => 'project_request',
                'action_type' => $action,
                'title' => $titles[$action] ?? 'طلبات المشاريع',
                'message' => $customMessage ?? ($messages[$action] ?? "إجراء على طلب المشروع {$request->request_number}"),
                'page_name' => 'طلبات المشاريع',
                'action_url' => $url,
                'icon' => $icons[$action] ?? 'fas fa-folder-open',
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyProjectRequest Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات مبررات التأخير ومبررات الموازنة والتنفيذ
     */
    public function notifyExecutionDelay(Project $project, ExecutionDelayExplanation $delay, string $action, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';
            $users = collect();

            if ($action === 'submitted') {
                $supervisors = User::where('status', 'active')
                    ->whereHas('permissions', fn ($q) => $q->where('slug', 'execution.approve'))
                    ->get();
                $users = $users->merge($supervisors);
                $msg = "تم تقديم مبرر تأخير للمشروع \"{$project->project_name}\" بواسطة {$causerName}.";
                $title = 'مبرر تأخير جديد: '.$project->project_name;
                $icon = 'fas fa-clock';
            } else {
                if ($delay->user_id) {
                    $submitter = User::find($delay->user_id);
                    if ($submitter) {
                        $users->push($submitter);
                    }
                }
                $statusText = $action === 'approved' ? 'الموافقة على' : 'رفض';
                $msg = "تمت {$statusText} مبرر التأخير للمشروع \"{$project->project_name}\" بواسطة {$causerName}.";
                $title = 'اعتماد مبرر التأخير: '.$project->project_name;
                $icon = $action === 'approved' ? 'fas fa-check-circle' : 'fas fa-times-circle';
            }

            $this->sendNotification($users, [
                'type' => 'execution',
                'action_type' => $action,
                'title' => $title,
                'message' => $msg,
                'page_name' => 'متابعة التنفيذ',
                'action_url' => route('projects.execution', $project->id),
                'icon' => $icon,
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyExecutionDelay Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات المبررات المالية للموازنة
     */
    public function notifyBudgetJustification(Project $project, $justification, string $action, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';
            $users = collect();

            if ($action === 'submitted') {
                $financialUsers = $this->getFinancialReviewers();
                $users = $users->merge($financialUsers);
                $msg = "تم تقديم مبرر مالي للموازنة للمشروع \"{$project->project_name}\" بواسطة {$causerName}.";
                $title = 'مبرر مالي للموازنة: '.$project->project_name;
                $icon = 'fas fa-file-invoice-dollar';
            } else {
                if (! empty($justification->user_id)) {
                    $submitter = User::find($justification->user_id);
                    if ($submitter) {
                        $users->push($submitter);
                    }
                }
                $msg = "تم اعتماد المبرر المالي للمشروع \"{$project->project_name}\" بواسطة {$causerName}.";
                $title = 'اعتماد المبرر المالي: '.$project->project_name;
                $icon = 'fas fa-check-double';
            }

            $this->sendNotification($users, [
                'type' => 'execution',
                'action_type' => $action,
                'title' => $title,
                'message' => $msg,
                'page_name' => 'المبررات المالية',
                'action_url' => route('financial-justifications.index'),
                'icon' => $icon,
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyBudgetJustification Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات المذكرات الإدارية (Memoirs)
     */
    public function notifyMemoir(Memoir $memoir, string $action, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';
            $users = collect();

            if ($memoir->entity_id) {
                $entityUsers = User::where('entity_id', $memoir->entity_id)->where('status', 'active')->get();
                $users = $users->merge($entityUsers);
            }
            if ($memoir->created_by && $memoir->created_by !== $causer?->id) {
                $creator = User::find($memoir->created_by);
                if ($creator) {
                    $users->push($creator);
                }
            }

            $titles = [
                'created' => 'مذكرة إدارية جديدة: '.$memoir->memoir_number,
                'printed' => 'تجهيز وطباعة المذكرة: '.$memoir->memoir_number,
                'updated' => 'تحديث المذكرة: '.$memoir->memoir_number,
            ];

            $messages = [
                'created' => "تم إنشاء مذكرة إدارية جديدة بعنوان \"{$memoir->subject}\" برقم ({$memoir->memoir_number}) بواسطة {$causerName}.",
                'printed' => "تم تجهيز وتوقيع المذكرة رقم ({$memoir->memoir_number}) بواسطة {$causerName}.",
                'updated' => "تم تعديل المذكرة رقم ({$memoir->memoir_number}) بواسطة {$causerName}.",
            ];

            $this->sendNotification($users, [
                'type' => 'memoir',
                'action_type' => $action,
                'title' => $titles[$action] ?? 'المذكرات الإدارية',
                'message' => $messages[$action] ?? "إجراء على المذكرة {$memoir->memoir_number}",
                'page_name' => 'المذكرات الإدارية',
                'action_url' => route('memoirs.show', $memoir->id),
                'icon' => 'fas fa-stamp',
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyMemoir Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات طلبات النزول الميداني (Request Descend)
     */
    public function notifyRequestDescend(RequestDescend $requestDescend, string $action, ?string $customMessage = null, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';
            $users = collect();

            if ($requestDescend->created_by) {
                $creator = User::find($requestDescend->created_by);
                if ($creator) {
                    $users->push($creator);
                }
            }

            // إشعار للأعضاء المشاركين
            if (method_exists($requestDescend, 'members')) {
                foreach ($requestDescend->members as $member) {
                    if (! empty($member->user_id)) {
                        $u = User::find($member->user_id);
                        if ($u) {
                            $users->push($u);
                        }
                    }
                }
            }

            if ($action === 'submitted') {
                $approvers = User::where('status', 'active')
                    ->whereHas('permissions', fn ($q) => $q->where('slug', 'LIKE', 'requests_descend.process%'))
                    ->get();
                $users = $users->merge($approvers);
            }

            $titles = [
                'created' => 'طلب نزول ميداني جديد',
                'submitted' => 'إرسال طلب نزول للاعتماد',
                'approved' => 'الموافقة على طلب النزول الميداني',
                'rejected' => 'رفض طلب النزول الميداني',
                'financial_updated' => 'تحديث المخصصات المالية للنزول الميداني',
            ];

            $messages = [
                'created' => "تم إنشاء مسودة طلب نزول ميداني بواسطة {$causerName}.",
                'submitted' => "قام {$causerName} بإرسال طلب نزول ميداني للاعتماد والمراجعة.",
                'approved' => "تمت الموافقة على طلب النزول الميداني بواسطة {$causerName}.",
                'rejected' => "تم رفض طلب النزول الميداني بواسطة {$causerName}.",
                'financial_updated' => "تم تحديث الحالة المالية والمخصصات لطلب النزول الميداني بواسطة {$causerName}.",
            ];

            $icons = [
                'created' => 'fas fa-map-marked-alt',
                'submitted' => 'fas fa-paper-plane',
                'approved' => 'fas fa-check-circle',
                'rejected' => 'fas fa-times-circle',
                'financial_updated' => 'fas fa-money-check-alt',
            ];

            $this->sendNotification($users, [
                'type' => 'request_descend',
                'action_type' => $action,
                'title' => $titles[$action] ?? 'طلبات النزول الميداني',
                'message' => $customMessage ?? ($messages[$action] ?? 'إجراء على طلب النزول الميداني'),
                'page_name' => 'طلبات النزول الميداني',
                'action_url' => route('requests_descend.show', $requestDescend->id),
                'icon' => $icons[$action] ?? 'fas fa-hiking',
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyRequestDescend Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات الاقتراحات (Suggestions)
     */
    public function notifySuggestion(Suggestion $suggestion, string $action, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';
            $users = collect();

            if ($action === 'created') {
                $managers = User::where('status', 'active')
                    ->whereHas('permissions', fn ($q) => $q->where('slug', 'LIKE', 'suggestions.%'))
                    ->get();
                $users = $users->merge($managers);
                $title = 'اقتراح جديد تم تقديمه';
                $msg = "قام {$causerName} بتقديم اقتراح جديد في النظام.";
                $icon = 'fas fa-lightbulb';
            } else {
                if ($suggestion->user_id) {
                    $creator = User::find($suggestion->user_id);
                    if ($creator) {
                        $users->push($creator);
                    }
                }
                $stateText = $suggestion->is_completed ? 'تمت معالجة وإكمال' : 'إعادة فتح';
                $title = 'تحديث حالة الاقتراح';
                $msg = "{$stateText} مقترحك بواسطة {$causerName}.";
                $icon = 'fas fa-check';
            }

            $this->sendNotification($users, [
                'type' => 'suggestion',
                'action_type' => $action,
                'title' => $title,
                'message' => $msg,
                'page_name' => 'الاقتراحات والتطوير',
                'action_url' => route('dashboard'),
                'icon' => $icon,
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifySuggestion Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات الخطط التنموية (Planning)
     */
    public function notifyPlan(Plan $plan, string $action, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';

            $planners = User::where('status', 'active')
                ->whereHas('permissions', fn ($q) => $q->where('slug', 'LIKE', 'plans.%'))
                ->get();

            $titles = [
                'created' => 'خطة جديدة: '.$plan->name,
                'updated' => 'تحديث الخطة: '.$plan->name,
                'implementation_updated' => 'تحديث نسب تنفيذ الخطة: '.$plan->name,
            ];

            $messages = [
                'created' => "تم إنشاء خطة جديدة بعنوان \"{$plan->name}\" بواسطة {$causerName}.",
                'updated' => "تم تحديث بيانات الخطة \"{$plan->name}\" بواسطة {$causerName}.",
                'implementation_updated' => "تم تحديث متابعة تنفيذ الخطة \"{$plan->name}\" بواسطة {$causerName}.",
            ];

            $this->sendNotification($planners, [
                'type' => 'plan',
                'action_type' => $action,
                'title' => $titles[$action] ?? 'الخطط والمشاريع',
                'message' => $messages[$action] ?? "إجراء على الخطة {$plan->name}",
                'page_name' => 'الخطط التنموية',
                'action_url' => route('plans.show', $plan->id),
                'icon' => 'fas fa-clipboard-list',
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyPlan Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات سلاسل القيمة وخطط السلاسل
     */
    public function notifyValueChain($chainOrPlan, string $action, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';

            $users = User::where('status', 'active')
                ->whereHas('permissions', fn ($q) => $q->where('slug', 'LIKE', 'value_chains.%'))
                ->get();

            $name = $chainOrPlan->name ?? ($chainOrPlan->chain_name ?? 'سلسلة القيمة');

            $this->sendNotification($users, [
                'type' => 'value_chain',
                'action_type' => $action,
                'title' => 'سلاسل القيمة: '.$name,
                'message' => "تم إجراء ({$action}) على {$name} بواسطة {$causerName}.",
                'page_name' => 'سلاسل القيمة',
                'action_url' => route('chain_plans.index'),
                'icon' => 'fas fa-link',
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyValueChain Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات اعتماد ورفض البيانات المرجعية (برامج، تدخلات، وحدات، نطاقات، عناصر مالية)
     */
    public function notifyLookupApproval(string $moduleName, string $itemName, string $action, ?int $createdByUserId, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';
            $users = collect();

            if ($createdByUserId) {
                $creator = User::find($createdByUserId);
                if ($creator) {
                    $users->push($creator);
                }
            }

            $actionText = $action === 'approved' ? 'الموافقة على' : 'رفض';
            $icon = $action === 'approved' ? 'fas fa-check-circle' : 'fas fa-times-circle';

            $this->sendNotification($users, [
                'type' => 'lookup',
                'action_type' => $action,
                'title' => "اعتماد البيانات المرجعية ({$moduleName})",
                'message' => "تمت {$actionText} {$moduleName} \"{$itemName}\" بواسطة {$causerName}.",
                'page_name' => 'البيانات المرجعية',
                'action_url' => route('dashboard'),
                'icon' => $icon,
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyLookupApproval Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات إدارة المستخدمين
     */
    public function notifyUserAccount(User $targetUser, string $action, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';

            $actions = [
                'created' => 'تم إنشاء حساب مستخدم جديد لك',
                'enabled' => 'تم تفعيل حسابك في النظام',
                'disabled' => 'تم تعطيل حسابك في النظام',
                'password_reset' => 'تمت إعادة تعيين كلمة المرور الخاصة بك',
            ];

            $this->sendNotification($targetUser, [
                'type' => 'user',
                'action_type' => $action,
                'title' => 'إشعار الحساب: '.$targetUser->name,
                'message' => ($actions[$action] ?? 'تحديث في حسابك')." بواسطة {$causerName}.",
                'page_name' => 'إدارة المستخدمين',
                'action_url' => route('profile.show'),
                'icon' => 'fas fa-user-shield',
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
                'notify_self' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyUserAccount Error: '.$e->getMessage());
        }
    }

    /**
     * إشعارات إيقاف أو استئناف المهمة
     */
    public function notifyTaskStopped(Task $task, string $actionType = 'stopped', ?string $customMessage = null, ?User $causer = null): void
    {
        try {
            $causer = $causer ?? auth()->user();
            $causerName = $causer?->name ?? 'النظام';
            $isStopped = ($actionType === 'stopped');

            $title = $isStopped ? 'إيقاف مهمة: '.$task->title : 'استئناف مهمة: '.$task->title;
            $verb = $isStopped ? 'إيقاف' : 'استئناف';
            $icon = $isStopped ? 'fas fa-pause-circle' : 'fas fa-play-circle';

            $message = $customMessage ?? "تم {$verb} المهمة \"{$task->title}\" بواسطة {$causerName}.";

            $task->loadMissing(['assignees']);
            $users = $task->assignees;

            $actionUrl = $task->project_id
                ? route('projects.tasks.show', [$task->project_id, $task->id])
                : route('tasks.show', $task->id);

            $this->sendNotification($users, [
                'type' => 'task',
                'action_type' => $actionType,
                'title' => $title,
                'message' => $message,
                'page_name' => 'المهمات والأنشطة',
                'action_url' => $actionUrl,
                'icon' => $icon,
                'causer_id' => $causer?->id,
                'causer_name' => $causerName,
            ]);
        } catch (\Exception $e) {
            Log::error('notifyTaskStopped Error: '.$e->getMessage());
        }
    }
}
