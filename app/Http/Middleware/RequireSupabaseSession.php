<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSupabaseSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('supabase.access_token')) {
            return redirect()->route('login')->withErrors(['email' => 'Please sign in first.']);
        }

        return $next($request);
    }
}
