<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Banned accounts are logged out on their next request rather than left with a
 * live session that keeps hitting the AI endpoint.
 */
class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBanned()) {
            $reason = $user->ban_reason;

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => $reason
                    ? "This account has been suspended: {$reason}"
                    : 'This account has been suspended. Contact support if you think this is a mistake.',
            ]);
        }

        return $next($request);
    }
}
