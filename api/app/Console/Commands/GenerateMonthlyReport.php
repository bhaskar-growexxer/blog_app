<?php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Blog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class GenerateMonthlyReport extends Command
{
    protected $signature = 'report:monthly';
    
    protected $description = 'Generate and send monthly blog statistics report';

    public function handle()
    {
        try {
            $this->info('Generating monthly report...');

            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            $blogsCreated = Blog::whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->count();

            $categoryStats = Blog::whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->groupBy('category')
                ->selectRaw('category, COUNT(*) as count')
                ->get();

            $reportData = [
                'month' => Carbon::now()->format('F Y'),
                'total_blogs' => $blogsCreated,
                'categories' => $categoryStats,
                'date' => now(),
            ];

            // Log report
            \Log::info('Monthly Report Generated', $reportData);

            $this->info('Report generated successfully!');
            $this->table(['Category', 'Count'], $categoryStats->map(fn($s) => [$s->category, $s->count])->toArray());

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Report generation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}