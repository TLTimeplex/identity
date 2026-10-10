<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Hydra\ConsentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ConsentController extends Controller
{
    /**
     * Permissions we currently choose to support.
     *
     * @var array<string, string>
     */
    private const array SUPPORTED_SCOPES = [
        'openid' => 'Use your identity to sign in',
        'profile' => 'Read profile data such as your name and profile information',
        'email' => 'Read your email address and its verification status',
    ];

    public function __invoke(
        Request $request,
        ConsentService $consentService,
    ): InertiaResponse|SymfonyResponse {
        $validated = $request->validate([
            'consent_challenge' => ['required', 'string'],
        ]);

        $challenge = $validated['consent_challenge'];
        $consent = $consentService->getRequest($challenge);
        $this->assertConsentSubject($request, $consent);

        if ($consent['skip'] ?? false) {
            $requestedScopes = $this->stringList(
                $consent['requested_scope'] ?? [],
            );
            $grantedScopes = $this->supportedRequestedScopes($requestedScopes);

            $result = $consentService->accept(
                $challenge,
                $grantedScopes,
                remember: true,
                session: $this->idTokenSession($request, $grantedScopes),
            );

            return $this->redirectToHydra($result);
        }

        $requestedScopes = $this->stringList(
            $consent['requested_scope'] ?? [],
        );

        $supportedScopes = $this->supportedRequestedScopes(
            $requestedScopes,
        );

        $request->session()->put('hydra.consent_challenge', $challenge);

        return Inertia::render('oauth/Consent', [
            'clientName' => data_get(
                $consent,
                'client.client_name',
                data_get($consent, 'client.client_id', 'Unbekannte Anwendung'),
            ),
            'requestedScopes' => array_map(
                fn (string $scope): array => [
                    'name' => $scope,
                    'description' => self::SUPPORTED_SCOPES[$scope],
                ],
                $supportedScopes,
            ),
            'hasUnsupportedScopes' => count($requestedScopes)
                !== count($supportedScopes),
        ]);
    }

    public function accept(
        Request $request,
        ConsentService $consentService,
    ): SymfonyResponse {
        $challenge = $request->session()->get('hydra.consent_challenge');

        abort_unless(is_string($challenge) && $challenge !== '', 419);

        // Die Berechtigungen immer frisch von Hydra beziehen.
        $consent = $consentService->getRequest($challenge);
        $this->assertConsentSubject($request, $consent);

        $requestedScopes = $this->stringList(
            $consent['requested_scope'] ?? [],
        );

        $grantedScopes = $this->supportedRequestedScopes($requestedScopes);

        $result = $consentService->accept(
            $challenge,
            $grantedScopes,
            // Audiences werden erst freigegeben, wenn wir sie explizit
            // in der Consent-Oberfläche unterstützen.
            grantAccessTokenAudience: [],
            remember: true,
            session: $this->idTokenSession($request, $grantedScopes),
        );

        $request->session()->forget('hydra.consent_challenge');

        return $this->redirectToHydra($result);
    }

    public function reject(
        Request $request,
        ConsentService $consentService,
    ): SymfonyResponse {
        $challenge = $request->session()->get('hydra.consent_challenge');

        abort_unless(is_string($challenge) && $challenge !== '', 419);

        $result = $consentService->reject($challenge);

        $request->session()->forget('hydra.consent_challenge');

        return $this->redirectToHydra($result);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function redirectToHydra(array $result): SymfonyResponse
    {
        $redirectTo = $result['redirect_to'] ?? null;

        abort_unless(
            is_string($redirectTo)
                && filter_var($redirectTo, FILTER_VALIDATE_URL)
                && in_array(
                    parse_url($redirectTo, PHP_URL_SCHEME),
                    ['http', 'https'],
                    true,
                ),
            502,
            'Hydra returned an invalid consent redirect URL.',
        );

        return Inertia::location($redirectTo);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $values,
            static fn (mixed $value): bool => is_string($value),
        )));
    }

    /**
     * @param  list<string>  $requestedScopes
     * @return list<string>
     */
    private function supportedRequestedScopes(array $requestedScopes): array
    {
        return array_values(array_intersect(
            $requestedScopes,
            array_keys(self::SUPPORTED_SCOPES),
        ));
    }

    /**
     * @param  list<string>  $grantedScopes
     * @return array<string, mixed>
     */
    private function idTokenClaims(Request $request, array $grantedScopes): array
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $claims = [];

        if (in_array('profile', $grantedScopes, true)) {
            $claims['name'] = $user->name;
        }

        if (in_array('email', $grantedScopes, true)) {
            $claims['email'] = $user->email;
            $claims['email_verified'] = $user->email_verified_at !== null;
        }

        return $claims;
    }

    /**
     * @param  list<string>  $grantedScopes
     * @return array{id_token?: array<string, mixed>}
     */
    private function idTokenSession(Request $request, array $grantedScopes): array
    {
        $claims = $this->idTokenClaims($request, $grantedScopes);

        return $claims === [] ? [] : ['id_token' => $claims];
    }

    /**
     * @param  array<string, mixed>  $consent
     */
    private function assertConsentSubject(Request $request, array $consent): void
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $subject = $consent['subject'] ?? null;

        abort_unless(
            \is_string($subject)
                && hash_equals((string) $user->oidc_subject, $subject),
            403,
            'The consent request does not belong to the authenticated user.',
        );
    }
}
