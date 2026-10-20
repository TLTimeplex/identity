<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

function createHydraAdmin(): User
{
    test()->seed(DatabaseSeeder::class);

    $user = tap(User::factory()->create())->assignRole('identity.admin');
    $user->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user;
}

function hydraClientPayload(array $overrides = []): array
{
    return array_merge([
        'client_id' => 'test-client',
        'client_name' => 'Test Client',
        'redirect_uris' => ['https://client.example/callback'],
        'post_logout_redirect_uris' => [],
        'grant_types' => ['authorization_code'],
        'response_types' => ['code'],
        'scope' => 'openid profile',
        'audience' => [],
        'allowed_cors_origins' => [],
        'contacts' => [],
        'token_endpoint_auth_method' => 'client_secret_basic',
        'owner' => '',
        'client_uri' => '',
        'logo_uri' => '',
        'policy_uri' => '',
        'tos_uri' => '',
        'skip_consent' => false,
        'skip_logout_consent' => false,
        'created_at' => '2026-10-11T12:00:00Z',
        'updated_at' => '2026-10-11T12:00:00Z',
    ], $overrides);
}

beforeEach(function () {
    config()->set('hydra.admin_url', 'http://hydra.test:4445');
});

it('lists all Hydra clients and follows pagination links', function () {
    $first = hydraClientPayload(['client_id' => 'first']);
    $second = hydraClientPayload(['client_id' => 'second']);
    Http::fake([
        'hydra.test:4445/admin/clients*' => Http::sequence()
            ->push([$first], 200, [
                'Link' => '<http://hydra.test:4445/admin/clients?page_size=1000&page_token=next>; rel="next"',
            ])
            ->push([$second], 200),
    ]);

    $this->actingAs(createHydraAdmin())
        ->get(route('admin_hydra_clients_index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Hydra/Clients/Index')
            ->has('clients', 2)
            ->where('clients.0.client_id', 'first')
            ->where('clients.1.client_id', 'second'));

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'page_token=next'));
});

it('shows a Hydra client without exposing a saved secret', function () {
    Http::fake([
        'hydra.test:4445/admin/clients/test-client' => Http::response(
            hydraClientPayload(['client_secret' => 'must-not-be-shared']),
            200,
        ),
    ]);

    $this->actingAs(createHydraAdmin())
        ->get(route('admin_hydra_clients_show', 'test-client'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Hydra/Clients/Show')
            ->where('client.client_id', 'test-client')
            ->where('generatedSecret', null)
            ->missing('client.client_secret'));
});

it('creates a client and reveals the secret only on the next detail page', function () {
    Http::fake([
        'hydra.test:4445/admin/clients' => Http::response(
            hydraClientPayload(['client_secret' => 'new-secret-value']),
            201,
        ),
        'hydra.test:4445/admin/clients/test-client' => Http::response(hydraClientPayload(), 200),
    ]);

    $admin = createHydraAdmin();

    $this->actingAs($admin)
        ->post(route('admin_hydra_clients_store'), [
            'client_name' => 'Test Client',
            'client_secret' => '',
            'redirect_uris' => 'https://client.example/callback',
            'post_logout_redirect_uris' => '',
            'grant_types' => "authorization_code\nrefresh_token",
            'response_types' => 'code',
            'scope' => 'openid profile',
            'audience' => '',
            'allowed_cors_origins' => '',
            'contacts' => '',
            'token_endpoint_auth_method' => 'client_secret_basic',
            'owner' => '',
            'client_uri' => '',
            'logo_uri' => '',
            'policy_uri' => '',
            'tos_uri' => '',
            'skip_consent' => false,
            'skip_logout_consent' => false,
        ])
        ->assertRedirect(route('admin_hydra_clients_show', 'test-client'));

    $this->get(route('admin_hydra_clients_show', 'test-client'))
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Hydra/Clients/Show')
            ->where('generatedSecret', 'new-secret-value')
            ->missing('client.client_secret'));

    $this->get(route('admin_hydra_clients_show', 'test-client'))
        ->assertInertia(fn ($page) => $page->where('generatedSecret', null));

    Http::assertSent(function (Request $request): bool {
        if (
            $request->method() !== 'POST'
            || ! str_starts_with($request->url(), 'http://hydra.test:4445/admin/clients')
        ) {
            return false;
        }

        $body = json_decode($request->body(), true);

        return $body['grant_types'] === ['authorization_code', 'refresh_token']
            && ! array_key_exists('client_secret', $body);
    });
});

