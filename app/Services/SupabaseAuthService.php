<?php

namespace App\Services;

use App\Exceptions\SupabaseEmailRateLimitException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseAuthService
{
    public function signIn(string $email, string $password): array
    {
        $response = $this->client()
            ->post($this->url('/auth/v1/token?grant_type=password'), [
                'email' => $email,
                'password' => $password,
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->message($response->json()) ?: 'Invalid login credentials.');
        }

        return $response->json();
    }

    public function signUp(string $email, string $password, array $metadata = [], ?string $redirectTo = null): array
    {
        $response = $this->client()
            ->post($this->url('/auth/v1/signup', $redirectTo), [
                'email' => $email,
                'password' => $password,
                'data' => $metadata,
            ]);

        if ($response->failed()) {
            $this->throwForFailedResponse($response, 'Registration failed.');
        }

        return $response->json();
    }

    public function resendSignupConfirmation(string $email, ?string $redirectTo = null): void
    {
        $response = $this->client()
            ->post($this->url('/auth/v1/resend', $redirectTo), [
                'email' => $email,
                'type' => 'signup',
            ]);

        if ($response->failed()) {
            $this->throwForFailedResponse($response, 'Confirmation email could not be resent.');
        }
    }

    public function recover(string $email, ?string $redirectTo = null): void
    {
        $response = $this->client()
            ->post($this->url('/auth/v1/recover', $redirectTo), [
                'email' => $email,
            ]);

        if ($response->failed()) {
            $this->throwForFailedResponse($response, 'Password reset request failed.');
        }
    }

    public function updatePassword(string $token, string $password): array
    {
        $response = $this->client($token)
            ->put($this->url('/auth/v1/user'), [
                'password' => $password,
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->message($response->json()) ?: 'Password could not be updated.');
        }

        return $response->json();
    }

    public function logout(string $token): void
    {
        $this->client($token)->post($this->url('/auth/v1/logout'));
    }

    private function client(?string $token = null): PendingRequest
    {
        $apiKey = config('services.supabase.anon_key');

        if (! config('services.supabase.url') || ! $apiKey) {
            throw new RuntimeException('Supabase URL or publishable/anon key is not configured.');
        }

        $headers = ['apikey' => $apiKey];

        if ($token !== null && trim($token) !== '') {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return Http::acceptJson()
            ->withOptions(['verify' => (bool) config('services.supabase.verify_ssl')])
            ->withHeaders($headers);
    }

    private function url(string $path, ?string $redirectTo = null): string
    {
        $url = rtrim((string) config('services.supabase.url'), '/').$path;

        if ($redirectTo === null || trim($redirectTo) === '') {
            return $url;
        }

        return $url.'?'.http_build_query(['redirect_to' => $redirectTo], '', '&', PHP_QUERY_RFC3986);
    }

    private function message(mixed $payload): ?string
    {
        return is_array($payload) ? ($payload['msg'] ?? $payload['message'] ?? $payload['error_description'] ?? null) : null;
    }

    private function throwForFailedResponse(Response $response, string $fallback): never
    {
        if ($response->status() === 429) {
            throw new SupabaseEmailRateLimitException('Email delivery is temporarily limited.');
        }

        throw new RuntimeException($this->message($response->json()) ?: $fallback);
    }
}
