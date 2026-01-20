# Laravel Telescope - Setup & Understanding

## Table of Contents
1. [What is Telescope?](#what-is-telescope)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Watchers Explained](#watchers-explained)
5. [Security & Production](#security--production)
6. [Usage Guide](#usage-guide)
7. [Performance Optimization](#performance-optimization)
8. [Troubleshooting](#troubleshooting)

---

## What is Telescope?

Laravel Telescope is an elegant debug assistant for Laravel applications. It provides insight into:
- Requests entering your application
- Exceptions
- Database queries
- Queued jobs
- Mail
- Notifications
- Cache operations
- Scheduled tasks
- Variable dumps

**Best for:** Development and staging environments for debugging and performance monitoring.

---

## Installation

### Step 1: Install via Composer

```bash
composer require laravel/telescope
```

### Step 2: Publish Assets & Configuration

```bash
php artisan telescope:install
```

This command:
- Creates migrations in `database/migrations/`
- Publishes `config/telescope.php`
- Creates `TelescopeServiceProvider.php` in `app/Providers/`
- Publishes public assets

### Step 3: Run Migrations

```bash
php artisan migrate
```

Creates tables:
- `telescope_entries`
- `telescope_entries_tags`
- `telescope_monitoring`

### Step 4: Register Service Provider (Auto-registered in Laravel 10)

Check `config/app.php` or `bootstrap/providers.php`:
```php
App\Providers\TelescopeServiceProvider::class,
```

---

## Configuration

### Environment Variables

Add to `.env`:

```env
TELESCOPE_ENABLED=true
TELESCOPE_DRIVER=database

# Watcher Controls
TELESCOPE_CACHE_WATCHER=true
TELESCOPE_COMMAND_WATCHER=true
TELESCOPE_DUMP_WATCHER=true
TELESCOPE_EVENT_WATCHER=true
TELESCOPE_EXCEPTION_WATCHER=true
TELESCOPE_GATE_WATCHER=true
TELESCOPE_JOB_WATCHER=true
TELESCOPE_LOG_WATCHER=true
TELESCOPE_MAIL_WATCHER=true
TELESCOPE_MODEL_WATCHER=true
TELESCOPE_NOTIFICATION_WATCHER=true
TELESCOPE_QUERY_WATCHER=true
TELESCOPE_REDIS_WATCHER=true
TELESCOPE_REQUEST_WATCHER=true
TELESCOPE_SCHEDULE_WATCHER=true
```

### Main Configuration File (`config/telescope.php`)

```php
return [
    // Storage driver: 'database' or null for in-memory
    'driver' => env('TELESCOPE_DRIVER', 'database'),

    // Storage options
    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'chunk' => 1000,
        ],
    ],

    // Telescope domain (useful for subdomains)
    'domain' => env('TELESCOPE_DOMAIN', null),

    // Path where Telescope will be accessible
    'path' => env('TELESCOPE_PATH', 'telescope'),

    // Enable/disable Telescope
    'enabled' => env('TELESCOPE_ENABLED', true),

    // Middleware
    'middleware' => [
        'web',
        Authorize::class,
    ],

    // Data retention (hours)
    'queue' => [
        'connection' => env('TELESCOPE_QUEUE_CONNECTION', null),
        'queue' => env('TELESCOPE_QUEUE', null),
    ],
];
```

---

## Watchers Explained

### 1. **Request Watcher**
Monitors all HTTP requests to your application.

**Captures:**
- URL, method, status code
- Headers, cookies
- Request payload
- Response content
- Session data

**Use case:** Debug API endpoints, track user requests

### 2. **Query Watcher**
Records all database queries.

**Captures:**
- SQL queries
- Bindings
- Execution time
- Connection name

**Use case:** Identify N+1 queries, slow queries, optimize database performance

### 3. **Exception Watcher**
Logs all exceptions thrown.

**Captures:**
- Exception class
- Message
- Stack trace
- File and line number

**Use case:** Track errors in production, debug issues

### 4. **Job Watcher**
Monitors queued jobs.

**Captures:**
- Job name
- Payload
- Status (pending, processed, failed)
- Attempts
- Exception (if failed)

**Use case:** Debug background jobs, monitor queue health

### 5. **Log Watcher**
Records log entries.

**Captures:**
- Log level
- Message
- Context

**Use case:** Centralized logging view

### 6. **Mail Watcher**
Tracks emails sent by your application.

**Captures:**
- Recipients
- Subject
- Mailable class
- Preview of content

**Use case:** Debug email functionality

### 7. **Model Watcher**
Monitors Eloquent model events.

**Captures:**
- Model created/updated/deleted
- Attributes changed
- Relationships loaded

**Use case:** Track data changes, debug model events

### 8. **Cache Watcher**
Records cache operations.

**Captures:**
- Cache hits/misses
- Keys accessed
- Values stored
- TTL

**Use case:** Optimize caching strategy

### 9. **Event Watcher**
Logs events dispatched.

**Captures:**
- Event class
- Listeners
- Payload

**Use case:** Debug event-driven features

### 10. **Command Watcher**
Records Artisan commands executed.

**Captures:**
- Command name
- Arguments
- Options
- Exit code

**Use case:** Monitor scheduled tasks, debug CLI commands

---

## Security & Production

### TelescopeServiceProvider Configuration

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        // Apply dark mode theme
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        // Filter what gets recorded
        Telescope::filter(function (IncomingEntry $entry) {
            // In local: record everything
            if ($this->app->environment('local')) {
                return true;
            }

            // In production: only record important events
            return $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        // Hide sensitive parameters
        Telescope::hideRequestParameters([
            '_token',
            'password',
            'password_confirmation',
            'api_key',
            'secret',
        ]);

        // Hide sensitive headers
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
        ]);
    }

    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            // Option 1: Check specific emails
            return in_array($user->email, [
                'admin@example.com',
            ]);

            // Option 2: Check user role
            // return $user->isAdmin();

            // Option 3: Check user ID
            // return in_array($user->id, [1, 2, 3]);
        });
    }
}
```

### Production Best Practices

1. **Disable in Production** (Recommended)
```env
TELESCOPE_ENABLED=false
```

2. **Or Limit Recording**
```php
// Only record exceptions and failed jobs
Telescope::filter(function (IncomingEntry $entry) {
    return $entry->isReportableException() || 
           $entry->isFailedJob();
});
```

3. **Use Authentication**
Always protect the Telescope route with authentication.

4. **Regular Pruning**
Schedule regular data cleanup:
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('telescope:prune --hours=48')->daily();
}
```

