<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\IdGenerator;
use App\Contracts\LoggerInterface;
use App\Services\Loggers\FileLogger;
use App\Services\Loggers\DatabaseLogger;

class ServiceContainerTest extends TestCase
{
    /** @test */
    public function singleton_binding_returns_same_instance()
    {
        $a = $this->app->make(IdGenerator::class);
        $b = $this->app->make(IdGenerator::class);

        $this->assertSame($a, $b);
    }

    /** @test */
    public function instance_binding_resolves_value()
    {
        $version = $this->app->make('api.version');

        $this->assertEquals('1.0', $version);
    }

    /** @test */
    public function tagged_bindings_are_resolvable()
    {
        $loggers = $this->app->tagged('loggers');

        $this->assertIsArray($loggers);

        $classes = array_map(fn($l) => get_class($l), $loggers);

        $this->assertContains(FileLogger::class, $classes);
        $this->assertContains(DatabaseLogger::class, $classes);
    }

    /** @test */
    public function contextual_binding_for_blogcontroller_uses_database_logger()
    {
        $controller = $this->app->make(\App\Http\Controllers\BlogController::class);

        // Use reflection to access private logger property
        $ref = new \ReflectionClass($controller);
        $prop = $ref->getProperty('logger');
        $prop->setAccessible(true);
        $logger = $prop->getValue($controller);

        $this->assertInstanceOf(DatabaseLogger::class, $logger);
    }
}
