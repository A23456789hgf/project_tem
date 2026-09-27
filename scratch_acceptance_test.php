<?php

use App\Models\EntityApprovalStage;
use App\Models\AuthorityApprovalStage;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Models\User;
use App\Services\ApprovalChainBuilder;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

echo "--- FINAL DYNAMIC WORKFLOW ACCEPTANCE TEST ---\n\n";

$project = Project::latest()->first();
if (!$project) {
    echo "No suitable project found for testing.\n";
    exit;
}

$entityUser = User::whereNotNull('entity_id')->where('status', 'Active')->first();
$authorityUser = User::whereNotNull('authority_id')->where('status', 'Active')->first();

if (!$entityUser || !$authorityUser) {
    echo "No valid users found for testing.\n";
    exit;
}

$entityId = $entityUser->entity_id;
$authorityId = $authorityUser->authority_id;

// Ensure an active route exists
$route = \App\Models\AuthorityApprovalRoute::firstOrCreate(
    ['authority_id' => $authorityId, 'is_active' => true],
    ['destination_type' => 'ministry']
);

if (!$entityUser || !$authorityUser) {
    echo "No valid users found for testing.\n";
    exit;
}

echo "[1] Testing Internal Entity Dynamic Stages\n";

// Backup old stages (in memory) for the origin entity
$oldEntityStages = EntityApprovalStage::where('entity_id', $entityId)->get();
EntityApprovalStage::where('entity_id', $entityId)->delete();

$stageKeys = [
    ['key' => 'legal_review', 'ar' => 'مراجعة قانونية', 'en' => 'legal_review', 'actions' => ['approve', 'reject']],
    ['key' => 'financial_review', 'ar' => 'مراجعة مالية', 'en' => 'financial_review', 'actions' => ['approve', 'return']],
    ['key' => 'final_approval', 'ar' => 'اعتماد نهائي', 'en' => 'final_approval', 'actions' => ['approve']],
];

foreach ($stageKeys as $index => $sk) {
    EntityApprovalStage::create([
        'entity_id' => $entityId,
        'stage_name_ar' => $sk['ar'],
        'stage_name_en' => $sk['en'],
        'stage_key' => $sk['key'],
        'stage' => $sk['key'],
        'stage_order' => $index + 1,
        'is_active' => true,
        'responsible_user_id' => $entityUser->id,
        'available_actions' => $sk['actions'],
    ]);
}

$builder = app(ApprovalChainBuilder::class);
$stages = $builder->buildInternalChain($entityId);

echo "Built " . count($stages) . " stages for internal entity.\n";
foreach ($stages as $stage) {
    echo "- name_ar: " . ($stage['name_ar'] ?? 'NULL') . "\n";
    echo "  stage_key: " . ($stage['stage_key'] ?? 'NULL') . "\n";
    echo "  responsible_user_id: " . ($stage['responsible_user_id'] ?? 'NULL') . "\n";
    echo "  available_actions: " . print_r($stage['available_actions'] ?? 'NULL', true) . "\n";
}

echo "\n[2] Testing External Authority Dynamic Stages\n";

$oldAuthStages = AuthorityApprovalStage::where('authority_id', $authorityId)->get();
AuthorityApprovalStage::where('authority_id', $authorityId)->delete();

foreach ($stageKeys as $index => $sk) {
    AuthorityApprovalStage::create([
        'authority_id' => $authorityId,
        'stage_name_ar' => $sk['ar'],
        'stage_name_en' => $sk['en'],
        // authority_approval_stages uses 'stage' as the key
        'stage' => $sk['key'],
        'stage_order' => $index + 1,
        'is_active' => true,
        'responsible_user_id' => $authorityUser->id,
        'available_actions' => $sk['actions'],
    ]);
}

$authStages = $builder->buildExternalChain($authorityId);
echo "Built " . count($authStages) . " stages for external authority.\n";
foreach ($authStages as $stage) {
    echo "- name_ar: " . ($stage['name_ar'] ?? 'NULL') . "\n";
    echo "  stage_key: " . ($stage['stage_key'] ?? 'NULL') . "\n";
    echo "  responsible_user_id: " . ($stage['responsible_user_id'] ?? 'NULL') . "\n";
    echo "  available_actions: " . print_r($stage['available_actions'] ?? 'NULL', true) . "\n";
}

echo "\n[3] Testing Action Constraints (Backend Validation)\n";

// We create a mock ProjectApproval row in db to test Validation
$projectApproval = ProjectApproval::create([
    'project_id' => $project->id,
    'phase' => 'final_approval',
    'stage_key' => 'final_approval',
    'status' => 'Pending',
    'assigned_user_id' => $entityUser->id,
    'available_actions' => ['approve'],
    'step_order' => 1,
    'is_active' => true,
]);

$service = app(ApprovalService::class);
$activeStep = $service->getActiveStep($project);
echo "Active Step Found: " . ($activeStep ? "YES (ID: {$activeStep->id})" : "NO") . "\n";
if ($activeStep) {
    echo "Can user act: " . ($service->canUserActOnStep($entityUser, $activeStep) ? "YES" : "NO") . "\n";
}
try {
    echo "Attempting to REJECT when only APPROVE is allowed...\n";
    $service->rejectActiveStep($project, $entityUser, 'Test rejection reason');
    echo "FAIL: Expected exception was not thrown.\n";
} catch (\Exception $e) {
    echo "SUCCESS: Rejection blocked! Exception: " . $e->getMessage() . "\n";
}

$projectApproval->delete();


// Restore old stages
EntityApprovalStage::where('entity_id', $entityId)->delete();
foreach ($oldEntityStages as $oldStage) {
    EntityApprovalStage::create($oldStage->toArray());
}

AuthorityApprovalStage::where('authority_id', $authorityId)->delete();
foreach ($oldAuthStages as $oldStage) {
    AuthorityApprovalStage::create($oldStage->toArray());
}

echo "\n--- Test Complete ---\n";
