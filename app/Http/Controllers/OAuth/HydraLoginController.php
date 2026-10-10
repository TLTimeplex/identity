<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Hydra\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\Ulid;

class HydraLoginController extends Controller
{
    public function __invoke(
        Request $request,
        LoginService $loginService,
    ): RedirectResponse {
        $challenge = $request->query('login_challenge');

        abort_unless(
            is_string($challenge) && $challenge !== '',
            400,
            'Missing login challenge.',
        );

        $loginRequest = $loginService->getRequest($challenge);

        if (($loginRequest['skip'] ?? false) === true) {
            return $this->handleSkippedLogin(
                $request,
                $loginService,
                $challenge,
                $loginRequest,
            );
        }

        $user = $request->user();

        if ($user instanceof User) {
            return $this->acceptLogin(
                $loginService,
                $challenge,
                $user,
            );
        }

        $request->session()->put(
            'hydra.login_challenge',
            $challenge,
        );

        return redirect()->route('login');
    }

    /**
     * @param  array<string, mixed>  $loginRequest
     */
    private function handleSkippedLogin(
        Request $request,
        LoginService $loginService,
        string $challenge,
        array $loginRequest,
    ): RedirectResponse {
        $subject = $loginRequest['subject'] ?? null;

        if (! \is_string($subject) || $subject === '') {
            abort(502, 'Hydra returned no subject for a skipped login.');
        }

        try {
            $ulid = Ulid::fromBase32($subject);
        } catch (\InvalidArgumentException) {
            abort(502, 'Hydra returned an invalid OIDC subject.');
        }

        $user = User::query()
            ->where('oidc_subject', $ulid)
            ->first();

        if (! $user) {
            abort(
                403,
                'The Hydra session refers to an unknown local user.',
            );
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->acceptLogin($loginService, $challenge, $user);
    }

    private function acceptLogin(
        LoginService $loginService,
        string $challenge,
        User $user,
    ): RedirectResponse {
        $result = $loginService->accept($challenge, $user);

        $redirectTo = $result['redirect_to'] ?? null;

        abort_unless(
            is_string($redirectTo)
                && $this->isTrustedHydraRedirect($redirectTo),
            502,
            'Hydra returned an invalid redirect URL.',
        );

        return redirect()->away($redirectTo);
    }

    private function isTrustedHydraRedirect(string $url): bool
    {
        $target = parse_url($url);
        $issuer = parse_url((string) config('hydra.public_url'));

        return \is_array($target)
            && \is_array($issuer)
            && ($target['scheme'] ?? null) === ($issuer['scheme'] ?? null)
            && isset($target['host'], $issuer['host'])
            && strtolower($target['host']) === strtolower($issuer['host'])
            && ($target['port'] ?? null) === ($issuer['port'] ?? null);
    }
}
