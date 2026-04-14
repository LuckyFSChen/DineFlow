<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $webhookSecret = (string) config('services.stripe.webhook_secret');
        $stripeSecret = (string) config('services.stripe.secret');

        if ($webhookSecret === '' || $stripeSecret === '') {
            return response('Stripe webhook is not configured.', 500);
        }

        Stripe::setApiKey($stripeSecret);

        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (\UnexpectedValueException $exception) {
            return response('Invalid payload.', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $exception) {
            return response('Invalid signature.', 400);
        }

        $type = $event->type;
        $object = $event->data->object;
        $eventId = (string) ($event->id ?? '');

        if ($eventId !== '' && SubscriptionPayment::query()->where('stripe_event_id', $eventId)->exists()) {
            return response('ok', 200);
        }

        if ($type === 'checkout.session.completed') {
            $this->handleCheckoutSessionCompleted($object, $eventId);
        }

        if ($type === 'customer.subscription.updated' || $type === 'customer.subscription.created') {
            $this->handleSubscriptionUpdated($object);
        }

        if ($type === 'customer.subscription.deleted') {
            $this->handleSubscriptionDeleted($object);
        }

        if ($type === 'invoice.payment_succeeded') {
            $this->handleInvoicePaymentSucceeded($object, $eventId);
        }

        if ($type === 'invoice.payment_failed') {
            $this->handleInvoicePaymentFailed($object, $eventId);
        }

        return response('ok', 200);
    }

    private function handleCheckoutSessionCompleted(object $session, string $eventId): void
    {
        $userId = (int) ($session->metadata->user_id ?? 0);
        $planId = (int) ($session->metadata->plan_id ?? 0);

        $user = User::query()->find($userId);
        $plan = SubscriptionPlan::query()->find($planId);

        if (! $user || ! $plan) {
            return;
        }

        $periodEnd = null;
        if (! empty($session->subscription)) {
            $stripeSubscription = Subscription::retrieve((string) $session->subscription);
            $periodEnd = (int) ($stripeSubscription->current_period_end ?? 0);
        }

        $this->applySubscription(
            $user,
            $plan,
            $periodEnd,
            (string) ($session->subscription ?? ''),
            (string) ($session->customer ?? '')
        );

        $this->recordPayment([
            'stripe_event_id' => $eventId,
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'stripe_checkout_session_id' => (string) ($session->id ?? ''),
            'stripe_subscription_id' => (string) ($session->subscription ?? ''),
            'stripe_invoice_id' => null,
            'stripe_payment_intent_id' => (string) ($session->payment_intent ?? ''),
            'amount_twd' => (int) (($session->amount_total ?? 0) / 100),
            'currency' => (string) ($session->currency ?? 'twd'),
            'status' => 'checkout_completed',
            'paid_at' => now(),
            'payload' => (array) $session,
        ]);
    }

    private function handleSubscriptionUpdated(object $stripeSubscription): void
    {
        $stripeSubscriptionId = (string) ($stripeSubscription->id ?? '');
        $stripeCustomerId = (string) ($stripeSubscription->customer ?? '');

        $user = User::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->orWhere('stripe_customer_id', $stripeCustomerId)
            ->first();

        if (! $user) {
            return;
        }

        $status = (string) ($stripeSubscription->status ?? '');
        $periodEnd = (int) ($stripeSubscription->current_period_end ?? 0);

        if (in_array($status, ['active', 'trialing', 'past_due'], true)) {
            $plan = $user->subscriptionPlan;
            if (! $plan) {
                return;
            }

            $this->applySubscription($user, $plan, $periodEnd, $stripeSubscriptionId, $stripeCustomerId);
            return;
        }

        $user->update([
            'stripe_customer_id' => $stripeCustomerId !== '' ? $stripeCustomerId : $user->stripe_customer_id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'subscription_ends_at' => now()->subSecond(),
        ]);
    }

    private function handleSubscriptionDeleted(object $stripeSubscription): void
    {
        $stripeSubscriptionId = (string) ($stripeSubscription->id ?? '');
        $stripeCustomerId = (string) ($stripeSubscription->customer ?? '');

        $user = User::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->orWhere('stripe_customer_id', $stripeCustomerId)
            ->first();

        if (! $user) {
            return;
        }

        $user->update([
            'stripe_customer_id' => $stripeCustomerId !== '' ? $stripeCustomerId : $user->stripe_customer_id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'subscription_ends_at' => now()->subSecond(),
        ]);
    }

    private function handleInvoicePaymentSucceeded(object $invoice, string $eventId): void
    {
        $stripeSubscriptionId = (string) ($invoice->subscription ?? '');
        $stripeCustomerId = (string) ($invoice->customer ?? '');

        $user = User::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->orWhere('stripe_customer_id', $stripeCustomerId)
            ->first();

        if (! $user) {
            return;
        }

        $this->recordPayment([
            'stripe_event_id' => $eventId,
            'user_id' => $user->id,
            'subscription_plan_id' => $user->subscription_plan_id,
            'stripe_checkout_session_id' => null,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'stripe_invoice_id' => (string) ($invoice->id ?? ''),
            'stripe_payment_intent_id' => (string) ($invoice->payment_intent ?? ''),
            'amount_twd' => (int) (($invoice->amount_paid ?? 0) / 100),
            'currency' => (string) ($invoice->currency ?? 'twd'),
            'status' => 'paid',
            'paid_at' => now(),
            'payload' => (array) $invoice,
        ]);
    }

    private function handleInvoicePaymentFailed(object $invoice, string $eventId): void
    {
        $stripeSubscriptionId = (string) ($invoice->subscription ?? '');
        $stripeCustomerId = (string) ($invoice->customer ?? '');

        $user = User::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->orWhere('stripe_customer_id', $stripeCustomerId)
            ->first();

        if (! $user) {
            return;
        }

        $this->recordPayment([
            'stripe_event_id' => $eventId,
            'user_id' => $user->id,
            'subscription_plan_id' => $user->subscription_plan_id,
            'stripe_checkout_session_id' => null,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'stripe_invoice_id' => (string) ($invoice->id ?? ''),
            'stripe_payment_intent_id' => (string) ($invoice->payment_intent ?? ''),
            'amount_twd' => (int) (($invoice->amount_due ?? 0) / 100),
            'currency' => (string) ($invoice->currency ?? 'twd'),
            'status' => 'failed',
            'paid_at' => null,
            'payload' => (array) $invoice,
        ]);
    }

    private function applySubscription(
        User $user,
        SubscriptionPlan $plan,
        ?int $periodEndTimestamp,
        string $stripeSubscriptionId,
        string $stripeCustomerId
    ): void {
        $subscriptionEndsAt = $periodEndTimestamp && $periodEndTimestamp > 0
            ? Carbon::createFromTimestamp($periodEndTimestamp)
            : now()->addDays((int) $plan->duration_days);

        $user->update([
            'subscription_plan_id' => $plan->id,
            'subscription_ends_at' => $subscriptionEndsAt,
            'stripe_customer_id' => $stripeCustomerId !== '' ? $stripeCustomerId : $user->stripe_customer_id,
            'stripe_subscription_id' => $stripeSubscriptionId !== '' ? $stripeSubscriptionId : $user->stripe_subscription_id,
        ]);
    }

    private function recordPayment(array $data): void
    {
        SubscriptionPayment::query()->create([
            'stripe_event_id' => $data['stripe_event_id'] ?: null,
            'user_id' => $data['user_id'],
            'subscription_plan_id' => $data['subscription_plan_id'],
            'stripe_checkout_session_id' => $data['stripe_checkout_session_id'] ?: null,
            'stripe_subscription_id' => $data['stripe_subscription_id'] ?: null,
            'stripe_invoice_id' => $data['stripe_invoice_id'] ?: null,
            'stripe_payment_intent_id' => $data['stripe_payment_intent_id'] ?: null,
            'amount_twd' => max((int) $data['amount_twd'], 0),
            'currency' => strtolower((string) ($data['currency'] ?: 'twd')),
            'status' => $data['status'],
            'paid_at' => $data['paid_at'],
            'payload' => $data['payload'],
        ]);
    }
}
