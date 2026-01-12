<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityLogger
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        $user = $request->user();
        $meta = [
            'user_id' => $user ? $user->id : null,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'duration_ms' => $duration,
            'status' => $response->getStatusCode(),
        ];

        // Keep payload small to avoid huge logs - remove large fields
        $payload = $request->except(['password', 'password_confirmation', 'file', 'files']);
        $meta['payload'] = Str::limit(json_encode($payload), 1000);

        Log::info('api.activity', $meta);

        return $response;
    }
}