<?php

namespace Tests\Feature;

use App\Enums\EntityResponsibilityType;
use App\Enums\ReturnTarget;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectReferral;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectApprovalBladeViewTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;

    protected InternalEntity $childEntity;

    protected InternalEntity $parentEntity;

    protected User $childUser;

    protected User $parentUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put('signatures/test_signature.png', 'fake_signature_binary_data');

        $this->approvalService = app(ApprovalService::class);

        PermissionResolver::clearCache();
        Cache::flush();
        $this->seed(PermissionsSeeder::class);

        $permissionSlugs = [
            'main_modules.projects',
            'main_modules.correspondence',
            'approvals.view',
            'approvals.approve',
            'approvals.reject',
            'approvals.request-action',
            'projects.submit',
            'projects.create',
            'projects.edit',
            'projects.view',
            'projects.view-details',
            'projects.approve',
            'projects.reject',
            'projects.refer',
            'referrals.view',
            'reviews.financial',
            'reviews.technical',
        ];

        foreach ($permissionSlugs as $slug) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $slug,
                    'module' => explode('.', $slug)[0],
                    'type' => 'action',
                ]
            );
        }

        PermissionResolver::clearCache();
        Cache::flush();

        $role = Role::create([
            'name' => 'ManagerRole',
            'is_active' => true,
            'full_access' => true,
        ]);

        $permissions = Permission::all();
        foreach ($permissions as $perm) {
            RolePermission::firstOrCreate([
                'role_id' => $role->id,
                'permission_id' => $perm->id,
            ]);
        }
        User::incrementRolePermissionsVersion($role->id);

        $this->parentEntity = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'الاتحاد العام (Parent Entity)',
            'type' => 'union',
            'is_active' => true,
        ]);

        $this->childEntity = InternalEntity::withoutGlobalScopes()->create([
            'name' => 'الجمعية المحلية (Child Entity)',
            'type' => 'assembly',
            'parent_id' => $this->parentEntity->id,
            'is_active' => true,
        ]);

        $this->childUser = User::withoutGlobalScopes()->create([
            'name' => 'مستخدم الجمعية المنشئة',
            'username' => 'child_user_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'child_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->childEntity->id,
            'role_id' => $role->id,
            'signature' => 'signatures/test_signature.png',
        ]);

        $this->parentUser = User::withoutGlobalScopes()->create([
            'name' => 'مستخدم الاتحاد العام',
            'username' => 'parent_user_'.uniqid(),
            'user_id' => 'UID_'.uniqid(),
            'phone' => '77'.rand(10000000, 99999999),
            'email' => 'parent_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'entity_id' => $this->parentEntity->id,
            'role_id' => $role->id,
            'signature' => 'signatures/test_signature.png',
        ]);

        // Entity Approval Stages Configuration
        foreach ([
            EntityResponsibilityType::TechnicalReview,
            EntityResponsibilityType::FinancialReview,
            EntityResponsibilityType::Approval,
        ] as $type) {
            EntityApprovalStage::create([
                'entity_id' => $this->childEntity->id,
                'stage' => $type->value,
                'stage_order' => $type->stageOrder(),
                'responsible_user_id' => $this->childUser->id,
            ]);

            EntityApprovalStage::create([
                'entity_id' => $this->parentEntity->id,
                'stage' => $type->value,
                'stage_order' => $type->stageOrder(),
                'responsible_user_id' => $this->parentUser->id,
            ]);
        }
    }

    protected function createProject(string $status = 'draft'): Project
    {
        return Project::withoutGlobalScopes()->create([
            'project_name' => 'مشروع اختباري للعرض',
            'status' => $status,
            'creator_entity_id' => $this->childEntity->id,
            'internal_entity_id' => $this->childEntity->id,
            'created_by_user_id' => $this->childUser->id,
            'created_by' => $this->childUser->id,
        ]);
    }

    public function test_draft_project_renders_draft_actions_and_hides_approval_buttons(): void
    {
        $project = $this->createProject('draft');

        // Ensure zero approvals exist in draft
        $this->assertDatabaseCount('project_approvals', 0);

        $view = $this->actingAs($this->childUser)
            ->view('projects.partials.approval-tracker', ['project' => $project]);

        $view->assertSee('المشروع في مرحلة المسودة (Draft)');
        $view->assertSee('إغلاق المسودة وإرسالها للاعتماد');

        $formView = $this->actingAs($this->childUser)
            ->view('projects.partials.approval-form', ['project' => $project]);

        $formView->assertSee('إجراءات المسودة');
        $formView->assertSee('إغلاق المسودة وإرسالها للاعتماد');
        $formView->assertDontSee('اتخاذ قرار بشأن هذه المرحلة');
    }

    public function test_approval_tracker_renders_dynamic_entity_hierarchy_and_active_step(): void
    {
        $project = $this->createProject('draft');

        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childUser);

        $project->refresh();
        $this->assertEquals('pending_approval', $project->status);
        $this->assertDatabaseCount('project_approvals', 6); // 2 entities x 3 phases

        $view = $this->actingAs($this->childUser)
            ->view('projects.partials.approval-tracker', ['project' => $project]);

        // Should render both entities dynamically
        $view->assertSee('الجمعية المحلية (Child Entity)');
        $view->assertSee('الاتحاد العام (Parent Entity)');
        $view->assertSee('الجهة المنشئة');
        $view->assertSee('الجهة العليا (Root)');
        $view->assertSee('المرحلة النشطة حالياً');
        $view->assertSee('بانتظار وصول الدور');

        // Check active step in approval-form
        $formView = $this->actingAs($this->childUser)
            ->view('projects.partials.approval-form', ['project' => $project]);

        $formView->assertSee('الخطوة الحالية النشطة');
        $formView->assertSee('الجمعية المحلية (Child Entity)');
        $formView->assertSee('مراجعة فنية');
    }

    public function test_returned_for_completion_renders_resubmit_form_with_notes(): void
    {
        $project = $this->createProject('draft');

        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childUser);
        $this->approvalService->requestCompletion(
            $project,
            $this->childUser,
            'نرجو تزويدنا بدراسة الجدوى الفنية المحدثة',
            ReturnTarget::CreatorEntity
        );

        $project->refresh();
        $this->assertEquals('rolled_back_for_review', $project->status);

        $view = $this->actingAs($this->childUser)
            ->view('projects.partials.approval-tracker', ['project' => $project]);

        $view->assertSee('تم إرجاع المشروع لاستكمال النواقص (Returned for Completion)');
        $view->assertSee('نرجو تزويدنا بدراسة الجدوى الفنية المحدثة');

        $formView = $this->actingAs($this->childUser)
            ->view('projects.partials.approval-form', ['project' => $project]);

        $formView->assertSee('إعادة تقديم المشروع (Resubmit)');
        $formView->assertSee('ملاحظات ما تم استكماله / معالجته');
        $formView->assertSee('إعادة تقديم المشروع للاعتماد');
    }

    public function test_rejection_renders_rejection_banner_with_reason(): void
    {
        $project = $this->createProject('draft');

        $this->approvalService->closeDraftAndGenerateApprovalChain($project, $this->childUser);
        $this->approvalService->rejectActiveStep($project, $this->childUser, 'المشروع غير متوافق مع الاشتراطات الفنية والتنظيمية');

        $project->refresh();
        $this->assertEquals('rejected', $project->status);

        $view = $this->actingAs($this->childUser)
            ->view('projects.partials.approval-tracker', ['project' => $project]);

        $view->assertSee('تم رفض المشروع (Rejected)');
        $view->assertSee('المشروع غير متوافق مع الاشتراطات الفنية والتنظيمية');
    }

    public function test_consultation_section_renders_independently(): void
    {
        $project = $this->createProject('pending_approval');

        ProjectReferral::create([
            'project_id' => $project->id,
            'referring_entity_id' => $this->childEntity->id,
            'referred_entity_id' => $this->parentEntity->id,
            'referring_user_id' => $this->childUser->id,
            'referred_by_user_id' => $this->childUser->id,
            'referral_text' => 'طلب إبداء الرأي القانوني في التراخيص',
            'status' => 'pending',
        ]);

        $formView = $this->actingAs($this->childUser)
            ->view('projects.partials.approval-form', ['project' => $project]);

        $formView->assertSee('الاستشارات والإحالات الفنية (Consultations / Referrals)');
        $formView->assertSee('الاتحاد العام (Parent Entity)');
        $formView->assertSee('طلب إبداء الرأي القانوني في التراخيص');
        $formView->assertSee('قيد الانتظار');
    }
}
