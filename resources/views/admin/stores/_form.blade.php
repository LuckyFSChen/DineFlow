@csrf
@php
    $selectedCountryCode = strtolower((string) old('country_code', $store->country_code ?? 'tw'));
    $phoneDigits = $selectedCountryCode === 'cn' ? 11 : 10;
    $weekdays = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];
    $weekdayStorageMap = [
        'monday' => 'mon',
        'tuesday' => 'tue',
        'wednesday' => 'wed',
        'thursday' => 'thu',
        'friday' => 'fri',
        'saturday' => 'sat',
        'sunday' => 'sun',
    ];
    $storedWeeklyBusinessHours = is_array($store->weekly_business_hours ?? null) ? $store->weekly_business_hours : [];
    $storedWeeklyBreakHours = is_array($store->weekly_break_hours ?? null) ? $store->weekly_break_hours : [];
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.store_name') }}</label>
        <input type="text" name="name" value="{{ old('name', $store->name) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('name')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.phone') }}</label>
        <input type="text" name="phone" value="{{ old('phone', $store->phone) }}"
               placeholder="{{ __('admin.phone_placeholder', ['digits' => $phoneDigits]) }}"
               inputmode="numeric"
               maxlength="{{ $phoneDigits }}"
               pattern="[0-9]*"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <p class="mt-2 text-xs text-slate-500">{{ __('admin.phone_format_hint', ['digits' => $phoneDigits]) }}</p>
        @error('phone')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.address') }}</label>
        <input type="text" name="address" value="{{ old('address', $store->address) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('address')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.store_country') }}</label>
        <select name="country_code"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
            <option value="tw" @selected(old('country_code', $store->country_code ?? 'tw') === 'tw')>{{ __('admin.country_tw') }}</option>
            <option value="vn" @selected(old('country_code', $store->country_code ?? 'tw') === 'vn')>{{ __('admin.country_vn') }}</option>
            <option value="cn" @selected(old('country_code', $store->country_code ?? 'tw') === 'cn')>{{ __('admin.country_cn') }}</option>
            <option value="us" @selected(old('country_code', $store->country_code ?? 'tw') === 'us')>{{ __('admin.country_us') }}</option>
        </select>
        @error('country_code')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.currency') }}</label>
        <select name="currency"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
            <option value="twd" @selected(old('currency', $store->currency ?? 'twd') === 'twd')>{{ __('admin.currency_twd') }}</option>
            <option value="vnd" @selected(old('currency', $store->currency ?? 'twd') === 'vnd')>{{ __('admin.currency_vnd') }}</option>
            <option value="cny" @selected(old('currency', $store->currency ?? 'twd') === 'cny')>{{ __('admin.currency_cny') }}</option>
            <option value="usd" @selected(old('currency', $store->currency ?? 'twd') === 'usd')>{{ __('admin.currency_usd') }}</option>
        </select>
        @error('currency')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">Timezone</label>
        <select name="timezone"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
            <option value="Asia/Taipei" @selected(old('timezone', $store->timezone ?? 'Asia/Taipei') === 'Asia/Taipei')>Asia/Taipei</option>
            <option value="Asia/Ho_Chi_Minh" @selected(old('timezone', $store->timezone ?? 'Asia/Taipei') === 'Asia/Ho_Chi_Minh')>Asia/Ho_Chi_Minh</option>
            <option value="Asia/Shanghai" @selected(old('timezone', $store->timezone ?? 'Asia/Taipei') === 'Asia/Shanghai')>Asia/Shanghai</option>
            <option value="America/New_York" @selected(old('timezone', $store->timezone ?? 'Asia/Taipei') === 'America/New_York')>America/New_York</option>
        </select>
        @error('timezone')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.opening_time') }}</label>
        <input type="time" name="opening_time" value="{{ old('opening_time', $store->opening_time ? substr($store->opening_time, 0, 5) : '') }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <p class="mt-2 text-xs text-slate-500">{{ __('admin.opening_time_hint') }}</p>
        @error('opening_time')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.closing_time') }}</label>
        <input type="time" name="closing_time" value="{{ old('closing_time', $store->closing_time ? substr($store->closing_time, 0, 5) : '') }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <p class="mt-2 text-xs text-slate-500">{{ __('admin.closing_time_hint') }}</p>
        @error('closing_time')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
        <h3 class="text-sm font-semibold text-slate-800">{{ __('admin.business_hours_weekly_title') }}</h3>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin.business_hours_weekly_hint') }}</p>

        <div class="mt-4 grid gap-3">
            @foreach ($weekdays as $weekday)
                @php
                    $storageKey = $weekdayStorageMap[$weekday];
                    $storedSlot = is_array($storedWeeklyBusinessHours[$storageKey] ?? null) ? $storedWeeklyBusinessHours[$storageKey] : [];
                    $businessStartValue = old("business_hours.$weekday.start", isset($storedSlot['start']) ? substr((string) $storedSlot['start'], 0, 5) : '');
                    $businessEndValue = old("business_hours.$weekday.end", isset($storedSlot['end']) ? substr((string) $storedSlot['end'], 0, 5) : '');
                @endphp
                <div class="grid items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 md:grid-cols-[120px_1fr_1fr]">
                    <p class="pt-2 text-sm font-semibold text-slate-700">{{ __('admin.weekday_' . $weekday) }}</p>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.business_start_time') }}</label>
                        <input type="time"
                               name="business_hours[{{ $weekday }}][start]"
                               value="{{ $businessStartValue }}"
                               class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        @error("business_hours.$weekday.start")
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.business_end_time') }}</label>
                        <input type="time"
                               name="business_hours[{{ $weekday }}][end]"
                               value="{{ $businessEndValue }}"
                               class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        @error("business_hours.$weekday.end")
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
        <h3 class="text-sm font-semibold text-slate-800">{{ __('admin.break_hours_title') }}</h3>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin.break_hours_hint') }}</p>

        <div class="mt-4 grid gap-3">
            @foreach ($weekdays as $weekday)
                @php
                    $storageKey = $weekdayStorageMap[$weekday];
                    $storedSlot = is_array($storedWeeklyBreakHours[$storageKey] ?? null) ? $storedWeeklyBreakHours[$storageKey] : [];
                    $breakStartValue = old("break_hours.$weekday.start", isset($storedSlot['start']) ? substr((string) $storedSlot['start'], 0, 5) : '');
                    $breakEndValue = old("break_hours.$weekday.end", isset($storedSlot['end']) ? substr((string) $storedSlot['end'], 0, 5) : '');
                @endphp
                <div class="grid items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 md:grid-cols-[120px_1fr_1fr]">
                    <p class="pt-2 text-sm font-semibold text-slate-700">{{ __('admin.weekday_' . $weekday) }}</p>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.break_start_time') }}</label>
                        <input type="time"
                               name="break_hours[{{ $weekday }}][start]"
                               value="{{ $breakStartValue }}"
                               class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        @error("break_hours.$weekday.start")
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.break_end_time') }}</label>
                        <input type="time"
                               name="break_hours[{{ $weekday }}][end]"
                               value="{{ $breakEndValue }}"
                               class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        @error("break_hours.$weekday.end")
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.store_description') }}</label>
        <textarea name="description" rows="5"
                  class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('description', $store->description) }}</textarea>
        @error('description')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div id="uber-eats-integration" class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
        <div class="flex flex-col gap-1">
            <h3 class="text-sm font-semibold text-slate-800">{{ __('uber_eats.store_form_title') }}</h3>
            <p class="text-xs text-slate-500">{{ __('uber_eats.store_form_desc') }}</p>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="lg:col-span-2">
                <label class="inline-flex items-center gap-3">
                    <input type="checkbox"
                           name="uber_eats_enabled"
                           value="1"
                           {{ old('uber_eats_enabled', $store->uber_eats_enabled ?? false) ? 'checked' : '' }}
                           class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-semibold text-slate-700">{{ __('uber_eats.enable_auto_sync') }}</span>
                </label>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('uber_eats.store_id_label') }}</label>
                <input type="text"
                       name="uber_eats_store_id"
                       value="{{ old('uber_eats_store_id', $store->uber_eats_store_id) }}"
                       placeholder="{{ __('uber_eats.store_id_placeholder') }}"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <p class="mt-2 text-xs text-slate-500">{{ __('uber_eats.store_id_help') }}</p>
                @error('uber_eats_store_id')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('uber_eats.store_link') }}</label>
                <input type="url"
                       name="uber_eats_store_url"
                       value="{{ old('uber_eats_store_url', $store->uber_eats_store_url) }}"
                       placeholder="https://www.ubereats.com/store/..."
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <p class="mt-2 text-xs text-slate-500">{{ __('uber_eats.store_link_help') }}</p>
                @error('uber_eats_store_url')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('uber_eats.client_id') }}</label>
                <input type="text"
                       name="uber_eats_client_id"
                       value="{{ old('uber_eats_client_id', $store->uber_eats_client_id) }}"
                       placeholder="{{ __('uber_eats.client_id_placeholder') }}"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                @error('uber_eats_client_id')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('uber_eats.client_secret') }}</label>
                <input type="password"
                       name="uber_eats_client_secret"
                       value="{{ old('uber_eats_client_secret') }}"
                       placeholder="{{ ($store->uber_eats_has_client_secret ?? false) ? __('uber_eats.client_secret_keep') : __('uber_eats.client_secret_placeholder') }}"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <p class="mt-2 text-xs text-slate-500">
                    @if ($store->uber_eats_has_client_secret ?? false)
                        {{ __('uber_eats.client_secret_stored') }}
                    @else
                        {{ __('uber_eats.client_secret_enter') }}
                    @endif
                </p>
                @error('uber_eats_client_secret')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('uber_eats.webhook_signing_key') }}</label>
                <input type="password"
                       name="uber_eats_webhook_signing_key"
                       value="{{ old('uber_eats_webhook_signing_key') }}"
                       placeholder="{{ ($store->uber_eats_has_webhook_signing_key ?? false) ? __('uber_eats.webhook_signing_key_keep') : __('uber_eats.webhook_signing_key_placeholder') }}"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <p class="mt-2 text-xs text-slate-500">
                    @if ($store->uber_eats_has_webhook_signing_key ?? false)
                        {{ __('uber_eats.webhook_signing_key_stored') }}
                    @else
                        {{ __('uber_eats.webhook_signing_key_enter') }}
                    @endif
                </p>
                @error('uber_eats_webhook_signing_key')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('uber_eats.webhook_url') }}</label>
                <input type="text"
                       value="{{ route('webhooks.uber-eats') }}"
                       readonly
                       class="w-full rounded-2xl border border-slate-300 bg-slate-100 px-4 py-3 text-slate-600 focus:outline-none">
                <p class="mt-2 text-xs text-slate-500">{{ __('uber_eats.webhook_url_help') }}</p>
            </div>

            <div class="lg:col-span-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">{{ __('uber_eats.server_config') }}</p>
                <p class="mt-1 text-xs text-amber-800">{{ __('uber_eats.server_config_help') }}</p>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
        <div class="flex flex-col gap-1">
            <h3 class="text-sm font-semibold text-slate-800">Foodpanda Integration</h3>
            <p class="text-xs text-slate-500">設定 Foodpanda webhook 與 API 憑證後，Foodpanda 訂單會自動同步進餐廳板位，取消單與完單也會回寫到平台。</p>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="lg:col-span-2">
                <label class="inline-flex items-center gap-3">
                    <input type="checkbox"
                           name="foodpanda_enabled"
                           value="1"
                           {{ old('foodpanda_enabled', $store->foodpanda_enabled ?? false) ? 'checked' : '' }}
                           class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-semibold text-slate-700">啟用 Foodpanda 同步</span>
                </label>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Chain ID</label>
                <input type="text"
                       name="foodpanda_chain_id"
                       value="{{ old('foodpanda_chain_id', $store->foodpanda_chain_id) }}"
                       placeholder="例如 550e8400-e29b-41d4-a716-446655440000"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                @error('foodpanda_chain_id')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Store ID / Vendor ID</label>
                <input type="text"
                       name="foodpanda_store_id"
                       value="{{ old('foodpanda_store_id', $store->foodpanda_store_id) }}"
                       placeholder="Foodpanda 平台上的 store_id"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                @error('foodpanda_store_id')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">External Partner Config ID</label>
                <input type="text"
                       name="foodpanda_external_partner_config_id"
                       value="{{ old('foodpanda_external_partner_config_id', $store->foodpanda_external_partner_config_id) }}"
                       placeholder="建議填你的內部門市代碼"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <p class="mt-2 text-xs text-slate-500">如果 Partner Portal 有設定 internal store mapping，DineFlow 會優先用這個欄位對應門市。</p>
                @error('foodpanda_external_partner_config_id')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Webhook URL</label>
                <input type="text"
                       value="{{ route('webhooks.foodpanda.orders') }}"
                       readonly
                       class="w-full rounded-2xl border border-slate-300 bg-slate-100 px-4 py-3 text-slate-600 focus:outline-none">
                <p class="mt-2 text-xs text-slate-500">把這個網址填到 Foodpanda Partner Portal 的 webhook 設定。</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Client ID</label>
                <input type="text"
                       name="foodpanda_client_id"
                       value="{{ old('foodpanda_client_id', $store->foodpanda_client_id) }}"
                       placeholder="OAuth client id"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                @error('foodpanda_client_id')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Client Secret</label>
                <input type="text"
                       name="foodpanda_client_secret"
                       value="{{ old('foodpanda_client_secret', $store->foodpanda_client_secret) }}"
                       placeholder="OAuth client secret"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                @error('foodpanda_client_secret')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-700">Webhook Secret</label>
                <input type="text"
                       name="foodpanda_webhook_secret"
                       value="{{ old('foodpanda_webhook_secret', $store->foodpanda_webhook_secret) }}"
                       placeholder="Partner Portal 設定的 Authorization header 值"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <p class="mt-2 text-xs text-slate-500">Foodpanda 會把這個值原封不動放進 `Authorization` header，所以若你用 Basic Auth，就直接填 `Basic ...`。</p>
                @error('foodpanda_webhook_secret')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.banner_image') }}</label>

        {{-- Dropzone --}}
        <div id="dropzone"
            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition hover:border-indigo-400 hover:bg-indigo-50">

            <input type="file"
                id="banner-input"
                name="banner_image"
                accept="image/*"
                class="hidden">

            <p class="text-sm text-slate-600">
                {{ __('admin.banner_dropzone') }} <span class="font-semibold text-indigo-600">{{ __('admin.click_to_upload') }}</span>
            </p>
            <p class="mt-1 text-xs text-slate-400">
                {{ __('admin.banner_file_hint') }}
            </p>
        </div>

        {{-- Preview --}}
        <div id="preview-wrapper" class="mt-4 {{ $store->banner_image ? '' : 'hidden' }}" data-existing-banner-url="{{ $store->banner_image ? asset('storage/' . $store->banner_image) : '' }}">
            <canvas id="banner-crop-preview" width="1200" height="400" class="w-full rounded-xl border border-slate-300 bg-white"></canvas>
            <p id="banner-helper" class="mt-2 text-xs text-slate-500">尚未選擇橫幅</p>
            <div class="mt-3">
                <label for="banner-zoom" class="mb-1 block text-xs font-semibold text-slate-600">縮放</label>
                <input id="banner-zoom" type="range" min="1" max="3" step="0.05" value="1" class="w-full">
            </div>
            <div class="mt-2 flex flex-wrap gap-2">
                <button type="button" id="banner-reset" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">重設位置</button>
                <button type="button" id="banner-remove" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">{{ __('admin.remove') }}</button>
            </div>
            <p class="mt-2 text-[11px] text-slate-500">在預覽區拖曳可調整橫幅裁切範圍，儲存時會套用。</p>
        </div>

        @error('banner_image')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="inline-flex items-center gap-3">
            <input type="checkbox" name="is_active" value="1"
                   {{ old('is_active', $store->is_active) ? 'checked' : '' }}
                   class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-semibold text-slate-700">{{ __('admin.enable_store') }}</span>
        </label>
    </div>
