<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Blog;
use App\Services\EmailService;
use App\Services\ScheduledTaskMonitor;
use Carbon\Carbon;

class SendDailyNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan send:daily-notifications
     */
    protected $signature = 'send:daily-notifications';

    /**
     * The console command description.
     */
    protected $description = 'Send daily email notifications for newly created blog posts';

    /**
     * Execute the console command.
     */
    public function handle(EmailService $emailService)
    {
        try {
            $this->info('Starting daily notification process...');

            // Get today’s date
            $today = Carbon::today();

            // Retrieve blogs created today
            $blogs = Blog::whereDate('created_at', $today)->get();

            if ($blogs->isEmpty()) {
                $this->info('No new blog posts today.');
                ScheduledTaskMonitor::recordExecution('send:daily-notifications', true, 'No new blogs');
                return Command::SUCCESS;
            }

            $sent = 0;
            // Loop through blogs and send email notifications
            foreach ($blogs as $blog) {
                $emailService->sendNewBlogNotification($blog);
                $sent++;
            }

            $this->info("Sent notifications for {$sent} blog(s)");
            ScheduledTaskMonitor::recordExecution('send:daily-notifications', true, "Sent {$sent} notifications");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send notifications: ' . $e->getMessage());
            ScheduledTaskMonitor::recordExecution('send:daily-notifications', false, $e->getMessage());

            return Command::FAILURE;
        }
    }
}
