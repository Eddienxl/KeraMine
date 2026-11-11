<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    private const ALLOWED = ['id', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, self::ALLOWED, true)) {
            $request->session()->put('locale', $requested);
        }

        $locale = $request->session()->get('locale', config('app.locale', 'id'));
        if (! is_string($locale) || ! in_array($locale, self::ALLOWED, true)) {
            $locale = 'id';
        }

        App::setLocale($locale);

        return $next($request);
    }
}

