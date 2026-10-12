<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('phone', '772722363')->first();
if ($user) {
    echo "User found with normal scopes!\n";
} else {
    echo "User NOT found with normal scopes!\n";
}
