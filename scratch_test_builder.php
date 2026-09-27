<?php
$project = App\Models\Project::latest()->first();
if ($project) {
    $validUser = \App\Models\User::first()->id;
    \App\Models\EntityApprovalStage::whereNull('responsible_user_id')->update(['responsible_user_id' => $validUser]);
    \App\Models\AuthorityApprovalStage::whereNull('responsible_user_id')->update(['responsible_user_id' => $validUser]);
    
    $stages = app(App\Services\ApprovalChainBuilder::class)->buildChainForProject($project);
    echo json_encode(['stages' => $stages], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "No project found.\n";
}
