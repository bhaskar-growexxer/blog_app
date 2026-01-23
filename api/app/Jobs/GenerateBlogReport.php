<?php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Blog;
use Illuminate\Support\Facades\Log;

class GenerateBlogReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function handle()
    {
        try {
            Log::info('Generating blog report...');

            $totalBlogs = Blog::count();
            $blogsByCategory = Blog::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get();

            $report = [
                'total_blogs' => $totalBlogs,
                'categories' => $blogsByCategory,
                'generated_at' => now(),
            ];

            // Store report
            \Cache::put('blog_report', $report, 7 * 24 * 60); // Cache for 7 days

            Log::info('Blog report generated and cached', $report);
        } catch (\Exception $e) {
            Log::error('Failed to generate blog report', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::critical('Blog report generation job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}