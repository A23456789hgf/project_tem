<?php
$project = App\Models\Project::latest()->first();
if ($project) {
    echo "Project found: " . $project->id . "\n";
    $stages = app(App\Services\ApprovalChainBuilder::class)->buildChainForProject($project);
    echo json_encode(['stages' => $stages], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "No project found.\n";
}
