<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Blog;
use App\Services\EmailService;
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
        // Get today’s date
        $today = Carbon::today();

        // Retrieve blogs created today
        $blogs = Blog::whereDate('created_at', $today)->get();

        if ($blogs->isEmpty()) {
            $this->info('No new blog posts today.');
            return 0;
        }

        // Loop through blogs and send email notifications
        foreach ($blogs as $blog) {
            $emailService->sendNewBlogNotification($blog);
            $this->info("Notification sent for blog: {$blog->title}");
        }

        $this->info('Daily notifications sent successfully!');
        return 0;
    }
}
