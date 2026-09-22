<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';
$app = app();
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Project;
use App\Services\ErpNextService;

echo "==================================================\n";
echo "Testing ERPNext Gateway\n";
echo "==================================================\n";

$config = config('external.erpnext', []);
$erpUrl = $config['base_url'] ?? 'NULL';
$erpUser = $config['user'] ?? 'NULL';
$apiKey = $config['api_key'] ?? 'NULL';

echo 'ERP URL: '.$erpUrl."\n";
echo 'User: '.$erpUser."\n";
echo 'API Key: '.($apiKey !== 'NULL' ? 'Present' : 'Missing')."\n";

try {
    $service = app(ErpNextService::class);

    // Testing Authentication by calling getProject with a fake ID or checking auth logic
    // We can just use the public getProject method which calls getAuthToken implicitly
    echo "\n--- Testing ERPNext Auth & Connection ---\n";
    $result = $service->getProject('DUMMY-TEST-ID-123');
    echo "Connection/Auth Status: SUCCESS\n";
    echo 'Result: '.json_encode($result)."\n";
} catch (Exception $e) {
    echo "Connection/Auth Status: FAILED\n";
    echo 'Error: '.$e->getMessage()."\n";
}

// Let's test a real project sync if there's a project
$project = Project::first();
if ($project) {
    echo "\n--- Testing Project Sync ---\n";
    echo 'Project ID: '.$project->id.' | Name: '.$project->project_name."\n";
    try {
        $syncResult = $service->sendProject($project);
        echo "Sync Status: SUCCESS\n";
        echo 'Response: '.json_encode($syncResult)."\n";
    } catch (Exception $e) {
        echo "Sync Status: FAILED\n";
        echo 'Error: '.$e->getMessage()."\n";
    }
} else {
    echo "\nNo project found in DB to test sync.\n";
}
