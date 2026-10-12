<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\ProjectCost;
use App\Services\ProjectNumberGenerator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$projects = Project::with('cost')->whereNotNull('erpnext_project_id')->orderBy('id', 'asc')->get();

$unchanged = 0;
$renumbered = 0;
$withYear = 0;
$withoutYear = 0;
$reqYearBased = 0;
$reqNoYear = 0;

$changes = [];

foreach ($projects as $project) {
    // Some projects might not have a ProjectCost record because we only created them for > 0 previously
    // So let's fetch or create it for those that don't have it (or just check the relation)
    $cost = $project->cost;
    $hijriYear = $cost ? $cost->hijri_year : null;

    if ($hijriYear) {
        $withYear++;
    } else {
        $withoutYear++;
        // If it doesn't have a cost record at all, let's create one now to keep it consistent
        if (! $cost) {
            ProjectCost::create([
                'project_id' => $project->id,
                'total_cost' => 0,
                'hijri_year' => null,
            ]);
        }
    }

    $currentNumber = $project->form_number;
    $reason = null;
    $isMatch = false;

    if ($hijriYear) {
        if (str_starts_with($currentNumber, "PRO{$hijriYear}") && strlen($currentNumber) === 11) {
            $isMatch = true;
        } else {
            $reqYearBased++;
            if (preg_match('/^PRO\d{4}$/', $currentNumber)) {
                $reason = 'FUNDING_YEAR_ADDED'; // From null to year
            } else {
                $reason = 'FUNDING_YEAR_MISMATCH'; // Year changed
            }
        }
    } else {
        if (preg_match('/^PRO\d{4}$/', $currentNumber)) {
            $isMatch = true;
        } else {
            $reqNoYear++;
            $reason = 'NO_FUNDING_YEAR';
        }
    }

    if (! $isMatch) {
        DB::transaction(function () use ($project, $hijriYear, $currentNumber, $reason, &$renumbered, &$changes) {
            $newNumber = ProjectNumberGenerator::getNextProjectNumber($hijriYear);
            $project->update(['form_number' => $newNumber]);

            $changes[] = [
                'Laravel ID' => $project->id,
                'ERPNext name' => $project->erpnext_project_id,
                'Funding Hijri Year' => $hijriYear ?: 'NULL',
                'Old form_number' => $currentNumber,
                'New form_number' => $newNumber,
                'Reason' => $reason,
            ];
            $renumbered++;
        });
    } else {
        $unchanged++;
    }
}

echo 'Projects audited: '.$projects->count()."\n";
echo "Projects With Funding Hijri Year: $withYear\n";
echo "Projects Without Funding Hijri Year: $withoutYear\n";
echo "Projects requiring year-based renumbering: $reqYearBased\n";
echo "Projects requiring no-year renumbering: $reqNoYear\n";
echo "Projects actually renumbered: $renumbered\n";
echo "Projects unchanged: $unchanged\n\n";

if (! empty($changes)) {
    echo "Laravel ID | ERPNext name | Funding Hijri Year | Old form_number | New form_number | Reason\n";
    echo "--------------------------------------------------------------------------------------------\n";
    foreach ($changes as $c) {
        echo "{$c['Laravel ID']} | {$c['ERPNext name']} | {$c['Funding Hijri Year']} | {$c['Old form_number']} | {$c['New form_number']} | {$c['Reason']}\n";
    }
}
