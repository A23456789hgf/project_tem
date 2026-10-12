<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Fake HTTP Request
$request = Request::create('/login', 'POST', [
    'phone' => '775540028',
    'password' => '775540028',
]);

echo "Sending Request to AuthController...\n";
$response = app()->handle($request);

echo 'Response Status: '.$response->getStatusCode()."\n";
if ($response->isRedirect()) {
    echo 'Redirect Target: '.$response->headers->get('Location')."\n";
} else {
    echo 'Content: '.substr($response->getContent(), 0, 200)."\n";
}

if (app('session')->has('errors')) {
    echo 'Errors: '.json_encode(app('session')->get('errors')->all(), JSON_UNESCAPED_UNICODE)."\n";
}

// Check auth status
echo 'Auth::check(): '.(Auth::check() ? 'True' : 'False')."\n";
if (Auth::check()) {
    echo 'Auth ID: '.Auth::id()."\n";

    // Simulate request to dashboard
    $dashRequest = Request::create('/dashboard', 'GET');
    $dashRequest->setLaravelSession(app('session')->driver()); // pass session
    $dashResponse = app()->handle($dashRequest);

    echo 'Dashboard Status: '.$dashResponse->getStatusCode()."\n";
    if ($dashResponse->isRedirect()) {
        echo 'Dashboard Redirect: '.$dashResponse->headers->get('Location')."\n";
    }

    if (app('session')->has('errors')) {
        echo 'Dashboard Errors: '.json_encode(app('session')->get('errors')->all(), JSON_UNESCAPED_UNICODE)."\n";
    }
    echo 'Auth::check() after Dashboard: '.(Auth::check() ? 'True' : 'False')."\n";
}
