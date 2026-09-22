<?php

namespace App\Models;

use App\Enums\EntityResponsibilityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the configuration of a single approval stage for a given entity.
 *
 * A row in this table means:
 *   - The entity has this stage ENABLED.
 *   - The stage will be included (in stage_order) when generating an approval chain.
 *   - The responsible_user_id is the explicit user who will handle this stage.
 *
 * Source of Truth:
 *   entity_id + stage  → enabled (row exists means enabled)
 *   responsible_user_id → who will handle the stage
 *
 * @property int $id
 * @property int $entity_id
 * @property string $stage (EntityResponsibilityType value)
 * @property int $stage_order
 * @property int|null $responsible_user_id
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class EntityApprovalStage extends Model
{
    protected $fillable = [
        'entity_id',
        'stage',
        'is_active',
        'stage_order',
        'responsible_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'stage_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    public function entity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'entity_id');
    }

    /** The user explicitly configured as responsible for this stage. */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id')->withoutGlobalScopes();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ----------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------

    /** Return stages for an entity ordered by their stage_order. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('stage_order');
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /** Typed accessor for the stage enum. */
    public function stageEnum(): ?EntityResponsibilityType
    {
        return EntityResponsibilityType::tryFrom($this->stage)
            ?? EntityResponsibilityType::fromApprovalPhase($this->stage);
    }

    /**
     * Check whether the configured responsible user is valid:
     *   - Exists
     *   - Is Active
     *   - Belongs to this entity
     */
    public function hasValidResponsibleUser(): bool
    {
        $user = $this->responsibleUser;

        return $user !== null
            && in_array($user->status, ['Active', 'active'])
            && (int) $user->entity_id === (int) $this->entity_id;
    }
}
