<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipatingEntity extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'project_request_id',
        'authority_type',
        'authority_id',
        'internal_entity_id',
        'parent_id',
    ];

    /**
     * العلاقة مع نموذج Project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * العلاقة مع نموذج Authority (الجهة الرئيسية).
     */
    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class)->withoutGlobalScope('active_only');
    }

    /**
     * العلاقة مع نموذج Authority (الجهة الأم - جهة رئيسية أخرى).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'parent_id')->withoutGlobalScope('active_only');
    }

    /**
     * العلاقة مع الجهة الداخلية (إذا كان النوع داخلي)
     */
    public function internalEntity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'internal_entity_id')->withoutGlobalScope('active_only');
    }

    /**
     * العلاقة مع الجهة الداخلية الأب
     */
    public function parentInternalEntity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'parent_id')->withoutGlobalScope('active_only');
    }

    /**
     * Accessor for entity_id
     */
    public function getEntityIdAttribute()
    {
        return $this->authority_type === 'internal' ? $this->internal_entity_id : $this->authority_id;
    }

    public function getInternalEntityIdAttribute()
    {
        return $this->attributes['internal_entity_id'] ?? null;
    }
}
