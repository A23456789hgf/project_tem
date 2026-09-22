<?php

namespace App\Services;

use App\Models\ApprovalStage;
use App\Models\Project;
use App\Models\ProjectApprovalRequest;
use App\Models\ProjectMovementLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectApprovalService
{
    public function initializeApprovalProcess(Project $project, User $user): void
    {
        DB::transaction(function () use ($project, $user) {
            $project->update([
                'created_by_user_id' => $user->id,
                'approval_status' => 'in_process',
            ]);

            $stages = ApprovalStage::where('is_active', true)
                ->orderBy('order')
                ->get();

            foreach ($stages as $stage) {
                ProjectApprovalRequest::create([
                    'project_id' => $project->id,
                    'approval_stage_id' => $stage->id,
                    'user_id' => $user->id,
                    'status' => 'pending',
                ]);
            }

            $firstStage = $stages->first();
            if ($firstStage) {
                $project->update(['current_approval_stage_id' => $firstStage->id]);
                $this->logMovement($project, $firstStage, $user, 'pending', null, null, 'تم إرسال المشروع للموافقة');
            }

            // Send SMS to the user responsible for creating the draft if setting enabled
            try {
                if ($user->phone && app(SmsEventManager::class)->enabled('project.submitted')) {
                    $smsService = app(SmppSmsService::class);
                    $message = "عزيزي المستخدم، تم إغلاق مسودة المشروع '{$project->project_name}' وبدأت دورة الاعتماد.";
                    $smsService->sendSmsAsync($user->id, $user->phone, $message, 'project.submitted');
                }
            } catch (\Exception $e) {
                Log::error('Failed to send SMS on draft submission: '.$e->getMessage());
            }
        });
    }

    public function submitToNextStage(Project $project, User $user, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($project, $user, $notes) {
            $currentStage = $project->currentApprovalStage;
            if (! $currentStage) {
                return false;
            }

            $nextStage = ApprovalStage::where('order', '>', $currentStage->order)
                ->where('is_active', true)
                ->orderBy('order')
                ->first();

            if (! $nextStage) {
                $project->update([
                    'approval_status' => 'approved',
                    'finalized_at' => now(),
                ]);
                $this->logMovement($project, $currentStage, $user, 'approved', null, $notes, 'تمت الموافقة على المشروع بنهائي');

                if ($project->created_by_user_id && app(SmsEventManager::class)->enabled('project.approved')) {
                    try {
                        $creator = User::find($project->created_by_user_id);
                        if ($creator && $creator->phone) {
                            $smsService = app(SmppSmsService::class);
                            $message = "عزيزي المستخدم، تمت الموافقة النهائية على مشروعك '{$project->project_name}'.";
                            $smsService->sendSmsAsync($creator->id, $creator->phone, $message, 'project.approved');
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to send final approval SMS: '.$e->getMessage());
                    }
                }

                return true;
            }

            $project->update(['current_approval_stage_id' => $nextStage->id]);

            $nextRequest = ProjectApprovalRequest::where('project_id', $project->id)
                ->where('approval_stage_id', $nextStage->id)
                ->first();

            if ($nextRequest) {
                $nextRequest->update(['status' => 'pending']);
            }

            $this->logMovement($project, $nextStage, $user, 'pending', null, $notes, 'تم نقل المشروع للمرحلة التالية');

            return true;
        });
    }

    public function approveStage(Project $project, ApprovalStage $stage, User $user, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($project, $stage, $user, $notes) {
            $approvalRequest = ProjectApprovalRequest::where('project_id', $project->id)
                ->where('approval_stage_id', $stage->id)
                ->first();

            if (! $approvalRequest) {
                return false;
            }

            $approvalRequest->update([
                'status' => 'approved',
                'completed_at' => now(),
                'reviewer_name' => $user->name,
                'notes' => $notes,
            ]);

            $this->logMovement($project, $stage, $user, 'approved', null, $notes, 'تمت الموافقة على المرحلة');

            return $this->submitToNextStage($project, $user, $notes);
        });
    }

    public function rejectStage(Project $project, ApprovalStage $stage, User $user, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($project, $stage, $user, $reason) {
            $approvalRequest = ProjectApprovalRequest::where('project_id', $project->id)
                ->where('approval_stage_id', $stage->id)
                ->first();

            if (! $approvalRequest) {
                return false;
            }

            $approvalRequest->update([
                'status' => 'rejected',
                'completed_at' => now(),
                'reviewer_name' => $user->name,
                'action_required' => $reason,
                'notes' => $reason,
            ]);

            $project->update([
                'approval_status' => 'rejected',
                'current_approval_stage_id' => $stage->id,
            ]);
            $this->logMovement($project, $stage, $user, 'rejected', null, $reason, 'تم رفض المرحلة');

            if ($project->created_by_user_id && app(SmsEventManager::class)->enabled('project.rejected')) {
                try {
                    $creator = User::find($project->created_by_user_id);
                    if ($creator && $creator->phone) {
                        $smsService = app(SmppSmsService::class);
                        $message = "عزيزي المستخدم، تم رفض مرحلة الاعتماد لمشروعك '{$project->project_name}'. السبب: {$reason}";
                        $smsService->sendSmsAsync($creator->id, $creator->phone, $message, 'project.rejected');
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send project rejection SMS: '.$e->getMessage());
                }
            }

            return true;
        });
    }

    public function requestAction(Project $project, ApprovalStage $stage, User $user, string $actionRequired, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($project, $stage, $user, $actionRequired, $notes) {
            $approvalRequest = ProjectApprovalRequest::where('project_id', $project->id)
                ->where('approval_stage_id', $stage->id)
                ->first();

            if (! $approvalRequest) {
                return false;
            }

            $approvalRequest->update([
                'status' => 'requires_action',
                'action_required' => $actionRequired,
                'reviewer_name' => $user->name,
                'notes' => $notes,
            ]);

            $project->update([
                'approval_status' => 'requires_action',
                'current_approval_stage_id' => $stage->id,
            ]);
            $this->logMovement($project, $stage, $user, 'requires_action', $actionRequired, $notes, 'مطلوب إجراء على المشروع');

            if ($project->created_by_user_id && app(SmsEventManager::class)->enabled('project.action_required')) {
                try {
                    $creator = User::find($project->created_by_user_id);
                    if ($creator && $creator->phone) {
                        $smsService = app(SmppSmsService::class);
                        $message = "عزيزي المستخدم، مطلوب إجراء على مشروعك '{$project->project_name}': {$actionRequired}";
                        $smsService->sendSmsAsync($creator->id, $creator->phone, $message, 'project.action_required');
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send project action_required SMS: '.$e->getMessage());
                }
            }

            $this->logMovement($project, $stage, $user, 'requires_action', $actionRequired, $notes, 'مطلوب إجراء');

            return true;
        });
    }

    public function handleRejectedResubmission(Project $project, User $user, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($project, $user, $notes) {
            if ($project->approval_status !== 'rejected') {
                return false;
            }

            $rejectedStage = $project->currentApprovalStage;

            $approvalRequest = ProjectApprovalRequest::where('project_id', $project->id)
                ->where('approval_stage_id', $rejectedStage->id)
                ->first();

            if ($approvalRequest) {
                $approvalRequest->update([
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'completed_at' => null,
                    'reviewer_name' => null,
                    'action_required' => null,
                    'notes' => $notes,
                ]);
            }

            $project->update(['approval_status' => 'in_process']);
            $this->logMovement($project, $rejectedStage, $user, 'pending', null, $notes, 'تم إعادة إرسال المشروع للمراجعة');

            return true;
        });
    }

    public function logMovement(Project $project, ?ApprovalStage $stage, User $user, string $status, ?string $actionRequired = null, ?string $notes = null, ?string $entity = null): void
    {
        if (! $entity) {
            $entity = $user->role?->name ?? 'Unknown';
        }

        ProjectMovementLog::create([
            'project_id' => $project->id,
            'approval_stage_id' => $stage?->id,
            'user_id' => $user->id,
            'entity' => $entity,
            'status' => $status,
            'action_required' => $actionRequired,
            'notes' => $notes,
            'logged_at' => now(),
        ]);
    }

    public function canUserEditProject(Project $project, User $user, ?ApprovalStage $currentStage = null): bool
    {
        if (! $currentStage) {
            $currentStage = $project->currentApprovalStage;
        }

        if ($project->created_by_user_id !== $user->id) {
            return false;
        }

        if (! $currentStage) {
            return true;
        }

        if (in_array($currentStage->type, ['technical_review', 'financial_review'])) {
            return false;
        }

        if ($project->approval_status === 'rejected' || $project->approval_status === 'requires_action') {
            return true;
        }

        return false;
    }

    public function canUserEditRestrictedFields(Project $project, User $user, ?ApprovalStage $currentStage = null): bool
    {
        if (! $currentStage) {
            $currentStage = $project->currentApprovalStage;
        }

        if (! $currentStage) {
            return false;
        }

        if (in_array($currentStage->type, ['technical_review', 'financial_review'])) {
            if ($currentStage->type === 'technical_review' && $user->hasRole('technical_reviewer')) {
                return true;
            }
            if ($currentStage->type === 'financial_review' && $user->hasRole('financial_reviewer')) {
                return true;
            }
        }

        return false;
    }

    public function getApprovalProgress(Project $project): array
    {
        $stages = ApprovalStage::where('is_active', true)
            ->orderBy('order')
            ->get();

        $progress = [];
        foreach ($stages as $stage) {
            $request = ProjectApprovalRequest::where('project_id', $project->id)
                ->where('approval_stage_id', $stage->id)
                ->first();

            $progress[] = [
                'stage' => $stage,
                'status' => $request?->status ?? 'pending',
                'completed_at' => $request?->completed_at,
                'reviewer_name' => $request?->reviewer_name,
                'notes' => $request?->notes,
                'action_required' => $request?->action_required,
            ];
        }

        return $progress;
    }

    public function getMovementHistory(Project $project)
    {
        return ProjectMovementLog::where('project_id', $project->id)
            ->with(['approvalStage', 'user'])
            ->orderBy('logged_at', 'desc')
            ->get();
    }
}
