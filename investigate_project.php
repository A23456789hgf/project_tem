<?php

use App\Models\Project;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$project = Project::with(['location', 'entity'])->first();
if ($project) {
    echo "Project Entity:\n";
    print_r($project->entity ? $project->entity->toArray() : 'No Entity');
    echo "\n\nProject Location:\n";
    print_r($project->location ? $project->location->toArray() : 'No Location');

    echo "\n\nProject Status:\n".$project->status."\n";
    echo "Project Type:\n".$project->type."\n";
} else {
    echo "No project found.\n";
}
