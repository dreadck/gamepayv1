<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->get('lang') 
            ?? Session::get('locale') 
            ?? $request->header('Accept-Language', 'ru');

        // Normalize locale
        if (in_array($locale, ['ru', 'uz'])) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        } else {
            App::setLocale('ru');
            Session::put('locale', 'ru');
        }

        return $next($request);
    }
}

