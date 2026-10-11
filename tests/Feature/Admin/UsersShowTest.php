<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

test('admin can view a user', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin_users_show', $user))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Show')
            ->where('user.id', $user->id)
            ->where('sessions.total', 0));
});

test('admin can update name, email, verification and roles', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    Role::create(['name' => 'editor']);

    $this->actingAs($admin)
        ->patch(route('admin_users_update', $user), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'verified' => false,
            'roles' => ['editor'],
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->hasRole('editor'))->toBeTrue();
});

test('admin can remove two factor authentication', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_recovery_codes' => 'codes',
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($admin)->delete(route('admin_users_two_factor_destroy', $user))->assertRedirect();

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('admin can remove passkeys', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin_users_passkeys_destroy', $user))->assertRedirect();

    expect($user->passkeys()->count())->toBe(0);
});

test('admin can delete a user', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin_users_destroy', $user))
        ->assertRedirect(route('admin_users_index'));

    expect(User::find($user->id))->toBeNull();
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin_users_destroy', $admin))->assertForbidden();

    expect(User::find($admin->id))->not->toBeNull();
});

test('admin can sign a user out of all sessions', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    foreach (['a', 'b'] as $id) {
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(),
        ]);
    }

    $this->actingAs($admin)->delete(route('admin_users_sessions_destroy', $user))->assertRedirect();

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});
