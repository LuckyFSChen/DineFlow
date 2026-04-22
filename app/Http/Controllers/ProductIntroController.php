<?php

namespace App\Http\Controllers;

use App\Mail\MerchantInquiryNotificationMail;
use App\Support\SubscriptionPlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class ProductIntroController extends Controller
{
    public function show(SubscriptionPlanCatalog $catalog): View
    {
        return view('product-intro', [
            'plansByTier' => $catalog->activePlansByTier(),
        ]);
    }

    public function showPricingContact(SubscriptionPlanCatalog $catalog): View
    {
        return view('public-pricing-contact', [
            'plansByTier' => $catalog->activePlansByTier(),
        ]);
    }

    public function submitMerchantInquiry(Request $request): RedirectResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:50'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'restaurant_name' => ['required', 'string', 'max:255'],
                'status' => ['required', 'in:open,preparing'],
                'country' => ['required', 'in:tw,vn'],
                'address' => ['required', 'string', 'max:500'],
                'contact_time' => ['nullable', 'string', 'max:255'],
                'message' => ['nullable', 'string', 'max:2000'],
            ],
            [],
            [
                'name' => __('merchant_inquiry.field_name'),
                'phone' => __('merchant_inquiry.field_phone'),
                'email' => __('merchant_inquiry.field_email'),
                'restaurant_name' => __('merchant_inquiry.field_restaurant_name'),
                'status' => __('merchant_inquiry.field_status'),
                'country' => __('merchant_inquiry.field_country'),
                'address' => __('merchant_inquiry.field_address'),
                'contact_time' => __('merchant_inquiry.field_contact_time'),
                'message' => __('merchant_inquiry.field_message'),
            ],
        );

        if ($validator->fails()) {
            return redirect()
                ->to($this->pricingContactRedirectTarget($request))
                ->withErrors($validator)
                ->withInput();
        }

        $notifyEmail = trim((string) config('mail.merchant_registration_notify_to', ''));
        if ($notifyEmail === '') {
            return redirect()
                ->to($this->pricingContactRedirectTarget($request))
                ->withInput()
                ->with('merchantInquiryError', __('merchant_inquiry.send_failed'));
        }

        try {
            Mail::to($notifyEmail)->send(
                new MerchantInquiryNotificationMail($this->normalizeInquiryPayload($validator->validated()))
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->to($this->pricingContactRedirectTarget($request))
                ->withInput()
                ->with('merchantInquiryError', __('merchant_inquiry.send_failed'));
        }

        return redirect()
            ->to($this->pricingContactRedirectTarget($request))
            ->with('merchantInquirySuccess', __('merchant_inquiry.success'));
    }

    private function normalizeInquiryPayload(array $validated): array
    {
        $normalize = static fn (?string $value): ?string => filled($value)
            ? trim((string) $value)
            : null;

        return [
            'name' => $normalize($validated['name'] ?? null) ?? '',
            'phone' => $normalize($validated['phone'] ?? null) ?? '',
            'email' => $normalize($validated['email'] ?? null) ?? '',
            'restaurant_name' => $normalize($validated['restaurant_name'] ?? null) ?? '',
            'status' => $normalize($validated['status'] ?? null) ?? '',
            'country' => $normalize($validated['country'] ?? null) ?? '',
            'address' => $normalize($validated['address'] ?? null) ?? '',
            'contact_time' => $normalize($validated['contact_time'] ?? null),
            'message' => $normalize($validated['message'] ?? null),
        ];
    }

    private function pricingContactRedirectTarget(Request $request): string
    {
        $fallback = route('product.intro').'#pricing-contact';

        $target = trim((string) $request->input('return_to', ''));
        if ($target === '') {
            $target = trim((string) $request->headers->get('referer', ''));
        }

        if ($target === '') {
            return $fallback;
        }

        $target = preg_replace('/#.*$/', '', $target) ?: $target;

        $targetParts = parse_url($target);
        $appParts = parse_url(url('/'));

        if (
            ! is_array($targetParts)
            || ! is_array($appParts)
            || ($targetParts['scheme'] ?? null) !== ($appParts['scheme'] ?? null)
            || ($targetParts['host'] ?? null) !== ($appParts['host'] ?? null)
            || ($targetParts['port'] ?? null) !== ($appParts['port'] ?? null)
        ) {
            return $fallback;
        }

        return $target.'#pricing-contact';
    }
}
