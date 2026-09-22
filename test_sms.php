<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';
$app = app();
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SmsLog;
use App\Services\SmppSmsService;

echo "==================================================\n";
echo "Testing SMS Gateway\n";
echo "==================================================\n";

$apiUrl = config('sms.api_url');
$email = config('sms.email');
$password = config('sms.password');

echo 'URL: '.$apiUrl."\n";
echo 'Email: '.$email."\n";
echo 'Password: '.$password."\n";
echo 'Login Endpoint: '.rtrim($apiUrl, '/')."/login\n";
echo 'Send Endpoint: '.rtrim($apiUrl, '/')."/send-sms\n";

try {
    echo "\n--- Testing Login ---\n";
    $service = app(SmppSmsService::class);
    // Since login() is public now, we can just call it
    $token = $service->login();
    echo "Login Status: SUCCESS\n";
    echo 'Token Received: '.substr($token, 0, 10)."...\n";

    echo "\n--- Testing Send SMS ---\n";
    // We will test sendSmsSync with a dummy number
    // public function sendSmsSync($userId, $mobileNo, $message, $eventName = null)
    $service->sendSmsSync(1, '770000000', 'Test Message from Laravel Integration Test', 'test_event');
    echo "Send SMS Status: SUCCESS\n";
} catch (Exception $e) {
    echo "Login Status: FAILED\n";
    echo 'Error: '.$e->getMessage()."\n";
}

echo "\n--- Checking recent SmsLog ---\n";
$latestLog = SmsLog::orderBy('id', 'desc')->first();
if ($latestLog) {
    echo 'Last Log ID: '.$latestLog->id."\n";
    echo 'Phone: '.$latestLog->mobile_no."\n";
    echo 'Message: '.$latestLog->message."\n";
    echo 'Status: '.$latestLog->status."\n";
    echo 'Response: '.$latestLog->response."\n";
} else {
    echo "No SMS logs found.\n";
}
