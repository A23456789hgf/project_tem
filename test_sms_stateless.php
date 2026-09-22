<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';
$app = app();
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SmsLog;
use App\Services\SmppSmsService;
use Illuminate\Support\Facades\Auth;

echo "==================================================\n";
echo "Testing Stateless SMS Queue\n";
echo "==================================================\n";

try {
    // Fake login to simulate HTTP auth session
    Auth::loginUsingId(999);
    echo 'Simulated HTTP User ID: '.Auth::id()."\n";

    // Clear jobs to start fresh
    DB::table('jobs')->truncate();
    DB::table('sms_logs')->truncate();

    $service = app(SmppSmsService::class);
    $result = $service->sendSMS(123, '770000000', 'Test Stateless SMS Queue', 'test_event');

    echo 'Queue Dispatch Result: '.json_encode($result)."\n";
    echo 'PENDING JOBS IN DB: '.DB::table('jobs')->count()."\n";

    $jobRecord = DB::table('jobs')->first();
    if ($jobRecord) {
        $payload = json_decode($jobRecord->payload, true);
        $commandStr = $payload['data']['command'];
        $commandObj = unserialize($commandStr);
        echo 'Job Deserialization Success. sentByUserId = '.$commandObj->sentByUserId."\n";

        // Log out to simulate Worker environment
        Auth::logout();
        echo 'Logged out. Current Auth::id(): '.(Auth::id() ?? 'NULL')."\n";

        // Now let's execute the job directly to simulate the worker
        echo "Simulating Worker Execution...\n";
        $commandObj->handle($service);

        // Check logs
        $log = SmsLog::orderBy('id', 'desc')->first();
        echo 'SmsLog created: '.($log ? 'Yes' : 'No')."\n";
        if ($log) {
            echo 'Recipient ID: '.$log->recipient_user_id."\n";
            echo 'Sent By User ID: '.($log->sent_by_user_id ?? 'NULL')."\n";
            echo 'Status: '.$log->status."\n";
        }
    }
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
