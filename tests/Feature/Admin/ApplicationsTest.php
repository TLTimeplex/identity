<?php

use App\Models\Application;
use App\Models\ApplicationCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function createApplicationAdmin(): User
{
    test()->seed(DatabaseSeeder::class);

    $user = tap(User::factory()->create())->assignRole('identity.admin');
    $user->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user;
}

function applicationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Wiki',
        'description' => 'Internal wiki',
        'url' => 'https://wiki.example.com',
        'icon' => null,
        'is_active' => true,
        'category' => null,
        'required_permission' => null,
        'hydra_client_id' => 'wiki-client',
    ], $overrides);
}

it('creates an application', function () {
    $this->actingAs(createApplicationAdmin());

    $this->post(route('admin_applications_store'), applicationPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('application', [
        'name' => 'Wiki',
        'hydra_client_id' => 'wiki-client',
    ]);
});

it('updates an application', function () {
    $this->actingAs(createApplicationAdmin());
    $application = Application::factory()->create(['name' => 'Old']);

    $this->patch(
        route('admin_applications_update', $application),
        applicationPayload(['name' => 'New', 'hydra_client_id' => $application->hydra_client_id]),
    )->assertRedirect();

    expect($application->fresh()->name)->toBe('New');
});

it('deletes an application', function () {
    $this->actingAs(createApplicationAdmin());
    $application = Application::factory()->create();

    $this->delete(route('admin_applications_destroy', $application))
        ->assertRedirect(route('admin_applications_index'));

    $this->assertDatabaseMissing('application', ['id' => $application->id]);
});

it('creates an application category', function () {
    $this->actingAs(createApplicationAdmin());

    $this->post(route('admin_applications_categories_store'), ['name' => 'Tools'])
        ->assertRedirect();

    $this->assertDatabaseHas('application_category', ['name' => 'Tools']);
});

it('rejects duplicate category names', function () {
    $this->actingAs(createApplicationAdmin());
    ApplicationCategory::factory()->create(['name' => 'Tools']);

    $this->post(route('admin_applications_categories_store'), ['name' => 'Tools'])
        ->assertSessionHasErrors('name');

    expect(ApplicationCategory::where('name', 'Tools')->count())->toBe(1);
});

it('deletes an application category and keeps its applications', function () {
    $this->actingAs(createApplicationAdmin());
    $category = ApplicationCategory::factory()->create();
    $application = Application::factory()->create(['category_id' => $category->id]);

    $this->delete(route('admin_applications_categories_destroy', $category))
        ->assertRedirect();

    $this->assertDatabaseMissing('application_category', ['id' => $category->id]);
    expect($application->fresh()->category_id)->toBeNull();
});

it('assigns a category to an application on update', function () {
    $this->actingAs(createApplicationAdmin());
    $category = ApplicationCategory::factory()->create(['name' => 'Tools']);
    $application = Application::factory()->create(['category_id' => null]);

    $this->patch(
        route('admin_applications_update', $application),
        applicationPayload(['category' => 'Tools', 'hydra_client_id' => $application->hydra_client_id]),
    )->assertRedirect();

    expect($application->fresh()->category_id)->toBe($category->id);
});

it('does not allow a Hydra client ID to be assigned to two applications on create', function () {
    $this->actingAs(createApplicationAdmin());
    Application::factory()->create(['hydra_client_id' => 'shared-client']);

    $this->post(route('admin_applications_store'), applicationPayload(['hydra_client_id' => 'shared-client']))
        ->assertSessionHasErrors('hydra_client_id');

    expect(Application::where('hydra_client_id', 'shared-client')->count())->toBe(1);
});

it('does not allow a Hydra client ID to be reassigned to another application on update', function () {
    $this->actingAs(createApplicationAdmin());
    Application::factory()->create(['hydra_client_id' => 'taken']);
    $other = Application::factory()->create(['hydra_client_id' => 'free']);

    $this->patch(
        route('admin_applications_update', $other),
        applicationPayload(['hydra_client_id' => 'taken']),
    )->assertSessionHasErrors('hydra_client_id');

    expect($other->fresh()->hydra_client_id)->toBe('free');
});

it('allows an application to keep its own Hydra client ID on update', function () {
    $this->actingAs(createApplicationAdmin());
    $application = Application::factory()->create(['hydra_client_id' => 'mine']);

    $this->patch(
        route('admin_applications_update', $application),
        applicationPayload(['hydra_client_id' => 'mine']),
    )->assertSessionHasNoErrors();
});

it('enforces unique Hydra client IDs in the database', function () {
    Application::factory()->create(['hydra_client_id' => 'dup']);

    expect(fn () => Application::factory()->create(['hydra_client_id' => 'dup']))
        ->toThrow(QueryException::class);
});

it('shows a permission-protected application to users holding the permission', function () {
    $permission = Permission::create(['name' => 'wiki.access', 'guard_name' => 'web']);
    $application = Application::factory()->active()->create([
        'name' => 'Wiki',
        'required_permission' => $permission->id,
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('wiki.access');

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('apps', 1)
            ->where('apps.0.id', $application->id));
});

it('hides a permission-protected application from users without the permission', function () {
    $permission = Permission::create(['name' => 'wiki.access', 'guard_name' => 'web']);
    Application::factory()->active()->create(['required_permission' => $permission->id]);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('apps', 0));
});

it('shows applications without a required permission to every user', function () {
    Application::factory()->active()->create(['required_permission' => null]);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('apps', 1));
});
