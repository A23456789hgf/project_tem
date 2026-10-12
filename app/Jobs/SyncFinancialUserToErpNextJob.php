<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ErpNextUserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncFinancialUserToErpNextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [15, 60, 180];

    /**
     * Create a new job instance.
     *
     * @param  int  $userId  The Laravel user ID.
     * @param  string|null  $action  The triggered action context (create, update, disable, reactivate, sync).
     * @param  string|null  $oldEmail  Previous email if email was modified.
     */
    public function __construct(
        public int $userId,
        public ?string $action = 'sync',
        public ?string $oldEmail = null
    ) {
        // Ensure the job is dispatched only after the active database transaction commits.
        // We set this here instead of declaring the property directly to avoid
        // incompatibility with Illuminate\Bus\Queueable in PHP 8.5+.
        $this->afterCommit = true;
    }

    /**
     * Execute the job.
     */
    public function handle(ErpNextUserService $syncService): void
    {
        // Prevent Queue race conditions and stale synchronization jobs.
        // The final ERPNext state must always reflect the latest committed Laravel user state.
        $lockKey = "erpnext_sync_user_{$this->userId}";

        Cache::lock($lockKey, 15)->block(10, function () use ($syncService) {
            $user = User::find($this->userId);

            if (! $user) {
                Log::warning("SyncFinancialUserToErpNextJob: User #{$this->userId} not found in database. Skipping.");

                return;
            }

            Log::info("SyncFinancialUserToErpNextJob: Processing sync for User #{$user->id} ({$user->name}) [Action: {$this->action}]", [
                'is_financial_user' => $user->is_financial_user,
                'status' => $user->status,
                'erpnext_user_id' => $user->erpnext_user_id,
            ]);

            $result = $syncService->syncUser($user, $this->action, $this->oldEmail);

            if (! ($result['success'] ?? false)) {
                $errorMsg = $result['error'] ?? ($result['message'] ?? 'Unknown synchronization failure');
                Log::error("SyncFinancialUserToErpNextJob: Failed to sync user #{$user->id}: {$errorMsg}");

                // Re-throw if it was a transient network or server error to trigger Queue retries
                if (isset($result['status']) && in_array($result['status'], [500, 502, 503, 504, 0], true)) {
                    throw new \RuntimeException($errorMsg);
                }
            }
        });
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $errorMsg = $exception ? $exception->getMessage() : 'Job failed after all retry attempts.';
        Log::error("SyncFinancialUserToErpNextJob: Permanently failed for user #{$this->userId}: {$errorMsg}");

        $user = User::find($this->userId);
        if ($user) {
            $user->updateQuietly([
                'erpnext_sync_status' => 'failed',
                'erpnext_sync_error' => $errorMsg,
            ]);
        }
    }
}