</div>

<div class="mt-8 flex gap-3">
    <button type="submit"
            class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
        {{ __('admin.save') }}
    </button>

    <a href="{{ route('admin.stores.index') }}"
       class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
        {{ __('admin.back_to_list') }}
    </a>
</div>

<script>
(() => {
    const dropzone = document.getElementById('dropzone');
    const input = document.getElementById('banner-input');
    const wrapper = document.getElementById('preview-wrapper');
    const cropCanvas = document.getElementById('banner-crop-preview');
    const cropCtx = cropCanvas?.getContext('2d');
    const helper = document.getElementById('banner-helper');
    const zoomInput = document.getElementById('banner-zoom');
    const resetButton = document.getElementById('banner-reset');
    const removeButton = document.getElementById('banner-remove');
    const form = input?.closest('form');
    const maxUploadImageBytes = 2 * 1024 * 1024;
    const i18n = {
        imageOnly: @json(__('admin.error_image_only')),
        imageTooLarge: @json(__('admin.error_image_too_large')),
    };

    if (!dropzone || !input || !wrapper || !cropCanvas || !cropCtx || !zoomInput || !removeButton || !form) {
        return;
    }

    const state = {
        sourceImage: null,
        sourceObjectUrl: null,
        sourceFileName: 'banner.jpg',
        hasNewUpload: false,
        dirty: false,
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        dragging: false,
        lastX: 0,
        lastY: 0,
        isSubmittingPrepared: false,
    };

    const revokeObjectUrl = () => {
        if (!state.sourceObjectUrl) {
            return;
        }

        URL.revokeObjectURL(state.sourceObjectUrl);
        state.sourceObjectUrl = null;
    };

    const canvasToBlob = (canvas, mimeType, quality) => new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (blob) {
                resolve(blob);
                return;
            }

            reject(new Error('圖片轉換失敗'));
        }, mimeType, quality);
    });

    const clampOffsets = () => {
        if (!state.sourceImage) {
            return;
        }

        const baseScale = Math.max(
            cropCanvas.width / state.sourceImage.naturalWidth,
            cropCanvas.height / state.sourceImage.naturalHeight,
        );
        const drawWidth = state.sourceImage.naturalWidth * baseScale * state.zoom;
        const drawHeight = state.sourceImage.naturalHeight * baseScale * state.zoom;
        const minX = cropCanvas.width - drawWidth;
        const minY = cropCanvas.height - drawHeight;

        state.offsetX = Math.min(0, Math.max(minX, state.offsetX));
        state.offsetY = Math.min(0, Math.max(minY, state.offsetY));
    };

    const centerImage = () => {
        if (!state.sourceImage) {
            return;
        }

        const baseScale = Math.max(
            cropCanvas.width / state.sourceImage.naturalWidth,
            cropCanvas.height / state.sourceImage.naturalHeight,
        );
        const drawWidth = state.sourceImage.naturalWidth * baseScale * state.zoom;
        const drawHeight = state.sourceImage.naturalHeight * baseScale * state.zoom;
        state.offsetX = (cropCanvas.width - drawWidth) / 2;
        state.offsetY = (cropCanvas.height - drawHeight) / 2;
        clampOffsets();
    };

    const renderPreview = () => {
        cropCtx.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
        cropCtx.fillStyle = '#f8fafc';
        cropCtx.fillRect(0, 0, cropCanvas.width, cropCanvas.height);

        if (!state.sourceImage) {
            cropCtx.strokeStyle = '#cbd5e1';
            cropCtx.lineWidth = 2;
            cropCtx.strokeRect(8, 8, cropCanvas.width - 16, cropCanvas.height - 16);
            cropCtx.fillStyle = '#64748b';
            cropCtx.font = '24px sans-serif';
            cropCtx.textAlign = 'center';
            cropCtx.fillText('尚未選擇橫幅', cropCanvas.width / 2, cropCanvas.height / 2 + 8);
            return;
        }

        const baseScale = Math.max(
            cropCanvas.width / state.sourceImage.naturalWidth,
            cropCanvas.height / state.sourceImage.naturalHeight,
        );
        const drawWidth = state.sourceImage.naturalWidth * baseScale * state.zoom;
        const drawHeight = state.sourceImage.naturalHeight * baseScale * state.zoom;
        clampOffsets();
        cropCtx.drawImage(state.sourceImage, state.offsetX, state.offsetY, drawWidth, drawHeight);
    };

    const ensureBlobWithinLimit = async (canvas, maxBytes = maxUploadImageBytes) => {
        let quality = 0.92;
        let outputCanvas = canvas;
        let blob = await canvasToBlob(outputCanvas, 'image/jpeg', quality);

        while (blob.size > maxBytes && quality > 0.45) {
            quality = Math.max(0.45, quality - 0.08);
            blob = await canvasToBlob(outputCanvas, 'image/jpeg', quality);
        }

        while (blob.size > maxBytes && outputCanvas.width > 360 && outputCanvas.height > 120) {
            const nextCanvas = document.createElement('canvas');
            nextCanvas.width = Math.max(360, Math.floor(outputCanvas.width * 0.9));
            nextCanvas.height = Math.max(120, Math.floor(outputCanvas.height * 0.9));
            const nextCtx = nextCanvas.getContext('2d');
            if (!nextCtx) {
                break;
            }

            nextCtx.drawImage(outputCanvas, 0, 0, nextCanvas.width, nextCanvas.height);
            outputCanvas = nextCanvas;
            quality = Math.min(quality, 0.78);
            blob = await canvasToBlob(outputCanvas, 'image/jpeg', quality);
        }

        if (blob.size > maxBytes) {
            throw new Error(i18n.imageTooLarge);
        }

        return blob;
    };

    const clearPreview = () => {
        revokeObjectUrl();
        input.value = '';
        state.sourceImage = null;
        state.sourceFileName = 'banner.jpg';
        state.hasNewUpload = false;
        state.dirty = false;
        state.zoom = 1;
        state.offsetX = 0;
        state.offsetY = 0;
        state.dragging = false;
        zoomInput.value = '1';
        wrapper.classList.add('hidden');
        if (helper) {
            helper.textContent = '尚未選擇橫幅';
        }
        renderPreview();
    };

    const setImageFromUrl = (url) => {
        if (!url) {
            clearPreview();
            return;
        }

        revokeObjectUrl();
        const image = new Image();
        image.onload = () => {
            state.sourceImage = image;
            state.sourceFileName = 'banner.jpg';
            state.hasNewUpload = false;
            state.dirty = false;
            state.zoom = 1;
            zoomInput.value = '1';
            centerImage();
            wrapper.classList.remove('hidden');
            if (helper) {
                helper.textContent = '目前橫幅（可拖曳調整裁切）';
            }
            renderPreview();
        };
        image.onerror = clearPreview;
        image.src = url;
    };

    const setImageFromFile = (file) => {
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert(i18n.imageOnly);
            return;
        }

        revokeObjectUrl();
        const url = URL.createObjectURL(file);
        state.sourceObjectUrl = url;

        const image = new Image();
        image.onload = () => {
            state.sourceImage = image;
            state.sourceFileName = file.name || 'banner.jpg';
            state.hasNewUpload = true;
            state.dirty = false;
            state.zoom = 1;
            zoomInput.value = '1';
            centerImage();
            wrapper.classList.remove('hidden');
            if (helper) {
                helper.textContent = `已選擇：${file.name}`;
            }
            renderPreview();
        };
        image.onerror = () => {
            alert('圖片讀取失敗，請重新選擇。');
        };
        image.src = url;
    };

    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('border-indigo-500', 'bg-indigo-50');
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-indigo-500', 'bg-indigo-50');
    });
    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-indigo-500', 'bg-indigo-50');
        setImageFromFile(event.dataTransfer.files[0]);
    });
    input.addEventListener('change', (event) => {
        setImageFromFile(event.target.files[0]);
    });

    zoomInput.addEventListener('input', () => {
        if (!state.sourceImage) {
            return;
        }

        state.zoom = Number(zoomInput.value || '1');
        state.dirty = true;
        centerImage();
        renderPreview();
    });

    cropCanvas.addEventListener('mousedown', (event) => {
        if (!state.sourceImage) {
            return;
        }

        state.dragging = true;
        state.lastX = event.clientX;
        state.lastY = event.clientY;
    });
    cropCanvas.addEventListener('mousemove', (event) => {
        if (!state.dragging || !state.sourceImage) {
            return;
        }

        state.offsetX += event.clientX - state.lastX;
        state.offsetY += event.clientY - state.lastY;
        state.lastX = event.clientX;
        state.lastY = event.clientY;
        state.dirty = true;
        clampOffsets();
        renderPreview();
    });
    cropCanvas.addEventListener('mouseup', () => {
        state.dragging = false;
    });
    cropCanvas.addEventListener('mouseleave', () => {
        state.dragging = false;
    });
    cropCanvas.addEventListener('touchstart', (event) => {
        if (!state.sourceImage || event.touches.length === 0) {
            return;
        }

        const touch = event.touches[0];
        state.dragging = true;
        state.lastX = touch.clientX;
        state.lastY = touch.clientY;
    }, { passive: true });
    cropCanvas.addEventListener('touchmove', (event) => {
        if (!state.dragging || !state.sourceImage || event.touches.length === 0) {
            return;
        }

        const touch = event.touches[0];
        state.offsetX += touch.clientX - state.lastX;
        state.offsetY += touch.clientY - state.lastY;
        state.lastX = touch.clientX;
        state.lastY = touch.clientY;
        state.dirty = true;
        clampOffsets();
        renderPreview();
    }, { passive: true });
    cropCanvas.addEventListener('touchend', () => {
        state.dragging = false;
    }, { passive: true });
    cropCanvas.addEventListener('touchcancel', () => {
        state.dragging = false;
    }, { passive: true });

    resetButton?.addEventListener('click', () => {
        if (!state.sourceImage) {
            return;
        }

        state.zoom = 1;
        state.dirty = true;
        zoomInput.value = '1';
        centerImage();
        renderPreview();
    });
    removeButton.addEventListener('click', clearPreview);

    form.addEventListener('submit', async (event) => {
        if (state.isSubmittingPrepared) {
            return;
        }

        if (!state.sourceImage || (!state.hasNewUpload && !state.dirty)) {
            return;
        }

        event.preventDefault();
        try {
            const blob = await ensureBlobWithinLimit(cropCanvas, maxUploadImageBytes);
            const filename = (state.sourceFileName || 'banner.jpg').replace(/\.[^.]+$/, '.jpg');
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], filename, { type: 'image/jpeg' }));
            input.files = transfer.files;
            state.isSubmittingPrepared = true;
            form.submit();
        } catch (error) {
            alert(error?.message || i18n.imageTooLarge);
        }
    });

    const existingUrl = wrapper.getAttribute('data-existing-banner-url');
    if (existingUrl) {
        setImageFromUrl(existingUrl);
    } else {
        renderPreview();
    }
})();
</script>
