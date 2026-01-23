<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Example 1: Schedule a command to run daily at 8 AM
        $schedule->command('send:daily-notifications')
            ->dailyAt('08:00')
            ->timezone('Asia/Kolkata')
            ->name('daily_notifications')
            ->onSuccess(function () {
                \Log::info('Daily notifications sent successfully');
            })
            ->onFailure(function () {
                \Log::error('Failed to send daily notifications');
            });

        // Example 2: Schedule a closure to run every hour
        $schedule->call(function () {
            \App\Models\Blog::where('created_at', '<', now()->subDays(30))
                ->delete();
            \Log::info('Old blogs cleaned up');
        })->hourly()->name('cleanup_old_blogs');

        // Example 3: Schedule a queued job
        $schedule->job(new \App\Jobs\GenerateBlogReport())
            ->daily()
            ->at('22:00')
            ->name('blog_report_generation');

        // Example 4: Schedule a shell command
        $schedule->exec('php ' . base_path('artisan') . ' backup:run')
            ->weeklyOn(0, '03:00')
            ->name('weekly_backup');

        // Example 5: Schedule with conditional execution
        $schedule->command('sync:external-blogs')
            ->everyFiveMinutes()
            ->when(function () {
                return \Cache::get('sync_enabled', true);
            })
            ->name('sync_external_blogs');

        // Example 6: Schedule with environment check
        $schedule->command('optimize:clear')
            ->daily()
            ->onlyOnProduction()
            ->name('clear_cache_production');

        // Example 7: Schedule with multiple conditions
        $schedule->call(function () {
            \App\Services\EmailService::sendWeeklyReport();
        })->weeklyOn(1, '09:00') // Monday at 9 AM
            ->timezone('UTC')
            ->name('weekly_email_report')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
