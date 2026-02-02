<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LoggersDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:loggers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate tagged bindings and instance/singleton resolution';

    public function handle()
    {
        $loggers = $this->laravel->tagged('loggers');

        foreach ($loggers as $logger) {
            $logger->log('Demo log from demo:loggers command');
        }

        $apiVersion = $this->laravel->make('api.version');
        $idGen = $this->laravel->make(\App\Services\IdGenerator::class);

        $this->info('Logged via ' . count($loggers) . ' loggers.');
        $this->info('API version: ' . $apiVersion);
        $this->info('Generated id sample: ' . $idGen->generate());

        return 0;
    }
}
