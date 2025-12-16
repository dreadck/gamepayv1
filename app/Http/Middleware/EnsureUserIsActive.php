<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->is_banned) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', __('Your account has been banned.'));
        }

        if ($user->is_frozen) {
            return redirect()->route('dashboard')
                ->with('error', __('Your account is temporarily frozen.'));
        }

        return $next($request);
    }
}

