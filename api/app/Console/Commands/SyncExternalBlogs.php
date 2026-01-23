<?php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Blog;
use Illuminate\Support\Facades\Log;

class SyncExternalBlogs extends Command
{
    protected $signature = 'sync:external-blogs {--force : Force sync regardless of schedule}';
    
    protected $description = 'Sync blogs from external sources';

    public function handle()
    {
        try {
            $this->info('Starting external blogs sync...');
            
            // Simulate API call to external source
            $externalBlogs = $this->fetchExternalBlogs();
            
            $synced = 0;
            foreach ($externalBlogs as $blog) {
                Blog::updateOrCreate(
                    ['external_id' => $blog['id']],
                    [
                        'title' => $blog['title'],
                        'description' => $blog['description'],
                        'category' => $blog['category'],
                        'author' => $blog['author'],
                    ]
                );
                $synced++;
            }

            $this->info("Synced {$synced} blogs successfully");
            Log::info("External blogs synced: {$synced} blogs");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('External blogs sync failed', ['error' => $e->getMessage()]);
            
            return Command::FAILURE;
        }
    }

    private function fetchExternalBlogs()
    {
        // Mock implementation - replace with actual API call
        return [
            ['id' => 1, 'title' => 'External Blog 1', 'description' => 'Desc', 'category' => 'Tech', 'author' => 'Author 1'],
        ];
    }
}