<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseRest
{
    public function publicSelect(string $table, array $query = []): array
    {
        return $this->select(null, $table, $query);
    }

    public function user(string $token): array
    {
        return $this->client($token)
            ->get($this->url('/auth/v1/user'))
            ->throw()
            ->json();
    }

    public function select(?string $token, string $table, array $query = []): array
    {
        $response = $this->client($token)
            ->get($this->url("/rest/v1/{$table}"), ['select' => '*', ...$query]);

        if ($response->failed()) {
            throw new RuntimeException($response->body());
        }

        return $response->json() ?? [];
    }

    public function insert(string $token, string $table, array $row): array
    {
        $response = $this->client($token)
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post($this->url("/rest/v1/{$table}"), $row);

        if ($response->failed()) {
            throw new RuntimeException($response->body());
        }

        return $response->json() ?? [];
    }

    public function update(string $token, string $table, array $query, array $row): array
    {
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $response = $this->client($token)
            ->withHeaders(['Prefer' => 'return=representation'])
            ->patch($this->url("/rest/v1/{$table}").($queryString ? "?{$queryString}" : ''), $row);

        if ($response->failed()) {
            throw new RuntimeException($response->body());
        }

        return $response->json() ?? [];
    }

    public function upsert(string $token, string $table, array $rows, string $onConflict): array
    {
        if ($rows === []) {
            return [];
        }

        $response = $this->client($token)
            ->withHeaders([
                'Prefer' => 'resolution=merge-duplicates,return=representation',
            ])
            ->post($this->url("/rest/v1/{$table}")."?on_conflict={$onConflict}", $rows);

        if ($response->failed()) {
            throw new RuntimeException($response->body());
        }

        return $response->json() ?? [];
    }

    public function uploadObject(string $token, string $bucket, string $path, string $contents, ?string $contentType = null): array
    {
        $safeBucket = rawurlencode($bucket);
        $safePath = collect(explode('/', trim($path, '/')))
            ->filter(fn (string $segment) => $segment !== '')
            ->map(fn (string $segment) => rawurlencode($segment))
            ->implode('/');

        $response = $this->client($token)
            ->withHeaders(['x-upsert' => 'false'])
            ->withBody($contents, $contentType ?: 'application/octet-stream')
            ->post($this->url("/storage/v1/object/{$safeBucket}/{$safePath}"));

        if ($response->failed()) {
            throw new RuntimeException($response->body());
        }

        return $response->json() ?? [];
    }

    public function profileForUser(string $token, string $userId): ?array
    {
        $rows = $this->select($token, 'profiles', ['id' => "eq.{$userId}", 'limit' => 1]);

        return $rows[0] ?? null;
    }

    public function safeSelect(string $token, string $table, array $query = []): array
    {
        try {
            return $this->select($token, $table, $query);
        } catch (RuntimeException) {
            return [];
        }
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

    private function url(string $path): string
    {
        return rtrim((string) config('services.supabase.url'), '/').$path;
    }
}
