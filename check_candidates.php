<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = ['772722363', '0772722363', '967772722363'];
$users = \App\Models\User::whereIn('phone', $c)->orWhereIn('username', $c)->get();
echo 'Found: ' . $users->count() . "\n";
foreach($users as $u) {
    echo $u->id . ' - ' . $u->username . ' - ' . $u->phone . ' - ' . $u->status . "\n";
}
