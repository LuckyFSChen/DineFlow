<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreReview;
use App\Support\PhoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreReviewController extends Controller
{
    private const COMPLETED_ORDER_STATUSES = [
        'complete',
        'completed',
        'ready',
        'ready_for_pickup',
        'picked_up',
        'collected',
        'served',
    ];

    public function store(Request $request, Store $store, Order $order)
    {
        abort_unless($order->store_id === $store->id, 404);

        if (! $this->isCompletedOrder($order)) {
            throw ValidationException::withMessages([
                'store_rating' => __('customer.review_only_completed_order'),
            ]);
        }

        $validated = $request->validate([
            'store_rating' => ['required', 'integer', 'between:1,5'],
            'order_rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $submittedEmail = $this->normalizeEmail($validated['customer_email'] ?? null);
        $submittedPhone = PhoneFormatter::digitsOnly($validated['customer_phone'] ?? null, 32);

        $orderEmail = $this->normalizeEmail($order->getRawOriginal('customer_email'));
        $orderPhone = PhoneFormatter::digitsOnly($order->getRawOriginal('customer_phone'), 32);

        if ($orderEmail !== null && $submittedEmail !== null && $orderEmail !== $submittedEmail) {
            throw ValidationException::withMessages([
                'customer_email' => __('customer.review_identity_mismatch'),
            ]);
        }

        if ($orderPhone !== null && $submittedPhone !== null && $orderPhone !== $submittedPhone) {
            throw ValidationException::withMessages([
                'customer_phone' => __('customer.review_identity_mismatch'),
            ]);
        }

        $user = $request->user();
        $comment = trim((string) ($validated['comment'] ?? ''));

        StoreReview::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'store_id' => $store->id,
                'user_id' => $user?->isCustomer() ? $user->id : null,
                'rating' => (int) $validated['store_rating'],
                'order_rating' => (int) $validated['order_rating'],
                'comment' => $comment !== '' ? $comment : null,
                'customer_name' => $this->normalizeText($validated['customer_name'] ?? null) ?? $order->customer_name,
                'customer_email' => $submittedEmail ?? $orderEmail,
                'customer_phone' => $submittedPhone ?? $orderPhone,
                'is_visible' => true,
            ]
        );

        return back()->with('success', __('customer.review_saved'));
    }

    private function normalizeEmail(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = Str::lower(trim($value));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeText(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function isCompletedOrder(Order $order): bool
    {
        return in_array(strtolower((string) $order->status), self::COMPLETED_ORDER_STATUSES, true);
    }
}
