<x-mail::message>
@php
    $currencyCode = strtolower((string) ($order->store?->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
    $cancelReasons = $order->resolvedCancelReasons();
@endphp
# {{ __('mail_orders.cancelled.heading') }}

{{ __('mail_orders.greeting', ['name' => $order->customer_name]) }}

{{ __('mail_orders.cancelled.intro', ['store' => $order->store?->name ?? config('app.name')]) }}

- {{ __('mail_orders.fields.order_no') }}{{ $order->order_no }}
- {{ __('mail_orders.fields.order_type') }}{{ $order->order_type === 'takeout' ? __('mail_orders.order_type.takeout') : __('mail_orders.order_type.dine_in') }}
- {{ __('mail_orders.fields.status') }}{{ \App\Models\Order::customerStatusLabelByLocale($order->status, $order->payment_status, app()->getLocale()) }}
- {{ __('mail_orders.fields.total') }}{{ $currencySymbol }} {{ number_format((int) $order->total) }}

@if (!empty($cancelReasons))
{{ __('mail_orders.fields.cancel_reason') }}
@foreach ($cancelReasons as $cancelReason)
- {{ $cancelReason }}
@endforeach
@endif

<x-mail::button :url="route('customer.order.success', ['store' => $order->store->slug, 'order' => $order->uuid])">
{{ __('mail_orders.cta.view_order') }}
</x-mail::button>

{{ __('mail_orders.footer.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
