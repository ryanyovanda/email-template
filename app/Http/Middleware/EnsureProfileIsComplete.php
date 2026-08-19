<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Templates and applications are meaningless without a profile to render into
 * them, so unfinished accounts are sent back to onboarding.
 */
class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->profile()->exists()) {
            return redirect()->route('applicant-profile.edit')->with('status', 'Finish your profile first — it fills in every template.');
        }

        return $next($request);
    }
}
