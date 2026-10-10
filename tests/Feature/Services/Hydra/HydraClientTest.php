<?php

use App\Services\Hydra\HydraClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('sends a GET request to the Hydra Admin API', function () {
    config()->set('hydra.admin_url', 'http://hydra.test:4445');

    Http::fake([
        'hydra.test:4445/admin/oauth2/auth/requests/login*' => Http::response(['skip' => false], 200),
    ]);

    $response = app(HydraClient::class)->get(
        '/admin/oauth2/auth/requests/login',
        ['login_challenge' => 'test-challenge'],
    );

    expect($response->json('skip'))->toBeFalse();

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && str_starts_with(
                $request->url(),
                'http://hydra.test:4445/admin/oauth2/auth/requests/login',
            )
            && str_contains(
                $request->url(),
                'login_challenge=test-challenge',
            );
    });
});

it('throws when Hydra returns an HTTP error', function () {
    config()->set('hydra.admin_url', 'http://hydra.test:4445');

    Http::fake([
        'hydra.test:4445/*' => Http::response(
            ['error' => 'not_found'],
            404,
        ),
    ]);

    app(HydraClient::class)->get('/admin/test');
})->throws(RequestException::class);
