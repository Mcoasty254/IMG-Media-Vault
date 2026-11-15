<?php

/**
 * Server-side Supabase REST + Storage client.
 * Uses the service role key (never send this to the browser).
 */
class Supabase
{
    private string $url;
    private string $serviceKey;
    private string $bucket;
    private string $mediaBucket;

    public function __construct()
    {
        $this->url         = rtrim((string) env('SUPABASE_URL'), '/');
        $this->serviceKey  = (string) env('SUPABASE_SERVICE_ROLE_KEY');
        $this->bucket      = (string) env('SUPABASE_STORAGE_BUCKET', 'avatars');
        $this->mediaBucket = (string) env('SUPABASE_MEDIA_BUCKET', 'media');

        if ($this->url === '' || $this->serviceKey === '') {
            throw new RuntimeException(
                'Supabase is not configured. Copy .env.example to .env and add your project URL and service role key.'
            );
        }
    }

    public function get(string $table, string $query): array
    {
        $res = $this->request('GET', '/rest/v1/' . $table . '?' . $query);
        return is_array($res) ? $res : [];
    }

    public function getOne(string $table, string $query): ?array
    {
        $rows = $this->get($table, $query);
        return $rows[0] ?? null;
    }

    public function insert(string $table, array $data): array
    {
        $res = $this->request('POST', '/rest/v1/' . $table, $data, [
            'Prefer: return=representation',
        ]);
        if (isset($res[0]) && is_array($res[0])) {
            return $res[0];
        }
        return is_array($res) ? $res : [];
    }

    public function update(string $table, string $query, array $data): array
    {
        $res = $this->request('PATCH', '/rest/v1/' . $table . '?' . $query, $data, [
            'Prefer: return=representation',
        ]);
        return is_array($res) ? $res : [];
    }

    public function delete(string $table, string $query): void
    {
        $this->request('DELETE', '/rest/v1/' . $table . '?' . $query);
    }

    public function uploadPublic(string $objectPath, string $binary, string $mime, ?string $bucket = null): string
    {
        $bucket  = $bucket ?: $this->bucket;
        $encoded = $this->encodeStoragePath($objectPath);

        $this->request(
            'POST',
            '/storage/v1/object/' . rawurlencode($bucket) . '/' . $encoded,
            $binary,
            [
                'Content-Type: ' . $mime,
                'x-upsert: true',
            ],
            true
        );

        return $this->url . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . $encoded;
    }

    public function removeObject(string $objectPath, ?string $bucket = null): void
    {
        $this->request('DELETE', '/storage/v1/object/' . rawurlencode($bucket ?: $this->bucket), [
            'prefixes' => [$objectPath],
        ]);
    }

    public function mediaBucket(): string
    {
        return $this->mediaBucket;
    }

    public function bucketName(): string
    {
        return $this->bucket;
    }

    public function defaultAvatar(): string
    {
        return 'assets/default-avatar.svg';
    }

    private function encodeStoragePath(string $path): string
    {
        $parts = array_filter(explode('/', $path), static fn ($p) => $p !== '');
        return implode('/', array_map('rawurlencode', $parts));
    }

    /**
     * @param array<int, string> $extraHeaders
     * @return mixed
     */
    private function request(
        string $method,
        string $path,
        mixed $body = null,
        array $extraHeaders = [],
        bool $rawBody = false
    ): mixed {
        $url = $this->url . $path;
        $headers = array_merge([
            'apikey: ' . $this->serviceKey,
            'Authorization: Bearer ' . $this->serviceKey,
        ], $extraHeaders);

        if ($body !== null && !$rawBody) {
            $headers[] = 'Content-Type: application/json';
            $payload = json_encode($body, JSON_UNESCAPED_SLASHES);
        } elseif ($rawBody) {
            $payload = $body;
        } else {
            $payload = null;
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required to talk to Supabase.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 60,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Supabase request failed: ' . $error);
        }

        if ($status >= 400) {
            $decoded = json_decode((string) $response, true);
            $message = is_array($decoded)
                ? ($decoded['message'] ?? $decoded['error_description'] ?? $decoded['error'] ?? $response)
                : $response;
            throw new RuntimeException('Supabase error (' . $status . '): ' . $message);
        }

        if ($response === '') {
            return [];
        }

        return json_decode((string) $response, true);
    }
}

function supabase(): Supabase
{
    static $client = null;
    if ($client === null) {
        $client = new Supabase();
    }
    return $client;
}