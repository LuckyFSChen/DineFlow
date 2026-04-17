<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(session('warning'))
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ session('warning') }}
        </div>
    @endif

    <div class="mb-4 rounded-lg border border-brand-soft bg-brand-soft/20 px-4 py-3 text-sm text-brand-dark">
        {{ __('auth.first_login_password_hint') }}
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Phone -->
        <div>
            <x-input-label for="phone" :value="__('auth.Phone')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" required autofocus autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('auth.Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="mt-2 text-xs text-gray-500">{{ __('auth.first_login_password_hint') }}</p>
        </div>

        <div class="mt-4">
            <x-input-label for="captcha_answer" :value="__('auth.captcha_label')" />
            <p class="mt-1 text-sm text-gray-600">{{ session('auth_login_captcha_question', __('auth.captcha_fallback_question')) }}</p>
            <x-text-input
                id="captcha_answer"
                class="block mt-1 w-full"
                type="text"
                name="captcha_answer"
                :value="old('captcha_answer')"
                required
                autocomplete="off"
                inputmode="numeric"
                :placeholder="__('auth.captcha_placeholder')"
            />
            <x-input-error :messages="$errors->get('captcha_answer')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-primary shadow-sm focus:ring-brand-highlight" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('auth.Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('auth.Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
