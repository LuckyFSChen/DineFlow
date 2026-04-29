<?php

namespace App\Http\Requests\Auth;

use App\Support\LoginCaptcha;
use App\Support\PhoneFormatter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string'],
            'captcha_answer' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! LoginCaptcha::isValid($this, is_scalar($value) ? (string) $value : null)) {
                        LoginCaptcha::refresh($this);
                        $fail(trans('auth.captcha_invalid'));
                    }
                },
            ],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $normalizedPhone = PhoneFormatter::digitsOnly((string) $this->input('phone'), 32);

        if ($normalizedPhone === null || ! Auth::attempt([
            'phone' => $normalizedPhone,
            'role' => 'customer',
            'password' => (string) $this->input('password'),
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 30);
            LoginCaptcha::refresh($this);

            throw ValidationException::withMessages([
                'phone' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        LoginCaptcha::clear($this);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 10)) {
            return;
        }

        event(new Lockout($this));
        LoginCaptcha::refresh($this);

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'phone' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $normalizedPhone = PhoneFormatter::digitsOnly((string) $this->input('phone'), 32) ?? (string) $this->input('phone');

        return Str::transliterate(Str::lower($normalizedPhone).'|'.$this->ip());
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        LoginCaptcha::refresh($this);

        parent::failedValidation($validator);
    }
}
