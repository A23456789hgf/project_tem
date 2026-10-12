<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasActiveScope;
use App\Traits\HasCreatorTracking;
use App\Traits\HasDomainScope;
use App\Traits\HasModelVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InternalEntity extends Model
{
    use Auditable, HasActiveScope, HasCreatorTracking, HasDomainScope, HasFactory, HasModelVisibility {
        HasModelVisibility::scopeVisibleToUser insteadof HasDomainScope;
    }

    protected $table = 'internal_entities';

    protected $fillable = [
        'status',
        'name',
        'entity_type',
        'entity_code',
        'parent_id',
        'authority_id',
        'governorate_id',
        'directorate_id',
        'is_active',
        'creator_username',
        'creator_entity_id',
        'erpnext_id',
        'erpnext_type',
        'erpnext_parent_id',
        'is_ministry_root',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_ministry_root' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // العلاقات
    // ============================================

    public function parent()
    {
        return $this->belongsTo(InternalEntity::class, 'parent_id');
    }

    public function authority()
    {
        return $this->belongsTo(Authority::class, 'authority_id');
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    public function directorate()
    {
        return $this->belongsTo(Directorate::class, 'directorate_id');
    }

    public function children()
    {
        return $this->hasMany(InternalEntity::class, 'parent_id');
    }

    /**
     * The configured approval stages for this entity.
     * Each row represents an ENABLED stage with its responsible user.
     */
    public function approvalStages()
    {
        return $this->hasMany(EntityApprovalStage::class, 'entity_id')
            ->orderBy('stage_order');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'entity_id');
    }

    public function responsibilities()
    {
        return $this->hasMany(EntityResponsibility::class, 'internal_entity_id');
    }

    public function sentCorrespondences()
    {
        return $this->hasMany(Correspondence::class, 'sender_entity_id');
    }

    public function receivedCorrespondences()
    {
        return $this->hasMany(Correspondence::class, 'recipient_entity_id');
    }

    public function memoirs()
    {
        return $this->hasMany(Memoir::class, 'entity_id');
    }

    // ============================================
    // الحصول على الأب بأمان (بدون Lazy Loading)
    // ============================================

    /**
     * يرجع الأب بدون ما يرمي LazyLoadingViolationException.
     * - إذا العلاقة محمّلة مسبقاً (eager loaded) -> يستعملها.
     * - وإلا -> يعمل استعلام مباشر بدون Global Scopes.
     */
    public function getParentModel(): ?self
    {
        if ($this->relationLoaded('parent')) {
            return $this->getRelation('parent');
        }

        if (! $this->parent_id) {
            return null;
        }

        return static::withoutGlobalScopes()
            ->whereKey($this->parent_id)
            ->first();
    }

    /**
     * Scope لتحميل الأب مع أي استعلام.
     */
    public function scopeWithParent($query)
    {
        return $query->with('parent');
    }

    // ============================================
    // Children / Descendants
    // ============================================

    /**
     * Get all children (descendants) of this entity recursively.
     */
    public function getAllChildren()
    {
        $ids = static::getAllDescendantIds($this->id);

        if (empty($ids)) {
            return collect();
        }

        // Remove the current entity from the list if present
        $ids = array_diff($ids, [$this->id]);

        if (empty($ids)) {
            return collect();
        }

        return static::whereIn('id', $ids)->get();
    }

    /**
     * Get all descendant IDs for a given parent ID recursively.
     */
    public static function getAllDescendantIds($parentId): array
    {
        if (! $parentId) {
            return [];
        }

        $toProcess = [(int) $parentId];
        $allDescendants = [(int) $parentId];
        $visited = [(int) $parentId => true];

        while (! empty($toProcess)) {
            $children = DB::table('internal_entities')
                ->whereIn('parent_id', $toProcess)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            if (empty($children)) {
                break;
            }

            $newToProcess = [];
            foreach ($children as $child) {
                if (! isset($visited[$child])) {
                    $visited[$child] = true;
                    $allDescendants[] = $child;
                    $newToProcess[] = $child;
                }
            }

            $toProcess = $newToProcess;
        }

        return array_unique($allDescendants);
    }

    /**
     * جلب جميع معرفات الأطفال بشكل متكرر — يستخدم DB مباشرة لتجاوز DomainScope
     */
    public static function getAllChildrenIds($parentId): array
    {
        return static::getAllDescendantIds($parentId);
    }

    /**
     * جلب جميع معرفات الآباء بشكل متكرر — يستخدم DB مباشرة لتجاوز DomainScope
     * يُرجع من الأب المباشر إلى الجذر: [parent, grandparent, root]
     */
    public static function getAllParentIds(int $entityId): array
    {
        $ids = [];
        $currentId = $entityId;
        $visited = [(int) $entityId => true];

        while (true) {
            $parent = DB::table('internal_entities')
                ->where('id', $currentId)
                ->value('parent_id');

            if (! $parent) {
                break;
            }

            $parentId = (int) $parent;
            if (isset($visited[$parentId])) {
                break; // Prevent infinite loop due to circular reference
            }

            $ids[] = $parentId;
            $visited[$parentId] = true;
            $currentId = $parentId;
        }

        return $ids;
    }

    /**
     * جلب كل الجهات في محافظة — يستخدم DB مباشرة لتجاوز DomainScope
     */
    public static function getAllByGovernorate($governorateId): array
    {
        static $govCache = [];
        $cacheKey = (int) $governorateId;

        if (isset($govCache[$cacheKey])) {
            return $govCache[$cacheKey];
        }

        return $govCache[$cacheKey] = DB::table('internal_entities')
            ->leftJoin('authorities', 'internal_entities.authority_id', '=', 'authorities.id')
            ->where(function ($query) use ($governorateId) {
                $query->where('internal_entities.governorate_id', $governorateId)
                    ->orWhere('authorities.governorate_id', $governorateId);
            })
            ->pluck('internal_entities.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    /**
     * جلب كل الجهات في مديرية — يستخدم DB مباشرة لتجاوز DomainScope
     */
    public static function getAllByDirectorate($directorateId): array
    {
        static $dirCache = [];
        $cacheKey = (int) $directorateId;

        if (isset($dirCache[$cacheKey])) {
            return $dirCache[$cacheKey];
        }

        return $dirCache[$cacheKey] = DB::table('internal_entities')
            ->leftJoin('authorities', 'internal_entities.authority_id', '=', 'authorities.id')
            ->where(function ($query) use ($directorateId) {
                $query->where('internal_entities.directorate_id', $directorateId)
                    ->orWhere('authorities.directorate_id', $directorateId);
            })
            ->pluck('internal_entities.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    // ============================================
    // Scopes
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeIsMinistryRoot($query)
    {
        return $query->where('is_ministry_root', true);
    }

    public static function getMinistryRoot(): ?self
    {
        return self::isMinistryRoot()->first();
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * فلترة حسب governorate أو directorate
     */
    public function scopeByLocation($query, $governorateId = null, $directorateId = null)
    {
        if ($governorateId) {
            $query->where('governorate_id', $governorateId);
        }
        if ($directorateId) {
            $query->where('directorate_id', $directorateId);
        }

        return $query;
    }

    // ============================================
    // Methods: Hierarchy & Approval
    // ============================================

    /**
     * المسار الكامل: الجذر > ... > الأب > الابنة
     * يستعمل استعلام واحد بدل Lazy Loading.
     */
    public function getHierarchyPath(): string
    {
        // إن كان الأب محمّلاً مسبقاً، نستفيد منه
        $parentIds = static::getAllParentIds($this->id);

        if (empty($parentIds)) {
            return (string) $this->name;
        }

        $names = static::withoutGlobalScopes()
            ->whereIn('id', $parentIds)
            ->pluck('name', 'id');

        $path = [];
        // من الجذر نزولاً للأب المباشر
        foreach (array_reverse($parentIds) as $id) {
            if (isset($names[$id])) {
                $path[] = $names[$id];
            }
        }

        $path[] = $this->name;

        return implode(' > ', $path);
    }

    public function getFullNameAttribute(): string
    {
        return $this->getHierarchyPath();
    }

    /**
     * سلسلة الاعتماد من الابنة حتى الجذر (الابنة أولاً).
     */
    public function getApprovalChainToRoot(): Collection
    {
        $chain = collect([$this]);
        $current = $this;

        while ($parent = $current->getParentModel()) {
            $chain->push($parent);
            $current = $parent;
        }

        return $chain;
    }

    /**
     * كل الآباء من الأب المباشر حتى الجذر.
     */
    public function getParentChain(): Collection
    {
        $parents = collect();
        $current = $this->getParentModel();

        while ($current) {
            $parents->push($current);
            $current = $current->getParentModel();
        }

        return $parents;
    }

    public function isRootEntity(): bool
    {
        return is_null($this->parent_id);
    }

    public function getHierarchyLevel(): int
    {
        return count(static::getAllParentIds($this->id));
    }

    /**
     * Generate or return entity code
     */
    public function getEntityCode(): string
    {
        if (empty($this->entity_code)) {
            $words = explode(' ', trim($this->name));
            $code = count($words) >= 2
                ? substr($words[0], 0, 1).substr($words[1], 0, 1)
                : substr($this->name, 0, 2);

            $code = strtoupper(preg_replace('/[^A-Z0-9]/', 'X', $code));
            $code = str_pad($code, 2, 'X');

            $this->entity_code = $code;
            $this->save();
        }

        return $this->entity_code;
    }

    public function setEntityCode(string $code): void
    {
        $this->entity_code = strtoupper(
            substr(preg_replace('/[^A-Za-z0-9]/', '', $code), 0, 2)
        );
        $this->save();
    }

    /**
     * Find governorate-level ancestor (بدون Lazy Loading).
     */
    private function findGovernorateAncestor(InternalEntity $entity): ?InternalEntity
    {
        if (is_null($entity->parent_id)) {
            return $entity;
        }

        $current = $entity;

        while (($parent = $current->getParentModel()) && ! is_null($parent->parent_id)) {
            $current = $parent;
        }

        return $current->parent_id ? $current : $entity;
    }

    public static function getEntitiesForUserGovernorate(User $user): array
    {
        $entityIds = [];

        foreach ($user->geographicScopes as $scope) {
            if ($scope->governorate_id) {
                $entityIds = array_merge(
                    $entityIds,
                    static::getAllByGovernorate($scope->governorate_id)
                );
            }
        }

        return array_values(array_unique($entityIds));
    }
}
