<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['zh_TW', 'zh_CN', 'en', 'vi'];
    private const LOCALE_COOKIE_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $defaultLocale = in_array(config('app.locale'), self::SUPPORTED, true)
            ? (string) config('app.locale')
            : 'zh_TW';

        $locale = session('locale');

        if (! is_string($locale) || ! in_array($locale, self::SUPPORTED, true)) {
            $cookieLocale = $request->cookie(self::LOCALE_COOKIE_KEY);
            $locale = is_string($cookieLocale) && in_array($cookieLocale, self::SUPPORTED, true)
                ? $cookieLocale
                : $defaultLocale;

            // Keep session and cookie in sync after first request in a session.
            session(['locale' => $locale]);
        }

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = $defaultLocale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
