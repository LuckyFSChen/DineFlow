<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('admin.login.store') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('auth.Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('auth.Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="captcha_answer" :value="__('auth.captcha_label')" />
            <p class="mt-1 text-sm text-gray-600">{{ session('auth_login_captcha_question', __('auth.captcha_fallback_question')) }}</p>
            <x-text-input
                id="captcha_answer"
                class="block mt-1 w-full"
                type="text"
                name="captcha_answer"
                required
                autocomplete="off"
                inputmode="numeric"
                :placeholder="__('auth.captcha_placeholder')"
            />
            <x-input-error :messages="$errors->get('captcha_answer')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-primary shadow-sm focus:ring-brand-highlight" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('auth.Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-brand-primary hover:text-brand-dark rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-highlight" href="{{ route('password.request') }}">
                    {{ __('auth.Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('auth.Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
