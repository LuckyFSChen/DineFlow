<x-mail::message>
# {{ __('mail_orders.store_new_order.heading') }}

{{ __('mail_orders.store_new_order.intro') }}

<x-mail::button :url="''">
{{ __('mail_orders.store_new_order.cta') }}
</x-mail::button>

{{ __('mail_orders.footer.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
