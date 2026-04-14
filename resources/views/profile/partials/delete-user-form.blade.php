<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            刪除帳號
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            帳號刪除後，相關資料將永久移除且無法復原。請先確認你已備份需要保留的資訊。
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >刪除帳號</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                確定要刪除帳號嗎？
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                此動作不可復原。請輸入你的密碼以確認刪除。
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="密碼" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="請輸入密碼"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    取消
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    確認刪除
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
