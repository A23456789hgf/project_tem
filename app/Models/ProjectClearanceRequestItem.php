<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectClearanceRequestItem extends Model
{
    use HasFactory;

    protected $table = 'project_clearance_request_items';

    protected $fillable = [
        'clearance_request_id',
        'execution_type',
        'execution_id',
        'amount_spent',
        'completion_percentage',
    ];

    protected $casts = [
        'amount_spent' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
    ];

    public function clearanceRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectClearanceRequest::class, 'clearance_request_id');
    }

    /**
     * Resolve the underlying execution model instance
     */
    public function getExecutionModel()
    {
        if ($this->execution_type === 'preliminary_execution') {
            return PreliminaryProcedureExecution::find($this->execution_id);
        }

        return ProjectExecution::find($this->execution_id);
    }
}
