<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::withoutGlobalScopes()->where('phone', '772722363')->first();
if ($user) {
    echo json_encode([
        'id' => $user->id,
        'must_change_password' => $user->must_change_password,
        'status' => $user->status,
        'password_is_123456' => \Illuminate\Support\Facades\Hash::check('123456', $user->password)
    ]);
} else {
    echo "User not found";
}
