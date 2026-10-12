<?php

use App\Models\Project;
use App\Services\ErpNextReportService;
use App\Services\FrappeAPIService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$projectId = 69;
$project = Project::find($projectId);
$erpNextProjectName = $project->erpnext_project_id;
$frappeService = app(FrappeAPIService::class);
$erpReportService = app(ErpNextReportService::class);

echo 'Laravel Project ID: '.$projectId.PHP_EOL;
echo 'ERPNext Project Name: '.$erpNextProjectName.PHP_EOL;

$projResponse = $frappeService->httpGet('Project/'.urlencode($erpNextProjectName));
$company = $projResponse['data']['company'] ?? null;
echo 'ERPNext Company: '.$company.PHP_EOL;

$filters = [
    'company' => $company,
    'project' => [$erpNextProjectName],
    'from_date' => '2000-01-01',
    'to_date' => now()->format('Y-m-d'),
];

echo 'Filters: '.json_encode($filters, JSON_UNESCAPED_UNICODE).PHP_EOL;

$glResponse = $erpReportService->getProjectFinancialOperationsByExpenseType($erpNextProjectName, $filters);

echo 'Actual Spent: '.($glResponse['actual_spent'] ?? 'null').PHP_EOL;
echo 'Grouped Count: '.count($glResponse['grouped'] ?? []).PHP_EOL;
echo 'Transactions Count: '.count($glResponse['transactions'] ?? []).PHP_EOL;

if (isset($glResponse['error'])) {
    echo 'Error: '.$glResponse['error'].PHP_EOL;
}

echo "First 10 GL report rows:\n";
$allGl = $erpReportService->getReport('General Ledger', $filters, false);
if ($allGl['success']) {
    $rows = $allGl['result'] ?? [];
    echo 'Total returned rows: '.count($rows).PHP_EOL;
    $count = 0;
    foreach ($rows as $r) {
        if ($count++ > 10) {
            break;
        }
        echo json_encode([
            'posting_date' => $r['posting_date'] ?? null,
            'voucher_no' => $r['voucher_no'] ?? null,
            'account' => $r['account'] ?? null,
            'claim_expense_type' => $r['claim_expense_type'] ?? null,
            'project' => $r['project'] ?? null,
            'debit' => $r['debit'] ?? 0,
            'credit' => $r['credit'] ?? 0,
        ], JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
}
