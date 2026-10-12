<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPaymentRequest extends Model
{
    use HasFactory;

    protected $table = 'project_payment_requests';

    protected $fillable = [
        'project_id',
        'disbursement_plan_detail_id',
        'request_number',
        'requested_amount',
        'notes',
        'status',
        'attachment_path',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(ProjectDisbursementPlanDetail::class, 'disbursement_plan_detail_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'قيد المراجعة والاعتماد',
            'approved' => 'معتمد للصرف',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'cancelled' => 'badge-secondary',
            default => 'badge-light',
        };
    }
}
