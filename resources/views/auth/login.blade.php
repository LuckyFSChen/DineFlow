<x-guest-layout>
    @php
        $allowedTypes = ['customer', 'merchant'];
        $requestedDefault = (string) ($defaultAccountType ?? 'customer');
        if ($requestedDefault === 'backend_staff') {
            $requestedDefault = 'merchant';
        }
        $defaultType = in_array($requestedDefault, $allowedTypes, true) ? $requestedDefault : 'customer';
        $selectedAccountType = old('account_type', $defaultType);

        if (! old('account_type') && ! in_array($selectedAccountType, $allowedTypes, true)) {
            $selectedAccountType = $defaultType;
        }

        if (! old('account_type')) {
            if ($errors->has('email')) {
                $selectedAccountType = 'merchant';
            } elseif ($errors->has('phone')) {
                $selectedAccountType = 'customer';
            }
        }

        $isCustomerSelected = $selectedAccountType === 'customer';
        $formAction = $isCustomerSelected ? route('login') : route('admin.login.store');
    @endphp

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(session('warning'))
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ session('warning') }}
        </div>
    @endif

    <div class="mb-4 rounded-lg border border-brand-soft bg-brand-soft/20 px-4 py-3 text-sm text-brand-dark">
        {{ __('auth.first_login_password_hint') }}
    </div>

    <form method="POST" action="{{ $formAction }}" id="login-form">
        @csrf

        <input type="hidden" name="account_type" id="account_type" value="{{ $selectedAccountType }}">

        <div>
            <x-input-label for="login_account_type" :value="__('auth.Account Type')" />
            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 p-3">
                    <input type="radio" id="login_account_type_customer" name="login_account_type" value="customer" class="mt-1" {{ $selectedAccountType === 'customer' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">{{ __('auth.customer_type') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('auth.customer_desc') }}</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 p-3">
                    <input type="radio" id="login_account_type_merchant" name="login_account_type" value="merchant" class="mt-1" {{ $selectedAccountType === 'merchant' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">{{ __('auth.merchant_staff_type') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('auth.merchant_staff_desc') }}</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="mt-4 {{ $isCustomerSelected ? '' : 'hidden' }}" id="phone-wrap">
            <x-input-label for="phone" :value="__('auth.Phone')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" :required="$isCustomerSelected" :autofocus="$isCustomerSelected" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="mt-4 {{ $isCustomerSelected ? 'hidden' : '' }}" id="email-wrap">
            <x-input-label for="email" :value="__('auth.Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" :required="! $isCustomerSelected" :autofocus="! $isCustomerSelected" autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

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
                required
                autocomplete="off"
                inputmode="numeric"
                :placeholder="__('auth.captcha_placeholder')"
            />
            <x-input-error :messages="$errors->get('captcha_answer')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-primary shadow-sm focus:ring-brand-highlight" name="remember" @checked(old('remember'))>
                <span class="ms-2 text-sm text-gray-600">{{ __('auth.Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4 gap-3">
            <a
                class="{{ $isCustomerSelected ? 'hidden' : '' }} underline text-sm text-brand-primary hover:text-brand-dark rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-highlight"
                href="{{ route('password.request') }}"
                id="forgot-password-link"
            >
                {{ __('auth.Forgot your password?') }}
            </a>

            <x-primary-button>
                {{ __('auth.Log in') }}
            </x-primary-button>
        </div>
    </form>

    <script>
    (() => {
        const form = document.getElementById('login-form');
        const accountTypeInput = document.getElementById('account_type');
        const accountTypeRadios = Array.from(document.querySelectorAll('input[name="login_account_type"]'));
        const phoneWrap = document.getElementById('phone-wrap');
        const emailWrap = document.getElementById('email-wrap');
        const phoneInput = document.getElementById('phone');
        const emailInput = document.getElementById('email');
        const forgotPasswordLink = document.getElementById('forgot-password-link');

        if (!form || !accountTypeInput || accountTypeRadios.length === 0 || !phoneWrap || !emailWrap || !phoneInput || !emailInput || !forgotPasswordLink) {
            return;
        }

        const customerLoginAction = @json(route('login'));
        const merchantLoginAction = @json(route('admin.login.store'));

        const syncByType = (accountType) => {
            const isCustomer = accountType === 'customer';

            accountTypeInput.value = isCustomer ? 'customer' : 'merchant';
            form.action = isCustomer ? customerLoginAction : merchantLoginAction;

            phoneWrap.classList.toggle('hidden', !isCustomer);
            emailWrap.classList.toggle('hidden', isCustomer);
            forgotPasswordLink.classList.toggle('hidden', isCustomer);

            phoneInput.required = isCustomer;
            emailInput.required = !isCustomer;
        };

        const applyCurrentSelection = () => {
            const selected = accountTypeRadios.find((radio) => radio.checked)?.value || 'customer';
            syncByType(selected);
        };

        accountTypeRadios.forEach((radio) => {
            radio.addEventListener('change', applyCurrentSelection);
        });

        applyCurrentSelection();
    })();
    </script>
</x-guest-layout>
