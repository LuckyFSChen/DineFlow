<x-mail::message>
# 您的訂單已完成

您好{{ filled($order->customer_name) ? '，' . $order->customer_name : '' }}：

您在 **{{ $order->store?->name ?? config('app.name') }}** 的訂單已完成，歡迎前往取餐。

- 訂單編號：{{ $order->order_no }}
- 訂單類型：{{ $order->order_type === 'takeout' ? '外帶' : '內用' }}
- 目前狀態：{{ $order->customer_status_label }}
- 訂單金額：NT$ {{ number_format((int) $order->total) }}

<x-mail::button :url="route('customer.order.success', ['store' => $order->store->slug, 'order' => $order->uuid])">
查看訂單
</x-mail::button>

感謝您的訂購，<br>
{{ config('app.name') }}
</x-mail::message>
