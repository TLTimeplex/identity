<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasEnabledTwoFactorAuthentication()) {
            if ($request->expectsJson()) {
                abort(403, 'Two-factor authentication must be enabled.');
            }

            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
