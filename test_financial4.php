<?php

use App\Models\Project;
use App\Services\ErpNextReportService;
use App\Services\FrappeAPIService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$frappeService = app(FrappeAPIService::class);
$erpReportService = app(ErpNextReportService::class);

$project = Project::where('erpnext_project_id', 'PROJ-0003')->first();
if (! $project) {
    echo "PROJ-0003 not found. Trying any old project.\n";
    $project = Project::where('project_type', 'old')->whereNotNull('erpnext_project_id')->first();
}

if ($project) {
    $erpNextProjectName = $project->erpnext_project_id;
    echo "Laravel Project ID: {$project->id}\n";
    echo "ERPNext Project Name: {$erpNextProjectName}\n";

    $projResponse = $frappeService->httpGet('Project/'.urlencode($erpNextProjectName));
    $company = $projResponse['data']['company'] ?? null;
    echo "ERPNext Company: {$company}\n";

    $filters1 = [
        'company' => $company,
        'from_date' => '2000-01-01',
        'to_date' => now()->format('Y-m-d'),
    ];
    $filters2 = [
        'company' => $company,
        'project' => [$erpNextProjectName],
        'from_date' => '2000-01-01',
        'to_date' => now()->format('Y-m-d'),
    ];
    $filters3 = [
        'company' => $company,
        'project' => $erpNextProjectName,
        'from_date' => '2000-01-01',
        'to_date' => now()->format('Y-m-d'),
    ];

    echo "\nTEST 1: No project filter\n";
    $allGl = $erpReportService->getReport('General Ledger', $filters1, false);
    echo 'Rows returned: '.count($allGl['result'] ?? [])."\n";

    echo "\nTEST 2: project filter as ARRAY\n";
    $allGl2 = $erpReportService->getReport('General Ledger', $filters2, false);
    echo 'Rows returned: '.count($allGl2['result'] ?? [])."\n";
    if (isset($allGl2['error'])) {
        echo 'Error: '.$allGl2['error']."\n";
    }

    echo "\nTEST 3: project filter as STRING\n";
    $allGl3 = $erpReportService->getReport('General Ledger', $filters3, false);
    echo 'Rows returned: '.count($allGl3['result'] ?? [])."\n";
    if (isset($allGl3['error'])) {
        echo 'Error: '.$allGl3['error']."\n";
    }
}
