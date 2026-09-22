<?php

namespace Tests\Feature;

use App\Enums\EntityResponsibilityType;
use App\Enums\ProjectStatus;
use App\Models\Authority;
use App\Models\AuthorityApprovalRoute;
use App\Models\AuthorityApprovalStage;
use App\Models\EntityApprovalStage;
use App\Models\ExecutiveActivity;
use App\Models\ExecutiveActivityAction;
use App\Models\Governorate;
use App\Models\InternalEntity;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ComprehensiveProjectLifecycleE2ETest extends TestCase
{
    use RefreshDatabase;

    protected InternalEntity $ministryEntity;

    protected Authority $authorityA;

    protected Authority $authorityB;

    protected Governorate $governorate;

    protected User $externalUserA;

    protected User $externalApproverA;

    protected User $externalUserB;

    protected User $ministryApprover;

    protected User $adminUser;

    protected ApprovalService $approvalService;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionResolver::clearCache();
        Cache::flush();

        $this->approvalService = app(ApprovalService::class);

        // 1. Geographic Setup
        $this->governorate = Governorate::create([
            'name' => 'محافظة الحديدة',
            'is_active' => true,
        ]);

        // 2. Ministry Root Entity (Internal)
        $this->ministryEntity = InternalEntity::create([
            'name' => 'وزارة الإدارة والتخطيط',
            'type' => 'Company',
            'is_active' => true,
            'is_ministry_root' => true,
        ]);

        // 3. Authorities (External)
        $this->authorityA = Authority::create([
            'agency_name' => 'هيئة تطوير الساحل الغربي',
            'is_active' => true,
            'governorate_id' => $this->governorate->id,
        ]);

        $this->authorityB = Authority::create([
            'agency_name' => 'هيئة مياه حوض صنعاء',
            'is_active' => true,
        ]);

        // 4. Permissions
        $permissionDefs = [
            'main_modules.projects' => ['name' => 'الوحدة الرئيسية للمشاريع', 'module' => 'main_modules', 'type' => 'page'],
            'main_modules.dashboard' => ['name' => 'لوحة التحكم', 'module' => 'main_modules', 'type' => 'page'],
            'projects.view' => ['name' => 'عرض المشاريع', 'module' => 'projects', 'type' => 'page'],
            'projects.view-details' => ['name' => 'عرض تفاصيل المشروع', 'module' => 'projects', 'type' => 'page'],
            'projects.create' => ['name' => 'إنشاء مشروع', 'module' => 'projects', 'type' => 'action'],
            'projects.edit' => ['name' => 'تعديل مشروع', 'module' => 'projects', 'type' => 'action'],
            'projects.delete' => ['name' => 'حذف مشروع', 'module' => 'projects', 'type' => 'action'],
            'projects.submit' => ['name' => 'إرسال مشروع للاعتماد', 'module' => 'projects', 'type' => 'action'],
            'projects.approve' => ['name' => 'اعتماد مشروع', 'module' => 'projects', 'type' => 'action'],
            'projects.execute' => ['name' => 'تنفيذ مشاريع', 'module' => 'projects', 'type' => 'action'],
            'approvals.view' => ['name' => 'عرض الموافقات', 'module' => 'approvals', 'type' => 'page'],
            'approvals.approve' => ['name' => 'الموافقة على المراحل', 'module' => 'approvals', 'type' => 'action'],
            'execution.view' => ['name' => 'عرض التنفيذ', 'module' => 'execution', 'type' => 'page'],
            'execution.add' => ['name' => 'إضافة تنفيذ', 'module' => 'execution', 'type' => 'action'],
            'execution.edit' => ['name' => 'تعديل تنفيذ', 'module' => 'execution', 'type' => 'action'],
        ];

        $allPermModels = [];
        foreach ($permissionDefs as $slug => $data) {
            $allPermModels[$slug] = Permission::firstOrCreate(['slug' => $slug], $data);
        }

        $roleExt = Role::create(['name' => 'external_officer', 'label' => 'ضابط جهة خارجية', 'is_active' => true]);
        $roleMin = Role::create(['name' => 'ministry_officer', 'label' => 'مسؤول وزارة', 'is_active' => true]);

        foreach ($allPermModels as $perm) {
            RolePermission::create(['role_id' => $roleExt->id, 'permission_id' => $perm->id, 'role_legacy' => $roleExt->name]);
            RolePermission::create(['role_id' => $roleMin->id, 'permission_id' => $perm->id, 'role_legacy' => $roleMin->name]);
        }

        PermissionResolver::clearCache();

        // 5. Users
        $this->externalUserA = User::create([
            'user_id' => 'EXT_USER_A',
            'phone' => '771000001',
            'name' => 'مهندس مشاريع هيئة أ',
            'username' => 'ext_user_a',
            'email' => 'ext_a@agency.gov.ye',
            'password' => bcrypt('password'),
            'organization_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'entity_id' => null,
            'governorate_id' => $this->governorate->id,
            'role_id' => $roleExt->id,
            'status' => 'active',
        ]);

        $this->externalApproverA = User::create([
            'user_id' => 'EXT_APP_A',
            'phone' => '771000002',
            'name' => 'رئيس هيئة أ (معتمد)',
            'username' => 'ext_approver_a',
            'email' => 'ext_app_a@agency.gov.ye',
            'password' => bcrypt('password'),
            'organization_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'entity_id' => null,
            'responsibility' => 'ENTITY_APPROVER',
            'role_id' => $roleExt->id,
            'status' => 'active',
        ]);

        $this->externalUserB = User::create([
            'user_id' => 'EXT_USER_B',
            'phone' => '771000003',
            'name' => 'مهندس هيئة ب',
            'username' => 'ext_user_b',
            'email' => 'ext_b@agency.gov.ye',
            'password' => bcrypt('password'),
            'organization_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'entity_id' => null,
            'role_id' => $roleExt->id,
            'status' => 'active',
        ]);

        $this->ministryApprover = User::create([
            'user_id' => 'MIN_APPROVER',
            'phone' => '771000004',
            'name' => 'مدير التخطيط بالوزارة',
            'username' => 'ministry_approver',
            'email' => 'min_approver@ministry.gov.ye',
            'password' => bcrypt('password'),
            'organization_type' => 'internal',
            'entity_id' => $this->ministryEntity->id,
            'authority_id' => null,
            'responsibility' => 'ENTITY_APPROVER',
            'role_id' => $roleMin->id,
            'status' => 'active',
        ]);

        $this->adminUser = User::create([
            'user_id' => 'SYS_ADMIN',
            'phone' => '771000005',
            'name' => 'مدير النظام العام',
            'username' => 'system_admin',
            'email' => 'admin@system.gov.ye',
            'password' => bcrypt('password'),
            'organization_type' => 'internal',
            'entity_id' => $this->ministryEntity->id,
            'authority_id' => null,
            'is_admin' => true,
            'full_access' => true,
            'status' => 'active',
        ]);

        // 6. Approval Chain Configuration (Internal & External)
        AuthorityApprovalStage::forceCreate([
            'authority_id' => $this->authorityA->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $this->externalApproverA->id,
        ]);

        AuthorityApprovalRoute::create([
            'authority_id' => $this->authorityA->id,
            'destination_type' => 'ministry',
            'is_active' => true,
        ]);

        EntityApprovalStage::forceCreate([
            'entity_id' => $this->ministryEntity->id,
            'stage' => EntityResponsibilityType::Approval->value,
            'stage_order' => 1,
            'is_active' => true,
            'responsible_user_id' => $this->ministryApprover->id,
        ]);
    }

    public function test_semantic_database_integrity_invariants(): void
    {
        // 1. Verify User Semantic Rules
        $this->assertEquals('external', $this->externalUserA->organization_type);
        $this->assertNotNull($this->externalUserA->authority_id);
        $this->assertNull($this->externalUserA->entity_id);

        $this->assertEquals('internal', $this->ministryApprover->organization_type);
        $this->assertNotNull($this->ministryApprover->entity_id);
        $this->assertNull($this->ministryApprover->authority_id);

        // 2. Create Projects & Verify Project Semantic Rules
        $extProject = Project::create([
            'project_name' => 'مشروع خارجي نموذجي',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'creator_entity_id' => null,
            'created_by_user_id' => $this->externalUserA->id,
            'organization_type' => 'external',
            'status' => 'draft',
        ]);

        $intProject = Project::create([
            'project_name' => 'مشروع داخلي نموذجي',
            'source_type' => 'internal',
            'authority_id' => null,
            'creator_entity_id' => $this->ministryEntity->id,
            'created_by_user_id' => $this->ministryApprover->id,
            'organization_type' => 'internal',
            'status' => 'draft',
        ]);

        $this->assertTrue($extProject->isExternal());
        $this->assertFalse($extProject->isInternal());
        $this->assertEquals($this->authorityA->agency_name, $extProject->creator_entity_name);

        $this->assertTrue($intProject->isInternal());
        $this->assertFalse($intProject->isExternal());
        $this->assertEquals($this->ministryEntity->name, $intProject->creator_entity_name);
    }

    public function test_end_to_end_external_project_lifecycle_with_approval_and_execution(): void
    {
        // Step 1: External User creates External Project Draft
        $project = Project::create([
            'project_name' => 'مشروع حصاد مياه الساحل الغربي',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'created_by_user_id' => $this->externalUserA->id,
            'governorate_id' => $this->governorate->id,
            'organization_type' => 'external',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'authority_id' => $this->authorityA->id,
            'source_type' => 'external',
            'status' => 'draft',
        ]);

        // Step 2: Submit Project to Approval Workflow
        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->externalUserA);
        $project->refresh();

        $this->assertEquals(ProjectStatus::PendingApproval->value, $project->status);
        $this->assertGreaterThan(0, $project->projectApprovals()->count());

        // Snapshot preservation: Ensure approval stages match dynamic settings
        $initialSnapshotCount = $project->projectApprovals()->count();

        // Step 3: Authority Approver Approves Authority Stage
        $authStep = $project->projectApprovals()->where('is_active', true)->first();
        $this->assertNotNull($authStep);

        $this->approvalService->approveActiveStep($project, $this->externalApproverA, 'معتمد من رئيس الهيئة');
        $project->refresh();

        // Step 4: Ministry Approver Approves Ministry Stage (External -> Internal Approval Handoff)
        $ministryStep = $project->projectApprovals()->where('is_active', true)->first();
        $this->assertNotNull($ministryStep);

        $this->approvalService->approveActiveStep($project, $this->ministryApprover, 'معتمد نهائياً من الوزارة');
        $project->refresh();

        // Step 5: Verification of Final Status & Execution
        $this->assertEquals(ProjectStatus::InExecution->value, $project->status);
        $this->assertEquals($initialSnapshotCount, $project->projectApprovals()->count(), 'Approval snapshot was preserved');

        // Step 6: External User Logs Execution
        $activity = ExecutiveActivity::create([
            'project_id' => $project->id,
            'name' => 'حفر الآبار والمسوحات الهندسية',
        ]);

        $action = ExecutiveActivityAction::create([
            'executive_activity_id' => $activity->id,
            'action' => 'المسح الهيدرولوجي الميداني',
            'weight' => 10,
        ]);

        $response = $this->actingAs($this->externalUserA)->postJson(route('projects.execution.store', $project->id), [
            'executive_activity_action_id' => $action->id,
            'status' => 'completed',
            'completion_percentage' => 100,
            'amount_spent' => 15000,
            'notes' => 'تم إنجاز المسح الهيدرولوجي بنجاح',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_executions', [
            'project_id' => $project->id,
            'executive_activity_action_id' => $action->id,
            'completion_percentage' => 100,
        ]);

        // Step 7: Cross-Authority Isolation Verification
        // User B from Authority B MUST be denied access to Project A
        $responseB = $this->actingAs($this->externalUserB)->get(route('projects.show', $project->id));
        $this->assertContains($responseB->status(), [403, 404]);

        // Admin retains full access
        $responseAdmin = $this->actingAs($this->adminUser)->get(route('projects.show', $project->id));
        $responseAdmin->assertStatus(200);
    }
}
