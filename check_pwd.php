<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$u = \App\Models\User::withoutGlobalScopes()->find(9);
echo 'Hash: ' . ($u ? $u->password : 'null') . "\n";
echo 'Check 123456: ' . (\Illuminate\Support\Facades\Hash::check('123456', $u->password) ? 'true' : 'false') . "\n";
