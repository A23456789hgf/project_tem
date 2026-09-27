<?php

use App\Models\EntityApprovalStage;
use App\Models\Project;
use App\Models\User;
use App\Services\ApprovalChainBuilder;

echo "--- Dynamic Stage Test ---\n";
$project = Project::latest()->first();
if (!$project) {
    echo "No project found to test.\n";
    exit;
}

$entityId = $project->creator_entity_id;
$validUser = User::where('entity_id', $entityId)->where('status', 'Active')->first();
if (!$validUser) {
    echo "No valid user found for entity $entityId.\n";
    exit;
}
echo "Using valid user: $validUser->id for entity: $entityId\n";

// Backup old stages (in memory) for the origin entity
$oldStages = EntityApprovalStage::where('entity_id', $entityId)->get();

// Delete old stages temporarily
EntityApprovalStage::where('entity_id', $entityId)->delete();

// Create new dynamic stage
$stage = EntityApprovalStage::create([
    'entity_id' => $entityId,
    'stage_name_ar' => 'مراجعة قانونية',
    'stage_name_en' => 'legal_review',
    'stage_key' => 'legal_review',
    'stage' => 'legal_review', // keep legacy field populated just in case it requires string
    'stage_order' => 1,
    'is_active' => true,
    'responsible_user_id' => $validUser->id,
    'available_actions' => json_encode(['approve', 'reject', 'return']),
]);

echo "Created Dynamic Stage: " . $stage->stage_name_ar . " (Key: " . $stage->stage_key . ")\n";

$builder = app(ApprovalChainBuilder::class);
$stages = $builder->buildChainForProject($project);

echo "Built " . count($stages) . " stages for project.\n";
if (count($stages) > 0) {
    $firstStage = $stages[0];
    echo "- name_ar: " . ($firstStage['name_ar'] ?? 'NULL') . "\n";
    echo "- stage_key: " . ($firstStage['stage_key'] ?? 'NULL') . "\n";
    echo "- responsible_user_id: " . ($firstStage['responsible_user_id'] ?? 'NULL') . "\n";
    echo "- available_actions: " . print_r($firstStage['available_actions'] ?? 'NULL', true) . "\n";
}

// Restore old stages
$stage->delete();
foreach ($oldStages as $oldStage) {
    EntityApprovalStage::create($oldStage->toArray());
}
echo "--- Test Complete ---\n";
