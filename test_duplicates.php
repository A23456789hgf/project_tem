<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$dups = DB::select('SELECT form_number, count(*) as c FROM projects GROUP BY form_number HAVING count(*) > 1');
echo json_encode($dups);
