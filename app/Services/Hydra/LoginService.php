<?php

namespace App\Services\Hydra;

use App\Models\Application;
use App\Models\User;

class LoginService
{
    public function __construct(
        private readonly HydraClient $hydra,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getRequest(string $challenge): array
    {
        return $this->hydra
            ->get('/admin/oauth2/auth/requests/login', [
                'login_challenge' => $challenge,
            ])
            ->json();
    }

    /**
     * Clients without a matching application entry are not restricted.
     * Otherwise the application must be active and its required permission,
     * if any, must be held by the user.
     */
    public function userMayAccessClient(?string $clientId, User $user): bool
    {
        if ($clientId === null || $clientId === '') {
            return true;
        }

        $application = Application::query()
            ->with('requiredPermission')
            ->where('hydra_client_id', $clientId)
            ->first();

        if ($application === null) {
            return true;
        }

        if (! $application->is_active) {
            return false;
        }

        $permission = $application->requiredPermission;

        return $permission === null || $user->hasPermissionTo($permission);
    }

    /**
     * @return array<string, mixed>
     */
    public function accept(
        string $challenge,
        User $user,
        bool $remember = true,
        int $rememberFor = 2592000,
    ): array {
        return $this->hydra
            ->put(
                '/admin/oauth2/auth/requests/login/accept',
                ['login_challenge' => $challenge],
                [
                    'subject' => (string) $user->oidc_subject,
                    'remember' => $remember,
                    'remember_for' => $rememberFor,
                ],
            )
            ->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function reject(
        string $challenge,
        string $error = 'access_denied',
        string $description = 'The login request was denied.',
    ): array {
        return $this->hydra
            ->put(
                '/admin/oauth2/auth/requests/login/reject',
                ['login_challenge' => $challenge],
                [
                    'error' => $error,
                    'error_description' => $description,
                ],
            )
            ->json();
    }
}
