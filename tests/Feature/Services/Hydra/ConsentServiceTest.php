<?php

use App\Services\Hydra\ConsentService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('hydra.admin_url', 'http://hydra.test:4445');
});

it('retrieves a consent request from Hydra', function () {
    $grantScope = ['openid'];
    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/consent*' => Http::response([
            'skip' => false,
            'subject' => '',
            'grant_scope' => $grantScope,
        ]),
    ]);

    $request = app(ConsentService::class)
        ->getRequest('test-challenge');

    expect($request['skip'])->toBeFalse();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_contains($request->url(), 'consent_challenge=test-challenge')
    );
});

it('accepts a consent with the granted scopes', function () {
    $grantScope = ['openid'];
    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/consent/accept*' => Http::response([
            'redirect_to' => 'http://client.test/callback',
        ]),
    ]);

    $result = app(ConsentService::class)->accept(
        'test-challenge',
        $grantScope,
    );

    expect($result['redirect_to'])
        ->toBe('http://client.test/callback');

    Http::assertSent(function (Request $request) use ($grantScope): bool {
        $data = $request->data();

        return $request->method() === 'PUT'
            && str_contains(
                $request->url(),
                'consent_challenge=test-challenge',
            )
            && ($data['grant_scope'] ?? null) === $grantScope
            && ($data['grant_access_token_audience'] ?? null) === [];
    });
});

it('rejects a consent request', function () {
    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/consent/reject*' => Http::response([
            'redirect_to' => 'http://client.test/error',
        ]),
    ]);

    app(ConsentService::class)->reject('test-challenge');

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'PUT'
            && str_contains(
                $request->url(),
                'consent_challenge=test-challenge',
            )
            && ($data['error'] ?? null) === 'access_denied';
    });
});
