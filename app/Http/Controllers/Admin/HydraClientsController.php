<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Hydra\OAuth2ClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class HydraClientsController extends Controller
{
    private const LIST_FIELDS = [
        'redirect_uris',
        'post_logout_redirect_uris',
        'grant_types',
        'response_types',
        'audience',
        'allowed_cors_origins',
        'contacts',
    ];

    public function index(OAuth2ClientService $clients): Response
    {
        $items = array_map(static fn (array $client): array => [
            'client_id' => $client['client_id'] ?? '',
            'client_name' => $client['client_name'] ?? '',
            'scope' => $client['scope'] ?? '',
            'grant_types' => is_array($client['grant_types'] ?? null) ? $client['grant_types'] : [],
            'redirect_uri_count' => is_array($client['redirect_uris'] ?? null) ? count($client['redirect_uris']) : 0,
            'created_at' => $client['created_at'] ?? null,
        ], $clients->all());

        return Inertia::render('Admin/Hydra/Clients/Index', [
            'clients' => $items,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Hydra/Clients/Create');
    }

    public function show(string $clientId, OAuth2ClientService $clients): Response
    {
        return Inertia::render('Admin/Hydra/Clients/Show', [
            'client' => $this->clientForForm($clients->find($clientId)),
            'generatedSecret' => request()->session()->pull('hydra_client_secret'),
        ]);
    }

    public function store(Request $request, OAuth2ClientService $clients): RedirectResponse
    {
        $created = $clients->create($this->toHydraAttributes($this->validated($request)));
        $clientId = $created['client_id'] ?? null;

        if (! is_string($clientId) || $clientId === '') {
            throw new RuntimeException('Hydra created a client without returning its client ID.');
        }

        $this->flashSecret($created['client_secret'] ?? null);

        return to_route('admin_hydra_clients_show', $clientId);
    }

    public function update(Request $request, string $clientId, OAuth2ClientService $clients): RedirectResponse
    {
        $attributes = $this->toHydraAttributes($this->validated($request));
        $updated = $clients->update($clientId, $attributes);

        if (isset($attributes['client_secret'])) {
            $this->flashSecret($updated['client_secret'] ?? $attributes['client_secret']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hydra client updated.')]);

        return to_route('admin_hydra_clients_show', $clientId);
    }

    public function destroy(string $clientId, OAuth2ClientService $clients): RedirectResponse
    {
        $clients->delete($clientId);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hydra client deleted.')]);

        return to_route('admin_hydra_clients_index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:4096'],
            'redirect_uris' => ['present', 'nullable', 'string', 'max:65535'],
            'post_logout_redirect_uris' => ['present', 'nullable', 'string', 'max:65535'],
            'grant_types' => ['present', 'nullable', 'string', 'max:65535'],
            'response_types' => ['present', 'nullable', 'string', 'max:65535'],
            'scope' => ['present', 'nullable', 'string', 'max:65535'],
            'audience' => ['present', 'nullable', 'string', 'max:65535'],
            'allowed_cors_origins' => ['present', 'nullable', 'string', 'max:65535'],
            'contacts' => ['present', 'nullable', 'string', 'max:65535'],
            'token_endpoint_auth_method' => ['required', 'string', 'max:255'],
            'owner' => ['present', 'nullable', 'string', 'max:255'],
            'client_uri' => ['present', 'nullable', 'string', 'max:2048'],
            'logo_uri' => ['present', 'nullable', 'string', 'max:2048'],
            'policy_uri' => ['present', 'nullable', 'string', 'max:2048'],
            'tos_uri' => ['present', 'nullable', 'string', 'max:2048'],
            'skip_consent' => ['required', 'boolean'],
            'skip_logout_consent' => ['required', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function toHydraAttributes(array $data): array
    {
        foreach (self::LIST_FIELDS as $field) {
            $data[$field] = array_values(array_filter(
                array_map('trim', preg_split('/\\R/', $data[$field] ?? '') ?: []),
                static fn (string $value): bool => $value !== '',
            ));
        }

        foreach (['scope', 'owner', 'client_uri', 'logo_uri', 'policy_uri', 'tos_uri'] as $field) {
            $data[$field] ??= '';
        }

        if (! filled($data['client_secret'] ?? null)) {
            unset($data['client_secret']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $client
     * @return array<string, mixed>
     */
    private function clientForForm(array $client): array
    {
        unset($client['client_secret']);

        foreach (self::LIST_FIELDS as $field) {
            $values = $client[$field] ?? [];
            $client[$field] = is_array($values) ? implode("\n", $values) : '';
        }

        foreach ([
            'client_name',
            'scope',
            'token_endpoint_auth_method',
            'owner',
            'client_uri',
            'logo_uri',
            'policy_uri',
            'tos_uri',
        ] as $field) {
            $client[$field] = is_string($client[$field] ?? null) ? $client[$field] : '';
        }

        foreach (['skip_consent', 'skip_logout_consent'] as $field) {
            $client[$field] = (bool) ($client[$field] ?? false);
        }

        return $client;
    }

    private function flashSecret(mixed $secret): void
    {
        if (is_string($secret) && $secret !== '') {
            request()->session()->flash('hydra_client_secret', $secret);
        }
    }
}
