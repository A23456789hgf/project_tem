<?php

namespace App\Models;

use App\Services\CorrespondenceOrganizationResolver;
use App\Services\OrganizationResolver;
use App\Traits\Auditable;
use App\Traits\HasCreatorTracking;
use App\Traits\HasDomainScope;
use App\Traits\HasMovementLog;
use ArPHP\I18N\Arabic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Correspondence extends Model
{
    use Auditable, HasCreatorTracking, HasDomainScope, HasFactory, HasMovementLog, SoftDeletes;

    protected $fillable = [
        'subject',
        'message_body',
        'sender_entity_id',
        'sender_user_id',
        'recipient_entity_id',
        'priority',
        'confidential',
        'attachments',
        'notes',
        'status',
        'sent_at',
        'closed_at',
        'closed_by_user_id',
        'close_reason',
        'last_action_at',
        'parent_id',
        'correspondence_type',
        'geographic_scope_id',
        'administrative_scope_id',
        'creator_username',
        'creator_entity_id',
        'project_id',
        'task_id',
        'signed_by',
        'signed_at',
        'signature_path',
    ];

    protected $casts = [
        'attachments' => 'json',
        'confidential' => 'boolean',
        'sent_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_action_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {

        static::creating(function ($correspondence) {
            if (empty($correspondence->correspondence_number)) {
                $correspondence->correspondence_number = $correspondence->generateAutoNumber();
            }

            if (! $correspondence->sent_at) {
                $correspondence->sent_at = now();
            }

            if (auth()->check()) {
                $user = auth()->user();

                if (! $correspondence->geographic_scope_id) {
                    $correspondence->geographic_scope_id = $user->geographic_scope_id;
                }

                if (! $correspondence->administrative_scope_id) {
                    $correspondence->administrative_scope_id = $user->administrative_scope_id;
                }
            }

            $correspondence->last_action_at = now();
        });

        static::updating(function ($correspondence) {
            $correspondence->last_action_at = now();

            if ($correspondence->isDirty('status')) {
                Log::info('تغيير حالة المراسلة تلقائياً', [
                    'correspondence_id' => $correspondence->id,
                    'old_status' => $correspondence->getOriginal('status'),
                    'new_status' => $correspondence->status,
                    'user_id' => auth()->id(),
                ]);
            }
        });
    }

    /**
     * دالة لتوليد رقم مراسلة تلقائي
     * الصيغة: رمز_الجهة/السنة/الرقم_التسلسلي
     * مثال: IT/2024/0001
     */
    public function generateAutoNumber()
    {
        try {
            $arPHP = new Arabic;
            $hijriYear = $arPHP->date('Y', time(), 1); // 1 = Islamic calendar

            $prefix = 'COR'.$hijriYear;

            /**
             * البحث عن آخر مراسلة عالمياً للعام الهجري الحالي
             * يتم تضمين المحذوفات (withTrashed) لتجنب تكرار الأرقام
             */
            $lastCorrespondence = self::withoutGlobalScopes()->withTrashed()
                ->where('correspondence_number', 'like', $prefix.'%')
                ->where('correspondence_number', 'not like', 'TEMP-%')
                ->orderBy('correspondence_number', 'desc')
                ->first();

            if ($lastCorrespondence) {
                // استخراج الرقم التسلسلي (آخر 4 خانات)
                $lastNumber = $lastCorrespondence->correspondence_number;
                $lastSequence = (int) substr($lastNumber, -4);
                $nextSequence = $lastSequence + 1;
            } else {
                // أول مراسلة لهذا العام الهجري
                $nextSequence = 1;
            }

            /**
             * حلقة لضمان تفرد الرقم المولد، حتى لو حدث تداخل في قاعدة البيانات
             */
            do {
                $formattedSequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                $finalNumber = $prefix.$formattedSequence;
                $exists = self::withoutGlobalScopes()->withTrashed()->where('correspondence_number', $finalNumber)->exists();

                if ($exists) {
                    $nextSequence++;
                }
            } while ($exists);

            return $finalNumber;

        } catch (\Exception $e) {
            // تسجيل الخطأ وعرض رقم مؤقت
            Log::error('فشل في توليد رقم المراسلة الهجري: '.$e->getMessage());

            return 'TEMP-'.date('Ymd-His').'-'.uniqid();
        }
    }

    /**
     * التحقق مما إذا كان رقم المراسلة تم توليده تلقائياً
     */
    public function isAutoGenerated()
    {
        return empty($this->getOriginal('correspondence_number')) &&
            ! empty($this->correspondence_number);
    }

    /**
     * التحقق من صحة تنسيق رقم المراسلة
     */
    public function isValidCorrespondenceNumber()
    {
        if (empty($this->correspondence_number)) {
            return false;
        }

        // التنسيق الجديد: COR + السنة الهجرية (4 أرقام) + رقم تسلسلي (4 أرقام)
        $newPattern = '/^COR\d{8}$/';
        // التنسيق القديم: رمز_الجهة/السنة/رقم_تسلسلي
        $oldPattern = '/^[A-Z0-9]+\/\d{4}\/\d{4}$/';

        return preg_match($newPattern, $this->correspondence_number) === 1 ||
            preg_match($oldPattern, $this->correspondence_number) === 1;
    }

    /**
     * إعادة توليد رقم المراسلة (في حالات خاصة)
     */
    public function regenerateCorrespondenceNumber()
    {
        if ($this->exists && $this->isValidCorrespondenceNumber()) {
            throw new \Exception('لا يمكن إعادة توليد رقم مراسلة صحيح');
        }

        $this->correspondence_number = $this->generateAutoNumber();

        return $this->save();
    }

    /**
     * ============================================
     * العلاقات (Relationships)
     * ============================================
     */
    public function senderAuthority()
    {
        return $this->belongsTo(Authority::class, 'sender_authority_id');
    }

    public function getSenderOrganizationAttribute()
    {
        return app(CorrespondenceOrganizationResolver::class)->resolveSender($this);
    }

    public function senderEntity()
    {
        return $this->belongsTo(InternalEntity::class, 'sender_entity_id');
    }

    public function recipientAuthority()
    {
        return $this->belongsTo(Authority::class, 'recipient_authority_id');
    }

    public function getRecipientOrganizationAttribute()
    {
        return app(CorrespondenceOrganizationResolver::class)->resolveRecipient($this);
    }

    public function recipientEntity()
    {
        return $this->belongsTo(InternalEntity::class, 'recipient_entity_id');
    }

    public function senderUser()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function replies()
    {
        return $this->hasMany(CorrespondenceReply::class);
    }

    /**
     * Internal Forwardings Relationship
     */
    public function forwardings()
    {
        return $this->hasMany(CorrespondenceForwarding::class);
    }

    public function referrals()
    {
        return $this->hasMany(CorrespondenceReferral::class);
    }

    /**
     * Get the latest referral for the correspondence
     */
    public function latestReferral()
    {
        return $this->hasOne(CorrespondenceReferral::class)->latestOfMany();
    }

    /**
     * ============================================
     * النطاقات (Scopes)
     * ============================================
     */
    public function scopeByCorrespondenceNumber($query, $number)
    {
        return $query->where('correspondence_number', $number);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where(function ($q) use ($year) {
            $q->where('correspondence_number', 'like', "%/{$year}/%")
                ->orWhere('correspondence_number', 'like', "COR{$year}%");
        });
    }

    public function scopeByEntityCode($query, $entityCode)
    {
        return $query->where('correspondence_number', 'like', "{$entityCode}/%");
    }

    /**
     * Scope for filtering correspondences visible to an entity (sender, recipient, or referral).
     */
    public function scopeVisibleToEntity(Builder $query, $entityId): Builder
    {
        return $query->where(function ($q) use ($entityId) {
            $q->where('sender_entity_id', $entityId)
                ->orWhere('recipient_entity_id', $entityId)
                ->orWhereHas('referrals', function ($r) use ($entityId) {
                    $r->where('referred_to_entity_id', $entityId);
                });
        });
    }

    /**
     * ============================================
     * السمات (Accessors)
     * ============================================
     */

    /**
     * الحصول على لون الحالة
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'replied' => 'success',
            'referred' => 'info',
            'forwarded' => 'primary',
            'in_progress' => 'info',
            'returned' => 'danger',
            'closed' => 'secondary',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * الحصول على السنة من رقم المراسلة
     */
    public function getCorrespondenceYearAttribute()
    {
        if (! $this->isValidCorrespondenceNumber()) {
            return null;
        }

        if (str_starts_with($this->correspondence_number, 'COR')) {
            return substr($this->correspondence_number, 3, 4);
        }

        $parts = explode('/', $this->correspondence_number);

        return $parts[1] ?? null;
    }

    /**
     * الحصول على رمز الجهة من رقم المراسلة
     */
    public function getCorrespondenceEntityCodeAttribute()
    {
        if (! $this->isValidCorrespondenceNumber()) {
            return null;
        }

        if (str_starts_with($this->correspondence_number, 'COR')) {
            return 'COR';
        }

        $parts = explode('/', $this->correspondence_number);

        return $parts[0] ?? null;
    }

    /**
     * الحصول على الرقم التسلسلي من رقم المراسلة
     */
    public function getCorrespondenceSequenceAttribute()
    {
        if (! $this->isValidCorrespondenceNumber()) {
            return null;
        }

        if (str_starts_with($this->correspondence_number, 'COR')) {
            return (int) substr($this->correspondence_number, 7, 4);
        }

        $parts = explode('/', $this->correspondence_number);

        return (int) ($parts[2] ?? 0);
    }

    /**
     * الحصول على تنسيق جميل لرقم المراسلة
     */
    public function getFormattedCorrespondenceNumberAttribute()
    {
        if (! $this->correspondence_number) {
            return 'غير محدد';
        }

        if (str_starts_with($this->correspondence_number, 'TEMP-')) {
            return 'رقم مؤقت - '.$this->correspondence_number;
        }

        return $this->correspondence_number;
    }

    /**
     * ============================================
     * دوال مساعدة
     * ============================================
     */

    /**
     * الحصول على إحصائيات السنوية لرقم المراسلة
     */
    public static function getYearlyStatistics($entityId = null)
    {
        $query = self::query();

        if ($entityId) {
            $query->where('sender_entity_id', $entityId);
        }

        $results = $query->get()
            ->groupBy('correspondence_year')
            ->map(function ($correspondences, $year) {
                return [
                    'year' => $year,
                    'count' => $correspondences->count(),
                    'last_number' => $correspondences->max('correspondence_sequence'),
                ];
            })
            ->sortByDesc('year');

        return $results;
    }

    /**
     * البحث عن الرقم التسلسلي التالي لجهة معينة
     */
    public static function getNextSequenceNumber($entityCode, $year = null)
    {
        $year = $year ?? date('Y');

        if ($entityCode === 'COR') {
            $lastCorrespondence = self::withTrashed()
                ->where('correspondence_number', 'like', "COR{$year}%")
                ->orderBy('correspondence_number', 'desc')
                ->first();

            if ($lastCorrespondence) {
                $lastSequence = (int) substr($lastCorrespondence->correspondence_number, -4);

                return $lastSequence + 1;
            }

            return 1;
        }

        $lastCorrespondence = self::withTrashed()
            ->where('correspondence_number', 'like', "{$entityCode}/{$year}/%")
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastCorrespondence) {
            $parts = explode('/', $lastCorrespondence->correspondence_number);
            $lastSequence = (int) end($parts);

            return $lastSequence + 1;
        }

        return 1;
    }

    /**
     * التحقق من وجود رقم مراسلة مكرر
     */
    public static function isDuplicateCorrespondenceNumber($correspondenceNumber, $excludeId = null)
    {
        $query = self::where('correspondence_number', $correspondenceNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * التحقق من صحة رقم المراسلة قبل الحفظ
     */
    public function validateCorrespondenceNumber()
    {
        if (empty($this->correspondence_number)) {
            return ['valid' => false, 'message' => 'رقم المراسلة مطلوب'];
        }

        // التحقق من التنسيق
        if (! $this->isValidCorrespondenceNumber()) {
            return ['valid' => false, 'message' => 'تنسيق رقم المراسلة غير صحيح'];
        }

        // التحقق من التكرار
        if (self::isDuplicateCorrespondenceNumber($this->correspondence_number, $this->id)) {
            return ['valid' => false, 'message' => 'رقم المراسلة موجود مسبقاً'];
        }

        // التحقق من أن رمز الجهة يطابق الجهة المرسلة
        $entityCode = $this->correspondence_entity_code;
        $senderEntity = InternalEntity::find($this->sender_entity_id);

        if ($senderEntity && $senderEntity->entity_code !== $entityCode) {
            return ['valid' => false, 'message' => 'رمز الجهة في رقم المراسلة لا يطابق الجهة المرسلة'];
        }

        return ['valid' => true, 'message' => 'رقم المراسلة صحيح'];
    }

    /**
     * التحقق مما إذا كان المستخدم يمكنه عرض هذه المراسلة
     */
    public function canBeViewedBy($user)
    {
        // Users with "all" scope handled by DomainScope and hasPermission logic.

        // 1. Check if user belongs to sender or recipient entity
        $orgResolver = app(CorrespondenceOrganizationResolver::class);
        $userOrg = app(OrganizationResolver::class)->resolve($user);

        if ($orgResolver->matchesUserOrganization($userOrg, $this->sender_type, $this->sender_entity_id, $this->sender_authority_id) ||
            $orgResolver->matchesUserOrganization($userOrg, $this->recipient_type, $this->recipient_entity_id, $this->recipient_authority_id)) {
            return true;
        }

        // 2. Check if it was referred to user's entity
        if ($this->referrals()->where(function ($q) use ($userOrg) {
            if ($userOrg['type'] === 'external') {
                $q->where('referred_to_type', 'external')->where('referred_to_authority_id', $userOrg['id']);
            } else {
                $q->where(function ($sq) {
                    $sq->where('referred_to_type', 'internal')->orWhereNull('referred_to_type');
                })->where('referred_to_entity_id', $userOrg['id']);
            }
        })->exists()) {
            return true;
        }

        return false;
    }

    /**
     * التحقق مما إذا كان المستخدم يمكنه الرد على هذه المراسلة
     */
    public function canBeRepliedBy($user)
    {
        // Business logic: can't reply if closed
        if ($this->status === 'closed') {
            return false;
        }

        // The user must have the permission (handled by Policy, but kept here for model-level checks)
        // Note: we don't check roles here anymore.

        // Sender/Recipient/Referral can reply if they have the permission slug
        $orgResolver = app(CorrespondenceOrganizationResolver::class);
        $userOrg = app(OrganizationResolver::class)->resolve($user);

        if ($orgResolver->matchesUserOrganization($userOrg, $this->sender_type, $this->sender_entity_id, $this->sender_authority_id) ||
            $orgResolver->matchesUserOrganization($userOrg, $this->recipient_type, $this->recipient_entity_id, $this->recipient_authority_id)) {
            return true;
        }

        if ($this->referrals()->where(function ($q) use ($userOrg) {
            if ($userOrg['type'] === 'external') {
                $q->where('referred_to_type', 'external')->where('referred_to_authority_id', $userOrg['id']);
            } else {
                $q->where(function ($sq) {
                    $sq->where('referred_to_type', 'internal')->orWhereNull('referred_to_type');
                })->where('referred_to_entity_id', $userOrg['id']);
            }
        })->exists()) {
            return true;
        }

        return false;
    }

    /**
     * التحقق مما إذا كان المستخدم يمكنه إحالة هذه المراسلة
     */
    public function canBeReferredBy($user)
    {
        // Business logic
        if ($this->status === 'closed') {
            return false;
        }

        // Sender/Recipient/Referral can refer
        $orgResolver = app(CorrespondenceOrganizationResolver::class);
        $userOrg = app(OrganizationResolver::class)->resolve($user);

        if ($orgResolver->matchesUserOrganization($userOrg, $this->sender_type, $this->sender_entity_id, $this->sender_authority_id) ||
            $orgResolver->matchesUserOrganization($userOrg, $this->recipient_type, $this->recipient_entity_id, $this->recipient_authority_id)) {
            return true;
        }

        if ($this->referrals()->where(function ($q) use ($userOrg) {
            if ($userOrg['type'] === 'external') {
                $q->where('referred_to_type', 'external')->where('referred_to_authority_id', $userOrg['id']);
            } else {
                $q->where(function ($sq) {
                    $sq->where('referred_to_type', 'internal')->orWhereNull('referred_to_type');
                })->where('referred_to_entity_id', $userOrg['id']);
            }
        })->exists()) {
            return true;
        }

        return false;
    }

    /**
     * التحقق مما إذا كانت المراسلة قابلة للتعديل
     */
    public function canBeEdited()
    {
        // يمكن تعديل المراسلات التي لا تزال في حالة "قيد الانتظار" فقط
        return $this->status === 'pending';
    }

    /**
     * التحقق مما إذا كانت المراسلة قابلة للإغلاق
     */
    public function canBeClosed()
    {
        // لا يمكن إغلاق المراسلات المغلقة بالفعل
        return $this->status !== 'closed';
    }

    /**
     * التحقق مما إذا كانت المراسلة قابلة للحذف
     */
    public function canBeDeleted()
    {
        // يمكن حذف المراسلة فقط إذا كانت قيد الانتظار ولم يتم الرد عليها أو إحالتها
        return $this->status === 'pending' &&
            $this->replies()->count() === 0 &&
            $this->referrals()->count() === 0;
    }

    /**
     * الحصول على تسمية الحالة بالعربية
     */
    public function getStatusLabelAttribute()
    {
        $user = auth()->user();
        $userOrg = $user ? app(OrganizationResolver::class)->resolve($user) : null;
        $orgResolver = app(CorrespondenceOrganizationResolver::class);

        if ($this->status === 'pending') {
            if ($user && $orgResolver->matchesUserOrganization($userOrg, $this->sender_type, $this->sender_entity_id, $this->sender_authority_id)) {
                return 'بانتظار الرد';
            }

            return 'جديدة / قيد الانتظار';
        }

        $labels = [
            'pending' => 'جديدة / قيد الانتظار',
            'replied' => 'تم الرد',
            'referred' => 'تم الإحالة',
            'forwarded' => 'موجهة',
            'in_progress' => 'قيد المعالجة',
            'returned' => 'تم الإرجاع',
            'closed' => 'مغلقة',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * الحصول على تسمية الأولوية بالعربية
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
     * التحقق مما إذا كانت المراسلة متأخرة
     */
    public function getIsOverdueAttribute()
    {
        // المراسلة تعتبر متأخرة إذا كانت قيد الانتظار لأكثر من 7 أيام
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->created_at ? $this->created_at->diffInDays(now()) > 7 : false;
    }

    /**
     * نطاق المراسلات المتأخرة
     */
    public function scopeIsOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7));
    }

    /**
     * تاريخ الإنشاء منسق
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i') : '';
    }

    /**
     * عدد الأيام منذ الإنشاء
     */
    public function getDaysSinceCreationAttribute()
    {
        return $this->created_at ? $this->created_at->diffInDays(now()) : 0;
    }

    /**
     * Parent Correspondence Relationship
     */
    public function parent()
    {
        return $this->belongsTo(Correspondence::class, 'parent_id');
    }

    /**
     * Child Correspondences (Replies/Returns as new messages) Relationship
     */
    public function children()
    {
        return $this->hasMany(Correspondence::class, 'parent_id');
    }

    /**
     * Activities Relationship
     */
    public function activities()
    {
        return $this->hasMany(CorrespondenceActivity::class);
    }

    /**
     * Log an activity for this correspondence
     */
    public function logActivity($action, $notes = null, $userId = null)
    {
        return $this->activities()->create([
            'action' => $action,
            'notes' => $notes,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * عدد المرفقات
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
     * Get the movement log for this correspondence
     */
    public function movements()
    {
        return $this->movementLogs()->orderBy('action_date', 'asc');
    }

    /**
     * Static cache for full thread timelines.
     */
    protected static array $threadTimelineCache = [];

    /**
     * Get the full movement timeline for the entire thread
     */
    public function getFullThreadTimeline(): Collection
    {
        // 1. Traverse up to find the root ID
        $rootId = $this->id;
        $current = $this;

        static $parentMap = [];

        while ($current->parent_id) {
            $parentId = $current->parent_id;
            if (isset($parentMap[$parentId])) {
                $rootId = $parentMap[$parentId];
                break;
            }
            $parent = self::withoutGlobalScopes()->find($parentId);
            if (! $parent) {
                break;
            }
            $parentMap[$current->id] = $parent->parent_id ?: $parent->id;
            $current = $parent;
            $rootId = $current->id;
        }

        if (isset(self::$threadTimelineCache[$rootId])) {
            return self::$threadTimelineCache[$rootId];
        }

        // 2. BFS traversal to collect all descendant IDs in a single batch
        $threadIds = [$rootId];
        $currentLevelIds = [$rootId];
        while (! empty($currentLevelIds)) {
            $nextLevelIds = self::withoutGlobalScopes()
                ->whereIn('parent_id', $currentLevelIds)
                ->pluck('id')
                ->toArray();
            if (empty($nextLevelIds)) {
                break;
            }
            $threadIds = array_merge($threadIds, $nextLevelIds);
            $currentLevelIds = $nextLevelIds;
        }

        $timeline = CorrespondenceMovementLog::whereIn('correspondence_id', $threadIds)
            ->with(['user', 'correspondence'])
            ->orderBy('action_date', 'asc')
            ->get();

        self::$threadTimelineCache[$rootId] = $timeline;

        // Cache the result for all IDs in the thread to prevent redundant work
        foreach ($threadIds as $id) {
            self::$threadTimelineCache[$id] = $timeline;
        }

        return $timeline;
    }
}
