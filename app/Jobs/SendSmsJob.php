<?php

namespace App\Jobs;

use App\Services\SmppSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [10, 30, 60];

    public $userId;

    public $mobileNo;

    public $message;

    public $eventName;

    public $sentByUserId;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $mobileNo, $message, $eventName = null, $sentByUserId = null)
    {
        $this->userId = $userId;
        $this->mobileNo = $mobileNo;
        $this->message = $message;
        $this->eventName = $eventName;
        $this->sentByUserId = $sentByUserId;
    }

    /**
     * Execute the job.
     */
    public function handle(SmppSmsService $smsService): void
    {
        $smsService->sendSMS($this->userId, $this->mobileNo, $this->message, $this->eventName, $this->sentByUserId);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendSmsJob queued processing failed', [
            'user_id' => $this->userId,
            'mobile' => $this->mobileNo,
            'event' => $this->eventName,
            'error' => $exception->getMessage(),
        ]);
    }
}
