<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';
$app = app();
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\SmppSmsService;

echo "==================================================\n";
echo "Testing Queued SMS\n";
echo "==================================================\n";

try {
    $service = app(SmppSmsService::class);
    $result = $service->sendSMS(1, '770000000', 'Test Message via Queue', 'test_event');
    echo "Queue Dispatch Result:\n";
    print_r($result);
    echo 'PENDING JOBS IN DB: '.DB::table('jobs')->count()."\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
