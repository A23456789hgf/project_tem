<?php

namespace Tests\Feature;

use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\InternalEntity;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardScopedQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionResolver::clearCache();
    }

    public function test_scoped_user_with_entity_id_can_access_dashboard_without_unknown_column_exception(): void
    {
        $role = Role::create([
            'name' => 'InternalEntityUser',
            'description' => 'مستخدم جهة داخلية',
            'is_active' => true,
            'module_scopes' => [
                'programs' => 'all',
                'projects' => 'own_entity',
                'dashboard' => 'all',
            ],
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'dashboard.view',
            'module' => 'dashboard',
            'type' => 'page',
        ]);

        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        $entity = InternalEntity::create([
            'name' => 'وحدة تمويل المشاريع والمبادرات',
            'entity_type' => 'Department',
            'entity_code' => 'UF',
            'is_ministry_root' => false,
            'is_active' => true,
            'status' => 1,
        ]);

        $user = User::create([
            'name' => 'مستخدم تجريبي',
            'username' => 'test_scoped_user',
            'user_id' => 'USR-TEST-001',
            'phone' => '777123999',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
            'entity_id' => $entity->id,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $stage = Stage::create([
            'code' => 'review',
            'name_ar' => 'مرحلة المراجعة',
            'order' => 1,
            'type' => 'assessment',
            'is_active' => true,
            'is_system' => true,
        ]);

        $program = Program::create([
            'name' => 'برنامج التنمية الزراعية',
            'is_active' => true,
            'status' => 1,
            'created_by_user_id' => $user->id,
            'created_by_entity' => $entity->name,
            'creator_entity_id' => $entity->id,
        ]);

        $project = Project::create([
            'project_name' => 'مشروع الطاقة الشمسية للمزارعين',
            'form_number' => 'PRJ-TEST-001',
            'project_type' => 'agriculture',
            'program_id' => $program->id,
            'creator_entity_id' => $entity->id,
            'internal_entity_id' => $entity->id,
            'created_by_user_id' => $user->id,
            'created_by_entity' => $entity->name,
            'current_approval_stage_id' => $stage->id,
            'status' => 'under_review',
            'is_data_completed' => true,
            'entity_modified' => false,
        ]);

        ProjectApproval::create([
            'project_id' => $project->id,
            'stage_id' => $stage->id,
            'entity_id' => $entity->id,
            'step_order' => 1,
            'status' => 'pending',
            'is_active' => true,
            'financial_review_completed' => false,
            'technical_review_completed' => false,
            'is_completed' => false,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['user_permissions' => ['dashboard.view']])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHas('projectCount', 1);
        $response->assertViewHas('programCount', 1);
    }

    public function test_accessible_projects_query_does_not_contain_invalid_stages_entity_id(): void
    {
        $entity = InternalEntity::create([
            'name' => 'الجهة الإدارية',
            'entity_type' => 'Department',
            'is_ministry_root' => false,
            'is_active' => true,
            'status' => 1,
        ]);

        $user = User::create([
            'name' => 'مستخدم فحص الاستعلام',
            'username' => 'query_test_user',
            'user_id' => 'USR-QUERY-001',
            'phone' => '777444999',
            'password' => bcrypt('Password123!'),
            'entity_id' => $entity->id,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $projectService = app(ProjectService::class);
        $query = Project::query();
        $projectService->applyAccessibleProjectsQuery($query, $user);

        $sql = $query->toSql();

        // Must not contain `stages`.`entity_id` or `stages`.entity_id
        $this->assertStringNotContainsString('`stages`.`entity_id`', $sql);
        $this->assertStringNotContainsString('stages.entity_id', $sql);

        // Should successfully execute without throwing QueryException
        $count = $query->count();
        $this->assertIsInt($count);
    }

    public function test_dashboard_queries_strictly_isolate_data_by_entity_scope(): void
    {
        $role = Role::create([
            'name' => 'ScopedUserRole',
            'description' => 'دور محدد النطاق',
            'is_active' => true,
            'module_scopes' => [
                'programs' => 'all',
                'projects' => 'own_entity',
                'dashboard' => 'all',
            ],
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'dashboard.view',
            'module' => 'dashboard',
            'type' => 'page',
        ]);

        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        $entityA = InternalEntity::create([
            'name' => 'جهة المستخدم أ',
            'entity_type' => 'Department',
            'is_ministry_root' => false,
            'is_active' => true,
            'status' => 1,
        ]);

        $entityB = InternalEntity::create([
            'name' => 'جهة أخرى ب',
            'entity_type' => 'Department',
            'is_ministry_root' => false,
            'is_active' => true,
            'status' => 1,
        ]);

        $userA = User::create([
            'name' => 'مستخدم الجهة أ',
            'username' => 'user_entity_a',
            'user_id' => 'USR-ENT-A',
            'phone' => '777111000',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
            'entity_id' => $entityA->id,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $userB = User::create([
            'name' => 'مستخدم الجهة ب',
            'username' => 'user_entity_b',
            'user_id' => 'USR-ENT-B',
            'phone' => '777222000',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
            'entity_id' => $entityB->id,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $programA = Program::create([
            'name' => 'برنامج الجهة أ',
            'is_active' => true,
            'status' => 1,
            'created_by_user_id' => $userA->id,
            'created_by_entity' => $entityA->name,
            'creator_entity_id' => $entityA->id,
        ]);

        $programB = Program::create([
            'name' => 'برنامج الجهة ب',
            'is_active' => true,
            'status' => 1,
            'created_by_user_id' => $userB->id,
            'created_by_entity' => $entityB->name,
            'creator_entity_id' => $entityB->id,
        ]);

        Project::create([
            'project_name' => 'مشروع يتبع الجهة أ',
            'form_number' => 'PRJ-A-001',
            'project_type' => 'agriculture',
            'program_id' => $programA->id,
            'creator_entity_id' => $entityA->id,
            'internal_entity_id' => $entityA->id,
            'created_by_user_id' => $userA->id,
            'created_by_entity' => $entityA->name,
            'status' => 'completed',
            'is_data_completed' => true,
            'entity_modified' => false,
        ]);

        Project::create([
            'project_name' => 'مشروع يتبع الجهة ب فقط',
            'form_number' => 'PRJ-B-001',
            'project_type' => 'agriculture',
            'program_id' => $programB->id,
            'creator_entity_id' => $entityB->id,
            'internal_entity_id' => $entityB->id,
            'created_by_user_id' => $userB->id,
            'created_by_entity' => $entityB->name,
            'status' => 'completed',
            'is_data_completed' => true,
            'entity_modified' => false,
        ]);

        $response = $this->actingAs($userA)
            ->withSession(['user_permissions' => ['dashboard.view']])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        // User A must only see their 1 project and 1 program
        $response->assertViewHas('projectCount', 1);
        $response->assertViewHas('programCount', 1);
        $response->assertViewHas('completedProjectsCount', 1);
    }

    public function test_user_without_dashboard_view_permission_is_forbidden(): void
    {
        $role = Role::create([
            'name' => 'RestrictedRole',
            'description' => 'دور بدون صلاحية لوحة التحكم',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'مستخدم بدون صلاحية',
            'username' => 'unauthorized_user',
            'user_id' => 'USR-UNAUTH-001',
            'phone' => '777999888',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['user_permissions' => []])
            ->get(route('dashboard'));

        $response->assertStatus(403);
    }
}
