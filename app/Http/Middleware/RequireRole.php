<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = (string) $request->session()->get('profile.role', 'donor');

        abort_unless(in_array($role, $roles, true), 403);

        return $next($request);
    }
}
