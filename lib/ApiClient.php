<?php

declare(strict_types=1);

/**
 * Minimal signed HTTP client for the WebIT "call home" API.
 *
 * Every request is signed with HMAC-SHA256 over:
 *     "{timestamp}\n{METHOD}\n{path}\n{body}"
 * and carries X-Api-Key / X-Timestamp / X-Signature headers. Only outbound
 * HTTPS is required — the local database is never exposed.
 */
final class ApiClient
{
    public function __construct(
        private string $base,
        private string $apiKey,
        private string $secret
    ) {
    }

    /** @return array<string,mixed> */
    public function get(string $path): array
    {
        return $this->send('GET', $path, '');
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function post(string $path, array $payload): array
    {
        return $this->send('POST', $path, json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '');
    }

    /** @return array<string,mixed> */
    private function send(string $method, string $path, string $body): array
    {
        $timestamp = (string) time();
        $canonical = $timestamp . "\n" . $method . "\n" . $path . "\n" . $body;
        $signature = hash_hmac('sha256', $canonical, $this->secret);

        $ch = curl_init(rtrim($this->base, '/') . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Api-Key: ' . $this->apiKey,
                'X-Timestamp: ' . $timestamp,
                'X-Signature: ' . $signature,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            throw new RuntimeException('HTTP error: ' . curl_error($ch));
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            throw new RuntimeException("Bad response ({$status}): {$raw}");
        }
        if ($status >= 400 || ($data['ok'] ?? false) !== true) {
            throw new RuntimeException("API error ({$status}): " . ($data['error'] ?? 'unknown'));
        }
        return $data;
    }
}
