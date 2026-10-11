<?php

namespace App\Providers;

use App\Http\Responses\HydraLoginResponse;
use App\Models\User;
use App\Services\Hydra\HydraClient;
use App\Services\Hydra\LoginService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            LoginResponseContract::class,
            fn ($app) => new HydraLoginResponse(
                $app->make(LoginService::class),
            ),
        );

        $this->app->singleton(
            TwoFactorLoginResponseContract::class,
            fn ($app) => new HydraLoginResponse(
                $app->make(LoginService::class),
                twoFactor: true,
            ),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->revokeHydraLoginSessionsOnLogout();
    }

    private function revokeHydraLoginSessionsOnLogout(): void
    {
        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            try {
                app(HydraClient::class)->delete(
                    '/admin/oauth2/auth/sessions/login',
                    ['subject' => (string) $event->user->oidc_subject],
                );
            } catch (\Throwable $exception) {
                Log::warning(
                    'Unable to revoke Hydra login sessions during local logout.',
                    [
                        'user_id' => $event->user->getAuthIdentifier(),
                        'exception' => $exception,
                    ],
                );
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        if (app()->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceHttps();
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
