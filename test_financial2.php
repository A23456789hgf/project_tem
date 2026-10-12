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

$projects = Project::where('project_type', 'old')->whereNotNull('erpnext_project_id')->take(20)->get();

foreach ($projects as $project) {
    $erpNextProjectName = $project->erpnext_project_id;
    $projResponse = $frappeService->httpGet('Project/'.urlencode($erpNextProjectName));
    $company = $projResponse['data']['company'] ?? null;
    if (! $company) {
        continue;
    }

    $filters = [
        'company' => $company,
        'project' => [$erpNextProjectName],
        'from_date' => '2000-01-01',
        'to_date' => now()->format('Y-m-d'),
    ];

    $glResponse = $erpReportService->getProjectFinancialOperationsByExpenseType($erpNextProjectName, $filters);

    if (($glResponse['actual_spent'] ?? 0) > 0) {
        echo 'FOUND ONE: '.$erpNextProjectName.' - Actual: '.$glResponse['actual_spent'].PHP_EOL;
        break;
    }
}
