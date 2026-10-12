<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectInstallmentAggregation extends Model
{
    protected $fillable = ['project_disbursement_plan_detail_id', 'status', 'submitted_by', 'payload', 'clearance_request_id'];

    protected $casts = ['payload' => 'array'];

    public function installment(): BelongsTo
    {
        return $this->belongsTo(ProjectDisbursementPlanDetail::class, 'project_disbursement_plan_detail_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function clearanceRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectClearanceRequest::class);
    }
}
