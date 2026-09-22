<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Permission;
use Illuminate\Contracts\Console\Kernel;

$count = Permission::count();
echo 'Total DB Permissions: '.$count.PHP_EOL;

$sample = Permission::where('module', 'projects')->orWhere('slug', 'like', '%approve%')->limit(20)->get(['slug', 'module', 'name']);
foreach ($sample as $p) {
    echo " - [{$p->module}] {$p->slug} ({$p->name})\n";
}

$modules = Permission::distinct()->pluck('module')->sort()->values();
echo "\nTotal Modules: ".count($modules).PHP_EOL;
echo implode(', ', $modules->toArray()).PHP_EOL;
