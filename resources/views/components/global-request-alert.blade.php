<div id="global-request-alert"
     class="fixed inset-0 z-[180] hidden items-center justify-center bg-slate-950/55 px-4 py-6"
     role="dialog"
     aria-modal="true"
     aria-labelledby="global-request-alert-title"
     data-title-error="{{ __('errors.request_failed') }}"
     data-title-success="{{ __('errors.request_success') }}"
     data-subtitle="{{ __('errors.request_subtitle') }}"
     data-network-error="{{ __('errors.request_network_error') }}"
     data-action-failed="{{ __('errors.request_action_failed') }}"
     data-action-completed="{{ __('errors.request_action_completed') }}"
     data-working="{{ __('errors.request_working') }}"
     data-http-error-template="{{ __('errors.request_http_error', ['status' => '__STATUS__', 'status_text' => '__STATUS_TEXT__']) }}">
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p id="global-request-alert-title" data-global-request-alert-title class="text-base font-bold text-slate-900">
                        {{ __('errors.request_failed') }}
                    </p>
                    <p data-global-request-alert-subtitle class="mt-1 text-xs text-slate-500">
                        {{ __('errors.request_subtitle') }}
                    </p>
                </div>
                <button type="button"
                        data-global-request-alert-close
                        class="rounded-full p-2 text-slate-500 hover:bg-slate-100"
                        aria-label="{{ __('errors.request_close') }}">
                    x
                </button>
            </div>
        </div>

        <div class="px-5 py-5">
            <p data-global-request-alert-message class="whitespace-pre-wrap break-words text-sm leading-6 text-slate-700"></p>
        </div>

        <div class="flex justify-end border-t border-slate-200 px-5 py-4">
            <button type="button"
                    data-global-request-alert-ok
                    class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-500">
                {{ __('errors.request_ok') }}
            </button>
        </div>
    </div>
</div>
