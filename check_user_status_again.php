<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('phone', '772722363')->first();
if ($user) {
    echo "Status: " . $user->status . "\n";
    echo "Is Active: " . (isset($user->is_active) ? $user->is_active : 'null') . "\n";
    echo "Password starts with: " . substr($user->password, 0, 10) . "\n";
} else {
    echo "User not found\n";
}
