<?php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScheduledTaskMonitor
{
    /**
     * Record task execution
     */
    public static function recordExecution(string $taskName, bool $success, ?string $message = null)
    {
        $execution = [
            'name' => $taskName,
            'success' => $success,
            'message' => $message,
            'executed_at' => now(),
        ];

        // Store in cache for recent executions
        $executions = Cache::get('scheduled_task_executions', []);
        array_unshift($executions, $execution);
        $executions = array_slice($executions, 0, 100); // Keep last 100 executions
        
        Cache::put('scheduled_task_executions', $executions);

        // Log execution
        $level = $success ? 'info' : 'error';
        Log::$level("Scheduled task: {$taskName}", $execution);
    }

    /**
     * Get task execution history
     */
    public static function getExecutionHistory(string $taskName = null)
    {
        $executions = Cache::get('scheduled_task_executions', []);

        if ($taskName) {
            $executions = array_filter($executions, fn($e) => $e['name'] === $taskName);
        }

        return $executions;
    }

    /**
     * Get failed tasks
     */
    public static function getFailedTasks()
    {
        $executions = Cache::get('scheduled_task_executions', []);
        return array_filter($executions, fn($e) => !$e['success']);
    }
}