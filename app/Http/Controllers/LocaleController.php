<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const SUPPORTED = ['zh_TW', 'en', 'vi'];

    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, self::SUPPORTED, true)) {
            session(['locale' => $locale]);
        }

        return redirect()->back(fallback: route('home'));
    }
}
