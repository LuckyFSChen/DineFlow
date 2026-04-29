<x-mail::message>
# {{ __('mail_admin.merchant_registered.heading') }}

{{ __('mail_admin.merchant_registered.intro') }}

- {{ __('mail_admin.fields.name') }}: {{ $merchant->name }}
- {{ __('mail_admin.fields.email') }}: {{ $merchant->email ?? __('mail_admin.empty_value') }}
- {{ __('mail_admin.fields.phone') }}: {{ $merchant->phone ?? __('mail_admin.empty_value') }}
- {{ __('mail_admin.fields.region') }}: {{ strtoupper((string) ($merchant->merchant_region ?? __('mail_admin.empty_value'))) }}
- {{ __('mail_admin.fields.user_id') }}: {{ $merchant->id }}
- {{ __('mail_admin.fields.registered_at') }}: {{ $registeredAt->format('Y-m-d H:i:s') }}

{{ __('mail_admin.footer.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