---

## Usage Guide

### Accessing Telescope

Visit: `http://your-domain.test/telescope`

### Dashboard Sections

#### Requests Tab
- View all HTTP requests
- Filter by status code, method
- Click to see full details: headers, payload, response

#### Commands Tab
- See Artisan commands executed
- View output and exit codes

#### Schedule Tab
- Monitor scheduled tasks
- See execution time and status

#### Jobs Tab
- Track queued jobs
- See pending, processed, and failed jobs
- Retry failed jobs

#### Exceptions Tab
- List all exceptions
- View stack traces
- Filter by exception type

#### Logs Tab
- Centralized log viewer
- Filter by level (error, warning, info)

#### Queries Tab
- Database query log
- Execution time
- Identify slow queries
- Detect N+1 problems

#### Models Tab
- Track model changes
- See created/updated/deleted records

#### Mail Tab
- Preview sent emails
- See recipients and subject

#### Cache Tab
- Monitor cache hits/misses
- View cached keys

### Filtering & Searching

- **Tag filtering:** Click tags to filter entries
- **Date range:** Use date picker
- **Search:** Type in search box for specific entries

### Monitoring Specific Code

Add tags to monitor specific features:

```php
use Laravel\Telescope\Telescope;

Telescope::tag(function () {
    return ['user:' . auth()->id()];
});

// Or tag specific code
Telescope::recordQuery(function ($query) {
    if ($query->time > 100) {
        return ['slow-query'];
    }
});
```

---

## Performance Optimization

### 1. Limit Data Retention

```bash
php artisan telescope:prune --hours=48
```

### 2. Disable Unnecessary Watchers

In `config/telescope.php`:

```php
'watchers' => [
    Watchers\CacheWatcher::class => false, // Disable if not needed
    Watchers\DumpWatcher::class => env('APP_DEBUG', false),
    Watchers\QueryWatcher::class => [
        'enabled' => true,
        'slow' => 100, // Only record queries slower than 100ms
    ],
],
```

### 3. Use Queue for Processing

```php
'queue' => [
    'connection' => 'redis',
    'queue' => 'telescope',
],
```

### 4. Ignore Specific Paths

```php
Telescope::filter(function (IncomingEntry $entry) {
    if ($entry->type === 'request') {
        return !in_array($entry->content['uri'], [
            'health-check',
            'metrics',
        ]);
    }

    return true;
});
```

---

## Troubleshooting

### Telescope Not Showing Data

**Check:**
1. Is Telescope enabled? `TELESCOPE_ENABLED=true`
2. Are migrations run? `php artisan migrate`
3. Check database connection
4. Clear cache: `php artisan config:clear`

### 404 on /telescope Route

**Solution:**
```bash
php artisan telescope:install
php artisan route:clear
```

### Performance Issues

**Solutions:**
1. Prune old data regularly
2. Disable unused watchers
3. Use queue processing
4. Only enable in local environment

### Access Denied

**Check:**
1. `viewTelescope` gate in `TelescopeServiceProvider`
2. User authentication
3. Middleware configuration

### Too Much Data

**Solution:**
```bash
# Clear all Telescope data
php artisan telescope:clear

# Prune old entries
php artisan telescope:prune --hours=24
```

---

## Useful Commands

```bash
# Install Telescope
php artisan telescope:install

# Publish config
php artisan vendor:publish --tag=telescope-config

# Publish migrations
php artisan vendor:publish --tag=telescope-migrations

# Prune old entries (older than 24 hours)
php artisan telescope:prune --hours=24

# Clear all Telescope data
php artisan telescope:clear

# Pause recording
php artisan telescope:pause

# Resume recording
php artisan telescope:resume
```

---

## REST API Specific Tips

### 1. Monitor API Performance

Focus on:
- Request watcher for endpoint monitoring
- Query watcher for database optimization
- Exception watcher for API errors

### 2. Track API Response Times

```php
Telescope::recordRequest(function ($request) {
    if ($request->time > 1000) { // Slower than 1 second
        return ['slow-api'];
    }
});
```

### 3. Debug Authentication Issues

Monitor:
- Request headers (Authorization tokens)
- Session data
- Cache for rate limiting

### 4. Optimize Database Queries

Use Query watcher to:
- Find N+1 queries
- Identify missing indexes
- Optimize eager loading

---

## Additional Resources

- [Official Documentation](https://laravel.com/docs/10.x/telescope)
- [GitHub Repository](https://github.com/laravel/telescope)
- [Laracasts Telescope Series](https://laracasts.com)

---

**Last Updated:** January 2026  
**Telescope Version:** 5.x