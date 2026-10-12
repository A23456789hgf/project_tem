<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::where('phone', '772722363')->get();
echo 'Found: ' . $users->count() . "\n";
foreach($users as $u) {
    echo $u->id . ' - ' . $u->status . ' - ' . (isset($u->is_active) ? $u->is_active : 'null') . "\n";
}
