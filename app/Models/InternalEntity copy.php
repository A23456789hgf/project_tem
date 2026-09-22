<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasCreatorTracking;
use App\Traits\HasDomainScope;
use App\Traits\HasModelVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalEntity extends Model
{
    use Auditable, HasCreatorTracking, HasDomainScope, HasFactory, HasModelVisibility {
        HasModelVisibility::scopeVisibleToUser insteadof HasDomainScope;
    }

    protected $table = 'internal_entities';

    protected $fillable = [
        'name',
        'entity_code',
        'parent_id',
        'authority_id',
        'governorate_id',
        'directorate_id',
        'is_active',
        'creator_username',
        'creator_entity_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ============================================
     * Global Scope: Administrative & Geographic Visibility
     * ============================================
     */

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

    public static function getAllChildrenIds($parentId)
    {
        $ids = [$parentId];

        $children = InternalEntity::where('parent_id', $parentId)->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, InternalEntity::getAllChildrenIds($childId));
        }

        return $ids;
    }

    public function users()
    {
        return $this->hasMany(User::class, 'entity_id');
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
    // Scopes
    // ============================================
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
    public function getHierarchyPath()
    {
        $path = [];
        $entity = $this;

        while ($entity) {
            array_unshift($path, $entity->name);
            $entity = $entity->parent;
        }

        return implode(' > ', $path);
    }

    public function getFullNameAttribute(): string
    {
        return $this->getHierarchyPath();
    }

    public function getAllChildren()
    {
        $children = collect();

        foreach ($this->children as $child) {
            $children->push($child);
            $children = $children->merge($child->getAllChildren());
        }

        return $children;
    }

    public function getApprovalChainToRoot()
    {
        $chain = collect([$this]);
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            $chain->push($current);
        }

        return $chain;
    }

    public function getParentChain()
    {
        $parents = collect();
        $current = $this->parent;

        while ($current) {
            $parents->push($current);
            $current = $current->parent;
        }

        return $parents;
    }

    public function isRootEntity(): bool
    {
        return is_null($this->parent_id);
    }

    public function getHierarchyLevel(): int
    {
        $level = 0;
        $current = $this;

        while ($current->parent) {
            $level++;
            $current = $current->parent;
        }

        return $level;
    }

    /**
     * Generate or return entity code
     */
    public function getEntityCode(): string
    {
        if (empty($this->entity_code)) {
            $words = explode(' ', trim($this->name));
            $code = count($words) >= 2 ? substr($words[0], 0, 1).substr($words[1], 0, 1) : substr($this->name, 0, 2);
            $code = strtoupper(preg_replace('/[^A-Z0-9]/', 'X', $code));
            $code = str_pad($code, 2, 'X');
            $this->entity_code = $code;
            $this->save();
        }

        return $this->entity_code;
    }

    public function setEntityCode(string $code): void
    {
        $this->entity_code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $code), 0, 2));
        $this->save();
    }

    /**
     * Find governorate-level ancestor
     */
    private function findGovernorateAncestor(InternalEntity $entity): ?InternalEntity
    {
        if (is_null($entity->parent_id)) {
            return $entity;
        }

        $current = $entity;
        while ($current->parent && ! is_null($current->parent->parent_id)) {
            $current = $current->parent;
        }

        return $current->parent_id ? $current : $entity;
    }
}
