<?php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ScheduledTaskMonitor;

class MonitorScheduledTasks extends Command
{
    protected $signature = 'schedule:monitor {--failed : Show only failed tasks}';
    
    protected $description = 'Monitor scheduled task executions';

    public function handle()
    {
        if ($this->option('failed')) {
            $tasks = ScheduledTaskMonitor::getFailedTasks();
            $this->error('Failed Scheduled Tasks:');
        } else {
            $tasks = ScheduledTaskMonitor::getExecutionHistory();
            $this->info('Recent Scheduled Task Executions:');
        }

        if (empty($tasks)) {
            $this->info('No tasks found');
            return Command::SUCCESS;
        }

        $rows = array_map(function ($task) {
            return [
                $task['name'],
                $task['success'] ? '✓' : '✗',
                $task['executed_at'],
                $task['message'] ?? 'N/A',
            ];
        }, $tasks);

        $this->table(['Task Name', 'Status', 'Executed At', 'Message'], $rows);

        return Command::SUCCESS;
    }
}