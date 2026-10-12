<?php

use App\Models\Project;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$projects = Project::whereIn('id', [127, 128, 131])->with('cost')->get();
foreach ($projects as $p) {
    echo 'Laravel ID: '.$p->id."\n";
    echo 'ERPNext name: '.$p->erpnext_project_id."\n";
    echo 'project_name: '.$p->project_name."\n";
    echo 'Current hijri_year: '.($p->cost ? $p->cost->hijri_year : 'NULL')."\n";
    echo 'start_date_gregorian: '.$p->start_date_gregorian."\n";
    echo 'end_date_gregorian: '.$p->end_date_gregorian."\n";
    echo "---------------------------\n";
}
