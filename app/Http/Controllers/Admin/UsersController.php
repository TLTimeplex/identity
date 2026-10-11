<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::with('roles:name')->get(['name', 'email', 'id', 'oidc_subject', 'created_at', 'updated_at']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function show(User $user): Response
    {
        $lifetime = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        $sessions = DB::table(config('session.table', 'sessions'))->where('user_id', $user->id);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'oidc_subject' => $user->oidc_subject,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'two_factor_pending' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
                'roles' => $user->roles()->pluck('name')->all(),
            ],
            'allRoles' => Role::query()->orderBy('name')->pluck('name')->all(),
            'passkeys' => $user->passkeys()
                ->latest()
                ->get()
                ->map(fn ($passkey) => [
                    'id' => $passkey->id,
                    'name' => $passkey->name,
                    'created_at' => $passkey->created_at?->toIso8601String(),
                    'last_used_at' => $passkey->last_used_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'sessions' => [
                'total' => (clone $sessions)->count(),
                'active' => (clone $sessions)->where('last_activity', '>=', $lifetime)->count(),
            ],
            'logs' => [],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'verified' => ['required', 'boolean'],
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $user->fill(['name' => $data['name'], 'email' => $data['email']]);

        if ($data['verified']) {
            $user->email_verified_at ??= now();
        } else {
            $user->email_verified_at = null;
        }

        $user->save();
        $user->syncRoles($data['roles']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return back();
    }

    public function destroyTwoFactor(User $user, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $disable($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Two-factor authentication removed.')]);

        return back();
    }

    public function destroyPasskey(User $user, int $passkey): RedirectResponse
    {
        $user->passkeys()->whereKey($passkey)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Passkey removed.')]);

        return back();
    }

    public function destroyPasskeys(User $user): RedirectResponse
    {
        $user->passkeys()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('All passkeys removed.')]);

        return back();
    }

    public function destroySessions(User $user): RedirectResponse
    {
        DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User signed out of all sessions.')]);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, __('You cannot delete your own account here.'));

        DB::transaction(function () use ($user): void {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            $user->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('admin_users_index');
    }
}
