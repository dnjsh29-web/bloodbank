<?php

namespace App\Http\Middleware;

use App\Services\SupabaseRest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifySupabaseToken
{
    public function __construct(private readonly SupabaseRest $supabase)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Missing bearer token.'], 401);
        }

        try {
            $user = $this->supabase->user($token);
            $profile = $this->supabase->profileForUser($token, (string) ($user['id'] ?? ''));
        } catch (Throwable $error) {
            Log::warning('Supabase token validation failed.', ['message' => $error->getMessage()]);
            return response()->json(['message' => 'Invalid bearer token.'], 401);
        }

        $request->attributes->set('supabase_token', $token);
        $request->attributes->set('supabase_user', $user);
        $request->attributes->set('profile', $profile);
        $request->attributes->set('role', $profile['role'] ?? 'donor');

        return $next($request);
    }
}
