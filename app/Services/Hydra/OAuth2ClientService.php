<?php

namespace App\Services\Hydra;

use RuntimeException;

class OAuth2ClientService
{
    public function __construct(
        private readonly HydraClient $hydra,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $clients = [];
        $query = ['page_size' => 1000];
        $seenTokens = [];

        do {
            $response = $this->hydra->get('/admin/clients', $query);
            $page = $response->json();

            if (! is_array($page) || ! array_is_list($page)) {
                throw new RuntimeException('Hydra returned an invalid client list.');
            }

            foreach ($page as $client) {
                if (! is_array($client)) {
                    throw new RuntimeException('Hydra returned an invalid client record.');
                }

                unset($client['client_secret']);
                $clients[] = $client;
            }

            $nextToken = $this->nextPageToken($response->header('Link'));

            if ($nextToken !== null && isset($seenTokens[$nextToken])) {
                throw new RuntimeException('Hydra returned a repeated client pagination token.');
            }

            if ($nextToken !== null) {
                $seenTokens[$nextToken] = true;
                $query['page_token'] = $nextToken;
            }
        } while ($nextToken !== null);

        return $clients;
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $clientId): array
    {
        $client = $this->hydra
            ->get('/admin/clients/'.rawurlencode($clientId))
            ->json();

        if (! is_array($client)) {
            throw new RuntimeException('Hydra returned an invalid client record.');
        }

        unset($client['client_secret']);

        return $client;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function create(array $attributes): array
    {
        return $this->hydra
            ->post('/admin/clients', $attributes)
            ->json();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function update(string $clientId, array $attributes): array
    {
        $client = $this->find($clientId);
        $client = array_merge($client, $attributes);
        $client['client_id'] = $clientId;

        unset(
            $client['created_at'],
            $client['updated_at'],
            $client['registration_access_token'],
            $client['registration_client_uri'],
            $client['client_secret_expires_at'],
        );

        // Empty JSON objects decode to [] in PHP and must be sent back as {}.
        foreach (['metadata', 'jwks'] as $field) {
            if (array_key_exists($field, $client) && $client[$field] === []) {
                $client[$field] = new \stdClass;
            }
        }

        return $this->hydra
            ->put('/admin/clients/'.rawurlencode($clientId), [], $client)
            ->json();
    }

    public function delete(string $clientId): void
    {
        $this->hydra->delete('/admin/clients/'.rawurlencode($clientId));
    }

    private function nextPageToken(?string $linkHeader): ?string
    {
        if ($linkHeader === null) {
            return null;
        }

        preg_match_all('/<([^>]+)>\\s*;\\s*rel="?([^";]+)"?/i', $linkHeader, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (strtolower($match[2]) !== 'next') {
                continue;
            }

            parse_str((string) parse_url($match[1], PHP_URL_QUERY), $query);

            return is_string($query['page_token'] ?? null)
                ? $query['page_token']
                : null;
        }

        return null;
    }
}
