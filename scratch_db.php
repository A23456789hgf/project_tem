<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumns('projects');
$required = [];
foreach ($columns as $c) {
    if (! $c['nullable'] && $c['default'] === null && $c['auto_increment'] === false && ! in_array($c['name'], ['created_at', 'updated_at'])) {
        $required[] = $c['name'];
    }
}
echo 'Required DB Columns: '.json_encode($required)."\n";
