<?php

namespace App\Models;

use App\Enums\UserResponsibilityType;
use App\Services\OrganizationResolver;
use App\Services\PermissionResolver;
use App\Services\ProjectAuthorizationService;
use App\Services\SchemaCache;
use App\Services\UserDataScopeService;
use App\Traits\Auditable;
use App\Traits\HasActiveScope;
use App\Traits\HasCreatorTracking;
use App\Traits\HasDomainScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Auditable, HasActiveScope, HasApiTokens, HasCreatorTracking, HasDomainScope, HasFactory, Notifiable;

    public $statusColumn = 'status';

    protected $rolesCache = null;

    protected $localPermissionsCache = [];

    protected $fillable = [
        'user_id',
        'username',
        'name',
        'email',
        'password',
        'phone',
        'department',
        'organization_type',
        'entity_id',
        'authority_id',
        'governorate_id',
        'directorate_id',
        'geographic_scope_id',
        'administrative_scope_id',
        'work',
        'role_id',
        'status',
        'responsibility',
        'created_by',
        'updated_by',
        'creator_username',
        'creator_entity_id',
        'module_scopes',
        'module_geo_scopes',
        'must_change_password',
        'signature_path',
        'is_admin',
        'erpnext_id',
        'is_financial_user',
        'erpnext_user_id',
        'erpnext_sync_status',
        'erpnext_synced_at',
        'erpnext_sync_error',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_geographic_subset' => 'boolean',
        'module_scopes' => 'array',
        'module_geo_scopes' => 'array',
        'must_change_password' => 'boolean',
        'is_admin' => 'boolean',
        'responsibility' => UserResponsibilityType::class,
        'is_financial_user' => 'boolean',
        'erpnext_synced_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function (User $user) {
            if ($user->organization_type === OrganizationResolver::EXTERNAL) {
                $user->entity_id = null;
            } elseif ($user->organization_type === OrganizationResolver::INTERNAL) {
                $user->authority_id = null;
            } elseif ($user->authority_id && ! $user->entity_id) {
                $user->organization_type = OrganizationResolver::EXTERNAL;
            } elseif ($user->entity_id && ! $user->authority_id) {
                $user->organization_type = OrganizationResolver::INTERNAL;
            }
        });

        static::updated(function ($user) {
            $domainFields = [
                'entity_id',
                'administrative_scope_id',
                'governorate_id',
                'directorate_id',
                'geographic_scope_id',
                'role_id',
                'module_scopes',
                'module_geo_scopes',
            ];

            $changed = false;
            foreach ($domainFields as $field) {
                if ($user->wasChanged($field)) {
                    $changed = true;
                    break;
                }
            }

            if ($changed) {
                $user->clearDomainCache(
                    $user->getOriginal('entity_id'),
                    $user->getOriginal('administrative_scope_id')
                );
            }
        });
    }

    /**
     * Safely load missing relations to prevent LazyLoadingViolationExceptions when strict lazy loading is enabled.
     */
    protected function getRelationshipFromMethod($method)
    {
        $this->loadMissing($method);

        return $this->getRelation($method);
    }

    // ------------------------------------------------
    // Relationships
    // ------------------------------------------------

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    public function directorate()
    {
        return $this->belongsTo(Directorate::class, 'directorate_id');
    }

    public function entity()
    {
        return $this->belongsTo(InternalEntity::class, 'entity_id');
    }

    public function authority()
    {
        return $this->belongsTo(Authority::class, 'authority_id');
    }

    public function isInternal(): bool
    {
        if ($this->organization_type === null && $this->entity_id && $this->authority_id) {
            return false;
        }

        return (new OrganizationResolver)->type($this) === OrganizationResolver::INTERNAL;
    }

    public function isExternal(): bool
    {
        if ($this->organization_type === null && $this->entity_id && $this->authority_id) {
            return false;
        }

        return (new OrganizationResolver)->type($this) === OrganizationResolver::EXTERNAL;
    }

    /**
     * @return array{type: string, id: int|null, model: InternalEntity|Authority|null}
     */
    public function organization(): array
    {
        return (new OrganizationResolver)->resolve($this);
    }

    public function organizationModel(): InternalEntity|Authority|null
    {
        return (new OrganizationResolver)->model($this);
    }

    public function administrativeOrganization(): InternalEntity|Authority|null
    {
        return (new OrganizationResolver)->administrativeScope($this);
    }

    public function geographicScope()
    {
        return $this->belongsTo(Governorate::class, 'geographic_scope_id');
    }

    public function internalEntity()
    {
        return $this->belongsTo(InternalEntity::class, 'administrative_scope_id');
    }

    public function administrativeAuthority()
    {
        return $this->belongsTo(Authority::class, 'administrative_scope_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function geographicScopes(): HasMany
    {
        return $this->hasMany(UserGeographicScope::class, 'user_id');
    }

    public function taskAchievementReports(): HasMany
    {
        return $this->hasMany(TaskAchievementReport::class, 'user_id');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by', 'id');
    }

    public function updatedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'updated_by', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function projectApprovalsCreated(): HasMany
    {
        return $this->hasMany(ProjectApproval::class, 'created_by');
    }

    /**
     * The entity_approval_stages rows where this user is the configured responsible.
     * Used to guard against deactivating/moving a user who is assigned to an active stage.
     */
    public function responsibleApprovalStages(): HasMany
    {
        return $this->hasMany(EntityApprovalStage::class, 'responsible_user_id');
    }

    public function responsibleAuthorityApprovalStages(): HasMany
    {
        return $this->hasMany(AuthorityApprovalStage::class, 'responsible_user_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'receiver_id');
    }

    // ------------------------------------------------
    // Scopes
    // ------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('user_id', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
        });
    }

    // ------------------------------------------------
    // Utility Methods
    // ------------------------------------------------

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withInactive()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

    public static function generateNextUserId(): string
    {
        $prefix = 'MAFWRPRO';
        $userIds = static::withoutGlobalScopes()
            ->where('user_id', 'LIKE', $prefix.'%')
            ->pluck('user_id');

        $maxNumber = 0;
        foreach ($userIds as $uid) {
            $num = (int) substr((string) $uid, strlen($prefix));
            if ($num > $maxNumber) {
                $maxNumber = $num;
            }
        }

        $nextNumber = $maxNumber + 1;

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getFormattedUserIdAttribute(): string
    {
        return 'EMP'.str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->username;
    }

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    public function isAdmin(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        $this->loadMissing('role');
        $roleName = $this->role?->name ?? '';

        return in_array(strtolower($roleName), ['admin', 'مدير النظام']) ||
            $this->hasRole('admin') ||
            $this->hasRole('Admin') ||
            $this->hasRole('مدير النظام');
    }

    public function hasSignature(): bool
    {
        return ! empty($this->signature_path) && Storage::disk('public')->exists($this->signature_path);
    }

    public function isFinancialUser(): bool
    {
        return (bool) $this->is_financial_user;
    }

    public function isLinkedToErpNext(): bool
    {
        return ! empty($this->erpnext_user_id);
    }

    public function isErpNextSynced(): bool
    {
        return $this->erpnext_sync_status === 'synced';
    }

    public function shouldBeEnabledInErpNext(): bool
    {
        return $this->isFinancialUser() && $this->isActive();
    }

    // ------------------------------------------------
    // Null-Safe Attribute Accessors
    // ------------------------------------------------

    public function getGovernorateNameAttribute(): ?string
    {
        $this->loadMissing(['governorate', 'entity.authority.governorate']);

        return $this->governorate?->name
            ?? $this->entity?->authority?->governorate?->name
            ?? ($this->isCentralUser() ? 'المركز الرئيسي' : null);
    }

    public function getDirectorateNameAttribute(): ?string
    {
        $this->loadMissing(['directorate', 'entity.authority.directorate']);

        return $this->directorate?->name
            ?? $this->entity?->authority?->directorate?->name
            ?? ($this->isCentralUser() ? 'المركز الرئيسي' : null);
    }

    public function getAuthorityNameAttribute(): ?string
    {
        $this->loadMissing('entity.authority');

        return $this->entity?->authority?->name
            ?? ($this->isCentralUser() ? 'المركز الرئيسي' : null);
    }

    public function getRoleDisplayNameAttribute(): ?string
    {
        $this->loadMissing('role');

        return $this->role?->description ?? $this->role?->name ?? null;
    }

    public function getOrganizationNameAttribute(): ?string
    {
        $orgModel = $this->organizationModel();
        if (! $orgModel) {
            return $this->isCentralUser() ? 'المركز الرئيسي' : null;
        }

        return $orgModel instanceof Authority ? $orgModel->agency_name : $orgModel->name;
    }

    // ------------------------------------------------
    // Central / Geographic User Check
    // ------------------------------------------------

    /**
     * تحديد ما إذا كان المستخدم مركزياً (غير مرتبط بمحافظة أو مديرية)
     * المستخدم المركزى لا يملك governorate_id ولا directorate_id ولا أي ارتباط عبر geographicScopes
     */
    public function isCentralUser(): bool
    {
        // 1. A user with a directly assigned governorate or directorate is NOT central
        if ($this->governorate_id || $this->directorate_id) {
            return false;
        }

        // 2. Resolve geographic context from entity or geographic_scopes table
        $orgResolver = new OrganizationResolver;

        return count($orgResolver->governorateIds($this)) === 0 && count($orgResolver->directorateIds($this)) === 0;
    }

    /**
     * Returns true when the user has a directly-assigned governorate or directorate
     * (i.e. they are a regional/geographic user as opposed to a central one).
     */
    public function isGeographicUser(): bool
    {
        return ! $this->isCentralUser();
    }

    // ------------------------------------------------
    // Roles & Permissions
    // ------------------------------------------------

    public function getAllRoles(): array
    {
        if ($this->rolesCache !== null) {
            return $this->rolesCache;
        }

        $this->loadMissing('role');
        $roles = $this->role ? [$this->role->name] : [];

        $hasUserRolesTable = Cache::remember('sys_has_user_roles_table', now()->addDays(30), function () {
            return Schema::hasTable('user_roles');
        });

        if ($hasUserRolesTable) {
            $this->loadMissing('userRoles.role');
            $additionalRoles = $this->userRoles
                ->map(fn ($ur) => $ur->role?->name ?? (is_string($ur->role) ? $ur->role : null))
                ->filter()
                ->toArray();
            $roles = array_unique(array_merge($roles, $additionalRoles));
        }

        return $this->rolesCache = array_values(array_filter($roles));
    }

    public function getAllRoleIds(): array
    {
        $ids = [];
        if ($this->role_id) {
            $ids[] = (int) $this->role_id;
        }

        $hasUserRolesTable = Cache::remember('sys_has_user_roles_table', now()->addDays(30), function () {
            return Schema::hasTable('user_roles');
        });

        if ($hasUserRolesTable) {
            $this->loadMissing('userRoles.role');
            $additionalRoleIds = $this->userRoles
                ->map(fn ($ur) => $ur->role_id ?? $ur->role?->id)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->toArray();
            $ids = array_merge($ids, $additionalRoleIds);
        }

        $ids = array_values(array_unique(array_filter($ids)));
        sort($ids);

        return $ids;
    }

    public function getCompositePermissionsVersionHash(): string
    {
        $roleIds = $this->getAllRoleIds();
        if (empty($roleIds)) {
            return md5('r0_v1');
        }

        $parts = [];
        foreach ($roleIds as $roleId) {
            $version = static::getRolePermissionsVersion($roleId);
            $parts[] = "r{$roleId}_v{$version}";
        }

        return md5(implode('_', $parts));
    }

    public function permissions()
    {
        $roles = $this->getAllRoles();

        return Permission::whereHas('rolePermissions', function ($q) use ($roles) {
            $q->whereHas('role', fn ($q2) => $q2->whereIn('name', $roles));
        })->get();
    }

    public function getAllPermissionsSlugs(): array
    {
        if ($this->isAdmin()) {
            return PermissionResolver::getMatrixSlugs();
        }

        $compositeHash = $this->getCompositePermissionsVersionHash();

        return \Cache::remember("user_slugs_v4_{$this->id}_{$compositeHash}", now()->addDays(1), function () {
            // Matrix is the boundary for what's "active" in the system
            $matrixSlugs = PermissionResolver::getMatrixSlugs();

            $roles = $this->getAllRoles();
            $dbSlugs = Permission::whereHas('rolePermissions', function ($q) use ($roles) {
                $q->whereHas('role', fn ($q2) => $q2->whereIn('name', $roles));
            })->pluck('slug')->toArray();

            return array_intersect($dbSlugs, $matrixSlugs);
        });
    }

    public function hasRole($role): bool
    {
        if (is_numeric($role)) {
            return (int) $this->role_id === (int) $role;
        }

        return in_array($role, $this->getAllRoles());
    }

    public function hasAnyRole($roles): bool
    {
        $roles = (array) $roles;
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllRoles($roles): bool
    {
        $roles = (array) $roles;
        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Proxies to Laravel Gate for centralized authorization enforcement.
     * This ensures all checks, regardless of how they are called, use the same authoritative logic.
     */
    public function hasPermission($permissionName, $record = null): bool
    {
        return Gate::forUser($this)->allows((string) $permissionName, $record);
    }

    /**
     * Internal database-backed check. This is the logic that the Gate layer calls
     * to perform the actual role/permission lookup after matrix and admin checks.
     */
    public function checkDatabasePermission($ability, $record = null): bool
    {
        // 1. Database-backed Permission Check
        if (! $this->checkBasePermission($ability)) {
            return false;
        }

        // 2. Strict creation/editing context separation for projects.edit bypass
        if ($ability === 'projects.edit' && $record) {
            $userPermissions = $this->getAllPermissionsSlugs();
            $hasRealEdit = in_array('projects.edit', $userPermissions);
            $hasCreate = in_array('projects.create', $userPermissions);

            if (! $hasRealEdit && $hasCreate) {
                // Strictly separate creation/editing contexts.
                // A user with projects.create can ONLY modify project records that are in 'draft' status.
                $project = null;
                if ($record instanceof Project) {
                    $project = $record;
                } elseif (is_object($record) && isset($record->project)) {
                    $project = $record->project;
                }

                if ($project && ! in_array($project->status, ['draft', 'completed_draft', 'rolled_back_for_review'])) {
                    return false;
                }
            }
        }

        // 3. Record Scope Validation
        if ($record) {
            return $this->checkRecordScope($ability, $record);
        }

        return true;
    }

    protected function checkBasePermission($ability): bool
    {
        if (isset($this->localPermissionsCache[$ability])) {
            return $this->localPermissionsCache[$ability];
        }

        $userPermissions = $this->getAllPermissionsSlugs();

        $hasPerm = in_array($ability, $userPermissions);

        // Dynamic umbrella check: If they have projects.create, grant step actions and edit/view contexts for draft creation
        if (! $hasPerm && in_array('projects.create', $userPermissions)) {
            if (
                str_starts_with($ability, 'projects.step.') ||
                in_array($ability, ['projects.edit', 'projects.view', 'projects.view-details'])
            ) {

                // Centralized context-aware check (Concern #4)
                if (ProjectAuthorizationService::isCreationContext()) {
                    $hasPerm = true;
                }
            }
        }

        return $this->localPermissionsCache[$ability] = $hasPerm;
    }

    protected function checkRecordScope($ability, $record): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return app(UserDataScopeService::class)->canAccessRecord($record, $this);
    }

    public function hasAnyPermission($permissions): bool
    {
        $permissions = (array) $permissions;
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions($permissions): bool
    {
        $permissions = (array) $permissions;
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    public function clearPermissionCache(): void
    {
        $this->localPermissionsCache = [];
        $this->rolesCache = null;
        $this->unsetRelation('role');
        $this->unsetRelation('userRoles');
        $compositeHash = $this->getCompositePermissionsVersionHash();
        \Cache::forget("user_slugs_v4_{$this->id}_{$compositeHash}");

        // Also purge legacy v3 key if present
        $version = static::getRolePermissionsVersion($this->role_id ?? 0);
        \Cache::forget("user_slugs_v3_{$this->id}_v{$version}");
    }

    public static function getRolePermissionsVersion($roleId): int
    {
        return (int) \Cache::get("role_perms_version_{$roleId}", 1);
    }

    public static function incrementRolePermissionsVersion($roleId): void
    {
        \Cache::increment("role_perms_version_{$roleId}");
    }

    // ------------------------------------------------
    // Entity Display Scopes (safe)
    // ------------------------------------------------

    public function getEntityDisplayScope(string $type): string
    {
        if ($this->isAdmin()) {
            return 'all';
        }
        $this->loadMissing('role');

        return data_get($this->role?->entity_display_scope, $type, 'none');
    }

    public function getEntityAddScope(string $type): string
    {
        if ($this->isAdmin()) {
            return 'all';
        }
        $this->loadMissing('role');

        return data_get($this->role?->entity_add_scope, $type, 'none');
    }

    public function canViewInternalEntities(): bool
    {
        return $this->hasPermission('stakeholders.view-internal');
    }

    public function canViewAuthorities(): bool
    {
        return $this->hasPermission('stakeholders.view-external');
    }

    public function canViewEntitiesInDropdowns(): bool
    {
        return $this->hasPermission('stakeholders.view-in-dropdowns');
    }

    // ------------------------------------------------
    // Module Scopes (Geo & Admin)
    // ------------------------------------------------

    /**
     * الحصول على النطاق الجغرافي لوحدة معينة (مثل 'projects')
     * القيم الممكنة: 'all', 'same_governorate', 'same_directorate', 'custom', 'none'
     */
    public function getModuleGeoScope(string $module): string
    {
        if ($this->isAdmin()) {
            return 'all';
        }

        $this->loadMissing('role');

        $singular = str_ends_with($module, 's') ? substr($module, 0, -1) : $module;
        $plural = str_ends_with($module, 's') ? $module : $module.'s';

        // 1. User Level Override
        if (! in_array($module, ['internal-entities', 'authorities', 'internal_entities'])) {
            $userGeoScopes = $this->module_geo_scopes ?? [];
            $userGeoScope = $userGeoScopes[$module] ?? $userGeoScopes[$plural] ?? $userGeoScopes[$singular] ?? null;
            if ($userGeoScope !== null && $userGeoScope !== '') {
                return $userGeoScope;
            }
        }

        // 2. Role Level
        if (in_array($module, ['internal-entities', 'authorities', 'internal_entities'])) {
            $entityScopes = $this->role?->entity_display_scope ?? [];
            $key = ($module === 'internal-entities' || $module === 'internal_entities') ? 'internal_entities' : 'authorities';
            $geoScope = $entityScopes[$key] ?? null;
        } else {
            $geoScopes = $this->role?->module_geo_scopes ?? [];
            $geoScope = $geoScopes[$module] ?? $geoScopes[$plural] ?? $geoScopes[$singular] ?? null;
        }

        if ($geoScope !== null && $geoScope !== '') {
            return $geoScope;
        }

        return 'none';
    }

    /**
     * الحصول على النطاق الإداري للمستخدم لوحدة معينة
     * القيم الممكنة: 'all', 'none', 'user', 'own_entity', 'sub_entities'
     */
    public function getModuleAdminScope(string $module): string
    {
        if ($this->isAdmin()) {
            return 'all';
        }

        $this->loadMissing('role');

        $singular = str_ends_with($module, 's') ? substr($module, 0, -1) : $module;
        $plural = str_ends_with($module, 's') ? $module : $module.'s';

        // 1. User Level Override
        $userScopes = $this->module_scopes ?? [];
        $userScope = $userScopes[$module] ?? $userScopes[$plural] ?? $userScopes[$singular] ?? null;
        if ($userScope !== null && $userScope !== '') {
            return $userScope;
        }

        // 2. Role Level
        $roleScopes = $this->role?->module_scopes ?? [];
        $roleScope = $roleScopes[$module] ?? $roleScopes[$plural] ?? $roleScopes[$singular] ?? null;
        if ($roleScope !== null && $roleScope !== '') {
            return $roleScope;
        }

        return 'none';
    }

    // ------------------------------------------------
    // Geographic Helpers (Governorate / Directorate)
    // ------------------------------------------------

    protected $rawEntityDataCache = false;

    protected $assignedGovIdCache = false;

    protected $assignedDirIdCache = false;

    /**
     * الحصول على معرف المحافظة الأساسي للمستخدم (من direct assignment أو entity)
     */
    public function getAssignedGovernorateId(): ?int
    {
        if ($this->assignedGovIdCache !== false) {
            return $this->assignedGovIdCache;
        }

        return $this->assignedGovIdCache = (new OrganizationResolver)->governorateIds($this)[0] ?? null;
    }

    /**
     * الحصول على جميع معرفات المحافظات المرتبطة بالمستخدم (بما فيها geographicScopes)
     */
    public function getAssignedGovernorateIds(): array
    {
        return (new OrganizationResolver)->governorateIds($this);
    }

    /**
     * الحصول على معرف المديرية الأساسي للمستخدم
     */
    public function getAssignedDirectorateId(): ?int
    {
        if ($this->assignedDirIdCache !== false) {
            return $this->assignedDirIdCache;
        }

        return $this->assignedDirIdCache = (new OrganizationResolver)->directorateIds($this)[0] ?? null;
    }

    /**
     * الحصول على جميع معرفات المديريات المرتبطة بالمستخدم
     */
    public function getAssignedDirectorateIds(): array
    {
        return (new OrganizationResolver)->directorateIds($this);
    }

    /**
     * الحصول على معرفات المحافظات المخصصة للنطاق 'custom'
     */
    public function getCustomGovernorateIds(string $module): array
    {
        $this->loadMissing('role');
        $roleGeoScopes = $this->role?->module_geo_scopes ?? [];
        $customKey = $module.'_custom_govs';

        if (! empty($roleGeoScopes[$customKey])) {
            $raw = $roleGeoScopes[$customKey];
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && ! empty($decoded)) {
                    return array_map('intval', $decoded);
                }
            }
            if (is_array($raw) && ! empty($raw)) {
                return array_map('intval', $raw);
            }
        }

        $govIds = $this->getAssignedGovernorateIds();

        return ! empty($govIds) ? $govIds : [];
    }

    // ------------------------------------------------
    // Administrative Helpers (Entities)
    // ------------------------------------------------

    /**
     * الحصول على ID جهة المستخدم (لنطاق own_entity)
     */
    public function getUserEntityId(): ?int
    {
        return $this->organization()['id'];
    }

    /**
     * الحصول على جميع IDs الجهات التابعة (لنطاق sub_entities)
     * تشمل جهة المستخدم وجميع الجهات التابعة لها في التسلسل الهرمي
     */
    public function getSubEntityIds(): array
    {
        if ($this->isExternal() || $this->administrative_scope_id) {
            return (new OrganizationResolver)->administrativeOrganizationIds($this);
        }

        // If geographically restricted, do not grant broad central administrative access
        $hasGeoScopes = count($this->getAssignedGovernorateIds()) > 0 || count($this->getAssignedDirectorateIds()) > 0;
        if ($hasGeoScopes) {
            $entityData = $this->getRawEntityData();
            $isEntityCentral = false;

            if ($entityData && empty($entityData->governorate_id) && empty($entityData->directorate_id)) {
                if (empty($entityData->authority_id)) {
                    $isEntityCentral = true;
                } else {
                    $auth = DB::table('authorities')->where('id', $entityData->authority_id)->first();
                    if ($auth && empty($auth->governorate_id) && empty($auth->directorate_id)) {
                        $isEntityCentral = true;
                    }
                }
            }

            if ($isEntityCentral) {
                return [];
            }
        }

        $id = $this->entity_id;
        if (! $id) {
            return [];
        }

        return $this->getDescendantEntityIds((int) $id);
    }

    /**
     * الحصول على قائمة معرفات الجهات المسموح بها إدارياً للمستخدم في وحدة معينة
     * القيم المعادة:
     * - 'all' : جميع الجهات
     * - 'user' : المشاريع التي أنشأها المستخدم فقط
     * - array : قائمة معرفات جهات محددة
     */
    public function getAllowedAdministrativeIds(string $module): array|string
    {
        $scope = $this->getModuleAdminScope($module);

        switch ($scope) {
            case 'all':
                return 'all';
            case 'none':
                return [];
            case 'user':
                return 'user';
            case 'own':
            case 'own_entity':
                $entityId = $this->getUserEntityId();

                return $entityId ? [$entityId] : [];
            case 'sub_entities':
                return $this->getSubEntityIds();
            default:
                return [];
        }
    }

    /**
     * الحصول على قائمة معرفات الجهات التابعة لجهة معينة (recursive)
     */
    protected function getDescendantEntityIds(int $entityId): array
    {
        return InternalEntity::getAllDescendantIds($entityId);
    }

    // ------------------------------------------------
    // Raw DB Helpers
    // ------------------------------------------------

    public function getRawEntityData(): ?object
    {
        if ($this->rawEntityDataCache !== false) {
            return $this->rawEntityDataCache;
        }

        return $this->rawEntityDataCache = ($this->entity_id
            ? DB::table('entities')->where('id', $this->entity_id)->first()
            : null);
    }

    public function getRawEntityAuthorityId(): ?int
    {
        return $this->getRawEntityData()?->authority_id;
    }

    public function getAllowedGeographicIds(string $module): array|int|string
    {
        $scope = $this->getModuleGeoScope($module);

        if ($scope === 'all' || $scope === 'custom') {
            return $scope;
        }

        if ($scope === 'same_governorate' || $scope === 'governorate') {
            $id = (int) $this->getAssignedGovernorateId();

            return $id > 0 ? $id : [];
        }

        if ($scope === 'same_directorate' || $scope === 'directorate') {
            $id = (int) $this->getAssignedDirectorateId();

            return $id > 0 ? $id : [];
        }

        return [];
    }

    // ------------------------------------------------
    // Scope Check Methods (for records)
    // ------------------------------------------------

    public function isInAdminScope($record, $module = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return app(UserDataScopeService::class)->canAccessRecord($record, $this);
    }

    public function isInGeoScope($record, $module = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return app(UserDataScopeService::class)->canAccessRecord($record, $this);
    }

    // ------------------------------------------------
    // Helper to resolve module from record
    // ------------------------------------------------

    public function resolveModuleFromRecord($record): string
    {
        $table = method_exists($record, 'getTable') ? $record->getTable() : (is_string($record) ? $record : 'general');

        $map = [
            'correspondences' => 'correspondence',
            'project_requests' => 'project-requests',
            'project_approvals' => 'approvals',
            'internal_entities' => 'internal-entities',
            'plans' => 'planning',
            'authorities' => 'authorities',
            'projects' => 'projects',
            'empowerment_projects' => 'empowerment',
            'memoirs' => 'memoirs',
            'suggestions' => 'suggestions',
            'reports' => 'reports',
        ];

        return $map[$table] ?? $table;
    }

    public function detectUserColumn(string $table): ?string
    {
        $map = [
            'correspondences' => 'sender_user_id',
        ];

        if (isset($map[$table])) {
            return $map[$table];
        }

        $candidates = ['created_by_user_id', 'user_id', 'created_by', 'processed_by'];
        foreach ($candidates as $col) {
            if (SchemaCache::hasColumn($table, $col)) {
                return $col;
            }
        }

        return null;
    }

    public function detectAdminColumn(string $table): string|array|null
    {
        if ($table === 'correspondences') {
            return ['sender_entity_id', 'recipient_entity_id', 'referrals'];
        }

        $map = [
            'plans' => 'submitting_entity_id',
        ];

        if (isset($map[$table])) {
            return $map[$table];
        }

        // للمستخدمين الخارجيين، يجب البحث عن عمود authority_id أولاً
        if ($this->organization_type === 'external') {
            if (SchemaCache::hasColumn($table, 'authority_id')) {
                return 'authority_id';
            }
        }

        $candidates = ['internal_entity_id', 'entity_id', 'submitting_entity_id', 'sender_entity_id', 'created_by_entity', 'creator_entity_id'];
        foreach ($candidates as $col) {
            if (SchemaCache::hasColumn($table, $col)) {
                return $col;
            }
        }

        return null;
    }

    // ------------------------------------------------
    // Project specific helper
    // ------------------------------------------------

    public function canViewProject($project): bool
    {
        return $this->hasPermission('projects.view', $project);
    }

    // ------------------------------------------------
    // VisibleForUser scope (for internal use)
    // ------------------------------------------------

    public function scopeVisibleForUser($query)
    {
        $user = auth()->user();

        return $query->where(function ($q) use ($user) {
            if (! $user) {
                $q->whereRaw('0=1');

                return;
            }

            if ($user->hasPermission('users.view-all')) {
                return;
            }

            $q->where('created_by_entity', $user->entity_id);
        });
    }

    public function canViewAllCorrespondence(): bool
    {
        return $this->hasPermission('correspondence.view-all');
    }

    public function canViewAllPlans(): bool
    {
        return $this->hasPermission('plans.view-all');
    }

    public function getFilteredEntityIds(): array
    {
        return app(UserDataScopeService::class)->getUserEntityIds($this);
    }

    public function hasGeographicScope(): bool
    {
        return count($this->getAssignedGovernorateIds()) > 0
            || count($this->getAssignedDirectorateIds()) > 0;
    }

    /**
     * Clear all cache data associated with the user's previous/current domain,
     * then reload the data for the new domain.
     */
    public function clearDomainCache(?int $oldEntityId = null, ?int $oldAdminScopeId = null): void
    {
        $userId = $this->id;
        $roleId = $this->role_id ?? 0;

        // 1. Clear keys for the entity hierarchies (previous and current)
        $entityIdsToClear = array_filter(array_unique([
            $this->entity_id,
            $this->administrative_scope_id,
            $oldEntityId,
            $oldAdminScopeId,
        ]));

        foreach ($entityIdsToClear as $entId) {
            Cache::forget("descendant_entity_ids_{$entId}");
            Cache::forget("child_entity_ids_{$entId}");
            Cache::forget("suggestion_child_entities_{$entId}");
        }

        // 2. Clear keys for the user ID across all possible modules
        $modules = [
            'projects', 'planning', 'correspondence', 'internal_entities', 'internal-entities',
            'authorities', 'users', 'roles', 'domains', 'subdomains', 'interventions',
            'suggestions', 'chain_plans', 'value_chains', 'tasks', 'activity_assignments',
            'governorates', 'directorates',
        ];

        $roleIdsToClear = array_unique([$roleId, 0, 'null']);

        foreach ($modules as $module) {
            foreach ($roleIdsToClear as $rId) {
                Cache::forget("admin_scope_{$userId}_{$module}_{$rId}");
                Cache::forget("geo_scope_{$userId}_{$module}_{$rId}");
            }
        }

        // Clear user-specific slugs and assignments
        Cache::forget("my_assignments_{$userId}");

        $compositeHash = $this->getCompositePermissionsVersionHash();
        Cache::forget("user_slugs_v4_{$userId}_{$compositeHash}");

        $version = static::getRolePermissionsVersion($roleId);
        Cache::forget("user_slugs_v3_{$userId}_v{$version}");

        if ($this->isDirty('role_id')) {
            $oldRoleId = $this->getOriginal('role_id') ?? 0;
            $oldVersion = static::getRolePermissionsVersion($oldRoleId);
            Cache::forget("user_slugs_v3_{$userId}_v{$oldVersion}");
        }

        // 3. Reload/preload the hierarchy and permission data using only the new domain
        $this->getSubEntityIds();
        $this->getAllPermissionsSlugs();
    }
}
