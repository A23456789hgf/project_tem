<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('phone', '775540028')->first();
Auth::login($user);

$request = Illuminate\Http\Request::create('/force-change-password', 'POST', [
    'current_password' => '123456',
    'password' => '12345678',
    'password_confirmation' => '12345678'
]);

// Since the controller uses FormRequests or ValidatesRequests trait, we need to inject the request into the container
app()->instance('request', $request);

try {
    $controller = new App\Http\Controllers\AuthController();
    $response = $controller->forceChangePassword($request);
    
    echo 'Response status: ' . $response->getStatusCode() . PHP_EOL;
    if ($response->isRedirection()) {
        echo 'Redirect location: ' . $response->headers->get('Location') . PHP_EOL;
    }
    if (session()->has('errors')) {
        echo 'Errors: ' . json_encode(session('errors')->toArray()) . PHP_EOL;
    }
} catch (Illuminate\Validation\ValidationException $e) {
    echo "Validation failed: " . json_encode($e->errors()) . PHP_EOL;
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}
