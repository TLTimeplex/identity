<?php

namespace App\Services\Hydra;

class ConsentService
{
    /**
     * @return array<string, mixed>
     */
    public function getRequest(string $challenge): array
    {
        return $this->hydra
            ->get('/admin/oauth2/auth/requests/consent', [
                'consent_challenge' => $challenge,
            ])
            ->json();
    }

    public function __construct(
        private readonly HydraClient $hydra,
    ) {}

    /**
     * @param  list<string>  $grantScope
     * @param  list<string>  $grantAccessTokenAudience
     * @param  array{id_token?: array<string, mixed>, access_token?: array<string, mixed>}  $session
     * @return array<string, mixed>
     */
    public function accept(
        string $challenge,
        array $grantScope,
        array $grantAccessTokenAudience = [],
        bool $remember = false,
        int $rememberFor = 2592000,
        array $session = [],
    ): array {
        $body = [
            'grant_scope' => $grantScope,
            'grant_access_token_audience' => $grantAccessTokenAudience,
            'remember' => $remember,
            'remember_for' => $rememberFor,
        ];

        if ($session !== []) {
            $body['session'] = $session;
        }

        return $this->hydra
            ->put(
                '/admin/oauth2/auth/requests/consent/accept',
                ['consent_challenge' => $challenge],
                $body,
            )
            ->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function reject(
        string $challenge,
        string $error = 'access_denied',
        string $description = 'The user denied the consent request.',
    ): array {
        return $this->hydra
            ->put(
                '/admin/oauth2/auth/requests/consent/reject',
                ['consent_challenge' => $challenge],
                [
                    'error' => $error,
                    'error_description' => $description,
                ],
            )
            ->json();
    }
}
