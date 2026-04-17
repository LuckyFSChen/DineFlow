<?php

namespace App\Support;

use Illuminate\Http\Request;

class LoginCaptcha
{
    private const QUESTION_KEY = 'auth_login_captcha_question';

    private const ANSWER_KEY = 'auth_login_captcha_answer';

    public static function refresh(Request $request): string
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $operator = random_int(0, 1) === 0 ? '+' : '-';

        if ($operator === '-' && $left < $right) {
            [$left, $right] = [$right, $left];
        }

        $answer = $operator === '+' ? $left + $right : $left - $right;
        $question = sprintf('%d %s %d = ?', $left, $operator, $right);

        $request->session()->put(self::QUESTION_KEY, $question);
        $request->session()->put(self::ANSWER_KEY, (string) $answer);

        return $question;
    }

    public static function question(Request $request): ?string
    {
        return $request->session()->get(self::QUESTION_KEY);
    }

    public static function isValid(Request $request, ?string $providedAnswer): bool
    {
        $expected = (string) $request->session()->get(self::ANSWER_KEY, '');
        $provided = trim((string) $providedAnswer);

        return $expected !== '' && hash_equals($expected, $provided);
    }

    public static function clear(Request $request): void
    {
        $request->session()->forget([self::QUESTION_KEY, self::ANSWER_KEY]);
    }
}
