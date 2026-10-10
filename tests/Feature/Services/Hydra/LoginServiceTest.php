<?php

use App\Models\User;
use App\Services\Hydra\LoginService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Uid\Ulid;

beforeEach(function () {
    config()->set('hydra.admin_url', 'http://hydra.test:4445');
});

it('retrieves a login request from Hydra', function () {
    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login*' => Http::response([
            'skip' => false,
            'subject' => '',
        ]),
    ]);

    $request = app(LoginService::class)
        ->getRequest('test-challenge');

    expect($request['skip'])->toBeFalse();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_contains($request->url(), 'login_challenge=test-challenge')
    );
});

it('accepts a login using the user OIDC subject', function () {
    $user = new User;
    $user->oidc_subject = new Ulid;

    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login/accept*' => Http::response([
            'redirect_to' => 'http://client.test/callback',
        ]),
    ]);

    $result = app(LoginService::class)->accept(
        'test-challenge',
        $user,
    );

    expect($result['redirect_to'])
        ->toBe('http://client.test/callback');

    Http::assertSent(function (Request $request) use ($user): bool {
        $data = $request->data();

        return $request->method() === 'PUT'
            && str_contains(
                $request->url(),
                'login_challenge=test-challenge',
            )
            && ($data['subject'] ?? null)
                === $user->oidc_subject->toBase32()
            && ($data['remember'] ?? null) === true
            && ($data['remember_for'] ?? null) === 2592000;
    });
});

it('rejects a login request', function () {
    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login/reject*' => Http::response([
            'redirect_to' => 'http://client.test/error',
        ]),
    ]);

    app(LoginService::class)->reject('test-challenge');

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'PUT'
            && str_contains(
                $request->url(),
                'login_challenge=test-challenge',
            )
            && ($data['error'] ?? null) === 'access_denied';
    });
});
