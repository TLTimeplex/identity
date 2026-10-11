<?php

namespace App\Services\Hydra;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HydraClient
{
    private function request(): PendingRequest
    {
        $baseUrl = rtrim(
            (string) config('hydra.admin_url'),
            '/',
        );

        if ($baseUrl === '') {
            throw new RuntimeException(
                'The Hydra Admin API URL is not configured.',
            );
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(
                (int) config('hydra.connect_timeout', 2),
            )
            ->timeout(
                (int) config('hydra.timeout', 5),
            );
    }

    /**
     * Send a GET request to the Hydra Admin API.
     *
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->request()
            ->get($this->normalizePath($path), $query)
            ->throw();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function post(string $path, array $body = []): Response
    {
        return $this->request()
            ->post($this->normalizePath($path), $body)
            ->throw();
    }

    /**
     * Send a PUT request to the Hydra Admin API.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     */
    public function put(
        string $path,
        array $query = [],
        array $body = [],
    ): Response {
        return $this->request()
            ->put($this->normalizePath($path).'?'.http_build_query($query), $body)
            ->throw();
    }

    /**
     * Send a DELETE request to the Hydra Admin API.
     *
     * @param  array<string, mixed>  $query
     */
    public function delete(string $path, array $query = []): Response
    {
        $url = $this->normalizePath($path);

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $this->request()
            ->delete($url)
            ->throw();
    }

    private function normalizePath(string $path): string
    {
        return '/'.ltrim($path, '/');
    }
}
