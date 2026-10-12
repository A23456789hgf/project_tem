<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProjectInstallmentPaymentObligation extends Model
{
    protected $fillable = [
        'project_disbursement_plan_detail_id',
        'requestable_type',
        'requestable_id',
        'status',
        'erpnext_payment_reference',
        'erpnext_payment_id',
        'erpnext_sync_status',
        'erpnext_sync_error',
        'erpnext_synced_at',
    ];

    protected $casts = [
        'erpnext_synced_at' => 'datetime',
    ];

    public function installment(): BelongsTo
    {
        return $this->belongsTo(ProjectDisbursementPlanDetail::class, 'project_disbursement_plan_detail_id');
    }

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getErpnextLastErrorAttribute(): ?string
    {
        return $this->erpnext_sync_error;
    }

    public function setErpnextLastErrorAttribute(?string $value): void
    {
        $this->attributes['erpnext_sync_error'] = $value;
    }
}
