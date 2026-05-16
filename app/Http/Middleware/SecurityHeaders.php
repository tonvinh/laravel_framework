<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            'X-Frame-Options'        => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'no-referrer',
            'X-XSS-Protection'       => '1; mode=block',
            'Permissions-Policy'     => 'geolocation=(), microphone=(), camera=()',
        ]);

        return $response;
    }
}
