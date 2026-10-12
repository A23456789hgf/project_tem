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

    $filters = [
        'company' => $company,
        'from_date' => '2000-01-01',
        'to_date' => now()->format('Y-m-d'),
    ];
    echo 'Filters: '.json_encode($filters, JSON_UNESCAPED_UNICODE)."\n";

    // Call API exactly like getProjectFinancialOperationsByExpenseType does
    $baseUrl = config('external.erpnext.base_url');
    $apiKey = config('external.erpnext.user');
    $apiSecret = config('external.erpnext.pass');

    $response = Http::timeout(30)
        ->withHeaders([
            'Authorization' => 'token '.$apiKey.':'.$apiSecret,
            'Accept' => 'application/json',
        ])
        ->get($baseUrl.'/api/method/get_gl_report', [
            'filters' => json_encode($filters),
        ]);

    $data = $response->json();
    $rows = $data['message']['result'] ?? [];
    echo 'Total GL rows returned: '.count($rows)."\n";

    $projectRows = [];
    $validExpenseRows = [];
    $actualSpent = 0;

    foreach ($rows as $row) {
        $accountStr = trim($row['account'] ?? '');
        if (in_array($accountStr, ['Opening', 'Total', 'Closing', 'Closing (Opening + Total)']) || str_starts_with($accountStr, "'") || str_starts_with($accountStr, '"')) {
            continue;
        }

        if (($row['project'] ?? '') !== $erpNextProjectName) {
            continue;
        }
        $projectRows[] = $row;

        $expenseType = $row['claim_expense_type'] ?? null;
        if (! $expenseType) {
            continue;
        }

        $normalizedType = preg_replace('/\s+/', ' ', trim($expenseType));
        if (strtolower($normalizedType) === 'no expense type found') {
            continue;
        }

        $validExpenseRows[] = $row;
        $net = ($row['debit'] ?? 0) - ($row['credit'] ?? 0);
        $actualSpent += $net;
    }

    echo 'Rows matching project: '.count($projectRows)."\n";
    echo 'Rows with valid claim_expense_type: '.count($validExpenseRows)."\n";
    echo "Calculated Actual Spent: {$actualSpent}\n";
    echo "Approved Total: {$project->cost->total_cost}\n";
    $rem = ($project->cost->total_cost ?? 0) - $actualSpent;
    echo "Calculated Remaining: {$rem}\n\n";

    echo "FIRST 10 ROWS DUMP:\n";
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
            'cost_center' => $r['cost_center'] ?? null,
            'debit' => $r['debit'] ?? 0,
            'credit' => $r['credit'] ?? 0,
        ], JSON_UNESCAPED_UNICODE)."\n";
    }
}
