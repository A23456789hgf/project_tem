<?php

namespace App\Enums;

/**
 * Represents the three approval stage types used in the workflow.
 *
 * Fixed logical order: TechnicalReview(1) → FinancialReview(2) → Approval(3)
 *
 * CONSULTATION and REFERRAL are intentionally excluded from this enum;
 * they are handled by a separate mechanism (ProjectReferral) and do not
 * participate in the entity_approval_stages configuration.
 */
enum EntityResponsibilityType: string
{
    case TechnicalReview = 'TECHNICAL_REVIEW';
    case FinancialReview = 'FINANCIAL_REVIEW';
    case Approval = 'APPROVAL';

    /**
     * Fixed numeric order used when storing stage_order in entity_approval_stages.
     * Stages that are disabled for an entity are simply not stored.
     */
    public function stageOrder(): int
    {
        return match ($this) {
            self::TechnicalReview => 1,
            self::FinancialReview => 2,
            self::Approval => 3,
        };
    }

    /**
     * Returns the legacy `phase` string stored in project_approvals.phase.
     * Kept for backward compatibility with existing records.
     */
    public function phaseCode(): string
    {
        return match ($this) {
            self::TechnicalReview => 'technical_review',
            self::FinancialReview => 'financial_review',
            self::Approval => 'stage_approval',
        };
    }

    /** Arabic display label. */
    public function label(): string
    {
        return match ($this) {
            self::TechnicalReview => 'مراجعة فنية',
            self::FinancialReview => 'مراجعة مالية',
            self::Approval => 'اعتماد',
        };
    }

    public static function fromApprovalPhase(?string $phase): ?self
    {
        return match ($phase) {
            'technical_review' => self::TechnicalReview,
            'financial_review' => self::FinancialReview,
            'stage_approval', 'approval' => self::Approval,
            default => null,
        };
    }

    /** Ordered list of all stage types. */
    public static function orderedCases(): array
    {
        return [self::TechnicalReview, self::FinancialReview, self::Approval];
    }
}
