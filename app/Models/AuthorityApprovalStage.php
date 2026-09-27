<?php

namespace App\Models;

use App\Enums\EntityResponsibilityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorityApprovalStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'authority_id',
        'stage',
        'stage_order',
        'responsible_user_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function authority()
    {
        return $this->belongsTo(Authority::class, 'authority_id');
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function stageEnum(): ?EntityResponsibilityType
    {
        return EntityResponsibilityType::tryFrom($this->stage)
            ?? EntityResponsibilityType::fromApprovalPhase($this->stage);
    }

    public function hasValidResponsibleUser(): bool
    {
        $user = $this->responsibleUser;

        return $user !== null
            && in_array($user->status, ['Active', 'active'])
            && (int) $user->authority_id === (int) $this->authority_id;
    }
}
