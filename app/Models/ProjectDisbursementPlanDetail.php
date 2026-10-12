<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectDisbursementPlanDetail extends Model
{
    use HasFactory;

    protected $table = 'project_disbursement_plan_details';

    protected $fillable = [
        'project_disbursement_plan_id',
        'sequence_number',
        'funding_source_id',
        'planned_amount',
        'disbursement_clearance_mechanism',
        'status',
        'disbursed_amount',
        'disbursed_at',
        'erpnext_payment_id',
        'erpnext_payment_reference',
        'cleared_at',
        'cleared_by',
        'created_by',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'disbursed_at' => 'datetime',
        'cleared_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProjectDisbursementPlan::class, 'project_disbursement_plan_id');
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class);
    }

    public function clearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projectExecutions(): HasMany
    {
        return $this->hasMany(ProjectExecution::class, 'disbursement_plan_detail_id');
    }

    public function preliminaryExecutions(): HasMany
    {
        return $this->hasMany(PreliminaryProcedureExecution::class, 'disbursement_plan_detail_id');
    }

    public function clearanceRequests(): HasMany
    {
        return $this->hasMany(ProjectClearanceRequest::class, 'disbursement_plan_detail_id')
            ->orderByDesc('id');
    }

    public function activeClearanceRequest(): HasOne
    {
        return $this->hasOne(ProjectClearanceRequest::class, 'disbursement_plan_detail_id')
            ->where('status', 'pending');
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(ProjectPaymentRequest::class, 'disbursement_plan_detail_id')
            ->orderByDesc('id');
    }

    public function activePaymentRequest(): HasOne
    {
        return $this->hasOne(ProjectPaymentRequest::class, 'disbursement_plan_detail_id')
            ->where('status', 'pending');
    }

    public function paymentObligation(): HasOne
    {
        return $this->hasOne(ProjectInstallmentPaymentObligation::class, 'project_disbursement_plan_detail_id');
    }

    public function isPlanned(): bool
    {
        return $this->status === 'planned';
    }

    public function isPaymentRequested(): bool
    {
        return $this->status === 'payment_requested';
    }

    public function isPaymentApproved(): bool
    {
        return $this->status === 'payment_approved';
    }

    public function isDisbursing(): bool
    {
        return $this->status === 'disbursing';
    }

    public function isDisbursingFailed(): bool
    {
        return $this->status === 'disbursing_failed';
    }

    public function isDisbursed(): bool
    {
        return $this->status === 'disbursed';
    }

    public function isClearanceRequested(): bool
    {
        return $this->status === 'clearance_requested';
    }

    public function isCleared(): bool
    {
        return $this->status === 'cleared';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'planned' => 'مخطط',
            'payment_requested' => 'تم طلب الصرف',
            'payment_approved' => 'تمت الموافقة على طلب الصرف',
            'disbursing' => 'جاري التنفيذ المالي (ERPNext)',
            'disbursing_failed' => 'فشل الصرف (ERPNext)',
            'disbursed' => 'تم الصرف',
            'clearance_requested' => 'تم تقديم طلب الإخلاء',
            'cleared' => 'تم الإخلاء بالكامل',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'planned' => 'badge-secondary',
            'payment_requested' => 'badge-info',
            'payment_approved' => 'badge-primary',
            'disbursing' => 'badge-warning',
            'disbursing_failed' => 'badge-danger',
            'disbursed' => 'badge-success',
            'clearance_requested' => 'badge-warning',
            'cleared' => 'badge-dark',
            default => 'badge-light',
        };
    }
}
