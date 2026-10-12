<?php

namespace App\Models;

use App\Services\OrganizationResolver;
use App\Traits\HasActiveScope;
use App\Traits\HasCreatorTracking;
use App\Traits\HasDomainScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Authority extends Model
{
    use HasActiveScope, HasCreatorTracking, HasDomainScope, HasFactory;

    protected $fillable = [
        'status',
        'agency_name',
        'parent_id',
        'governorate_id',
        'directorate_id',
        'type_entity_id',
        'is_active',
        'is_funded',
        'creator_username',
        'creator_entity_id',
        'entity_scope',
        'financing_type_id',
        'financing_form_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_funded' => 'boolean',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Authority::class, 'parent_id');
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    public function directorate(): BelongsTo
    {
        return $this->belongsTo(Directorate::class, 'directorate_id');
    }

    /**
     * العلاقة مع نوع الكيان (Entity Type)
     */
    public function typeEntity(): BelongsTo
    {
        return $this->belongsTo(TypeEntity::class, 'type_entity_id');
    }

    public function financingType(): BelongsTo
    {
        return $this->belongsTo(FinancingType::class, 'financing_type_id');
    }

    public function financingForm(): BelongsTo
    {
        return $this->belongsTo(FormFinancing::class, 'financing_form_id');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function approvalFlows(): HasMany
    {
        return $this->hasMany(ApprovalFlow::class);
    }

    public function projectApprovals(): HasMany
    {
        return $this->hasMany(ProjectApproval::class);
    }

    // -----------------------------------------------------------------------
    // Helper Methods
    // -----------------------------------------------------------------------

    /**
     * دالة مساعدة للحصول على جميع الأحفاد (recursive)
     */
    public function getAllChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * دالة للتحقق مما إذا كانت الجهة لها أطفال
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * دالة للحصول على المسار الكامل
     */
    public function getFullPathAttribute(): string
    {
        $path = [];
        $current = $this;

        while ($current) {
            $path[] = $current->safe_name; // استخدام الاسم الآمن
            $current = $current->parent;
        }

        return implode(' → ', array_reverse($path));
    }

    /**
     * Accessor for `name` attribute to maintain backward compatibility.
     * Returns the safe agency name.
     */
    public function getNameAttribute()
    {
        return $this->safe_name;
    }

    /**
     * Accessor للحصول على اسم آمن (لا يحتوي على مراجع إكسل)
     */
    public function getSafeNameAttribute(): string
    {
        $name = $this->agency_name ?? '';

        // إذا كان الاسم يبدأ بـ '='، نعتبره مرجع إكسل غير صالح
        if (strpos($name, '=') === 0) {
            // يمكنك تسجيل خطأ أو إرجاع اسم افتراضي
            // نُرجِع الاسم مع إزالة '=' إن أمكن، أو نرجِع "جهة غير محددة"
            // محاولة استخراج النص بعد '=' (قد يكون مرجع خلية)
            $cleaned = ltrim($name, '=');
            // إذا كان الاسم بعد '=' فارغاً أو رقماً فقط، نرجِع اسماً افتراضياً
            if (empty($cleaned) || is_numeric($cleaned)) {
                return 'جهة غير محددة (بيانات ملوثة)';
            }

            return $cleaned;
        }

        return $name;
    }

    /**
     * التحقق من صحة الاسم (لا يبدأ بـ '=' وليس فارغاً)
     */
    public function isNameValid(): bool
    {
        $name = $this->agency_name ?? '';

        return ! empty($name) && strpos($name, '=') !== 0;
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    protected static function booted()
    {
        parent::booted();

        static::addGlobalScope('valid_names', function (Builder $builder) {
            $builder->whereNotNull('authorities.agency_name')
                ->where('authorities.agency_name', '!=', '')
                ->where('authorities.agency_name', 'NOT LIKE', '=%');
        });
    }

    /**
     * Scope لاستبعاد الجهات ذات الأسماء غير الصالحة (تبدأ بـ '=' أو فارغة)
     */
    public function scopeWithValidNames(Builder $query): Builder
    {
        return $query->whereNotNull('authorities.agency_name')
            ->where('authorities.agency_name', '!=', '')
            ->where('authorities.agency_name', 'NOT LIKE', '=%');
    }

    /**
     * Scope لعكس السابق: استبعاد الصالحة فقط (للاستخدام في التنظيف)
     */
    public function scopeWithoutValidNames(Builder $query): Builder
    {
        return $query->withoutGlobalScope('valid_names')->where(function ($q) {
            $q->whereNull('authorities.agency_name')
                ->orWhere('authorities.agency_name', '=', '')
                ->orWhere('authorities.agency_name', 'LIKE', '=%');
        });
    }

    /**
     * Scope للجهات التي يمكن للمستخدم إضافتها
     * يستخدم في نماذج الإنشاء والتعديل
     */
    public function scopeAddableToUser(Builder $query)
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // دائماً نطبق فلتر الأسماء الصالحة
        $query->withValidNames();

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isExternal()) {
            return (new OrganizationResolver)->applyVisibleAuthorities($query, $user);
        }

        if (method_exists($user, 'canAddAuthorities') && $user->canAddAuthorities()) {
            return $query;
        }

        if ($user->entity && $user->entity->authority_id) {
            $authorityId = $user->entity->authority_id;

            return $query->where(function ($q) use ($authorityId) {
                $q->where('id', $authorityId)
                    ->orWhere('parent_id', $authorityId);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope للجهات التي يمكن للمستخدم رؤيتها
     */
    public function scopeVisibleToUser(Builder $query)
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // دائماً نطبق فلتر الأسماء الصالحة
        $query->withValidNames();

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isExternal()) {
            return (new OrganizationResolver)->applyVisibleAuthorities($query, $user);
        }

        if ($user->entity && $user->entity->authority_id) {
            $authorityId = $user->entity->authority_id;

            return $query->where(function ($q) use ($authorityId) {
                $q->where('id', $authorityId)
                    ->orWhere('parent_id', $authorityId)
                    ->orWhereIn('id', function ($subQuery) use ($authorityId) {
                        $subQuery->select('id')
                            ->from('authorities')
                            ->where('parent_id', $authorityId);
                    });
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope للجهات النشطة فقط
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للجهات غير النشطة
     */
    public function scopeInactive(Builder $query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope للجهات الممولة
     */
    public function scopeFunded(Builder $query)
    {
        return $query->where('is_funded', true);
    }

    /**
     * Scope للجهات غير الممولة
     */
    public function scopeUnfunded(Builder $query)
    {
        return $query->where('is_funded', false);
    }

    /**
     * Scope للبحث في اسم الجهة (يبحث في الاسم الأصلي)
     */
    public function scopeSearch(Builder $query, string $searchTerm)
    {
        return $query->where('agency_name', 'like', "%{$searchTerm}%");
    }

    /**
     * Scope للجهات التي ليس لها أب (الجذور)
     */
    public function scopeRoots(Builder $query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope للجهات التابعة لجهة معينة
     */
    public function scopeChildrenOf(Builder $query, int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope للجهات التي لها أطفال
     */
    public function scopeHasChildren(Builder $query)
    {
        return $query->has('children');
    }

    /**
     * Scope للجهات التي ليس لها أطفال
     */
    public function scopeWithoutChildren(Builder $query)
    {
        return $query->doesntHave('children');
    }

    /**
     * Scope للجهات حسب المحافظة
     */
    public function scopeInGovernorate(Builder $query, int $governorateId)
    {
        return $query->where('governorate_id', $governorateId);
    }

    /**
     * Scope للجهات حسب المديرية
     */
    public function scopeInDirectorate(Builder $query, int $directorateId)
    {
        return $query->where('directorate_id', $directorateId);
    }

    /**
     * Scope للجهات حسب نوع الكيان
     */
    public function scopeOfType(Builder $query, int $typeEntityId)
    {
        return $query->where('type_entity_id', $typeEntityId);
    }

    /**
     * Scope للجهات حسب النطاق (داخلي/خارجي)
     */
    public function scopeWithEntityScope(Builder $query, string $scope)
    {
        return $query->where('entity_scope', $scope);
    }

    // -----------------------------------------------------------------------
    // Helper Functions for Tree Operations
    // -----------------------------------------------------------------------

    /**
     * الحصول على جميع أسلاف الجهة (من الأعلى إلى الأسفل)
     */
    public function getAncestors()
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * الحصول على جميع أحفاد الجهة (بشكل متكرر)
     */
    public function getDescendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    /**
     * التحقق مما إذا كانت الجهة لها أحفاد
     */
    public function hasDescendants(): bool
    {
        return $this->getDescendants()->isNotEmpty();
    }

    /**
     * الحصول على مستوى الجهة في الشجرة (0 للجذور)
     */
    public function getLevel(): int
    {
        $level = 0;
        $current = $this->parent;

        while ($current) {
            $level++;
            $current = $current->parent;
        }

        return $level;
    }

    /**
     * الحصول على شجرة الجهة كاملة (للعرض)
     */
    public function getTree()
    {
        return [
            'id' => $this->id,
            'name' => $this->safe_name, // استخدام الاسم الآمن
            'children' => $this->children->map(function ($child) {
                return $child->getTree();
            })->toArray(),
        ];
    }

    /**
     * الحصول على مسار الجهة كمصفوفة
     */
    public function getPathArray(): array
    {
        $path = [];
        $current = $this;

        while ($current) {
            $path[] = [
                'id' => $current->id,
                'name' => $current->safe_name, // استخدام الاسم الآمن
            ];
            $current = $current->parent;
        }

        return array_reverse($path);
    }

    // -----------------------------------------------------------------------
    // Additional Scopes for Bulk Operations
    // -----------------------------------------------------------------------

    /**
     * Scope للجهات التي يمكن تعديلها بشكل جماعي
     */
    public function scopeBulkEditable(Builder $query)
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // تطبيق فلتر الأسماء الصالحة
        $query->withValidNames();

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope للجهات التي يمكن حذفها
     */
    public function scopeDeletable(Builder $query)
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // تطبيق فلتر الأسماء الصالحة
        $query->withValidNames();

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope لتنظيف البيانات: استرجاع الجهات ذات الأسماء غير الصالحة لتحديثها
     */
    public function scopeInvalidNames(Builder $query)
    {
        return $query->withoutValidNames();
    }
}
