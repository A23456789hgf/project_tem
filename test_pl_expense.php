<?php

use App\Models\Project;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$baseUrl = config('external.erpnext.base_url');
$apiKey = config('external.erpnext.user');
$apiSecret = config('external.erpnext.pass');

$headers = [
    'Authorization' => 'token '.$apiKey.':'.$apiSecret,
    'Accept' => 'application/json',
];

$project = Project::find(69);
$erpNextProjectName = $project->erpnext_project_id;
$company = 'وحدة تمويل المشاريع والمبادرات الزراعية والسمكية بمحافظة عمران';

$filters = [
    'company' => $company,
    'filter_based_on' => 'Fiscal Year',
    'period_start_date' => '2023-01-01',
    'period_end_date' => '2026-12-31',
    'from_fiscal_year' => '1444',
    'to_fiscal_year' => '1447',
    'periodicity' => 'Yearly',
    'project' => [$erpNextProjectName],
    'show_account_details' => 'Summary',
    'selected_view' => 'Report',
    'include_default_book_entries' => 1,
    'accumulated_values' => 1,
];

echo "\nTesting get_pl_expense_type_summary from 1444 to 1447:\n";
$response = Http::timeout(30)
    ->withHeaders($headers)
    ->get($baseUrl.'/api/method/get_pl_expense_type_summary', [
        'filters' => json_encode($filters),
    ]);

echo 'Status: '.$response->status()."\n";
echo 'Response: '.json_encode($response->json(), JSON_UNESCAPED_UNICODE)."\n";
