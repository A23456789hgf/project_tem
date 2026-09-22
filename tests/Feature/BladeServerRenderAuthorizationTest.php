<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\PermissionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BladeServerRenderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        $this->seed(PermissionsTableSeeder::class);
        PermissionResolver::clearCache();
        Cache::flush();
    }

    /**
     * TEST 1: CREATE BUTTON SERVER-RENDER AUTHORIZATION & LIFECYCLE
     * Without projects.create: index returns 200, HTML does NOT render create button.
     * With projects.create: index returns 200, HTML renders create button.
     * After revoke: button disappears again from server-rendered HTML.
     */
    public function test_create_button_server_render_authorization_lifecycle(): void
    {
        $role = Role::create(['name' => 'ProjectViewer_'.uniqid(), 'is_active' => true]);
        $viewPerm = Permission::where('slug', 'projects.view')->firstOrFail();
        $createPerm = Permission::where('slug', 'projects.create')->firstOrFail();

        RolePermission::create(['role_id' => $role->id, 'permission_id' => $viewPerm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create(['role_id' => $role->id]);

        // 1. Initial State: view only -> 200 OK but NO create link/button in rendered HTML
        $response = $this->actingAs($user)->get(route('projects.index'));
        $response->assertStatus(200);
        $response->assertDontSee(route('projects.create'));

        // 2. Grant projects.create -> create link/button appears in rendered HTML
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $createPerm->id]);
        User::incrementRolePermissionsVersion($role->id);
        $user->clearPermissionCache();

        $userWithCreate = $user->fresh();
        $userWithCreate->clearPermissionCache();

        $response2 = $this->actingAs($userWithCreate)->get(route('projects.index'));
        $response2->assertStatus(200);
        $response2->assertSee(route('projects.create'));

        // 3. Revoke projects.create -> create link/button disappears from rendered HTML
        RolePermission::where('role_id', $role->id)->where('permission_id', $createPerm->id)->delete();
        User::incrementRolePermissionsVersion($role->id);
        $userWithCreate->clearPermissionCache();

        $userRevoked = $userWithCreate->fresh();
        $userRevoked->clearPermissionCache();

        $response3 = $this->actingAs($userRevoked)->get(route('projects.index'));
        $response3->assertStatus(200);
        $response3->assertDontSee(route('projects.create'));
    }

    /**
     * TEST 2A: VIEW-ONLY USER CANNOT SEE EDIT OR DELETE ACTIONS
     */
    public function test_view_only_user_cannot_see_edit_or_delete_actions(): void
    {
        $viewPerm = Permission::where('slug', 'projects.view')->firstOrFail();

        $role = Role::create(['name' => 'ProjectViewOnly_'.uniqid(), 'is_active' => true]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $viewPerm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create(['role_id' => $role->id]);
        $user->clearPermissionCache();

        $project = Project::factory()->create([
            'project_name' => 'Test Security Project',
            'status' => 'draft',
            'project_type' => 'new',
            'is_data_completed' => true,
            'created_by_user_id' => $user->id,
            'creator_entity_id' => $user->entity_id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.index'));
        $response->assertStatus(200);
        $response->assertSee(route('projects.show', $project->id));
        $response->assertDontSee(route('projects.edit', $project->id));
        $response->assertDontSee("confirmAction(this, 'تأكيد الحذف؟')", false);
    }

    /**
     * TEST 2B: EDIT PERMISSION RENDERS EDIT ACTION (DELETE REMAINS HIDDEN)
     */
    public function test_edit_permission_renders_edit_action(): void
    {
        $viewPerm = Permission::where('slug', 'projects.view')->firstOrFail();
        $editPerm = Permission::where('slug', 'projects.edit')->firstOrFail();

        $role = Role::create(['name' => 'ProjectEditor_'.uniqid(), 'is_active' => true]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $viewPerm->id]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $editPerm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create(['role_id' => $role->id]);
        $user->clearPermissionCache();

        $project = Project::factory()->create([
            'project_name' => 'Test Edit Project',
            'status' => 'draft',
            'project_type' => 'new',
            'is_data_completed' => true,
            'created_by_user_id' => $user->id,
            'creator_entity_id' => $user->entity_id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.index'));
        $response->assertStatus(200);
        $response->assertSee(route('projects.edit', $project->id));
        $response->assertDontSee("confirmAction(this, 'تأكيد الحذف؟')", false);
    }

    /**
     * TEST 2C: DELETE PERMISSION RENDERS DELETE ACTION (EDIT REMAINS HIDDEN)
     */
    public function test_delete_permission_renders_delete_action(): void
    {
        $viewPerm = Permission::where('slug', 'projects.view')->firstOrFail();
        $deletePerm = Permission::where('slug', 'projects.delete')->firstOrFail();

        $role = Role::create(['name' => 'ProjectDeleter_'.uniqid(), 'is_active' => true]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $viewPerm->id]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $deletePerm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create(['role_id' => $role->id]);
        $user->clearPermissionCache();

        $project = Project::factory()->create([
            'project_name' => 'Test Delete Project',
            'status' => 'draft',
            'project_type' => 'new',
            'is_data_completed' => true,
            'created_by_user_id' => $user->id,
            'creator_entity_id' => $user->entity_id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.index'));
        $response->assertStatus(200);
        $response->assertSee("confirmAction(this, 'تأكيد الحذف؟')", false);
        $response->assertDontSee(route('projects.edit', $project->id));
    }

    /**
     * TEST 3: EXPORT & IMPORT SERVER-RENDER AUTHORIZATION
     * Dropdown items for Export/Import are only rendered when respective permissions are granted.
     */
    public function test_export_and_import_server_render_authorization(): void
    {
        $viewPerm = Permission::where('slug', 'projects.view')->firstOrFail();
        $exportPerm = Permission::where('slug', 'projects.export')->firstOrFail();
        $importPerm = Permission::where('slug', 'projects.import')->firstOrFail();

        // 1. View only -> neither export nor import rendered
        $roleView = Role::create(['name' => 'ProjectViewer_'.uniqid(), 'is_active' => true]);
        RolePermission::create(['role_id' => $roleView->id, 'permission_id' => $viewPerm->id]);
        User::incrementRolePermissionsVersion($roleView->id);
        $viewUser = User::factory()->create(['role_id' => $roleView->id]);

        $responseView = $this->actingAs($viewUser)->get(route('projects.index'));
        $responseView->assertStatus(200);
        $responseView->assertDontSee(route('projects.import'));
        $responseView->assertDontSee("handleExport('excel')", false);
        $responseView->assertDontSee("handleExport('pdf')", false);

        // 2. Export user -> export rendered, import NOT rendered
        $roleExport = Role::create(['name' => 'ProjectExporter_'.uniqid(), 'is_active' => true]);
        RolePermission::create(['role_id' => $roleExport->id, 'permission_id' => $viewPerm->id]);
        RolePermission::create(['role_id' => $roleExport->id, 'permission_id' => $exportPerm->id]);
        User::incrementRolePermissionsVersion($roleExport->id);
        $exportUser = User::factory()->create(['role_id' => $roleExport->id]);

        $responseExport = $this->actingAs($exportUser)->get(route('projects.index'));
        $responseExport->assertStatus(200);
        $responseExport->assertSee("handleExport('excel')", false);
        $responseExport->assertSee("handleExport('pdf')", false);
        $responseExport->assertDontSee(route('projects.import'));

        // 3. Import user -> import rendered, export NOT rendered
        $roleImport = Role::create(['name' => 'ProjectImporter_'.uniqid(), 'is_active' => true]);
        RolePermission::create(['role_id' => $roleImport->id, 'permission_id' => $viewPerm->id]);
        RolePermission::create(['role_id' => $roleImport->id, 'permission_id' => $importPerm->id]);
        User::incrementRolePermissionsVersion($roleImport->id);
        $importUser = User::factory()->create(['role_id' => $roleImport->id]);

        $responseImport = $this->actingAs($importUser)->get(route('projects.index'));
        $responseImport->assertStatus(200);
        $responseImport->assertSee(route('projects.import'));
        $responseImport->assertDontSee("handleExport('excel')", false);
        $responseImport->assertDontSee("handleExport('pdf')", false);
    }

    /**
     * TEST 4: PERMISSION INDEPENDENCE (CREATE / EDIT / DELETE / EXPORT / APPROVE)
     * projects.view does NOT grant create, edit, delete, or export.
     * programs.edit does NOT grant programs.approve or programs.reject.
     */
    public function test_permission_independence_for_specialized_approvals(): void
    {
        $role = Role::create(['name' => 'IndependentRole_'.uniqid(), 'is_active' => true]);
        $viewPerm = Permission::where('slug', 'projects.view')->firstOrFail();
        $editPerm = Permission::where('slug', 'programs.edit')->firstOrFail();
        $approvePerm = Permission::where('slug', 'programs.approve')->firstOrFail();
        $rejectPerm = Permission::where('slug', 'programs.reject')->firstOrFail();

        RolePermission::create(['role_id' => $role->id, 'permission_id' => $viewPerm->id]);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $editPerm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create(['role_id' => $role->id]);

        // User has projects.view and programs.edit
        $this->assertTrue($user->hasPermission('projects.view'));
        $this->assertTrue($user->hasPermission('programs.edit'));

        // projects.view DOES NOT grant mutating/export permissions
        $this->assertFalse($user->hasPermission('projects.create'));
        $this->assertFalse($user->hasPermission('projects.edit'));
        $this->assertFalse($user->hasPermission('projects.delete'));
        $this->assertFalse($user->hasPermission('projects.export_excel'));

        // programs.edit DOES NOT grant approve or reject
        $this->assertFalse($user->hasPermission('programs.approve'));
        $this->assertFalse($user->hasPermission('programs.reject'));

        // Grant approve
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $approvePerm->id]);
        User::incrementRolePermissionsVersion($role->id);
        $user->clearPermissionCache();

        $this->assertTrue($user->hasPermission('programs.approve'));
        $this->assertFalse($user->hasPermission('programs.reject'));

        // Grant reject
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $rejectPerm->id]);
        User::incrementRolePermissionsVersion($role->id);
        $user->clearPermissionCache();

        $this->assertTrue($user->hasPermission('programs.approve'));
        $this->assertTrue($user->hasPermission('programs.reject'));
    }

    /**
     * TEST 5: SCOPE-AWARE UI & AUTHORIZATION
     * User with projects.edit attempting to edit a project outside geographic scope is denied.
     */
    public function test_scope_aware_authorization_enforces_record_boundaries(): void
    {
        $role = Role::create([
            'name' => 'GeoRestrictedEditor_'.uniqid(),
            'is_active' => true,
            'module_geo_scopes' => ['projects' => 'governorate'],
        ]);

        $editPerm = Permission::where('slug', 'projects.edit')->firstOrFail();
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $editPerm->id]);
        User::incrementRolePermissionsVersion($role->id);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $outOfScopeProject = Project::factory()->create([
            'geographic_scope_id' => 999,
            'status' => 'draft',
        ]);

        // When role geo scope is 'governorate' but user has no matching governorate -> out of scope
        $this->assertFalse($user->isInGeoScope($outOfScopeProject, 'projects'));
    }
}
