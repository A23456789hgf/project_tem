<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectDisbursementPlan extends Model
{
    use HasFactory;

    protected $table = 'project_disbursement_plans';

    protected $fillable = [
        'project_id',
        'project_total_cost',
        'total_planned_amount',
        'total_disbursed_amount',
        'status',
        'approved_by',
        'approved_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'project_total_cost' => 'decimal:2',
        'total_planned_amount' => 'decimal:2',
        'total_disbursed_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProjectDisbursementPlanDetail::class, 'project_disbursement_plan_id')
            ->orderBy('sequence_number');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة خطة الصرف',
            'approved' => 'معتمدة وقيد الصرف',
            'completed' => 'مكتملة ومخلاة بالكامل',
            'cancelled' => 'ملغاة',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'draft' => 'badge-secondary',
            'approved' => 'badge-primary',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger',
            default => 'badge-light',
        };
    }

    /**
     * Authoritative calculation of total actually disbursed across installments
     */
    public function getAuthoritativeDisbursedAmount(): float
    {
        return (float) $this->details()
            ->whereIn('status', ['disbursed', 'clearance_requested', 'cleared'])
            ->sum('disbursed_amount');
    }

    /**
     * Recalculates and persists the cached total_disbursed_amount column
     */
    public function refreshCachedDisbursedAmount(): void
    {
        $amount = $this->getAuthoritativeDisbursedAmount();
        $this->update(['total_disbursed_amount' => $amount]);
    }
}
