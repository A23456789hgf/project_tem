<?php

use App\Models\Project;
use App\Services\ErpNextReportService;
use App\Services\FrappeAPIService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$frappeService = app(FrappeAPIService::class);
$erpReportService = app(ErpNextReportService::class);

$project = Project::find(69);
$erpNextProjectName = $project->erpnext_project_id;
$projResponse = $frappeService->httpGet('Project/'.urlencode($erpNextProjectName));
$company = $projResponse['data']['company'] ?? null;

echo "Project: $erpNextProjectName | Company: $company\n\n";

$baseUrl = config('external.erpnext.base_url');
$apiKey = config('external.erpnext.user');
$apiSecret = config('external.erpnext.pass');

$headers = [
    'Authorization' => 'token '.$apiKey.':'.$apiSecret,
    'Accept' => 'application/json',
];

// Test get_pl_summary
$filters = [
    'company' => $company,
    'filter_based_on' => 'Fiscal Year',
    'period_start_date' => '2023-01-01',
    'period_end_date' => '2026-12-31',
    'from_fiscal_year' => '2023',
    'to_fiscal_year' => '2026',
    'periodicity' => 'Yearly',
    'project' => [$erpNextProjectName],
    'show_account_details' => 'Summary',
    'selected_view' => 'Report',
    'include_default_book_entries' => 1,
    'accumulated_values' => 1,
];

echo "Testing get_pl_summary with multiple years:\n";
$response = Http::timeout(30)
    ->withHeaders($headers)
    ->get($baseUrl.'/api/method/get_pl_summary', [
        'filters' => json_encode($filters),
    ]);

echo 'Status: '.$response->status()."\n";
echo 'Response: '.json_encode($response->json(), JSON_UNESCAPED_UNICODE)."\n\n";

// If the above fails or returns nothing, let's try a single year
$filtersSingle = $filters;
$filtersSingle['from_fiscal_year'] = '2023';
$filtersSingle['to_fiscal_year'] = '2023';
echo "Testing get_pl_summary with single year 2023:\n";
$response2 = Http::timeout(30)
    ->withHeaders($headers)
    ->get($baseUrl.'/api/method/get_pl_summary', [
        'filters' => json_encode($filtersSingle),
    ]);
echo 'Status: '.$response2->status()."\n";
echo 'Response: '.json_encode($response2->json(), JSON_UNESCAPED_UNICODE)."\n\n";

// Test get_pl_expense_type_summary
echo "Testing get_pl_expense_type_summary:\n";
$response3 = Http::timeout(30)
    ->withHeaders($headers)
    ->get($baseUrl.'/api/method/get_pl_expense_type_summary', [
        'filters' => json_encode($filters),
    ]);
echo 'Status: '.$response3->status()."\n";
echo 'Response: '.json_encode($response3->json(), JSON_UNESCAPED_UNICODE)."\n\n";
