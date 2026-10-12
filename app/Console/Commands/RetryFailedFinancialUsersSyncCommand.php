<?php

namespace App\Console\Commands;

use App\Jobs\SyncFinancialUserToErpNextJob;
use App\Models\User;
use App\Services\ErpNextUserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RetryFailedFinancialUsersSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erpnext:retry-failed-users {--limit=50 : Maximum number of failed users to process} {--sync : Run synchronously instead of queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إعادة محاولة المزامنة التلقائية للعمليات غير الحساسة للمستخدمين الماليين الفاشلين في ERPNext';

    /**
     * Execute the console command.
     */
    public function handle(ErpNextUserService $userService): int
    {
        $limit = (int) $this->option('limit');
        $runSync = (bool) $this->option('sync');

        $failedUsers = User::where(function ($query) {
            $query->where('is_financial_user', true)
                ->orWhereNotNull('erpnext_user_id');
        })
            ->where(function ($query) {
                $query->where('erpnext_sync_status', 'failed')
                    ->orWhere('erpnext_sync_status', 'pending');
            })
            ->limit($limit)
            ->get();

        if ($failedUsers->isEmpty()) {
            $this->info('لا يوجد مستخدمون ماليون بحاجة لإعادة المزامنة.');

            return self::SUCCESS;
        }

        $this->info("تم العثور على {$failedUsers->count()} مستخدم بحاجة لإعادة المزامنة. جاري المعالجة...");

        $successCount = 0;
        $failCount = 0;

        foreach ($failedUsers as $user) {
            $lockKey = "erpnext_sync_user_{$user->id}";

            // Ensure idempotency and race condition prevention
            $lock = Cache::lock($lockKey, 15);

            if (! $lock->get()) {
                $this->warn("- تخطي المستخدم #{$user->id} ({$user->name}) لوجود عملية مزامنة جارية حالياً.");

                continue;
            }

            try {
                if ($runSync) {
                    $result = $userService->syncUser($user, 'update');
                    if ($result['success'] ?? false) {
                        $this->info("✓ تمت مزامنة المستخدم #{$user->id} ({$user->name}) بنجاح.");
                        $successCount++;
                    } else {
                        $this->error("✗ فشلت مزامنة المستخدم #{$user->id}: ".($result['error'] ?? 'خطأ غير معروف'));
                        $failCount++;
                    }
                } else {
                    SyncFinancialUserToErpNextJob::dispatch($user->id, 'update');
                    $this->line("- تم إرسال مهمة المزامنة للمستخدم #{$user->id} ({$user->name}) إلى Queue.");
                    $successCount++;
                }
            } catch (\Throwable $e) {
                Log::error("RetryFailedFinancialUsersSyncCommand exception for User #{$user->id}: ".$e->getMessage());
                $this->error("✗ استثناء أثناء معالجة المستخدم #{$user->id}: ".$e->getMessage());
                $failCount++;
            } finally {
                $lock->release();
            }
        }

        $this->info("اكتملت العملية: نجاح/إرسال [{$successCount}]، فشل [{$failCount}].");

        return self::SUCCESS;
    }
}
