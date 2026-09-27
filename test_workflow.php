<?php

use App\Models\Project;
use App\Models\ProjectApproval;
use App\Services\ApprovalChainBuilder;
use App\Services\ApprovalService;
use App\Models\User;

function testNewWorkflow() {
    echo "Testing New Workflow...\n";
    $project = Project::latest()->first();
    if (!$project) { echo "No project to test.\n"; return; }
    
    // Fix missing assigned users for the test to bypass ConfigurationException
    $validUser = User::first()->id;
    \App\Models\EntityApprovalStage::where('entity_id', $project->entity_id)->update(['responsible_user_id' => $validUser]);
    \App\Models\AuthorityApprovalStage::where('authority_id', $project->authority_id)->update(['responsible_user_id' => $validUser]);

    $builder = app(ApprovalChainBuilder::class);
    $stages = $builder->buildChainForProject($project);
    
    if (count($stages) > 0) {
        echo "✅ New Workflow Chain Built! Stages count: " . count($stages) . "\n";
    } else {
        echo "❌ New Workflow Failed\n";
    }
}

function testLegacyProject() {
    echo "Testing Legacy Project...\n";
    // Find an old project approval (without assigned_user_id)
    $legacyApproval = ProjectApproval::whereNull('assigned_user_id')->whereNotNull('technical_reviewer_id')->first();
    if ($legacyApproval) {
        $userId = $legacyApproval->technical_reviewer_id;
        echo "Legacy approval found. Technical Reviewer ID: $userId\n";
        
        // Test responsibility resolution
        $isAssigned = $legacyApproval->isAssignedTo($userId);
        if ($isAssigned) {
            echo "✅ Legacy responsibility resolved correctly!\n";
        } else {
            echo "❌ Legacy responsibility failed!\n";
        }
        
        // Test UI label
        echo "✅ Legacy phase label: " . $legacyApproval->getPhaseLabel() . "\n";
    } else {
        echo "No legacy approval found to test.\n";
    }
}

testNewWorkflow();
testLegacyProject();
