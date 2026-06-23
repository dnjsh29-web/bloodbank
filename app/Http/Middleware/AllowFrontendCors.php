<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowFrontendCors
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204)->withHeaders($this->headers());
        }

        $response = $next($request);

        foreach ($this->headers() as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $origin = request()->headers->get('Origin', '');
        $allowed = array_filter(array_map('trim', explode(',', env('FRONTEND_ORIGINS', env('FRONTEND_URL', 'http://localhost:3000')))));
        $allowed[] = 'http://localhost:3000';
        $allowed[] = 'http://localhost:3002';
        $allowed[] = 'http://127.0.0.1:3000';
        $allowed[] = 'http://127.0.0.1:3002';
        $allowOrigin = in_array($origin, $allowed, true) ? $origin : ($allowed[0] ?? 'http://localhost:3000');

        return [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
        ];
    }
}
