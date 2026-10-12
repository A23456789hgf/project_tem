<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$projects = Project::with('cost')->whereNotNull('erpnext_project_id')->orderBy('id', 'asc')->get();

$validYears = 0;
$nullYears = 0;
$invalidYears = 0;

$changes = [];

foreach ($projects as $project) {
    $cost = $project->cost;
    $hijriYear = $cost ? $cost->hijri_year : null;
    $currentNumber = $project->form_number;

    $isValid = false;
    $isNull = false;

    if ($hijriYear) {
        $yearInt = (int) $hijriYear;
        if ($yearInt >= 1400 && $yearInt <= 1500) {
            $isValid = true;
            $validYears++;
        } else {
            $invalidYears++;

            DB::transaction(function () use ($project, $cost, $hijriYear, $currentNumber, &$changes) {
                // Update cost to NULL year
                $cost->update(['hijri_year' => null]);

                // The observer should automatically update the form_number.
                // But just to be sure we can check it.
                $project->refresh();
                $newNumber = $project->form_number;

                $changes[] = [
                    'Laravel ID' => $project->id,
                    'ERPNext name' => $project->erpnext_project_id,
                    'Old hijri_year' => $hijriYear,
                    'New hijri_year' => 'NULL',
                    'Old form_number' => $currentNumber,
                    'New form_number' => $newNumber,
                    'Source/Reason' => 'INVALID_YEAR_851',
                ];
            });
        }
    } else {
        $isNull = true;
        $nullYears++;
    }
}

echo 'Projects audited: '.$projects->count()."\n";
echo "Valid Funding Hijri Years: $validYears\n";
echo "NULL Funding Hijri Years: $nullYears\n";
echo "Invalid Funding Hijri Years: $invalidYears\n";
echo 'Projects corrected: '.count($changes)."\n\n";

if (! empty($changes)) {
    echo "Laravel ID | ERPNext name | Old hijri_year | New hijri_year | Old form_number | New form_number | Source/Reason\n";
    echo "-----------------------------------------------------------------------------------------------------------------\n";
    foreach ($changes as $c) {
        echo "{$c['Laravel ID']} | {$c['ERPNext name']} | {$c['Old hijri_year']} | {$c['New hijri_year']} | {$c['Old form_number']} | {$c['New form_number']} | {$c['Source/Reason']}\n";
    }
}
