<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Loggers\FileLogger;
use App\Services\Loggers\DatabaseLogger;
use App\Contracts\LoggerInterface;
use App\Services\IdGenerator;

class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton binding example: shared IdGenerator
        $this->app->singleton(IdGenerator::class, function ($app) {
            return new IdGenerator();
        });

        // Instance binding example: bind a specific API version string
        $this->app->instance('api.version', '1.0');

        // Default logger bindings
        $this->app->bind(FileLogger::class, FileLogger::class);
        $this->app->bind(DatabaseLogger::class, DatabaseLogger::class);

        // Tagged bindings: group multiple logger implementations under 'loggers'
        $this->app->tag([FileLogger::class, DatabaseLogger::class], 'loggers');

        // Bind contract to default implementation
        $this->app->bind(LoggerInterface::class, FileLogger::class);
    }

    public function boot(): void
    {
        // nothing for now
    }
}
