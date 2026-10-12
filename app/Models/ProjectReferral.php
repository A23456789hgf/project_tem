<?php

namespace App\Models;

use App\Services\UserDataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'drop',
        'stage_id',
        'referring_entity_id',
        'referring_user_id',
        'referred_entity_id',
        'referred_user_id',
        'referral_text',
        'referral_attachment',
        'referral_attachments',
        'response_text',
        'response_attachment',
        'response_attachments',
        'responding_user_id',
        'responded_at',
        'status',
    ];

    protected $appends = ['resolved_stage_name'];

    protected $casts = [
        'referral_attachments' => 'array',
        'response_attachments' => 'array',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withoutGlobalScopes();
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function referringEntity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'referring_entity_id')->withoutGlobalScopes();
    }

    public function referredEntity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'referred_entity_id')->withoutGlobalScopes();
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id')->withoutGlobalScopes();
    }

    public function referringUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referring_user_id')->withoutGlobalScopes();
    }

    public function respondingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responding_user_id')->withoutGlobalScopes();
    }

    /**
     * Scopes
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResponded($query)
    {
        return $query->where('status', 'responded');
    }

    public function scopeActionableFor(Builder $query, User $user): Builder
    {
        $allowedEntityIds = app(UserDataScopeService::class)->getUserEntityIds($user);

        return $query->where(function (Builder $query) use ($user, $allowedEntityIds) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', 'pending')
                    ->where('referred_user_id', $user->id);
            })->orWhere(function (Builder $q) use ($user) {
                $q->where('status', 'responded')
                    ->where('referring_user_id', $user->id);
            });

            if (! empty($allowedEntityIds)) {
                $query->orWhereIn('referred_entity_id', $allowedEntityIds)
                    ->orWhereIn('referring_entity_id', $allowedEntityIds);
            }
        });
    }

    public function scopeForEntity($query, $entityId)
    {
        return $query->where('referred_entity_id', $entityId)
            ->orWhere('referring_entity_id', $entityId);
    }

    public function scopeForUserEntity($query, $entityId)
    {
        return $query->where(function ($q) use ($entityId) {
            $q->where('referred_entity_id', $entityId)
                ->orWhere('referring_entity_id', $entityId);
        });
    }

    /**
     * Helper methods
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isResponded(): bool
    {
        return $this->status === 'responded';
    }

    public function isReturned(): bool
    {
        return $this->status === 'returned';
    }

    /**
     * Get resolved stage name (handles both static stage_id and dynamic entity-based drop)
     */
    public function getResolvedStageNameAttribute(): string
    {
        // 1. If we have a static stage relationship
        if ($this->stage) {
            return $this->stage->name_ar;
        }

        // 2. If it's an entity-based stage (drop starting with entity_)
        if ($this->drop && str_starts_with($this->drop, 'entity_')) {
            $entityId = preg_match('/^entity_(\d+)/', $this->drop, $matches) === 1
                ? (int) $matches[1]
                : null;
            $entity = $entityId ? InternalEntity::find($entityId) : null;

            return $entity ? $entity->name : 'جهة غير معروفة';
        }

        // 3. Fallback to drop code or generic label
        return match ($this->drop) {
            'implementation' => 'مرحلة التنفيذ',
            default => $this->drop ?: 'غير محدد'
        };
    }

    /**
     * Get formatted status label in Arabic
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'بانتظار الرد',
            'responded' => 'تم الرد',
            'returned' => 'تم الإرجاع',
            default => $this->status
        };
    }

    /**
     * Retrieve all referral attachments as an array
     */
    public function getAllReferralAttachments(): array
    {
        if (is_array($this->referral_attachments)) {
            return array_filter($this->referral_attachments);
        }

        if (is_string($this->referral_attachments)) {
            $decoded = json_decode($this->referral_attachments, true);
            if (is_array($decoded)) {
                return array_filter($decoded);
            }
        }

        if ($this->referral_attachment) {
            return [$this->referral_attachment];
        }

        return [];
    }

    /**
     * Retrieve all response attachments as an array
     */
    public function getAllResponseAttachments(): array
    {
        if (is_array($this->response_attachments)) {
            return array_filter($this->response_attachments);
        }

        if (is_string($this->response_attachments)) {
            $decoded = json_decode($this->response_attachments, true);
            if (is_array($decoded)) {
                return array_filter($decoded);
            }
        }

        if ($this->response_attachment) {
            return [$this->response_attachment];
        }

        return [];
    }
}
