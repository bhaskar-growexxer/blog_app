<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\LoggerInterface;
use App\Services\Loggers\FileLogger;
use App\Services\Loggers\DatabaseLogger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Contextual binding: when BlogController needs LoggerInterface, give DatabaseLogger
        $this->app->when(\App\Http\Controllers\BlogController::class)
            ->needs(LoggerInterface::class)
            ->give(DatabaseLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
