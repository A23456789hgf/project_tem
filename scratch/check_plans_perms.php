<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Permission;
use Illuminate\Contracts\Console\Kernel;

$plans = Permission::where('module', 'plans')->get(['slug', 'name']);
foreach ($plans as $p) {
    echo "plans: {$p->slug} - {$p->name}\n";
}

$progs = Permission::where('module', 'programs')->get(['slug', 'name']);
foreach ($progs as $p) {
    echo "programs: {$p->slug} - {$p->name}\n";
}

$subdoms = Permission::where('module', 'subdomains')->get(['slug', 'name']);
foreach ($subdoms as $p) {
    echo "subdomains: {$p->slug} - {$p->name}\n";
}
