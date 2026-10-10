<?php

use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config()->set('hydra.admin_url', 'http://hydra.test:4445');
});

function fakeConsentRequest(
    string $subject,
    array $scopes = ['openid', 'profile'],
    bool $skip = false,
): void {
    Http::fake(function (HttpRequest $request) use (
        $subject,
        $scopes,
        $skip,
    ) {
        $url = $request->url();

        if (str_contains($url, '/requests/consent/accept')) {
            return Http::response([
                'redirect_to' => 'https://client.test/callback',
            ]);
        }

        if (str_contains($url, '/requests/consent/reject')) {
            return Http::response([
                'redirect_to' => 'https://client.test/denied',
            ]);
        }

        if (str_contains($url, '/requests/consent')) {
            return Http::response([
                'skip' => $skip,
                'subject' => $subject,
                'requested_scope' => $scopes,
                'requested_access_token_audience' => [],
                'client' => [
                    'client_id' => 'test-client',
                    'client_name' => 'Test Client',
                ],
            ]);
        }

        return Http::response([], 404);
    });
}

it('shows the consent page for the authenticated subject', function () {
    $user = User::factory()->create();

    fakeConsentRequest((string) $user->oidc_subject);

    $this->actingAs($user)
        ->get(route('oauth.consent', [
            'consent_challenge' => 'test-challenge',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('oauth/Consent')
            ->where('clientName', 'Test Client')
            ->has('requestedScopes', 2)
            ->where('hasUnsupportedScopes', false)
        )
        ->assertSessionHas(
            'hydra.consent_challenge',
            'test-challenge',
        );
});

it('rejects a consent request belonging to another subject', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    fakeConsentRequest((string) $otherUser->oidc_subject);

    $this->actingAs($user)
        ->get(route('oauth.consent', [
            'consent_challenge' => 'test-challenge',
        ]))
        ->assertForbidden();
});

it('does not grant unsupported scopes', function () {
    $user = User::factory()->create();

    fakeConsentRequest(
        (string) $user->oidc_subject,
        ['openid', 'profile', 'admin'],
    );

    $this->actingAs($user)
        ->get(route('oauth.consent', [
            'consent_challenge' => 'test-challenge',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasUnsupportedScopes', true)
            ->has('requestedScopes', 2)
        );
});

it('ignores submitted scope changes and grants all supported requested scopes', function () {
    $user = User::factory()->create();

    fakeConsentRequest(
        (string) $user->oidc_subject,
        ['openid', 'profile', 'email', 'admin'],
    );

    $this->actingAs($user)
        ->withSession([
            'hydra.consent_challenge' => 'test-challenge',
        ])
        ->post(route('oauth.consent.accept'), [
            'scopes' => ['openid'],
        ])
        ->assertRedirect('https://client.test/callback')
        ->assertSessionMissing('hydra.consent_challenge');

    Http::assertSent(function (HttpRequest $request) use ($user): bool {
        if (
            $request->method() !== 'PUT'
            || ! str_contains(
                $request->url(),
                '/admin/oauth2/auth/requests/consent/accept',
            )
        ) {
            return false;
        }

        return ($request->data()['grant_scope'] ?? null)
                === ['openid', 'profile', 'email']
            && ($request->data()['remember'] ?? null) === true
            && ($request->data()['session']['id_token'] ?? null) === [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => true,
            ]
            && ($request->data()['grant_access_token_audience'] ?? null) === [];
    });
});

it('uses a full-page navigation after an Inertia consent submission', function () {
    $user = User::factory()->create();

    fakeConsentRequest((string) $user->oidc_subject);

    $this->actingAs($user)
        ->withSession([
            'hydra.consent_challenge' => 'test-challenge',
        ])
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('oauth.consent.accept'))
        ->assertStatus(409)
        ->assertHeader(
            'X-Inertia-Location',
            'https://client.test/callback',
        );
});

it('rejects the consent request', function () {
    $user = User::factory()->create();

    fakeConsentRequest((string) $user->oidc_subject);

    $this->actingAs($user)
        ->withSession([
            'hydra.consent_challenge' => 'test-challenge',
        ])
        ->post(route('oauth.consent.reject'))
        ->assertRedirect('https://client.test/denied')
        ->assertSessionMissing('hydra.consent_challenge');

    Http::assertSent(fn (HttpRequest $request): bool => $request->method() === 'PUT'
        && str_contains(
            $request->url(),
            '/admin/oauth2/auth/requests/consent/reject',
        )
        && ($request->data()['error'] ?? null) === 'access_denied'
    );
});

it('automatically accepts skippable consent without granting unsupported scopes', function () {
    $user = User::factory()->create();

    fakeConsentRequest(
        (string) $user->oidc_subject,
        ['openid', 'profile', 'admin'],
        skip: true,
    );

    $this->actingAs($user)
        ->get(route('oauth.consent', [
            'consent_challenge' => 'test-challenge',
        ]))
        ->assertRedirect('https://client.test/callback');

    Http::assertSent(function (HttpRequest $request) use ($user): bool {
        if (
            $request->method() !== 'PUT'
            || ! str_contains(
                $request->url(),
                '/admin/oauth2/auth/requests/consent/accept',
            )
        ) {
            return false;
        }

        return ($request->data()['grant_scope'] ?? null)
                === ['openid', 'profile']
            && ($request->data()['session']['id_token'] ?? null)
                === ['name' => $user->name];
    });
});

it('accepts openid-only skipped consent without an empty ID token session', function () {
    $user = User::factory()->create();

    fakeConsentRequest(
        (string) $user->oidc_subject,
        ['openid'],
        skip: true,
    );

    $this->actingAs($user)
        ->get(route('oauth.consent', [
            'consent_challenge' => 'test-challenge',
        ]))
        ->assertRedirect('https://client.test/callback');

    Http::assertSent(function (HttpRequest $request): bool {
        if (
            $request->method() !== 'PUT'
            || ! str_contains(
                $request->url(),
                '/admin/oauth2/auth/requests/consent/accept',
            )
        ) {
            return false;
        }

        return ($request->data()['grant_scope'] ?? null) === ['openid']
            && ! array_key_exists('session', $request->data());
    });
});
