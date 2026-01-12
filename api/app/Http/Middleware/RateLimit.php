<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response;

class RateLimit
{
    /**
     * Usage: ->middleware('rate_limit:60,60')  (maxRequests, decaySeconds)
     */
    public function handle($request, Closure $next, $max = 60, $decay = 60)
    {
        $max = (int) $max;
        $decay = (int) $decay;
        $identifier = $request->user() ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
        $key = "rl:{$identifier}:" . now()->format('YmdHi'); // short bucket, or could use finer granularity

        $hits = Cache::get($key, 0);
        $hits++;

        if ($hits > $max) {
            $retryAfter = Cache::get($key.':expires_at', now()->addSeconds($decay)->timestamp) - now()->timestamp;
            return response()->json([
                'message' => 'Too many requests.',
            ], Response::HTTP_TOO_MANY_REQUESTS)->header('Retry-After', $retryAfter);
        }

        // increment and set expiration
        Cache::put($key, $hits, $decay);
        Cache::put($key.':expires_at', now()->addSeconds($decay)->timestamp, $decay);

        return $next($request);
    }
}