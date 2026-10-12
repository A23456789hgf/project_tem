<?php

namespace App\Console;

use App\Console\Commands\ErpNextBidirectionalSync;
use App\Console\Commands\ErpSyncAllProjects;
use App\Console\Commands\FetchErpUnits;
use App\Console\Commands\RetryInternalEntityErpSync;
use App\Console\Commands\SendTaskReminders;
use App\Console\Commands\SyncProjectsToErpNext;
use App\Console\Commands\SyncProjectsToFrappe;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        FetchErpUnits::class,
        ErpSyncAllProjects::class,
        SyncProjectsToErpNext::class,
        SyncProjectsToFrappe::class,
        SendTaskReminders::class,
        ErpNextBidirectionalSync::class,
        RetryInternalEntityErpSync::class,
        \App\Console\Commands\RetryFailedFinancialUsersSyncCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /*
        |--------------------------------------------------------------------------
        | مزامنة المشاريع إلى ERPNext
        |--------------------------------------------------------------------------
        | تعمل كل ساعة.
        */
        $schedule->command('projects:sync-to-erpnext')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/erpnext-sync.log')
            );

        /*
        |--------------------------------------------------------------------------
        | مزامنة مشاريع مرحلة التنفيذ إلى Frappe
        |--------------------------------------------------------------------------
        | تعمل كل 30 دقيقة.
        */
        $schedule->command('frappe:sync-projects')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/frappe-sync-schedule.log')
            );

        /*
        |--------------------------------------------------------------------------
        | إرسال تذكيرات المهام
        |--------------------------------------------------------------------------
        | تعمل يومياً الساعة 08:00.
        */
        $schedule->command('tasks:send-reminders')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/task-reminders.log')
            );

        /*
        |--------------------------------------------------------------------------
        | المزامنة الثنائية Laravel ↔ ERPNext
        |--------------------------------------------------------------------------
        | تعمل كل دقيقة.
        */
        $schedule->command('erpnext:sync-bidirectional')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/erpnext-bidirectional-sync.log')
            );

        /*
        |--------------------------------------------------------------------------
        | إعادة محاولة مزامنة الجهات الداخلية مع ERPNext
        |--------------------------------------------------------------------------
        | تعمل كل 10 دقائق لمعالجة السجلات المعلقة والفاشلة تلقائياً.
        */
        $schedule->command('erpnext:retry-entity-sync')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/erpnext-entity-sync.log')
            );

        /*
        |--------------------------------------------------------------------------
        | إعادة محاولة مزامنة المستخدمين الماليين مع ERPNext (غير الحساسة)
        |--------------------------------------------------------------------------
        | تعمل كل 15 دقيقة لمعالجة السجلات المعلقة والفاشلة تلقائياً.
        */
        $schedule->command('erpnext:retry-failed-users')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(
                storage_path('logs/erpnext-users-sync.log')
            );
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // تحميل جميع أوامر Artisan من مجلد Commands
        $this->load(__DIR__.'/Commands');

        // تحميل أوامر routes/console.php
        require base_path('routes/console.php');
    }
}
