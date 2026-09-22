<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectDocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Authority $authorityA;

    protected Authority $authorityB;

    protected User $userA;

    protected User $userB;

    protected Project $projectA;

    protected Project $projectB;

    protected ProjectDocument $documentA;

    protected ProjectDocument $documentB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // 1. Setup Permissions
        $permissions = [
            'dashboard.view' => ['name' => 'عرض لوحة التحكم', 'module' => 'dashboard', 'type' => 'page'],
            'main_modules.dashboard' => ['name' => 'الوحدة الرئيسية للوحة التحكم', 'module' => 'main_modules', 'type' => 'page'],
            'main_modules.projects' => ['name' => 'الوحدة الرئيسية للمشاريع', 'module' => 'main_modules', 'type' => 'page'],
            'projects.view' => ['name' => 'عرض المشاريع', 'module' => 'projects', 'type' => 'page'],
            'projects.view-details' => ['name' => 'عرض تفاصيل المشروع', 'module' => 'projects', 'type' => 'page'],
            'projects.edit' => ['name' => 'تعديل المشروع', 'module' => 'projects', 'type' => 'action'],
            'projects.delete' => ['name' => 'حذف المشروع', 'module' => 'projects', 'type' => 'action'],
            'documents.view' => ['name' => 'عرض المستندات', 'module' => 'documents', 'type' => 'page'],
            'documents.create' => ['name' => 'رفع المستندات', 'module' => 'documents', 'type' => 'action'],
            'documents.delete' => ['name' => 'حذف المستندات', 'module' => 'documents', 'type' => 'action'],
        ];

        foreach ($permissions as $slug => $data) {
            Permission::firstOrCreate(['slug' => $slug], $data);
        }
        PermissionResolver::clearCache();

        $role = Role::forceCreate([
            'name' => 'doc_auth_role',
            'is_active' => true,
            'full_access' => false,
        ]);

        foreach (Permission::all() as $perm) {
            RolePermission::forceCreate([
                'role_id' => $role->id,
                'permission_id' => $perm->id,
                'role_legacy' => $role->name,
            ]);
        }

        // 2. Setup Authorities
        $this->authorityA = Authority::forceCreate([
            'agency_name' => 'هيئة تطوير تهامة',
            'is_active' => true,
        ]);

        $this->authorityB = Authority::forceCreate([
            'agency_name' => 'هيئة مياه الريف',
            'is_active' => true,
        ]);

        // 3. Setup Users
        $this->userA = User::forceCreate([
            'user_id' => 'USR_DOC_A',
            'phone' => '770001001',
            'username' => 'user_doc_a',
            'name' => 'مستخدم تهامة',
            'email' => 'doc_a@test.gov',
            'password' => 'password',
            'authority_id' => $this->authorityA->id,
            'organization_type' => 'external',
            'role_id' => $role->id,
            'status' => 'Active',
        ]);

        $this->userB = User::forceCreate([
            'user_id' => 'USR_DOC_B',
            'phone' => '770001002',
            'username' => 'user_doc_b',
            'name' => 'مستخدم الريف',
            'email' => 'doc_b@test.gov',
            'password' => 'password',
            'authority_id' => $this->authorityB->id,
            'organization_type' => 'external',
            'role_id' => $role->id,
            'status' => 'Active',
        ]);

        // 4. Setup Projects
        $this->projectA = Project::forceCreate([
            'project_name' => 'مشروع تهامة',
            'source_type' => 'external',
            'authority_id' => $this->authorityA->id,
            'status' => 'in_execution',
        ]);

        $this->projectB = Project::forceCreate([
            'project_name' => 'مشروع الريف',
            'source_type' => 'external',
            'authority_id' => $this->authorityB->id,
            'status' => 'in_execution',
        ]);

        // 5. Setup Documents
        Storage::disk('public')->put('projects/1/docA.pdf', 'Secret Document Content A');
        $this->documentA = ProjectDocument::forceCreate([
            'project_id' => $this->projectA->id,
            'document_type' => 'project_document',
            'file_path' => 'projects/1/docA.pdf',
            'file_name' => 'docA.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'uploaded_by' => $this->userA->id,
        ]);

        Storage::disk('public')->put('projects/2/docB.pdf', 'Confidential Document Content B');
        $this->documentB = ProjectDocument::forceCreate([
            'project_id' => $this->projectB->id,
            'document_type' => 'project_document',
            'file_path' => 'projects/2/docB.pdf',
            'file_name' => 'docB.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'uploaded_by' => $this->userB->id,
        ]);
    }

    /**
     * Test 1: User A cannot access Project B documents index (Cross-Authority Forbidden).
     */
    public function test_user_a_cannot_view_project_b_documents_index(): void
    {
        $response = $this->actingAs($this->userA)->get(route('projects.documents.index', $this->projectB->id));
        $response->assertStatus(403);
    }

    /**
     * Test 2: IDOR Security Test — User A requests Project A URL + Document B ID (which belongs to Project B).
     * Must return 404 and must NOT leak the file.
     */
    public function test_idor_cross_project_document_access_is_rejected_and_never_leaks(): void
    {
        // Try show
        $responseShow = $this->actingAs($this->userA)->get(
            route('projects.documents.show', ['project' => $this->projectA->id, 'document' => $this->documentB->id])
        );
        $responseShow->assertStatus(404);

        // Try download
        $responseDownload = $this->actingAs($this->userA)->get(
            route('projects.documents.download', ['project' => $this->projectA->id, 'document' => $this->documentB->id])
        );
        $responseDownload->assertStatus(404);

        // Try destroy
        $responseDestroy = $this->actingAs($this->userA)->delete(
            route('projects.documents.destroy', ['project' => $this->projectA->id, 'document' => $this->documentB->id])
        );
        $responseDestroy->assertStatus(404);

        // Ensure Document B is NOT deleted
        $this->assertDatabaseHas('project_documents', ['id' => $this->documentB->id]);
    }

    /**
     * Test 3: Legitimate access — User A can view, upload, and download Document A on Project A.
     */
    public function test_user_a_can_upload_and_manage_own_project_documents(): void
    {
        $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

        $responseUpload = $this->actingAs($this->userA)->post(route('projects.documents.store', $this->projectA->id), [
            'project_document' => $file,
        ]);

        $responseUpload->assertRedirect(route('projects.documents.index', $this->projectA->id));
        $this->assertDatabaseHas('project_documents', [
            'project_id' => $this->projectA->id,
            'file_name' => 'contract.pdf',
        ]);
    }
}