it('updates a client while preserving its secret and existing settings', function () {
    $existing = hydraClientPayload([
        'jwks_uri' => 'https://client.example/jwks',
        'client_secret' => 'must-not-be-sent-back',
    ]);
    Http::fake([
        'hydra.test:4445/admin/clients/test-client' => Http::sequence()
            ->push($existing, 200)
            ->push(hydraClientPayload(['client_name' => 'Updated Client']), 200),
    ]);

    $this->actingAs(createHydraAdmin())
        ->patch(route('admin_hydra_clients_update', 'test-client'), [
            'client_name' => 'Updated Client',
            'client_secret' => '',
            'redirect_uris' => 'https://client.example/callback',
            'post_logout_redirect_uris' => '',
            'grant_types' => 'authorization_code',
            'response_types' => 'code',
            'scope' => 'openid profile',
            'audience' => '',
            'allowed_cors_origins' => '',
            'contacts' => '',
            'token_endpoint_auth_method' => 'client_secret_basic',
            'owner' => '',
            'client_uri' => '',
            'logo_uri' => '',
            'policy_uri' => '',
            'tos_uri' => '',
            'skip_consent' => false,
            'skip_logout_consent' => false,
        ])
        ->assertRedirect(route('admin_hydra_clients_show', 'test-client'));

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && $request->data()['client_name'] === 'Updated Client'
        && $request->data()['jwks_uri'] === 'https://client.example/jwks'
        && ! array_key_exists('client_secret', $request->data()));
});

it('sends empty metadata and jwks back to Hydra as JSON objects', function () {
    $existing = json_encode(hydraClientPayload()).'';
    $existing = substr($existing, 0, -1).',"metadata":{},"jwks":{}}';
    Http::fake([
        'hydra.test:4445/admin/clients/test-client' => Http::sequence()
            ->push($existing, 200, ['Content-Type' => 'application/json'])
            ->push(hydraClientPayload(), 200),
    ]);

    $this->actingAs(createHydraAdmin())
        ->patch(route('admin_hydra_clients_update', 'test-client'), [
            'client_name' => 'Test Client',
            'client_secret' => '',
            'redirect_uris' => 'https://client.example/callback',
            'post_logout_redirect_uris' => '',
            'grant_types' => 'authorization_code',
            'response_types' => 'code',
            'scope' => 'openid profile',
            'audience' => '',
            'allowed_cors_origins' => '',
            'contacts' => '',
            'token_endpoint_auth_method' => 'client_secret_basic',
            'owner' => '',
            'client_uri' => '',
            'logo_uri' => '',
            'policy_uri' => '',
            'tos_uri' => '',
            'skip_consent' => false,
            'skip_logout_consent' => false,
        ])
        ->assertRedirect(route('admin_hydra_clients_show', 'test-client'));

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && str_contains($request->body(), '"metadata":{}')
        && str_contains($request->body(), '"jwks":{}'));
});

it('deletes a Hydra client', function () {
    Http::fake([
        'hydra.test:4445/admin/clients/test-client' => Http::response([], 204),
    ]);

    $this->actingAs(createHydraAdmin())
        ->delete(route('admin_hydra_clients_destroy', 'test-client'))
        ->assertRedirect(route('admin_hydra_clients_index'));

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE');
});

it('allows view-only admins to list clients but denies mutations', function () {
    test()->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('identity.admin.view'));
    $user->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ])->save();

    Http::fake([
        'hydra.test:4445/admin/clients*' => Http::response([], 200),
    ]);

    $this->actingAs($user)
        ->get(route('admin_hydra_clients_index'))
        ->assertOk();

    $this->post(route('admin_hydra_clients_store'), [])->assertForbidden();
    $this->patch(route('admin_hydra_clients_update', 'test-client'), [])->assertForbidden();
    $this->delete(route('admin_hydra_clients_destroy', 'test-client'))->assertForbidden();
});

it('enforces Hydra edit and delete permissions independently from user permissions', function () {
    test()->seed(DatabaseSeeder::class);
    $editor = User::factory()->create();
    $editor->givePermissionTo([
        Permission::findOrCreate('identity.admin.view'),
        Permission::findOrCreate('identity.admin.hydra.edit'),
    ]);
    $editor->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($editor)
        ->post(route('admin_hydra_clients_store'), [])
        ->assertSessionHasErrors('client_name');
    $this->delete(route('admin_hydra_clients_destroy', 'test-client'))->assertForbidden();

    $deleter = User::factory()->create();
    $deleter->givePermissionTo([
        Permission::findOrCreate('identity.admin.view'),
        Permission::findOrCreate('identity.admin.hydra.delete'),
    ]);
    $deleter->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ])->save();

    Http::fake([
        'hydra.test:4445/admin/clients/test-client' => Http::response([], 204),
    ]);

    $this->actingAs($deleter)
        ->delete(route('admin_hydra_clients_destroy', 'test-client'))
        ->assertRedirect(route('admin_hydra_clients_index'));
    $this->post(route('admin_hydra_clients_store'), [])->assertForbidden();
});
