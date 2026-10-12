<?php

use App\Models\Project;
use App\Services\ErpNextReportService;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$project = Project::find(69);
$erpNextProjectName = $project->erpnext_project_id;
$company = 'وحدة تمويل المشاريع والمبادرات الزراعية والسمكية بمحافظة عمران';

echo "Laravel Project ID:\n{$project->id}\n\n";
echo "ERPNext Project:\n{$erpNextProjectName}\n\n";
echo "ERPNext Company:\n{$company}\n\n";

$erpReportService = app(ErpNextReportService::class);
$filters = [
    'company' => $company,
    'from_date' => $project->start_date_gregorian ? Carbon::parse($project->start_date_gregorian)->format('Y-m-d') : '2000-01-01',
    'to_date' => now()->format('Y-m-d'),
];

$summaryResponse = $erpReportService->getProjectFinancialSummary($erpNextProjectName, $filters);
$expenseTypesResponse = $erpReportService->getProjectExpenseTypeSummary($erpNextProjectName, $filters);
$glTransactionsResponse = $erpReportService->getProjectGlTransactions($erpNextProjectName, $filters);

$approvedTotal = (float) ($project->cost?->total_cost ?? 0);
$actualSpent = (float) ($summaryResponse['actual_spent'] ?? 0);
$remainingAmount = $approvedTotal - $actualSpent;

echo "Approved Total:\n{$approvedTotal}\n\n";
echo "get_pl_summary Actual Spent:\n{$actualSpent}\n\n";
echo "Calculated Remaining:\n{$remainingAmount}\n\n";
echo "Expense Types Count:\n".count($expenseTypesResponse['expense_types'] ?? [])."\n\n";
echo "GL Transactions Count:\n".count($glTransactionsResponse['transactions'] ?? [])."\n\n";

echo "Frontend Approved Total:\n{$approvedTotal}\n\n";
echo "Frontend Actual Spent:\n{$actualSpent}\n\n";
echo "Frontend Remaining:\n{$remainingAmount}\n\n";
