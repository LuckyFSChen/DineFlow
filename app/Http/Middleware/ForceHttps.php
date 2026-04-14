<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $forwardedProto = strtolower((string) $request->headers->get('x-forwarded-proto', ''));
            $isForwardedHttps = $forwardedProto === 'https';

            if (! $request->secure() && ! $isForwardedHttps) {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        return $next($request);
    }
}
