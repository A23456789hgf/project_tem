<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case CompletedDraft = 'completed_draft';
    case PendingApproval = 'pending_approval';
    case AwaitingDisbursementPlan = 'awaiting_disbursement_plan';
    case RolledBackForReview = 'rolled_back_for_review';
    case InExecution = 'in_execution';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::CompletedDraft => 'مسودة مكتملة',
            self::PendingApproval => 'بانتظار الاعتماد',
            self::AwaitingDisbursementPlan => 'بانتظار خطة الصرف',
            self::RolledBackForReview => 'أعيد للمراجعة والاستكمال',
            self::InExecution => 'قيد التنفيذ',
            self::Completed => 'مكتمل',
            self::Rejected => 'مرفوض',
            self::Cancelled => 'ملغي',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-secondary',
            self::CompletedDraft => 'badge-info',
            self::PendingApproval => 'badge-warning',
            self::AwaitingDisbursementPlan => 'badge-warning',
            self::RolledBackForReview => 'badge-danger',
            self::InExecution => 'badge-primary',
            self::Completed => 'badge-success',
            self::Rejected => 'badge-dark',
            self::Cancelled => 'badge-light',
        };
    }

    public function isDraft(): bool
    {
        return in_array($this, [self::Draft, self::CompletedDraft], true);
    }

    public function isEditableByCreator(): bool
    {
        return in_array($this, [self::Draft, self::CompletedDraft, self::RolledBackForReview], true);
    }

    public function isInApproval(): bool
    {
        return $this === self::PendingApproval;
    }

    public function isInExecution(): bool
    {
        return in_array($this, [self::InExecution, self::Completed], true);
    }
}
