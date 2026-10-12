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

$fiscalYears = ['1444', '1445', '1446', '1447'];

$sumIndiv = 0;

echo "Fiscal Year | Expense Returned | Added To Final Total\n";

foreach ($fiscalYears as $fy) {
    $filters = [
        'company' => $company,
        'filter_based_on' => 'Fiscal Year',
        'period_start_date' => '2023-01-01',
        'period_end_date' => '2026-12-31',
        'from_fiscal_year' => $fy,
        'to_fiscal_year' => $fy,
        'periodicity' => 'Yearly',
        'project' => [$erpNextProjectName],
        'show_account_details' => 'Summary',
        'selected_view' => 'Report',
        'include_default_book_entries' => 1,
        'accumulated_values' => 1,
    ];

    $response = Http::timeout(30)
        ->withHeaders($headers)
        ->get($baseUrl.'/api/method/get_pl_summary', [
            'filters' => json_encode($filters),
        ]);

    $data = $response->json();
    $reportSummary = $data['message']['report_summary'] ?? [];
    $spent = 0;
    foreach ($reportSummary as $item) {
        $label = mb_strtolower($item['label'] ?? '');
        if (str_contains($label, 'expense') || str_contains($label, 'مصاريف') || str_contains($label, 'مصروف')) {
            $spent = abs((float) ($item['value'] ?? 0));
        }
    }

    echo str_pad($fy, 11).' | '.str_pad($spent, 16).' | '.$spent."\n";
    $sumIndiv += $spent;
}

echo "SUM:\n$sumIndiv\n\n";

// Multi-year call
$filtersMulti = [
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

$responseMulti = Http::timeout(30)
    ->withHeaders($headers)
    ->get($baseUrl.'/api/method/get_pl_summary', [
        'filters' => json_encode($filtersMulti),
    ]);

$dataMulti = $responseMulti->json();
$reportSummaryMulti = $dataMulti['message']['report_summary'] ?? [];
$spentMulti = 0;
foreach ($reportSummaryMulti as $item) {
    $label = mb_strtolower($item['label'] ?? '');
    if (str_contains($label, 'expense') || str_contains($label, 'مصاريف') || str_contains($label, 'مصروف')) {
        $spentMulti = abs((float) ($item['value'] ?? 0));
    }
}

echo "Final Actual Spent (Multi-Year API call):\n$spentMulti\n\n";

$rawErp = 0;
foreach ($reportSummaryMulti as $item) {
    if (mb_strpos(mb_strtolower($item['label'] ?? ''), 'مصاريف') !== false) {
        $rawErp = $item['value'];
    }
}

echo "Raw ERPNext Expense Value:\n$rawErp\n";
echo "Laravel Actual Spent:\n$spentMulti\n";
