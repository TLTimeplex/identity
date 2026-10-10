<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\Hydra\LoginService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Http\Responses\LoginResponse as DefaultLoginResponse;
use Laravel\Fortify\Http\Responses\TwoFactorLoginResponse as DefaultTwoFactorLoginResponse;
use Symfony\Component\HttpFoundation\Response;

class HydraLoginResponse implements LoginResponse, TwoFactorLoginResponse
{
    public function __construct(
        private readonly LoginService $loginService,
        private readonly bool $twoFactor = false,
    ) {}

    public function toResponse($request): Response
    {
        $challenge = $request->session()->get('hydra.login_challenge');

        if (! is_string($challenge) || $challenge === '') {
            return $this->defaultResponse($request);
        }

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $result = $this->loginService->accept($challenge, $user);
        $redirectTo = $result['redirect_to'] ?? null;

        abort_unless(
            is_string($redirectTo)
            && filter_var($redirectTo, FILTER_VALIDATE_URL)
            && in_array(
                parse_url($redirectTo, PHP_URL_SCHEME),
                ['http', 'https'],
                true,
            ),
            502,
            'Hydra returned an invalid redirect URL.',
        );

        $request->session()->forget('hydra.login_challenge');

        return Inertia::location($redirectTo);
    }

    private function defaultResponse(Request $request): Response
    {
        if ($this->twoFactor) {
            return (new DefaultTwoFactorLoginResponse)
                ->toResponse($request);
        }

        return (new DefaultLoginResponse)
            ->toResponse($request);
    }
}
