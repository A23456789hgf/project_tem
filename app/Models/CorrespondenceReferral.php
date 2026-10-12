<?php

namespace App\Models;

use App\Services\CorrespondenceOrganizationResolver;
use App\Services\OrganizationResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CorrespondenceReferral extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'correspondence_id',
        'referred_to_entity_id',
        'referral_text',
        'attachments',
        'deadline',
        'priority',
        'status',
        'notes',
        'referred_by_user_id',
        'completed_at',
        'completed_by_user_id',
        'referred_at',
        'referred_to_type',
        'referred_to_name',
        'referral_status',
        'referral_date',
        'referral_notes',
    ];

    protected $casts = [
        'referred_at' => 'datetime',
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'attachments' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'priority' => 'normal',
        'status' => 'pending',
    ];

    /**
     * Correspondence Relationship
     */
    public function correspondence()
    {
        return $this->belongsTo(Correspondence::class);
    }

    /**
     * Referred To Entity Relationship
     */
    public function referredToAuthority()
    {
        return $this->belongsTo(Authority::class, 'referred_to_authority_id');
    }

    public function getReferredToOrganizationAttribute()
    {
        $resolver = app(CorrespondenceOrganizationResolver::class);

        return $resolver->resolveOrganization($this->referred_to_type, $this->referred_to_entity_id, $this->referred_to_authority_id);
    }

    public function referredToEntity()
    {
        return $this->belongsTo(InternalEntity::class, 'referred_to_entity_id');
    }

    /**
     * Referred By User Relationship
     */
    public function referredByUser()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    /**
     * User who completed the referral Relationship
     */
    public function completedByUser()
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    /**
     * Replies for this referral Relationship
     */
    public function replies()
    {
        return $this->hasMany(CorrespondenceReply::class, 'referral_id');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: Filter by referred to entity
     */
    public function scopeByReferredToEntity($query, $entityId)
    {
        return $query->where('referred_to_entity_id', $entityId);
    }

    /**
     * Scope: Filter by referred by user
     */
    public function scopeByReferredByUser($query, $userId)
    {
        return $query->where('referred_by_user_id', $userId);
    }

    /**
     * Scope: Filter pending referrals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Filter completed referrals
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Filter in-progress referrals
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Filter overdue referrals
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereIn('status', ['pending', 'in_progress']);
    }

    /**
     * Scope: Filter urgent referrals
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    /**
     * Scope: Filter high priority referrals
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Scope: Search in referral text or notes
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('referral_text', 'like', "%{$searchTerm}%")
                ->orWhere('notes', 'like', "%{$searchTerm}%")
                ->orWhereHas('referredToEntity', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                });
        });
    }

    /**
     * Check if referral can be edited
     */
    public function canBeEdited()
    {
        return $this->status === 'pending' &&
               $this->replies()->count() === 0;
    }

    /**
     * Check if referral can be deleted
     */
    public function canBeDeleted()
    {
        return $this->status === 'pending' &&
               $this->replies()->count() === 0;
    }

    /**
     * Mark referral as completed
     */
    public function markAsCompleted($userId, $completionNotes = null)
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by_user_id' => $userId,
            'notes' => $completionNotes ? $this->notes."\n\n".$completionNotes : $this->notes,
        ]);

        return true;
    }

    /**
     * Mark referral as in progress
     */
    public function markAsInProgress()
    {
        if ($this->status === 'in_progress') {
            return false;
        }

        $this->update([
            'status' => 'in_progress',
        ]);

        return true;
    }

    /**
     * Check if referral is overdue
     */
    public function isOverdue()
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        if ($this->deadline && $this->deadline < now()) {
            return true;
        }

        return false;
    }

    /**
     * Get days remaining until deadline
     */
    public function getDaysRemainingAttribute()
    {
        if (! $this->deadline || $this->status === 'completed') {
            return null;
        }

        $remaining = now()->diffInDays($this->deadline, false);

        return $remaining >= 0 ? $remaining : 0;
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdueAttribute()
    {
        if (! $this->deadline || ! $this->isOverdue()) {
            return 0;
        }

        $overdue = now()->diffInDays($this->deadline, false);

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
     * Get reply count
     */
    public function getReplyCountAttribute()
    {
        return $this->replies()->count();
    }

    /**
     * Boot method to set referred_at timestamp
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($referral) {
            if (! $referral->referred_at) {
                $referral->referred_at = now();
            }
        });

        static::created(function ($referral) {
            // تحديث تاريخ آخر إجراء في المراسلة الأصلية
            $referral->correspondence->update([
                'status' => 'referred',
                'last_action_at' => now(),
            ]);
        });

        static::updated(function ($referral) {
            // تحديث تاريخ آخر إجراء في المراسلة الأصلية
            $referral->correspondence->update([
                'last_action_at' => now(),
            ]);
        });
    }

    /**
     * Accessor for formatted referred_at date
     */
    public function getFormattedReferredAtAttribute()
    {
        return $this->referred_at ? $this->referred_at->format('Y-m-d H:i') : null;
    }

    /**
     * Accessor for formatted deadline date
     */
    public function getFormattedDeadlineAttribute()
    {
        return $this->deadline ? $this->deadline->format('Y-m-d') : null;
    }

    /**
     * Accessor for formatted completed_at date
     */
    public function getFormattedCompletedAtAttribute()
    {
        return $this->completed_at ? $this->completed_at->format('Y-m-d H:i') : null;
    }

    /**
     * Accessor for status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد المعالجة',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Accessor for priority label
     */
    public function getPriorityLabelAttribute()
    {
        $labels = [
            'normal' => 'عادية',
            'high' => 'عالية',
            'urgent' => 'عاجلة',
        ];

        return $labels[$this->priority] ?? $this->priority;
    }

    /**
     * Accessor for referral_status label
     */
    public function getReferralStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'قيد الانتظار',
            'accepted' => 'مقبولة',
            'completed' => 'مكتملة',
            'rejected' => 'مرفوضة',
        ];

        return $labels[$this->referral_status] ?? 'غير محدد';
    }

    /**
     * Accessor for referral_status color
     */
    public function getReferralStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'accepted' => 'primary',
            'completed' => 'success',
            'rejected' => 'danger',
        ];

        return $colors[$this->referral_status] ?? 'secondary';
    }

    /**
     * Accessor for deadline status
     */
    public function getDeadlineStatusAttribute()
    {
        if (! $this->deadline) {
            return 'no_deadline';
        }

        if ($this->status === 'completed') {
            return 'completed';
        }

        $daysRemaining = $this->days_remaining;

        if ($daysRemaining === null) {
            return 'no_deadline';
        }

        if ($daysRemaining <= 0) {
            return 'overdue';
        } elseif ($daysRemaining <= 3) {
            return 'critical';
        } elseif ($daysRemaining <= 7) {
            return 'warning';
        } else {
            return 'normal';
        }
    }

    /**
     * Accessor for deadline status label
     */
    public function getDeadlineStatusLabelAttribute()
    {
        $labels = [
            'no_deadline' => 'لا يوجد موعد نهائي',
            'completed' => 'مكتملة',
            'overdue' => 'متأخرة',
            'critical' => 'حرجة',
            'warning' => 'تحذير',
            'normal' => 'طبيعي',
        ];

        return $labels[$this->deadline_status] ?? 'غير محدد';
    }

    /**
     * Accessor for deadline status color
     */
    public function getDeadlineStatusColorAttribute()
    {
        $colors = [
            'no_deadline' => 'secondary',
            'completed' => 'success',
            'overdue' => 'danger',
            'critical' => 'danger',
            'warning' => 'warning',
            'normal' => 'success',
        ];

        return $colors[$this->deadline_status] ?? 'secondary';
    }

    /**
     * Calculate completion percentage
     */
    public function getCompletionPercentageAttribute()
    {
        if ($this->status === 'completed') {
            return 100;
        }

        if ($this->status === 'in_progress') {
            return 50;
        }

        if ($this->status === 'pending') {
            return 0;
        }

        return 0;
    }

    /**
     * Check if user can view this referral
     */
    public function canBeViewedBy($user)
    {
        // المستخدم من الجهة المحال إليها
        $userOrg = app(OrganizationResolver::class)->resolve($user);
        $orgResolver = app(CorrespondenceOrganizationResolver::class);
        if ($orgResolver->matchesUserOrganization($userOrg, $this->referred_to_type, $this->referred_to_entity_id, $this->referred_to_authority_id)) {
            return true;
        }

        // المستخدم من الجهة التي أحالت المراسلة
        if ($this->referred_by_user_id === $user->id) {
            return true;
        }

        // المستخدم من الجهة المرسلة أو المستقبلة للمراسلة الأصلية
        $correspondence = $this->correspondence;
        $orgResolver = app(CorrespondenceOrganizationResolver::class);
        $userOrg = app(OrganizationResolver::class)->resolve($user);

        if ($orgResolver->matchesUserOrganization($userOrg, $this->sender_type, $this->sender_entity_id, $this->sender_authority_id) ||
            $orgResolver->matchesUserOrganization($userOrg, $this->recipient_type, $this->recipient_entity_id, $this->recipient_authority_id)) {
            return true;
        }

        // المستخدم مدير نظام
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Get all users who should be notified about this referral
     */
    public function getNotifiableUsers()
    {
        $users = collect();

        // إضافة المستخدمين من الجهة المحال إليها
        $referredEntityUsers = User::where('entity_id', $this->referred_to_entity_id)
            ->where('is_active', true)
            ->get();
        $users = $users->merge($referredEntityUsers);

        // إضافة المستخدم الذي قام بالإحالة
        if ($this->referredByUser) {
            $users->push($this->referredByUser);
        }

        return $users->unique('id');
    }

    /**
     * Generate referral number
     */
    public function getReferralNumberAttribute()
    {
        $correspondenceNumber = $this->correspondence->correspondence_number;
        $referralId = str_pad($this->id, 3, '0', STR_PAD_LEFT);

        return "{$correspondenceNumber}/REF/{$referralId}";
    }
}
