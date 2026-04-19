<x-mail::message>
# New Merchant Registration

A new merchant account has been registered.

- Name: {{ $merchant->name }}
- Email: {{ $merchant->email ?? '-' }}
- Phone: {{ $merchant->phone ?? '-' }}
- Region: {{ strtoupper((string) ($merchant->merchant_region ?? '-')) }}
- User ID: {{ $merchant->id }}
- Registered At: {{ $registeredAt->format('Y-m-d H:i:s') }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

