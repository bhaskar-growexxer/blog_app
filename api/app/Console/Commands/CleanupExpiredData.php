<?php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Blog;
use Carbon\Carbon;

class CleanupExpiredData extends Command
{
    protected $signature = 'cleanup:expired-data {--days=30 : Number of days to retain}';
    
    protected $description = 'Remove old and expired data from database';

    public function handle()
    {
        try {
            $days = $this->option('days');
            $this->info("Cleaning up data older than {$days} days...");

            // Delete old blogs
            $deletedBlogs = Blog::where('created_at', '<', Carbon::now()->subDays($days))
                ->where('is_archived', true)
                ->delete();

            $this->info("Deleted {$deletedBlogs} old blog records");

            // Clear old cache
            \Cache::flush();
            $this->info('Cache cleared');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}