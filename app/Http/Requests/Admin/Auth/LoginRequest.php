<?php

namespace App\Http\Requests\Admin\Auth;

use App\Models\User;
use App\Support\LoginCaptcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'email' => ['required', 'string', 'email', 'max:255'],
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

        $email = Str::lower((string) $this->input('email'));
        $password = (string) $this->input('password');

        $rolePriority = [
            'admin' => 1,
            'merchant' => 2,
            'chef' => 3,
            'cashier' => 4,
        ];

        $candidates = User::query()
            ->where('email', $email)
            ->whereIn('role', array_keys($rolePriority))
            ->get()
            ->sortBy(fn (User $user) => $rolePriority[(string) $user->role] ?? 99);

        foreach ($candidates as $candidate) {
            if (! Hash::check($password, (string) $candidate->password)) {
                continue;
            }

            Auth::login($candidate, $this->boolean('remember'));
            RateLimiter::clear($this->throttleKey());
            LoginCaptcha::clear($this);
            return;
        }

        RateLimiter::hit($this->throttleKey(), 30);
        LoginCaptcha::refresh($this);

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
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
            'email' => trans('auth.throttle', [
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
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
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
