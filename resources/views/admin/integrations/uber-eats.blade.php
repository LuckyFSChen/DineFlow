@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-6xl px-6 py-10 lg:px-8">
        <x-backend-header
            title="{{ __('uber_eats.integration_test_title') }}"
            subtitle="{{ __('uber_eats.integration_test_subtitle') }}"
        />

        @if (session('success'))
            <div data-uber-switch-alert class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @else
            <div data-uber-switch-alert class="mb-6 hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"></div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ __('uber_eats.environment') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('uber_eats.current_mode') }}: <span data-uber-mode-label class="font-bold text-slate-800">{{ strtoupper($mode) }}</span></p>
                    </div>
                    <span data-uber-mode-badge class="rounded-full px-3 py-1 text-xs font-bold {{ $mode === 'sandbox' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $mode }}
                    </span>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-600">{{ __('uber_eats.api_base_url') }}</dt>
                        <dd data-uber-api-base-url class="mt-1 break-all rounded-lg bg-slate-50 px-3 py-2 text-slate-800">{{ $apiBaseUrl }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-600">{{ __('uber_eats.auth_url') }}</dt>
                        <dd data-uber-auth-url class="mt-1 break-all rounded-lg bg-slate-50 px-3 py-2 text-slate-800">{{ $authUrl }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-600">{{ __('uber_eats.scopes') }}</dt>
                        <dd data-uber-scopes class="mt-1 break-all rounded-lg bg-slate-50 px-3 py-2 text-slate-800">{{ $scopes }}</dd>
                    </div>
                </dl>

                <form method="POST"
                      action="{{ route('super-admin.integrations.uber-eats.switch') }}"
                      class="mt-5 grid gap-3 sm:grid-cols-2"
                      data-uber-environment-switch
                      data-loading-text="{{ __('errors.request_working') }}"
                      data-no-global-ajax="true">
                    @csrf
                    <button type="submit" name="mode" value="sandbox"
                            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900 hover:bg-amber-100">
                        {{ __('uber_eats.switch_to_sandbox') }}
                    </button>
                    <button type="submit" name="mode" value="production"
                            class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-900 hover:bg-emerald-100">
                        {{ __('uber_eats.switch_to_production') }}
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">{{ __('uber_eats.connection_test') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('uber_eats.connection_test_desc') }}</p>

                <form method="POST" action="{{ route('super-admin.integrations.uber-eats.test') }}" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]">
                    @csrf
                    <select name="store_id"
                            class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((int) $selectedStoreId === (int) $store->id)>
                                {{ $store->name }} | {{ $store->uber_eats_store_id }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-500">
                        {{ __('uber_eats.run_test') }}
                    </button>
                </form>

                @if ($errors->any())
                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($testResult)
                    <div class="mt-5 rounded-xl border {{ $testResult['ok'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold {{ $testResult['ok'] ? 'text-emerald-900' : 'text-rose-900' }}">
                                    {{ $testResult['ok'] ? __('uber_eats.test_passed') : __('uber_eats.test_attention') }}
                                </p>
                                <p class="mt-1 text-xs text-slate-600">{{ $testResult['store'] }} | {{ strtoupper($testResult['mode']) }}</p>
                            </div>
                            <p class="text-xs text-slate-500">{{ $testResult['finished_at'] }}</p>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($testResult['steps'] as $step)
                                <div class="rounded-lg bg-white px-3 py-3 shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $step['ok'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        <p class="text-sm font-bold text-slate-800">{{ $step['name'] }}</p>
                                    </div>
                                    <p class="mt-2 break-words text-xs text-slate-600">{{ $step['message'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        </div>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ __('uber_eats.platform_credentials_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('uber_eats.platform_credentials_desc') }}</p>
            </div>

            <form method="POST" action="{{ route('super-admin.integrations.uber-eats.credentials') }}" class="mt-5 grid gap-4 lg:grid-cols-2">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('uber_eats.client_id') }}</label>
                    <input type="text"
                           name="client_id"
                           value="{{ old('client_id', $platformCredentials['client_id'] ?? '') }}"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('uber_eats.scopes') }}</label>
                    <input type="text"
                           name="scopes"
                           value="{{ old('scopes', $scopes) }}"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('uber_eats.client_secret') }}</label>
                    <input type="password"
                           name="client_secret"
                           placeholder="{{ ($platformCredentials['has_client_secret'] ?? false) ? __('uber_eats.client_secret_keep') : __('uber_eats.client_secret_placeholder') }}"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <p class="mt-1 text-[11px] text-slate-500">
                        {{ ($platformCredentials['has_client_secret'] ?? false) ? __('uber_eats.client_secret_stored') : __('uber_eats.platform_client_secret_enter') }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('uber_eats.webhook_signing_key') }}</label>
                    <input type="password"
                           name="webhook_signing_key"
                           placeholder="{{ ($platformCredentials['has_webhook_signing_key'] ?? false) ? __('uber_eats.webhook_signing_key_keep') : __('uber_eats.webhook_signing_key_placeholder') }}"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <p class="mt-1 text-[11px] text-slate-500">
                        {{ ($platformCredentials['has_webhook_signing_key'] ?? false) ? __('uber_eats.webhook_signing_key_stored') : __('uber_eats.platform_webhook_signing_key_enter') }}
                    </p>
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('uber_eats.webhook_url') }}</label>
                    <input type="text"
                           value="{{ route('webhooks.uber-eats') }}"
                           readonly
                           class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    <p class="mt-1 text-[11px] text-slate-500">{{ __('uber_eats.webhook_url_help') }}</p>
                </div>

                <div class="lg:col-span-2">
                    <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">
                        {{ __('uber_eats.save_platform_credentials') }}
                    </button>
                </div>
            </form>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">{{ __('uber_eats.queue_status') }}</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">{{ __('uber_eats.uber_eats_jobs') }}</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ $queueStats['uber_eats_jobs'] }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">{{ __('uber_eats.failed_jobs') }}</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ $queueStats['failed_jobs'] }}</p>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-500">{{ __('uber_eats.worker_hint') }}</p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">{{ __('uber_eats.latest_webhooks') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-bold uppercase text-slate-500">
                                <th class="py-2 pr-3">{{ __('uber_eats.event') }}</th>
                                <th class="py-2 pr-3">{{ __('uber_eats.type') }}</th>
                                <th class="py-2 pr-3">{{ __('uber_eats.store') }}</th>
                                <th class="py-2 pr-3">{{ __('uber_eats.status') }}</th>
                                <th class="py-2 pr-3">{{ __('uber_eats.processed') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($latestEvents as $event)
                                <tr>
                                    <td class="max-w-[180px] truncate py-2 pr-3 font-mono text-xs text-slate-700">{{ $event->event_id }}</td>
                                    <td class="py-2 pr-3 text-slate-700">{{ $event->event_type }}</td>
                                    <td class="max-w-[160px] truncate py-2 pr-3 font-mono text-xs text-slate-600">{{ $event->uber_store_id }}</td>
                                    <td class="py-2 pr-3">
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">{{ $event->status }}</span>
                                    </td>
                                    <td class="py-2 pr-3 text-xs text-slate-500">{{ optional($event->processed_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
                                </tr>
                                @if ($event->error_message)
                                    <tr>
                                        <td colspan="5" class="pb-2 text-xs text-rose-600">{{ $event->error_message }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-sm text-slate-500">{{ __('uber_eats.no_webhooks') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.querySelector('[data-uber-environment-switch]');
    if (!form) {
        return;
    }

    const alertBox = document.querySelector('[data-uber-switch-alert]');
    const modeLabel = document.querySelector('[data-uber-mode-label]');
    const modeBadge = document.querySelector('[data-uber-mode-badge]');
    const apiBaseUrl = document.querySelector('[data-uber-api-base-url]');
    const authUrl = document.querySelector('[data-uber-auth-url]');
    const scopes = document.querySelector('[data-uber-scopes]');

    const setBadgeMode = (mode) => {
        if (!modeBadge) {
            return;
        }

        modeBadge.textContent = mode;
        modeBadge.classList.remove('bg-amber-100', 'text-amber-800', 'bg-emerald-100', 'text-emerald-800');
        modeBadge.classList.add(
            mode === 'sandbox' ? 'bg-amber-100' : 'bg-emerald-100',
            mode === 'sandbox' ? 'text-amber-800' : 'text-emerald-800',
        );
    };

    const showInlineAlert = (message, isError = false) => {
        if (!alertBox) {
            return;
        }

        alertBox.textContent = message;
        alertBox.classList.remove('hidden', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-800', 'border-rose-200', 'bg-rose-50', 'text-rose-800');
        alertBox.classList.add(
            isError ? 'border-rose-200' : 'border-emerald-200',
            isError ? 'bg-rose-50' : 'bg-emerald-50',
            isError ? 'text-rose-800' : 'text-emerald-800',
        );
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        const originalText = submitter instanceof HTMLButtonElement ? submitter.textContent : null;
        let formData;

        try {
            formData = new FormData(form, submitter || undefined);
        } catch (_error) {
            formData = new FormData(form);
        }

        if (submitter?.getAttribute('name') && !formData.has(submitter.getAttribute('name'))) {
            formData.append(submitter.getAttribute('name'), submitter.getAttribute('value') || '');
        }

        if (submitter instanceof HTMLButtonElement) {
            submitter.disabled = true;
            submitter.textContent = form.dataset.loadingText || originalText;
            submitter.classList.add('opacity-70', 'cursor-wait');
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => null);
            if (!response.ok || payload?.ok === false) {
                showInlineAlert(payload?.message || '{{ __('errors.request_action_failed') }}', true);
                return;
            }

            const mode = String(payload?.mode || '').toLowerCase();
            if (modeLabel) {
                modeLabel.textContent = mode.toUpperCase();
            }
            setBadgeMode(mode);
            if (apiBaseUrl) {
                apiBaseUrl.textContent = payload?.api_base_url || '';
            }
            if (authUrl) {
                authUrl.textContent = payload?.auth_url || '';
            }
            if (scopes) {
                scopes.textContent = payload?.scopes || '';
            }

            showInlineAlert(payload?.message || '{{ __('errors.request_action_completed') }}');
        } catch (_error) {
            showInlineAlert('{{ __('errors.request_network_error') }}', true);
        } finally {
            if (submitter instanceof HTMLButtonElement) {
                submitter.disabled = false;
                submitter.textContent = originalText;
                submitter.classList.remove('opacity-70', 'cursor-wait');
            }
        }
    });
})();
</script>
@endsection
