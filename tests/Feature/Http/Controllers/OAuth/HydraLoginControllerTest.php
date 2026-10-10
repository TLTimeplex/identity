<?php

use App\Models\User;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Uid\Ulid;

beforeEach(function () {
    config()->set('hydra.admin_url', 'http://hydra.test:4445');
    config()->set('hydra.public_url', 'https://hydra.identity.test');
    config()->set('app.url', 'https://identity.test');
});

it('redirects to the login page when Hydra does not skip login', function () {
    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login*' => Http::response([
            'skip' => false,
            'subject' => '',
        ]),
    ]);

    $this->get('/oauth/login?login_challenge=test-challenge')
        ->assertRedirect(route('login'))
        ->assertSessionHas(
            'hydra.login_challenge',
            'test-challenge',
        );

    Http::assertSentCount(1);
});

it('accepts the Hydra challenge for an already authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'https://hydra.identity.test/oauth2/auth?login_verifier=test',
        ]),
        'hydra.test:4445/admin/oauth2/auth/requests/login*' => Http::response([
            'skip' => false,
            'subject' => '',
        ]),
    ]);

    $this->get('/oauth/login?login_challenge=test-challenge')
        ->assertRedirect(
            'https://hydra.identity.test/oauth2/auth?login_verifier=test',
        );

    Http::assertSentCount(2);
    Http::assertSent(function (ClientRequest $request) use ($user): bool {
        return str_contains(
            $request->url(),
            'login_challenge=test-challenge',
        )
            && ($request->data()['subject'] ?? null)
                === (string) $user->oidc_subject;
    });
});

it('continues a skipped login for a known user', function () {
    $user = User::factory()->create();

    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'https://hydra.identity.test/oauth2/auth?login_verifier=test',
        ]),
        'hydra.test:4445/admin/oauth2/auth/requests/login*' => Http::response([
            'skip' => true,
            'subject' => (string) $user->oidc_subject,
        ]),
    ]);

    $this->get('/oauth/login?login_challenge=test-challenge')
        ->assertRedirect(
            'https://hydra.identity.test/oauth2/auth?login_verifier=test',
        );

    expect(Auth::id())->toBe($user->id);

    Http::assertSentCount(2);
});

it('rejects an unknown subject instead of logging in a user', function () {
    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login*' => Http::response([
            'skip' => true,
            'subject' => (string) new Ulid,
        ]),
    ]);

    $this->get('/oauth/login?login_challenge=test-challenge')
        ->assertForbidden();

    expect(Auth::check())->toBeFalse();

    Http::assertSentCount(1);
});
