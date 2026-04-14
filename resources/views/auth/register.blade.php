<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('auth.Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('auth.Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="account_type" :value="__('auth.Account Type')" />
            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 p-3">
                    <input type="radio" name="account_type" value="customer" class="mt-1" {{ old('account_type', 'customer') === 'customer' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">{{ __('auth.customer_type') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('auth.customer_desc') }}</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 p-3">
                    <input type="radio" name="account_type" value="merchant" class="mt-1" {{ old('account_type') === 'merchant' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">{{ __('auth.merchant_type') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('auth.merchant_desc') }}</span>
                    </span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('account_type')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('auth.Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('auth.Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-brand-primary hover:text-brand-dark rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-highlight" href="{{ route('login') }}">
                {{ __('auth.Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('auth.Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
