<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const SUPPORTED = ['zh_TW', 'zh_CN', 'en', 'vi'];
    private const LOCALE_COOKIE_KEY = 'locale';
    private const LOCALE_COOKIE_MINUTES = 525600; // 365 days

    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, self::SUPPORTED, true)) {
            session(['locale' => $locale]);

            return redirect()
                ->back(fallback: route('home'))
                ->cookie(self::LOCALE_COOKIE_KEY, $locale, self::LOCALE_COOKIE_MINUTES);
        }

        return redirect()->back(fallback: route('home'));
    }
}
