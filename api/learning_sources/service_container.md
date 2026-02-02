# Laravel Service Container — Practical Guide

This short guide teaches advanced service container usage and dependency injection patterns in Laravel. It covers:

- Fundamentals of the service container and IoC
- Binding types: `singleton`, `instance`, `bind` (transient), contextual, and tagged bindings
- Creating and registering service providers
- Automatic dependency resolution, constructor and method injection
- Using contracts (interfaces) for loose coupling and testability

Each example references simple classes included in this repository (e.g., `App\Contracts\LoggerInterface`, `App\Services\Loggers\FileLogger`, `App\Services\Loggers\DatabaseLogger`, `App\Services\IdGenerator`, `App\Providers\LoggingServiceProvider`).

---

## 1 — Fundamentals & IoC

The service container is Laravel's IoC (Inversion of Control) container. It resolves class dependencies and performs dependency injection automatically.

When a class requests another class in its constructor, the container will try to instantiate the dependency graph for you:

```php
public function __construct(App\Contracts\LoggerInterface $logger)
{
    $this->logger = $logger;
}
```

If the container knows how to resolve `LoggerInterface` it will inject an implementation.

---

## 2 — Binding Types

- `bind`: transient binding — container returns a new instance each time.
- `singleton`: shared binding — container returns the same instance each time.
- `instance`: bind an already-built value or object.
- contextual binding: give specific implementations for specific consumers.
- tagged bindings: group multiple implementations under a tag and resolve them as an array.

Examples (see `App\Providers\LoggingServiceProvider`):

```php
// singleton
$this->app->singleton(App\Services\IdGenerator::class, fn() => new \App\Services\IdGenerator());

// instance
$this->app->instance('api.version', '1.0');

// transient / default binding
$this->app->bind(App\Contracts\LoggerInterface::class, App\Services\Loggers\FileLogger::class);

// tagged
$this->app->tag([
    App\Services\Loggers\FileLogger::class,
    App\Services\Loggers\DatabaseLogger::class,
], 'loggers');

// contextual: when BlogController requires LoggerInterface, give DatabaseLogger
$this->app->when(App\Http\Controllers\BlogController::class)
    ->needs(App\Contracts\LoggerInterface::class)
    ->give(App\Services\Loggers\DatabaseLogger::class);
```

---

## 3 — Service Providers

Service providers centralize binding and bootstrapping logic. Create a provider (e.g., `LoggingServiceProvider`) and register it in `config/app.php` providers array.

Providers should put binding logic in `register()` and runtime setup in `boot()`.

---

## 4 — Automatic resolution & Injection Patterns

- Constructor injection (preferred for required dependencies): the container will inject dependencies automatically when resolving the class.
- Method injection: you can type-hint dependencies in controller action methods and Laravel will inject them.

Example method injection:

```php
public function store(Request $request, App\Contracts\LoggerInterface $logger)
{
    $logger->log('Created blog');
}
```

Constructor injection example (used in this repo's `BlogController`):

```php
public function __construct(App\Contracts\LoggerInterface $logger)
{
    $this->logger = $logger;
}
```

---

## 5 — Contracts for loose coupling

Always depend on interfaces (contracts) rather than concrete classes. This makes swapping implementations and writing tests much easier.

```php
// Controller depends on a contract
public function __construct(App\Contracts\LoggerInterface $logger) { ... }

// Tests can swap implementation easily
$this->app->bind(App\Contracts\LoggerInterface::class, function () {
    return new \Tests\Doubles\InMemoryLogger();
});
```

---

## 6 — Tagged bindings & iterating implementations

Tagging allows resolving many implementations at once. Example: send an event to multiple notifiers.

```php
$notifiers = $this->app->tagged('notifiers');
foreach ($notifiers as $notifier) {
    $notifier->notify($payload);
}
```

In this repo we tag `FileLogger` and `DatabaseLogger` under `loggers`. The command `demo:loggers` demonstrates running each tagged logger.

---

## 7 — Examples & Commands (how to try)

- Run the artisan demo command that resolves tagged loggers and shows singleton/instance values:

```bash
php artisan demo:loggers
```

- Run unit tests for service container demonstrations:

```bash
./vendor/bin/phpunit --filter ServiceContainerTest
```

---

## 8 — Suggested exercises

1. Add a new `CacheLogger` implementation and tag it into `loggers`.
2. Replace `LoggerInterface` binding in a test with a fake logger and assert side effects.
3. Create a `Notifier` contract and register two implementations, then use tagged bindings to notify both.

---

If you'd like, I can also:

- run the `ServiceContainerTest` and `BlogController` tests now,
- add a `CacheLogger` example implementation, or
- add an in-repo `Tests\Doubles\InMemoryLogger` for easier testing.
