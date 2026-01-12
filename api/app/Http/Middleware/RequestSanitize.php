<?php

namespace App\Http\Middleware;

use Closure;

class RequestSanitize
{
    protected function sanitize($data)
    {
        if (is_array($data)) {
            $clean = [];
            foreach ($data as $k => $v) {
                $clean[$k] = $this->sanitize($v);
            }
            return $clean;
        }

        if (is_string($data)) {
            $s = trim($data);
            $s = strip_tags($s);
            return $s;
        }

        return $data;
    }

    public function handle($request, Closure $next)
    {
        $sanitized = $this->sanitize($request->all());
        $request->merge($sanitized);
        return $next($request);
    }
}