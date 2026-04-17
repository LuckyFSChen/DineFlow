<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use JsonException;

class CashierController extends Controller
{
    private const PENDING_STATUSES = ['pending', 'accepted', 'confirmed', 'received'];

    private const COMPLETED_STATUSES = ['complete', 'completed', 'ready', 'ready_for_pickup'];

    public function index(Request $request, Store $store)
    {
        $this->authorizeStore($request, $store);

        $orders = $this->fetchCashierOrders($store);
        $availableStores = $this->resolveAccessibleStores($request);

        $checkoutTiming = $store->checkout_timing ?? 'postpay';

        return view('admin.cashier.index', compact('store', 'orders', 'checkoutTiming', 'availableStores'));
    }

    public function orders(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $orders = $this->fetchCashierOrders($store);

        $data = $orders->map(fn (Order $o) => [
            'id'             => $o->id,
            'order_no'       => $o->order_no,
            'order_locale'   => $o->order_locale,
            'status'         => $o->status,
            'payment_status' => $o->payment_status,
            'order_type'     => $o->order_type,
            'note'           => $o->note,
            'customer_name'  => $o->customer_name,
            'created_at'     => $o->created_at?->toIso8601String(),
            'table'          => ($t = $o->getRelation('table')) ? ['table_no' => $t->table_no] : null,
            'items'          => $o->items->map(fn ($i) => [
                'id'             => $i->id,
                'product_name'   => $i->product_name,
                'qty'            => $i->qty,
                'note'           => $i->note,
                'option_summary' => null,
            ])->values()->all(),
            '_loading' => false,
        ])->values();

        return response()->json($data);
    }

    public function updateStatus(Request $request, Store $store, Order $order): JsonResponse
    {
        $this->authorizeStore($request, $store);

        abort_if($order->store_id !== $store->id, 403);

        $status = (string) $request->input('status');
        $allowed = ['preparing', 'paid', 'cancelled'];

        if (! in_array($status, $allowed, true)) {
            return response()->json(['ok' => false, 'message' => __('admin.error_invalid_status')], 422);
        }

        if ($status === 'cancelled') {
            if (! in_array((string) $order->status, self::PENDING_STATUSES, true)) {
                return response()->json(['ok' => false, 'message' => __('admin.error_only_pending_can_cancel')], 422);
            }

            $cancelReasonOptions = $this->normalizeCancelReasonOptions(
                $request->input('cancel_reason_options', $request->input('cancelReasonOptions', $request->input('cancel_reasons')))
            );
            $cancelReasonOther = $this->normalizeCancelReasonText(
                $request->input('cancel_reason_other', $request->input('cancelReasonOther', $request->input('cancel_reason')))
            );

            if ($cancelReasonOptions->isEmpty() && $cancelReasonOther === '') {
                return response()->json(['ok' => false, 'message' => __('admin.error_cancel_reason_required')], 422);
            }

            DB::transaction(function () use ($order, $cancelReasonOptions, $cancelReasonOther): void {
                $order->update([
                    'status' => 'cancelled',
                    'cancel_reason_options' => $cancelReasonOptions->isNotEmpty() ? $cancelReasonOptions->values()->all() : null,
                    'cancel_reason_other' => $cancelReasonOther !== '' ? $cancelReasonOther : null,
                ]);

                app(LoyaltyService::class)->reverseOrderLoyaltyOnCancellation($order);
            });

            $order->refresh();

            return response()->json([
                'ok'             => true,
                'status'         => $order->status,
                'payment_status' => $order->payment_status,
            ]);
        }

        if ($status === 'paid') {
            // Collect payment for a completed (postpay) order
            $order->update(['payment_status' => 'paid']);

            return response()->json([
                'ok'             => true,
                'status'         => $order->status,
                'payment_status' => $order->payment_status,
            ]);
        }

        // Accept order: pending → preparing
        $updates = ['status' => 'preparing'];

        if ($store->isPrepayCheckout()) {
            // Prepay: mark as paid immediately when accepting
            $updates['payment_status'] = 'paid';
        }

        $order->update($updates);

        return response()->json([
            'ok'             => true,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
        ]);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isMerchant() && (int) $store->user_id !== (int) $user->id) {
            abort(403);
        }

        if ($user->isCashier() && (int) $user->store_id !== (int) $store->id) {
            abort(403);
        }
    }

    private function fetchCashierOrders(Store $store): \Illuminate\Database\Eloquent\Collection
    {
        return Order::with(['items', 'table'])
            ->where('store_id', $store->id)
            ->where(function ($query) {
                // Pending orders waiting to be accepted
                $query->whereIn('status', self::PENDING_STATUSES)
                    // OR completed orders with outstanding payment (postpay)
                    ->orWhere(function ($q) {
                        $q->whereIn('status', self::COMPLETED_STATUSES)
                            ->where(function ($pq) {
                                $pq->where('payment_status', 'unpaid')
                                    ->orWhereNull('payment_status');
                            });
                    });
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function resolveAccessibleStores(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return Store::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'slug']);
        }

        if ($user->isMerchant()) {
            return Store::query()
                ->where('user_id', $user->id)
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name', 'slug']);
        }

        if ($user->isCashier() && $user->store_id) {
            return Store::query()->whereKey($user->store_id)->get(['id', 'name', 'slug']);
        }

        return Store::query()->whereRaw('1 = 0')->get(['id', 'name', 'slug']);
    }

    private function normalizeCancelReasonOptions(mixed $value): Collection
    {
        $options = $value;

        if (is_string($options)) {
            $decoded = null;

            try {
                $decoded = json_decode($options, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $decoded = null;
            }

            if (is_array($decoded)) {
                $options = $decoded;
            } else {
                $options = preg_split('/[\r\n,;|]+/u', $options) ?: [];
            }
        }

        if (! is_array($options)) {
            return collect();
        }

        return collect($options)
            ->map(fn ($reason) => trim((string) $reason))
            ->filter(fn (string $reason) => $reason !== '')
            ->unique()
            ->values();
    }

    private function normalizeCancelReasonText(mixed $value): string
    {
        if (! is_scalar($value) && $value !== null) {
            return '';
        }

        $text = trim((string) ($value ?? ''));

        if ($text === '' || strtolower($text) === 'null') {
            return '';
        }

        return $text;
    }
}
