<?php

namespace App\Models;

use App\Services\CorrespondenceOrganizationResolver;
use App\Services\OrganizationResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CorrespondenceReply extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'correspondence_id',
        'referral_id',
        'forwarding_id',
        'reply_text',
        'attachments',
        'return_date',
        'confidential',
        'status',
        'notes',
        'replied_by_user_id',
        'replied_at',
        'acknowledged_at',
        'acknowledged_by_user_id',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'return_date' => 'date',
        'attachments' => 'json',
        'confidential' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'confidential' => false,
        'status' => 'sent',
    ];

    /**
     * Correspondence Relationship
     */
    public function correspondence()
    {
        return $this->belongsTo(Correspondence::class);
    }

    /**
     * Referral Relationship (if this reply is for a specific referral)
     */
    public function referral()
    {
        return $this->belongsTo(CorrespondenceReferral::class, 'referral_id');
    }

    /**
     * Forwarding Relationship (if this reply is for a specific internal forwarding)
     */
    public function forwarding()
    {
        return $this->belongsTo(CorrespondenceForwarding::class, 'forwarding_id');
    }

    /**
     * Replied By User Relationship
     */
    public function repliedByUser()
    {
        return $this->belongsTo(User::class, 'replied_by_user_id');
    }

    /**
     * User who acknowledged the reply Relationship
     */
    public function acknowledgedByUser()
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by confidentiality
     */
    public function scopeConfidential($query)
    {
        return $query->where('confidential', true);
    }

    /**
     * Scope: Filter by non-confidential
     */
    public function scopeNonConfidential($query)
    {
        return $query->where('confidential', false);
    }

    /**
     * Scope: Filter by replied by user
     */
    public function scopeByRepliedByUser($query, $userId)
    {
        return $query->where('replied_by_user_id', $userId);
    }

    /**
     * Scope: Filter by correspondence
     */
    public function scopeByCorrespondence($query, $correspondenceId)
    {
        return $query->where('correspondence_id', $correspondenceId);
    }

    /**
     * Scope: Filter by referral
     */
    public function scopeByReferral($query, $referralId)
    {
        return $query->where('referral_id', $referralId);
    }

    /**
     * Scope: Filter by return date range
     */
    public function scopeReturnDateRange($query, $fromDate, $toDate)
    {
        return $query->whereBetween('return_date', [$fromDate, $toDate]);
    }

    /**
     * Scope: Filter overdue returns
     */
    public function scopeOverdueReturns($query)
    {
        return $query->whereNotNull('return_date')
            ->where('return_date', '<', now())
            ->whereIn('status', ['sent', 'delivered']);
    }

    /**
     * Scope: Search in reply text or notes
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('reply_text', 'like', "%{$searchTerm}%")
                ->orWhere('notes', 'like', "%{$searchTerm}%")
                ->orWhereHas('repliedByUser', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
        });
    }

    /**
     * Check if reply can be edited
     */
    public function canBeEdited()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if reply can be deleted
     */
    public function canBeDeleted()
    {
        return in_array($this->status, ['draft', 'sent']) &&
               ! $this->acknowledged_at;
    }

    /**
     * Mark reply as acknowledged
     */
    public function markAsAcknowledged($userId)
    {
        if ($this->acknowledged_at) {
            return false;
        }

        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by_user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Mark reply as delivered
     */
    public function markAsDelivered()
    {
        if ($this->status !== 'sent') {
            return false;
        }

        $this->update([
            'status' => 'delivered',
        ]);

        return true;
    }

    /**
     * Check if return date has passed
     */
    public function isReturnDatePassed()
    {
        if (! $this->return_date) {
            return false;
        }

        return $this->return_date < now();
    }

    /**
     * Get days until return date
     */
    public function getDaysUntilReturnAttribute()
    {
        if (! $this->return_date) {
            return null;
        }

        $days = now()->diffInDays($this->return_date, false);

        return $days >= 0 ? $days : 0;
    }

    /**
     * Get days overdue for return
     */
    public function getDaysReturnOverdueAttribute()
    {
        if (! $this->return_date || ! $this->isReturnDatePassed()) {
            return 0;
        }

        $overdue = now()->diffInDays($this->return_date, false);

        return abs($overdue);
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute()
    {
        $attachments = $this->attachments;
        if (is_string($attachments)) {
            $attachments = json_decode($attachments, true);
        }

        return is_array($attachments) ? count($attachments) : 0;
    }

    /**
     * Boot method to set replied_at timestamp and update correspondence
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reply) {
            if (! $reply->replied_at) {
                $reply->replied_at = now();
            }

            // إذا كانت الحالة غير محددة، ضعها كـ "sent"
            if (! $reply->status) {
                $reply->status = 'sent';
            }
        });

        static::created(function ($reply) {
            // تحديث حالة المراسلة الأصلية
            if ($reply->correspondence) {
                $reply->correspondence->update([
                    'status' => 'replied',
                    'last_action_at' => now(),
                ]);
            }

            // إذا كان الرد مرتبطاً بإحالة، تحديث حالة الإحالة
            if ($reply->referral_id && $reply->referral) {
                $reply->referral->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by_user_id' => $reply->replied_by_user_id,
                ]);
            }

            // إذا كان الرد مرتبطاً بتوجيه داخلي، تحديث حالة التوجيه
            if ($reply->forwarding_id && $reply->forwarding) {
                $reply->forwarding->update([
                    'status' => 'acknowledged',
                    'acknowledged_at' => now(),
                    'acknowledged_by_user_id' => $reply->replied_by_user_id,
                ]);
            }
        });

        static::updated(function ($reply) {
            // تحديث تاريخ آخر إجراء في المراسلة الأصلية
            if ($reply->correspondence) {
                $reply->correspondence->update([
                    'last_action_at' => now(),
                ]);
            }
        });
    }

    /**
     * Accessor for formatted replied_at date
     */
    public function getFormattedRepliedAtAttribute()
    {
        return $this->replied_at ? $this->replied_at->format('Y-m-d H:i') : null;
    }

    /**
     * Accessor for formatted acknowledged_at date
     */
    public function getFormattedAcknowledgedAtAttribute()
    {
        return $this->acknowledged_at ? $this->acknowledged_at->format('Y-m-d H:i') : null;
    }

    /**
     * Accessor for formatted return_date
     */
    public function getFormattedReturnDateAttribute()
    {
        return $this->return_date ? $this->return_date->format('Y-m-d') : null;
    }

    /**
     * Accessor for status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'مسودة',
            'sent' => 'مرسلة',
            'delivered' => 'تم التسليم',
            'acknowledged' => 'تم العلم',
            'cancelled' => 'ملغاة',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Accessor for confidential label
     */
    public function getConfidentialLabelAttribute()
    {
        return $this->confidential ? 'سري' : 'عادي';
    }

    /**
     * Accessor for return date status
     */
    public function getReturnDateStatusAttribute()
    {
        if (! $this->return_date) {
            return 'no_return_date';
        }

        if (in_array($this->status, ['acknowledged', 'cancelled'])) {
            return 'closed';
        }

        $daysUntilReturn = $this->days_until_return;

        if ($daysUntilReturn === null) {
            return 'no_return_date';
        }

        if ($this->isReturnDatePassed()) {
            return 'overdue';
        } elseif ($daysUntilReturn <= 3) {
            return 'critical';
        } elseif ($daysUntilReturn <= 7) {
            return 'warning';
        } else {
            return 'normal';
        }
    }

    /**
     * Accessor for return date status label
     */
    public function getReturnDateStatusLabelAttribute()
    {
        $labels = [
            'no_return_date' => 'لا يوجد تاريخ إرجاع',
            'closed' => 'مغلقة',
            'overdue' => 'متأخرة',
            'critical' => 'حرجة',
            'warning' => 'تحذير',
            'normal' => 'طبيعي',
        ];

        return $labels[$this->return_date_status] ?? 'غير محدد';
    }

    /**
     * Accessor for return date status color
     */
    public function getReturnDateStatusColorAttribute()
    {
        $colors = [
            'no_return_date' => 'secondary',
            'closed' => 'success',
            'overdue' => 'danger',
            'critical' => 'danger',
            'warning' => 'warning',
            'normal' => 'success',
        ];

        return $colors[$this->return_date_status] ?? 'secondary';
    }

    /**
     * Check if user can view this reply
     */
    public function canBeViewedBy($user)
    {
        // المستخدم الذي كتب الرد
        if ($this->replied_by_user_id === $user->id) {
            return true;
        }

        // المستخدم من الجهة المرسلة أو المستقبلة للمراسلة الأصلية
        $correspondence = $this->correspondence;
        if ($correspondence) {
            $orgResolver = app(CorrespondenceOrganizationResolver::class);
            $userOrg = app(OrganizationResolver::class)->resolve($user);

            if ($orgResolver->matchesUserOrganization($userOrg, $this->sender_type, $this->sender_entity_id, $this->sender_authority_id) ||
                $orgResolver->matchesUserOrganization($userOrg, $this->recipient_type, $this->recipient_entity_id, $this->recipient_authority_id)) {

                // إذا كان الرد سرياً، تحقق من الصلاحية
                if ($this->confidential) {
                    // فقط المستخدم الذي كتب الرد أو مدير النظام يمكنه رؤية الرد السري
                    return $this->replied_by_user_id === $user->id || $user->hasRole('admin');
                }

                return true;
            }
        }

        // إذا كان الرد مرتبطاً بإحالة
        if ($this->referral_id) {
            $referral = $this->referral;
            $orgResolver = app(CorrespondenceOrganizationResolver::class);
            if ($referral && $orgResolver->matchesUserOrganization($userOrg, $referral->referred_to_type, $referral->referred_to_entity_id, $referral->referred_to_authority_id)) {
                return true;
            }
        }

        // المستخدم مدير نظام
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Get reply number
     */
    public function getReplyNumberAttribute()
    {
        $correspondenceNumber = $this->correspondence->correspondence_number;
        $replyId = str_pad($this->id, 3, '0', STR_PAD_LEFT);

        return "{$correspondenceNumber}/REP/{$replyId}";
    }

    /**
     * Get the original correspondence sender entity
     */
    public function getOriginalSenderEntityAttribute()
    {
        return $this->correspondence ? $this->correspondence->senderEntity : null;
    }

    /**
     * Get the original correspondence recipient entity
     */
    public function getOriginalRecipientEntityAttribute()
    {
        return $this->correspondence ? $this->correspondence->recipientEntity : null;
    }

    /**
     * Check if this reply is for a referral
     */
    public function isForReferral()
    {
        return ! is_null($this->referral_id);
    }

    /**
     * Check if this reply is for an internal forwarding
     */
    public function isForForwarding()
    {
        return ! is_null($this->forwarding_id);
    }
}
