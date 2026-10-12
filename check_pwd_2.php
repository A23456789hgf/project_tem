<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('phone', '775540028')->first();
echo 'Password Hash: ' . $u->password . PHP_EOL;
echo 'Check 123456: ' . (Hash::check('123456', $u->password) ? 'TRUE' : 'FALSE') . PHP_EOL;
