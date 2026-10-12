<?php

namespace App\Models;

use App\Services\CorrespondenceOrganizationResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CorrespondenceForwarding extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'correspondence_id',
        'from_entity_id',
        'to_entity_id',
        'forwarded_by_user_id',
        'notes',
        'status',
        'forwarded_at',
        'acknowledged_at',
        'acknowledged_by_user_id',
    ];

    protected $casts = [
        'forwarded_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method to handle automatic status updates
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($forwarding) {
            if ($forwarding->correspondence && $forwarding->correspondence->status !== 'closed') {
                $forwarding->correspondence->update([
                    'status' => 'forwarded',
                    'last_action_at' => now(),
                ]);

                // تسجيل الحركة في السجل الجديد (إذا لم تكن مسجلة بالفعل)
                // Note: The controller might also log this, but model logic ensures it's always captured
                $forwarding->correspondence->logMovement(
                    'forward',
                    'تم توجيه المراسلة داخلياً إلى: '.($forwarding->toEntity->name ?? 'غير معروف'),
                    [
                        'forwarding_id' => $forwarding->id,
                        'from_entity' => $forwarding->fromEntity->name ?? null,
                        'to_entity' => $forwarding->toEntity->name ?? null,
                    ]
                );
            }
        });
    }

    /**
     * Correspondence Relationship
     */
    public function correspondence()
    {
        return $this->belongsTo(Correspondence::class);
    }

    /**
     * From Entity Relationship
     */
    public function fromAuthority()
    {
        return $this->belongsTo(Authority::class, 'from_authority_id');
    }

    public function getFromOrganizationAttribute()
    {
        $resolver = app(CorrespondenceOrganizationResolver::class);

        return $resolver->resolveOrganization($this->from_type, $this->from_entity_id, $this->from_authority_id);
    }

    public function fromEntity()
    {
        return $this->belongsTo(InternalEntity::class, 'from_entity_id');
    }

    /**
     * To Entity Relationship (Sub-department)
     */
    public function toAuthority()
    {
        return $this->belongsTo(Authority::class, 'to_authority_id');
    }

    public function getToOrganizationAttribute()
    {
        $resolver = app(CorrespondenceOrganizationResolver::class);

        return $resolver->resolveOrganization($this->to_type, $this->to_entity_id, $this->to_authority_id);
    }

    public function toEntity()
    {
        return $this->belongsTo(InternalEntity::class, 'to_entity_id');
    }

    /**
     * User who forwarded the message
     */
    public function forwardedByUser()
    {
        return $this->belongsTo(User::class, 'forwarded_by_user_id');
    }

    /**
     * User who acknowledged the message
     */
    public function acknowledgedByUser()
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    /**
     * Get status label in Arabic
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'قيد الانتظار',
            'acknowledged' => 'تم الاستلام / العلم',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'acknowledged' => 'success',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Accessor for formatted forwarded_at date
     */
    public function getFormattedForwardedAtAttribute()
    {
        return $this->forwarded_at ? $this->forwarded_at->format('Y-m-d H:i') : null;
    }

    /**
     * Accessor for formatted acknowledged_at date
     */
    public function getFormattedAcknowledgedAtAttribute()
    {
        return $this->acknowledged_at ? $this->acknowledged_at->format('Y-m-d H:i') : null;
    }

    /**
     * Replies for this forwarding relationship
     */
    public function replies()
    {
        return $this->hasMany(CorrespondenceReply::class, 'forwarding_id');
    }
}
