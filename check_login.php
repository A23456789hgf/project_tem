<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rawIdentifier = '772722363';
$password = '123456';

$digits = preg_replace('/\D+/', '', $rawIdentifier) ?? '';
$candidates = array_filter([trim($rawIdentifier), $digits]);
$local = $digits;
$candidates[] = $local;
$candidates[] = '0'.$local;
$candidates[] = '967'.$local;
$phoneCandidates = array_values(array_unique($candidates));

$user = \App\Models\User::where(function ($query) use ($rawIdentifier, $phoneCandidates) {
    $query->where('username', $rawIdentifier)
        ->orWhereIn('phone', $phoneCandidates)
        ->orWhereIn(\Illuminate\Support\Facades\DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', ''), '(', ''), ')', ''), '.', '')"), $phoneCandidates);
})->first();

if (! $user || $user->status !== 'Active') {
    echo "❌ LOGIN FAILED: Invalid credentials or inactive user\n";
    if (!$user) {
        echo "User is null\n";
    } else {
        echo "User status is " . $user->status . "\n";
    }
} else {
    echo "User found: " . $user->id . "\n";
    if (! \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
        echo "❌ LOGIN FAILED: Invalid password\n";
    } else {
        echo "✅ LOGIN SUCCESS\n";
    }
}
