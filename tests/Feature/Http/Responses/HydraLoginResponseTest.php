<?php

use App\Http\Responses\HydraLoginResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

beforeEach(function () {
    config()->set('hydra.admin_url', 'http://hydra.test:4445');

    Route::middleware(['web', 'auth'])
        ->match(['get', 'post'], '/_test/hydra-login-response', function (
            Request $request,
        ) {
            return app(LoginResponseContract::class)
                ->toResponse($request);
        });
});

it('registers the custom response for both Fortify login stages', function () {
    expect(app(LoginResponseContract::class))
        ->toBeInstanceOf(HydraLoginResponse::class)
        ->and(app(TwoFactorLoginResponseContract::class))
        ->toBeInstanceOf(HydraLoginResponse::class);
});

it('redirects to Hydras returned URL after login', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->withSession([
        'hydra.login_challenge' => 'test-challenge',
    ]);

    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'https://client.example/callback?code=abc',
        ]),
    ]);

    $response = $this->get('/_test/hydra-login-response');

    $response->assertRedirect(
        'https://client.example/callback?code=abc',
    );

    $response->assertSessionMissing('hydra.login_challenge');

    Http::assertSent(function (
        Illuminate\Http\Client\Request $request,
    ) use ($user): bool {
        return $request->method() === 'PUT'
            && str_contains($request->url(), 'login_challenge=test-challenge')
            && ($request->data()['subject'] ?? null)
               === (string) $user->oidc_subject;
    });
});

it('uses a full-page location response for Inertia login requests', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->withSession([
        'hydra.login_challenge' => 'test-challenge',
    ]);

    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'https://hydra.identity.test/oauth2/auth?login_verifier=test',
        ]),
    ]);

    $this->withHeaders(['X-Inertia' => 'true'])
        ->post('/_test/hydra-login-response')
        ->assertStatus(409)
        ->assertHeader(
            'X-Inertia-Location',
            'https://hydra.identity.test/oauth2/auth?login_verifier=test',
        );
});

it('keeps normal Laravel login behavior without a Hydra challenge', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/_test/hydra-login-response')
        ->assertRedirect('/dashboard');

    Http::assertNothingSent();
});

it('keeps the Hydra challenge in the session when Hydra rejects the acceptance request', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->withSession([
        'hydra.login_challenge' => 'test-challenge',
    ]);

    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'error' => 'invalid_request',
            'error_description' => 'Invalid login challenge.',
        ], 400),
    ]);

    $this->get('/_test/hydra-login-response')
        ->assertServerError()
        ->assertSessionHas(
            'hydra.login_challenge',
            'test-challenge',
        );
});

it('uses the default two-factor response when no Hydra challenge exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Route::middleware(['web', 'auth'])
        ->get('/_test/hydra-two-factor-response', function (
            Request $request,
        ) {
            return app(TwoFactorLoginResponseContract::class)
                ->toResponse($request);
        });

    $this->get('/_test/hydra-two-factor-response')
        ->assertRedirect('/dashboard');

    Http::assertNothingSent();
});
