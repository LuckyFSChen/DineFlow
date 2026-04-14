<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            基本資料
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            更新你的帳號姓名與 Email。
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="role" value="帳號角色" />
            <x-text-input id="role" type="text" class="mt-1 block w-full bg-slate-50" :value="strtoupper((string) $user->role)" readonly />
        </div>

        @if($user->isMerchant())
            <div>
                <x-input-label for="subscription_ends_at" value="訂閱到期日" />
                <x-text-input id="subscription_ends_at" type="text" class="mt-1 block w-full bg-slate-50" :value="$user->subscription_ends_at ? $user->subscription_ends_at->format('Y-m-d H:i') : '尚未啟用'" readonly />
            </div>
        @endif

        <div>
            <x-input-label for="name" value="姓名" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        你的 Email 尚未驗證。

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            點這裡重新寄送驗證信。
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            已重新寄送驗證信到你的 Email。
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>儲存變更</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >已儲存。</p>
            @endif
        </div>
    </form>
</section>
