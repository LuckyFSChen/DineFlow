<x-mail::message>
# {{ __('merchant_inquiry.email_heading') }}

{{ __('merchant_inquiry.email_intro') }}

- {{ __('merchant_inquiry.field_name') }}: {{ $inquiry['name'] }}
- {{ __('merchant_inquiry.field_phone') }}: {{ $inquiry['phone'] }}
- {{ __('merchant_inquiry.field_email') }}: {{ $inquiry['email'] }}
- {{ __('merchant_inquiry.field_restaurant_name') }}: {{ $inquiry['restaurant_name'] }}
- {{ __('merchant_inquiry.field_status') }}: {{ __('merchant_inquiry.statuses.' . $inquiry['status']) }}
- {{ __('merchant_inquiry.field_country') }}: {{ __('merchant_inquiry.countries.' . $inquiry['country']) }}
- {{ __('merchant_inquiry.field_address') }}: {{ $inquiry['address'] }}
- {{ __('merchant_inquiry.field_contact_time') }}: {{ $inquiry['contact_time'] ?: __('merchant_inquiry.empty_value') }}
- {{ __('merchant_inquiry.field_message') }}: {{ $inquiry['message'] ?: __('merchant_inquiry.empty_value') }}
- {{ __('merchant_inquiry.email_submitted_at') }}: {{ $submittedAt->format('Y-m-d H:i:s') }}
- {{ __('merchant_inquiry.email_source') }}: {{ route('product.intro') }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
