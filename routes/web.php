<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\HydraClientsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\OAuth\ConsentController;
use App\Http\Controllers\OAuth\HydraLoginController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [Dashboard::class, 'index'])->name('dashboard');
    Route::middleware(['can:identity.admin.view', '2fa'])->group(function () {
        Route::get('admin/users', [UsersController::class, 'index'])->name('admin_users_index');
        Route::get('admin/users/{user}', [UsersController::class, 'show'])->name('admin_users_show');
        Route::get('admin/applications', [ApplicationController::class, 'index'])->name('admin_applications_index');
        Route::get('admin/applications/{application}', [ApplicationController::class, 'show'])->whereNumber('application')->name('admin_applications_show');
        Route::get('admin/hydra/clients', [HydraClientsController::class, 'index'])->name('admin_hydra_clients_index');
        Route::get('admin/hydra/clients/create', [HydraClientsController::class, 'create'])->name('admin_hydra_clients_create');
        Route::get('admin/hydra/clients/{clientId}', [HydraClientsController::class, 'show'])->name('admin_hydra_clients_show');

        Route::middleware('can:identity.admin.edit')->group(function () {
            Route::get('admin/applications/create', [ApplicationController::class, 'create'])->name('admin_applications_create');
            Route::post('admin/applications', [ApplicationController::class, 'store'])->name('admin_applications_store');
            Route::patch('admin/applications/{application}', [ApplicationController::class, 'update'])->whereNumber('application')->name('admin_applications_update');
            Route::post('admin/applications/categories', [ApplicationController::class, 'createCategory'])->name('admin_applications_categories_store');
            Route::patch('admin/users/{user}', [UsersController::class, 'update'])->name('admin_users_update');
        });

        Route::middleware('can:identity.admin.delete')->group(function () {
            Route::delete('admin/applications/categories/{category}', [ApplicationController::class, 'deleteCategory'])->name('admin_applications_categories_destroy');
            Route::delete('admin/applications/{application}', [ApplicationController::class, 'destroy'])->whereNumber('application')->name('admin_applications_destroy');
            Route::delete('admin/users/{user}', [UsersController::class, 'destroy'])->name('admin_users_destroy');
            Route::delete('admin/users/{user}/sessions', [UsersController::class, 'destroySessions'])->name('admin_users_sessions_destroy');
            Route::delete('admin/users/{user}/two-factor', [UsersController::class, 'destroyTwoFactor'])->name('admin_users_two_factor_destroy');
            Route::delete('admin/users/{user}/passkeys', [UsersController::class, 'destroyPasskeys'])->name('admin_users_passkeys_destroy');
            Route::delete('admin/users/{user}/passkeys/{passkey}', [UsersController::class, 'destroyPasskey'])->name('admin_users_passkey_destroy');
        });

        Route::middleware('can:identity.admin.hydra.edit')->group(function () {
            Route::post('admin/hydra/clients', [HydraClientsController::class, 'store'])->name('admin_hydra_clients_store');
            Route::patch('admin/hydra/clients/{clientId}', [HydraClientsController::class, 'update'])->name('admin_hydra_clients_update');
        });

        Route::middleware('can:identity.admin.hydra.delete')->group(function () {
            Route::delete('admin/hydra/clients/{clientId}', [HydraClientsController::class, 'destroy'])->name('admin_hydra_clients_destroy');
        });
    });
});

Route::get('/oauth/login', HydraLoginController::class)
    ->name('oauth.login');

Route::get('/oauth/consent', ConsentController::class)
    ->middleware('auth')
    ->name('oauth.consent');

Route::post('/oauth/consent/accept', [
    ConsentController::class,
    'accept',
])
    ->middleware('auth')
    ->name('oauth.consent.accept');

Route::post('/oauth/consent/reject', [
    ConsentController::class,
    'reject',
])
    ->middleware('auth')
    ->name('oauth.consent.reject');

require __DIR__.'/settings.php';
