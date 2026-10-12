<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InternalEntity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Admin']);
    }

    /**
     * Display a listing of the resource and permissions matrix.
     */
    public function index(Request $request)
    {
        $query = Role::withCount('users');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $roles = $query->paginate(15);

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissionMatrixData = $this->getPermissionMatrixData();
        $rolePermissions = [];
        $internalEntities = InternalEntity::active()->visibleToUser()->get();

        return view('roles.create', array_merge($permissionMatrixData, compact('rolePermissions', 'internalEntities')));
    }

    /**
     * Store a newly created resource in storage.
     * Note: Enforces unique role names via validation.
     * Multiple roles are allowed to share the same permission sets.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'full_access' => 'boolean',
            'permissions' => 'nullable|array',
            'module_scopes' => 'nullable|array',
            'entity_id' => 'nullable|exists:internal_entities,id',
            'entity_display_scope' => 'nullable|array',
            'entity_add_scope' => 'nullable|array',
            'module_geo_scopes' => 'nullable|array',
            'entity_display_scope.internal_entities' => 'nullable|string|in:all,same_governorate,same_directorate,none',
            'entity_display_scope.authorities' => 'nullable|string|in:all,same_governorate,same_directorate,none',
            'entity_add_scope.internal_entities' => 'nullable|string|in:all,same_governorate,same_directorate,none',
            'entity_add_scope.authorities' => 'nullable|string|in:all,same_governorate,same_directorate,none',
        ], [
            'name.unique' => 'هذا الدور موجود مسبقاً',
            'name.required' => 'يرجى إدخال اسم الدور',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true),
                'full_access' => $request->boolean('full_access'),
                'module_scopes' => $request->input('module_scopes', []),
                'module_geo_scopes' => $request->input('module_geo_scopes', []),
                'entity_display_scope' => $request->input('entity_display_scope', []),
                'entity_add_scope' => $request->input('entity_add_scope', []),
            ]);

            // Save permissions
            $permissionIds = $request->input('permissions', []);
            if ($request->has('permissions_json') && ! empty($request->permissions_json)) {
                $decoded = json_decode($request->permissions_json, true);
                if (is_array($decoded)) {
                    $permissionIds = $decoded;
                }
            }
            $moduleScopes = $request->input('module_scopes', []);
            $entityId = $request->input('entity_id');

            // Automatically add scope permissions from dropdown selections
            if (! empty($moduleScopes)) {
                foreach ($moduleScopes as $module => $scope) {
                    if (in_array($scope, ['all', 'own', 'parallel', 'user'])) {
                        $scopePerm = Permission::where('slug', "{$module}.view-{$scope}")->first();
                        if ($scopePerm) {
                            $permissionIds[] = $scopePerm->id;
                        }
                    }
                }
                $permissionIds = array_unique($permissionIds);
            }

            // Automatically add geographic scope permissions based on entity_display_scope
            $entityDisplayScope = $request->input('entity_display_scope', []);
            if (! empty($entityDisplayScope)) {
                $scopeMapping = [
                    'all' => 'all',
                    'same_governorate' => 'governorate',
                    'same_directorate' => 'directorate',
                ];

                if (isset($entityDisplayScope['internal_entities'])) {
                    $rawScope = $entityDisplayScope['internal_entities'];
                    $mappedScope = $scopeMapping[$rawScope] ?? null;
                    if ($mappedScope) {
                        $scopePerm = Permission::where('slug', "stakeholders.scope-{$mappedScope}-internal")->first();
                        if ($scopePerm) {
                            $permissionIds[] = $scopePerm->id;
                        }
                    }
                }

                if (isset($entityDisplayScope['authorities'])) {
                    $rawScope = $entityDisplayScope['authorities'];
                    $mappedScope = $scopeMapping[$rawScope] ?? null;
                    if ($mappedScope) {
                        $scopePerm = Permission::where('slug', "stakeholders.scope-{$mappedScope}-external")->first();
                        if ($scopePerm) {
                            $permissionIds[] = $scopePerm->id;
                        }
                    }
                }
                $permissionIds = array_unique($permissionIds);
            }

            if (! empty($permissionIds) && is_array($permissionIds)) {
                $insertData = [];
                $now = now();
                foreach ($permissionIds as $permId) {
                    $insertData[] = [
                        'role_id' => $role->id,
                        'permission_id' => $permId,
                        'entity_id' => $entityId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                RolePermission::insert($insertData);
            }

            DB::commit();

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'model_type' => 'Role',
                'model_id' => $role->id,
                'new_values' => $role->toArray(),
                'description' => 'تم إنشاء دور جديد: '.$role->name,
                'ip_address' => $request->ip(),
            ]);

            Log::info("Role created successfully: {$role->name} (ID: {$role->id}) by User ID: ".auth()->id());

            return redirect()->route('roles.index')->with('success', 'تم إضافة الدور الجديد وصلاحياته بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create role: '.$e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء الدور: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        if ($this->isAdminRole($role)) {
            abort(403, 'صلاحيات مدير النظام غير قابلة للتعديل.');
        }

        $permissionMatrixData = $this->getPermissionMatrixData();

        // Get current role permissions
        $rolePermissionRows = RolePermission::where('role_id', $role->id)->get(['permission_id', 'entity_id']);
        $rolePermissions = $rolePermissionRows->pluck('permission_id')->toArray();
        $internalEntities = InternalEntity::active()->visibleToUser()->get();

        // Find common entity_id if all permissions belong to same entity
        $commonEntityId = null;
        if (count($rolePermissions) > 0) {
            $entityIds = $rolePermissionRows->pluck('entity_id')->unique();
            if ($entityIds->count() === 1) {
                $commonEntityId = $entityIds->first();
            }
        }

        return view('roles.edit', array_merge($permissionMatrixData, compact('role', 'rolePermissions', 'internalEntities', 'commonEntityId')));
    }

    /**
     * Update the specified resource in storage.
     * Note: Role names must remain unique across the system.
     */
    public function update(Request $request, Role $role)
    {
        if ($this->isAdminRole($role)) {
            abort(403, 'صلاحيات مدير النظام غير قابلة للتعديل.');
        }
        $oldName = $role->name;

        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name,'.$role->id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'full_access' => 'boolean',
            'permissions' => 'nullable|array',
            'module_scopes' => 'nullable|array',
            'entity_id' => 'nullable|exists:internal_entities,id',
            'entity_display_scope' => 'nullable|array',
            'entity_add_scope' => 'nullable|array',
            'module_geo_scopes' => 'nullable|array',
            'entity_display_scope.internal_entities' => 'nullable|string|in:all,same_governorate,same_directorate,none',
            'entity_display_scope.authorities' => 'nullable|string|in:all,same_governorate,same_directorate,none',
            'entity_add_scope.internal_entities' => 'nullable|string|in:all,same_governorate,same_directorate,none',
            'entity_add_scope.authorities' => 'nullable|string|in:all,same_governorate,same_directorate,none',
        ]);

        try {
            DB::beginTransaction();

            $role->update([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', $role->is_active),
                'full_access' => $request->boolean('full_access'),
                'module_scopes' => $request->input('module_scopes', []),
                'module_geo_scopes' => $request->input('module_geo_scopes', []),
                'entity_display_scope' => $request->input('entity_display_scope', []),
                'entity_add_scope' => $request->input('entity_add_scope', []),
            ]);

            // Update permissions
            RolePermission::where('role_id', $role->id)->delete();

            $permissionIds = $request->input('permissions', []);
            if ($request->has('permissions_json') && ! empty($request->permissions_json)) {
                $decoded = json_decode($request->permissions_json, true);
                if (is_array($decoded)) {
                    $permissionIds = $decoded;
                }
            }
            $moduleScopes = $request->input('module_scopes', []);
            $entityId = $request->input('entity_id');

            // Automatically add scope permissions from dropdown selections
            if (! empty($moduleScopes)) {
                foreach ($moduleScopes as $module => $scope) {
                    if (in_array($scope, ['all', 'own', 'parallel', 'user'])) {
                        $scopePerm = Permission::where('slug', "{$module}.view-{$scope}")->first();
                        if ($scopePerm) {
                            $permissionIds[] = $scopePerm->id;
                        }
                    }
                }
                $permissionIds = array_unique($permissionIds);
            }

            // Automatically add geographic scope permissions based on entity_display_scope
            $entityDisplayScope = $request->input('entity_display_scope', []);
            if (! empty($entityDisplayScope)) {
                $scopeMapping = [
                    'all' => 'all',
                    'same_governorate' => 'governorate',
                    'same_directorate' => 'directorate',
                ];

                if (isset($entityDisplayScope['internal_entities'])) {
                    $rawScope = $entityDisplayScope['internal_entities'];
                    $mappedScope = $scopeMapping[$rawScope] ?? null;
                    if ($mappedScope) {
                        $scopePerm = Permission::where('slug', "stakeholders.scope-{$mappedScope}-internal")->first();
                        if ($scopePerm) {
                            $permissionIds[] = $scopePerm->id;
                        }
                    }
                }

                if (isset($entityDisplayScope['authorities'])) {
                    $rawScope = $entityDisplayScope['authorities'];
                    $mappedScope = $scopeMapping[$rawScope] ?? null;
                    if ($mappedScope) {
                        $scopePerm = Permission::where('slug', "stakeholders.scope-{$mappedScope}-external")->first();
                        if ($scopePerm) {
                            $permissionIds[] = $scopePerm->id;
                        }
                    }
                }
                $permissionIds = array_unique($permissionIds);
            }

            if (! empty($permissionIds) && is_array($permissionIds)) {
                $insertData = [];
                $now = now();
                foreach ($permissionIds as $permId) {
                    $insertData[] = [
                        'role_id' => $role->id,
                        'permission_id' => $permId,
                        'entity_id' => $entityId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                RolePermission::insert($insertData);
            }

            DB::commit();
            User::incrementRolePermissionsVersion($role->id);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'model_type' => 'Role',
                'model_id' => $role->id,
                'old_values' => $role->getOriginal(),
                'new_values' => $role->toArray(),
                'description' => 'تم تحديث بيانات الدور: '.$role->name,
                'ip_address' => $request->ip(),
            ]);

            Log::info("Role updated successfully: {$role->name} (ID: {$role->id}) by User ID: ".auth()->id());

            return redirect()->route('roles.index')->with('success', 'تم تحديث الدور والصلاحيات بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update role (ID: {$role->id}): ".$e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الدور: '.$e->getMessage());
        }
    }

    /**
     * Toggle the status of a role.
     */
    public function toggleStatus(Role $role)
    {
        if ($this->isAdminRole($role)) {
            return redirect()->back()->with('error', 'لا يمكن تعطيل دور مدير النظام الأساسي');
        }

        $role->update(['is_active' => ! $role->is_active]);

        $statusAr = $role->is_active ? 'تفعيل' : 'تعطيل';
        $statusEn = $role->is_active ? 'enabled' : 'disabled';

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'toggle_status',
            'model_type' => 'Role',
            'model_id' => $role->id,
            'description' => "تم {$statusAr} الدور: ".$role->name,
            'ip_address' => request()->ip(),
        ]);

        Log::info("Role status {$statusEn}: {$role->name} (ID: {$role->id}) by User ID: ".auth()->id());

        return redirect()->back()->with('success', "تم {$statusAr} الدور بنجاح");
    }

    /**
     * Update roles and permissions.
     */
    public function updatePermissions(Request $request)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'entity_display_scope' => 'nullable|array',
            'entity_add_scope' => 'nullable|array',
            'module_scopes' => 'nullable|array',
            'module_geo_scopes' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $permissions = $request->input('permissions', []);
            $entityDisplayScopes = $request->input('entity_display_scope', []);
            $entityAddScopes = $request->input('entity_add_scope', []);
            $moduleScopesRequest = $request->input('module_scopes', []);
            $moduleGeoScopesRequest = $request->input('module_geo_scopes', []);

            // Check if permissions are keyed by role name (from permissions matrix)
            // or if it's a simple array (from individual role edit)
            $isMultiRole = ! empty($permissions) && is_array(reset($permissions));

            if ($isMultiRole) {
                // Handle permissions matrix format: permissions[RoleName][] = [1, 2, 3]
                // and potentially scopes[RoleName][module] = scope
                foreach ($permissions as $roleName => $permissionIds) {
                    if (in_array(strtolower($roleName), ['admin', 'مدير النظام'])) {
                        continue;
                    }
                    $role = Role::where('name', $roleName)->first();

                    if (! $role) {
                        continue; // Skip if role not found
                    }

                    // Delete existing permissions for this role
                    RolePermission::where('role_id', $role->id)->delete();

                    // Insert new permissions
                    if (! empty($permissionIds) && is_array($permissionIds)) {
                        $permissionIds = array_unique($permissionIds);

                        // Automatically add geographic scope permissions based on entity_display_scope if provided for this role
                        // (Assuming matrix might send them per role, but if not, we use the global ones if intended)
                        // In the current matrix blade, they are global/single-role oriented.
                        // If multiple roles are edited at once, the matrix needs to be role-aware for scopes too.

                        $insertData = [];
                        $now = now();

                        foreach ($permissionIds as $permId) {
                            $insertData[] = [
                                'role_id' => $role->id,
                                'permission_id' => $permId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }

                        RolePermission::insert($insertData);
                    }

                    // If the request contains scopes (usually when editing a single role via the matrix UI)
                    if (! empty($entityDisplayScopes)) {
                        $role->update(['entity_display_scope' => $entityDisplayScopes]);
                    }
                    if (! empty($entityAddScopes)) {
                        $role->update(['entity_add_scope' => $entityAddScopes]);
                    }
                    if (! empty($moduleScopesRequest)) {
                        $role->update(['module_scopes' => $moduleScopesRequest]);
                    }
                    if (! empty($moduleGeoScopesRequest)) {
                        $role->update(['module_geo_scopes' => $moduleGeoScopesRequest]);
                    }
                    User::incrementRolePermissionsVersion($role->id);
                }

                DB::commit();

                Log::info('Permissions matrix updated for all roles by User ID: '.auth()->id());

                return redirect()->route('roles.index')
                    ->with('success', 'تم تحديث صلاحيات جميع الأدوار بنجاح');

            } else {
                // Handle single role format (backward compatibility)
                $request->validate([
                    'role_id' => 'required|exists:roles,id',
                ]);

                $roleId = $request->role_id;
                $role = Role::findOrFail($roleId);
                if ($this->isAdminRole($role)) {
                    return redirect()->back()->with('error', 'صلاحيات مدير النظام غير قابلة للتعديل.');
                }
                $permissionIds = $permissions; // Already a simple array
                if ($request->has('permissions_json') && ! empty($request->permissions_json)) {
                    $decoded = json_decode($request->permissions_json, true);
                    if (is_array($decoded)) {
                        $permissionIds = $decoded;
                    }
                }
                $moduleScopes = $request->input('module_scopes', []);
                $moduleGeoScopes = $request->input('module_geo_scopes', []);
                $entityDisplayScope = $request->input('entity_display_scope', []);
                $entityAddScope = $request->input('entity_add_scope', []);

                // Delete all existing permissions for this role
                RolePermission::where('role_id', $role->id)->delete();

                // Automatically add scope permissions from dropdown selections
                if (! empty($moduleScopes)) {
                    foreach ($moduleScopes as $module => $scope) {
                        if (in_array($scope, ['all', 'own', 'parallel', 'user'])) {
                            $scopePerm = Permission::where('slug', "{$module}.view-{$scope}")->first();
                            if ($scopePerm) {
                                $permissionIds[] = $scopePerm->id;
                            }
                        }
                    }
                }

                // Automatically add geographic scope permissions based on entity_display_scope
                if (! empty($entityDisplayScope)) {
                    $scopeMapping = [
                        'all' => 'all',
                        'same_governorate' => 'governorate',
                        'same_directorate' => 'directorate',
                    ];

                    foreach (['internal_entities', 'authorities'] as $type) {
                        if (isset($entityDisplayScope[$type])) {
                            $rawScope = $entityDisplayScope[$type];
                            $mappedScope = $scopeMapping[$rawScope] ?? null;
                            $suffix = ($type === 'internal_entities') ? 'internal' : 'external';
                            if ($mappedScope) {
                                $scopePerm = Permission::where('slug', "stakeholders.scope-{$mappedScope}-{$suffix}")->first();
                                if ($scopePerm) {
                                    $permissionIds[] = $scopePerm->id;
                                }
                            }
                        }
                    }
                }

                // Insert new permissions
                if (! empty($permissionIds) && is_array($permissionIds)) {
                    $permissionIds = array_unique($permissionIds);
                    $insertData = [];
                    $now = now();

                    foreach ($permissionIds as $permId) {
                        $insertData[] = [
                            'role_id' => $role->id,
                            'permission_id' => $permId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    RolePermission::insert($insertData);
                }

                // Update scopes
                $role->update([
                    'module_scopes' => $moduleScopes,
                    'module_geo_scopes' => $moduleGeoScopes,
                    'entity_display_scope' => $entityDisplayScope,
                    'entity_add_scope' => $entityAddScope,
                ]);

                DB::commit();
                User::incrementRolePermissionsVersion($role->id);

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'update_permissions',
                    'model_type' => 'Role',
                    'model_id' => $role->id,
                    'description' => 'تم تحديث صلاحيات الدور: '.$role->name,
                    'ip_address' => $request->ip(),
                ]);

                Log::info("Permissions updated for role: {$role->name} (ID: {$role->id}) by User ID: ".auth()->id());

                return redirect()->route('roles.index')
                    ->with('success', 'تم تحديث صلاحيات الدور بنجاح');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update permissions: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'حدث خطأ أثناء تحديث الصلاحيات: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        if ($this->isAdminRole($role)) {
            return redirect()->back()->with('error', 'لا يمكن حذف دور مدير النظام الأساسي');
        }

        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', 'لا يمكن حذف الدور لأنه مرتبط بمستخدمين');
        }

        $roleName = $role->name;
        $roleId = $role->id;

        try {
            DB::transaction(function () use ($role) {
                RolePermission::where('role_id', $role->id)->delete();
                $role->delete();
            });

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'model_type' => 'Role',
                'model_id' => $roleId,
                'description' => 'تم حذف الدور: '.$roleName,
                'ip_address' => request()->ip(),
            ]);

            Log::info("Role deleted successfully: {$roleName} (ID: {$roleId}) by User ID: ".auth()->id());

            return redirect()->back()->with('success', 'تم حذف الدور وكافة صلاحياته المرتبطة');
        } catch (\Exception $e) {
            Log::error("Failed to delete role (ID: {$roleId}): ".$e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف الدور: '.$e->getMessage());
        }
    }

    protected function isAdminRole($role): bool
    {
        return false;
    }

    /**
     * AJAX Endpoint to return permissions matrix table rows for a specific main module group
     */
    public function getMatrixModuleRows(Request $request)
    {
        $mainModuleKey = $request->query('main_module', 'projects');
        $roleId = $request->query('role_id');
        $viewOnly = $request->boolean('viewOnly', false);

        $role = $roleId ? Role::find($roleId) : null;
        $rolePermissions = $role ? RolePermission::where('role_id', $role->id)->pluck('permission_id')->toArray() : [];

        $matrixData = $this->getPermissionMatrixData();
        $subModulesMap = $this->getSubModulesMap();

        if ($mainModuleKey !== 'all') {
            $allowedSubs = $subModulesMap[$mainModuleKey] ?? [$mainModuleKey];
            $allModuleNames = $matrixData['allModuleNames']->filter(function ($moduleName) use ($allowedSubs, $mainModuleKey) {
                $mKey = strtolower($moduleName);
                if (in_array($mKey, $allowedSubs)) {
                    return true;
                }
                if ($mKey === $mainModuleKey || str_starts_with($mKey, $mainModuleKey.'-') || str_starts_with($mKey, $mainModuleKey.'_')) {
                    return true;
                }

                return false;
            })->values();
        } else {
            $allModuleNames = $matrixData['allModuleNames'];
        }

        return view('roles.partials._permissions_matrix_rows', array_merge($matrixData, [
            'allModuleNames' => $allModuleNames,
            'rolePermissions' => $rolePermissions,
            'role' => $role,
            'rolePermsLookup' => array_flip($rolePermissions),
            'viewOnly' => $viewOnly,
            'mainModuleKey' => $mainModuleKey,
        ]));
    }

    public function getSubModulesMap(): array
    {
        return [
            'dashboard' => ['dashboard', 'home'],
            'projects' => ['projects', 'projects-implementation', 'project-requests', 'project_requests', 'disbursements', 'project-drafts', 'project-files', 'project-risks', 'project-outputs', 'project-activity', 'project-documents', 'project-drafts-enhanced', 'executive-activities', 'execution', 'execution-log', 'schedule', 'quality', 'erp-integration'],
            'tasks' => ['tasks', 'task_reports'],
            'requests-descend' => ['requests_descend', 'requests-descend'],
            'correspondence' => ['correspondence', 'referrals', 'project-referrals', 'memoirs', 'messaging', 'department-reports'],
            'planning' => ['planning', 'plans', 'reviews'],
            'reports' => [
                'reports', 'reports-implementation', 'reports-quality', 'reports-financial', 'reports-progress', 'reports-erpnext-financial', 'reports-pl-expense-summary', 'reports-profit-and-loss', 'reports-official-summary', 'reports-stakeholders', 'reports-permissions',
            ],
            'empowerment' => ['empowerment', 'financial-justifications'],
            'value-chains' => ['value-chains', 'value-chain-members', 'global-financings', 'chain_plans'],
            'encoding' => ['encoding', 'configuration', 'programs', 'domains', 'subdomains', 'interventions', 'governorates', 'directorates', 'sub-areas', 'villages', 'financial-items', 'funding-sources', 'financing-types', 'form-financing', 'formfinancing', 'subfinancing-forms', 'authorities', 'main-routers', 'sub-routers', 'priorities', 'units', 'beneficiary-groups', 'signatures', 'internal-entities', 'entity-officers', 'entity-authorities', 'entities', 'associations', 'donors', 'executors', 'funded-entities', 'target-categories', 'participation', 'beneficiaries', 'stages', 'supervising-entities', 'supervisors', 'entity-father', 'entity-scopes', 'execution-procedures', 'financing-forms', 'procedure-budget-justifications'],
            'users' => ['users', 'roles', 'roles-permissions', 'audit-logs', 'audit_logs', 'system-operations', 'authentication', 'profile', 'activity-history', 'approvals', 'notifications'],
        ];
    }

    /**
     * Categorize permissions by type (sidebars, pages, actions, scopes)
     */
    private function categorizePermissions($allPermissions)
    {
        $categorized = [
            'sidebars' => [],
            'pages' => [],
            'actions' => [],
            'scopes' => [],
        ];

        foreach ($allPermissions as $permission) {
            $slug = $permission->slug;
            $module = $permission->module;

            $type = 'actions';

            // Check for scope permissions (view-own, view-all, view-parallel, view-user)
            if (str_contains($slug, 'view-own') || str_contains($slug, 'view-all') || str_contains($slug, 'view-parallel') || str_contains($slug, 'view-user')) {
                $type = 'scopes';
            }
            // Treat UI buttons as action permissions so they remain editable in the matrix.
            elseif (in_array($permission->type, ['button', 'icon'], true)) {
                $type = 'actions';
            }
            // Check for sidebar permissions
            elseif (str_contains($slug, 'sidebar')) {
                $type = 'sidebars';
            }
            // Check for view permissions (but not view-own or view-all)
            elseif (str_contains($slug, 'view')) {
                $type = 'pages';
            }

            if (! isset($categorized[$type][$module])) {
                $categorized[$type][$module] = [];
            }
            $categorized[$type][$module][] = (object) [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
            ];
        }

        return $categorized;
    }

    private function getPermissionMatrixData(): array
    {
        return Cache::remember('roles.permission_matrix_v4', now()->addHours(24), function (): array {
            $allPermissions = Permission::select('id', 'name', 'module', 'slug', 'type')
                ->orderBy('module')
                ->orderBy('slug')
                ->get();

            $categorized = $this->categorizePermissions($allPermissions);
            $allModuleNames = $allPermissions
                ->pluck('module')
                ->unique()
                ->sort()
                ->reject(fn ($moduleName) => strtolower($moduleName) === 'main_modules')
                ->values();
            $modulePermsMap = $this->buildModulePermissionsMap($allModuleNames, $categorized);

            return [
                'allModuleNames' => $allModuleNames,
                'categorized' => $categorized,
                'moduleTranslations' => $this->getModuleTranslations(),
                'moduleConfigs' => [],
                'modulePermsMap' => $modulePermsMap,
                'uniqueExtraSlugKeys' => [],
                'mainModulePerms' => $this->getMainModulePermissions($categorized),
                'subModulesMap' => $this->getSubModulesMap(),
            ];
        });
    }

    private function buildModulePermissionsMap($allModuleNames, array $categorized): array
    {
        $standardSuffixes = ['sidebar', 'view', 'show', 'index', 'create', 'add', 'edit', 'update', 'delete', 'destroy', 'print', 'search', 'export', 'import'];
        $modulePermsMap = [];

        foreach ($allModuleNames as $moduleName) {
            $modulePermissions = [];

            foreach (['sidebars', 'pages', 'buttons', 'icons', 'actions', 'settings'] as $category) {
                if (isset($categorized[$category][$moduleName])) {
                    foreach ($categorized[$category][$moduleName] as $permission) {
                        $modulePermissions[] = $permission;
                    }
                }
            }

            $indexedPermissions = [];
            foreach ($modulePermissions as $permission) {
                $slugParts = explode('.', $permission->slug);
                $indexedPermissions[end($slugParts)] = $permission;
            }

            $standardPermissionIds = [];
            foreach ($standardSuffixes as $suffix) {
                if (isset($indexedPermissions[$suffix])) {
                    $standardPermissionIds[$indexedPermissions[$suffix]->id] = true;
                }
                if (isset($indexedPermissions[$suffix.'s'])) {
                    $standardPermissionIds[$indexedPermissions[$suffix.'s']->id] = true;
                }
            }

            $extraPermissions = [];
            foreach ($modulePermissions as $permission) {
                if (! isset($standardPermissionIds[$permission->id])) {
                    $extraPermissions[] = $permission;
                }
            }

            $modulePermsMap[$moduleName] = [
                'perms' => $modulePermissions,
                'indexed' => $indexedPermissions,
                'extras' => $extraPermissions,
            ];
        }

        return $modulePermsMap;
    }

    private function getUniqueExtraSlugKeys(array $modulePermsMap): array
    {
        $standardSuffixes = ['sidebar', 'view', 'show', 'index', 'create', 'add', 'edit', 'update', 'delete', 'destroy', 'export', 'import'];
        $extraSlugKeys = [];

        foreach ($modulePermsMap as $modulePermissions) {
            $standardPermissionIds = [];
            $indexedPermissions = $modulePermissions['indexed'] ?? [];

            foreach ($standardSuffixes as $suffix) {
                if (isset($indexedPermissions[$suffix])) {
                    $standardPermissionIds[$indexedPermissions[$suffix]->id] = true;
                }
                if (isset($indexedPermissions[$suffix.'s'])) {
                    $standardPermissionIds[$indexedPermissions[$suffix.'s']->id] = true;
                }
            }

            foreach ($modulePermissions['perms'] ?? [] as $permission) {
                if (! isset($standardPermissionIds[$permission->id])) {
                    $slugParts = explode('.', $permission->slug);
                    $extraSlugKeys[end($slugParts)] = true;
                }
            }
        }

        $uniqueExtraSlugKeys = array_keys($extraSlugKeys);
        sort($uniqueExtraSlugKeys);

        return $uniqueExtraSlugKeys;
    }

    private function getMainModulePermissions(array $categorized)
    {
        $mainModulePermissions = collect();

        foreach (['sidebars', 'pages', 'actions'] as $category) {
            if (isset($categorized[$category]['main_modules'])) {
                $mainModulePermissions = $mainModulePermissions->merge($categorized[$category]['main_modules']);
            }
        }

        return $mainModulePermissions;
    }

    /**
     * Get module translation labels
     */
    private function getModuleTranslations()
    {
        return [
            'home' => 'الرئيسية والإحصائيات',
            'authentication' => 'الأمان والتحقق',
            'projects' => 'إدارة المشاريع',
            'projects-implementation' => 'مشاريع التنفيذ',
            'project-requests' => 'طلبات المشاريع',
            'project_requests' => 'Unified Project Requests',
            'project-drafts' => 'مسودات المشاريع',
            'project-files' => 'ملفات المشروع',
            'project-risks' => 'مخاطر المشروع',
            'project-outputs' => 'مخرجات المشروع',
            'project-activity' => 'نشاط وسجل المشروع',
            'project-documents' => 'وثائق المشاريع',
            'project-drafts-enhanced' => 'المسودات المحسنة',
            'executive-activities' => 'الأنشطة التنفيذية',
            'erp-integration' => 'التكامل مع ERPNext',
            'execution' => 'عمليات التنفيذ والمتابعة',
            'execution-log' => 'سجل عمليات التنفيذ',
            'schedule' => 'الجدول الزمني',
            'quality' => 'إدارة الجودة',
            'task' => 'إدارة المهام',
            'task' => 'Task Management',
            'reports' => 'التقارير: لوحة التحكم الموحدة',
            'reports-implementation' => 'التقارير: ملخص التنفيذ الميداني',
            'reports-quality' => 'التقارير: مقاييس الجودة',
            'reports-financial' => 'التقارير: المؤشرات المالية للمشاريع',
            'reports-progress' => 'التقارير: تتبع الإنجاز والتقدم الزمني',
            'reports-erpnext-financial' => 'التقارير: التقرير المالي ERPNext',
            'reports-pl-expense-summary' => 'التقارير: ملخص الإيرادات والنفقات',
            'reports-profit-and-loss' => 'التقارير: الأرباح والخسائر',
            'reports-official-summary' => 'التقارير: التقرير الرسمي الشامل',
            'reports-stakeholders' => 'التقارير: أصحاب المصلحة والجهات',
            'reports-permissions' => 'التقارير: مراجعة صلاحيات النظام',
            'referrals' => 'إدارة الإحالات',
            'correspondence' => 'المراسلات الصادرة والواردة',
            'financial-justifications' => 'المبررات المالية',
            'messaging' => 'التواصل الداخلي',
            'department-reports' => 'تقارير الإدارات',
            'planning' => 'إدارة التخطيط',
            'reviews' => 'أعمال المراجعة',
            'internal-entities' => 'الجهات الداخلية',
            'entities' => 'الجهات والشركاء',
            'authorities' => 'الجهات الخارجية',
            'supervising-entities' => 'الجهات الإشرافية',
            'supervisors' => 'إدارة المشرفين',
            'governorates' => 'إدارة المحافظات',
            'directorates' => 'إدارة المديريات',
            'sub-areas' => 'إدارة السكن/العزل',
            'villages' => 'إدارة القرى/الأحياء',
            'users' => 'إدارة المستخدمين',
            'roles' => 'إدارة الأدوار',
            'roles-permissions' => 'صلاحيات النظام',
            'audit_logs' => 'سجلات الرقابة',
            'system-operations' => 'عمليات النظام',
            'programs' => 'إدارة البرامج',
            'domains' => 'المجالات الرئيسية',
            'subdomains' => 'المجالات الفرعية',
            'interventions' => 'إدارة التدخلات',
            'associations' => 'إدارة الجمعيات',
            'donors' => 'إدارة المانحين',
            'executors' => 'الجهات المنفذة',
            'funding-sources' => 'مصادر التمويل',
            'financing-types' => 'أنواع التمويل',
            'form-financing' => 'نماذج التمويل',
            'subfinancing-forms' => 'نماذج التمويل الفرعية',
            'funded-entities' => 'الجهات الممولة',
            'target-categories' => 'الفئات المستهدفة',
            'financial-items' => 'البنود المالية',
            'units' => 'إدارة الوحدات',
            'priorities' => 'إدارة الأولويات',
            'participation' => 'إدارة المشاركات',
            'main-routers' => 'الموجهات الرئيسية',
            'sub-routers' => 'الموجهات الفرعية',
            'beneficiaries' => 'إدارة المستفيدين',
            'beneficiary-groups' => 'فئات المستفيدين',
            'stages' => 'مراحل الاعتماد',
            'profile' => 'الملف الشخصي',
            'activity-history' => 'سجل العمليات التاريخي',
            'approvals' => 'الاعتمادات والموافقات',
            'audit-logs' => 'سجلات الرقابة والتدقيق',
            'configuration' => 'إعدادات النظام العامة',
            'entity-father' => 'الجهات الرئيسية (الأب)',
            'entity-scopes' => 'نطاقات العمل للجهات',
            'execution-procedures' => 'إجراءات التنفيذ المتبعة',
            'financing-forms' => 'نماذج التمويل المعتمدة',
            'notifications' => 'مركز الإشعارات',
            'procedure-budget-justifications' => 'تبريرات ميزانية الإجراءات',
            'plans' => 'خطط العمل والمشاريع',
            'disbursements' => 'خطط ودفعات الصرف والإخلاء',
            'project-requests' => 'طلبات المشاريع',
        ];
    }
}
