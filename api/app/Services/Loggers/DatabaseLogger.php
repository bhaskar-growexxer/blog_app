<?php

namespace App\Services\Loggers;

use App\Contracts\LoggerInterface;
use Illuminate\Support\Facades\DB;

class DatabaseLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        // lightweight example: write into a simple `logs` table if exists
        try {
            DB::table('logs')->insert([
                'message' => $message,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // fallback to file log if DB table doesn't exist
            logger()->error('DatabaseLogger failed: ' . $e->getMessage());
            logger()->info($message);
        }
    }
}
