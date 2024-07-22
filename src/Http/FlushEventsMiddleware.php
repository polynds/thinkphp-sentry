<?php

namespace Polynds\ThinkphpSentry\Http;

use Closure;
use Illuminate\Http\Request;
use Polynds\ThinkphpSentry\Integration;

class FlushEventsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        Integration::flushEvents();
    }
}
