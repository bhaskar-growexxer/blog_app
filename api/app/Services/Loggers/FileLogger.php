<?php

namespace App\Services\Loggers;

use App\Contracts\LoggerInterface;
use Illuminate\Support\Facades\Log;

class FileLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        Log::info($message);
    }
}
