<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Store;
use App\Models\StoreInvoice;
use App\Models\StoreInvoiceAllowance;
use App\Models\StoreInvoiceSetting;
use Illuminate\Database\Seeder;

class InvoiceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::query()->orderBy('id')->limit(5)->get();

        foreach ($stores as $index => $store) {
            $setting = StoreInvoiceSetting::query()->updateOrCreate(
                ['store_id' => $store->id],
                [
                    'onboarding_status' => $index === 0 ? 'live' : 'ready',
                    'wizard_step' => 6,
                    'eligible_for_invoice' => true,
                    'provider_mode' => 'value_center',
                    'provider_name' => 'Demo VAT Center',
                    'tax_id' => '12345675',
                    'company_name' => $store->name . ' Holdings Ltd.',
                    'branch_name' => $store->name,
                    'company_address' => $store->address ?: 'Taipei City',
                    'credential_notes' => 'Demo credential prepared',
                    'invoice_track_prefix' => 'DF',
                    'invoice_track_start' => 1,
                    'invoice_track_end' => 99999999,
                    'next_invoice_no' => 1000 + ($index * 200),
                    'store_no' => str_pad((string) $store->id, 8, '0', STR_PAD_LEFT),
                    'machine_no' => str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'last_tested_at' => now()->subDays(1),
                    'last_test_invoice_no' => 'DF00000999',
                    'blank_tracks_uploaded_at' => $index === 0 ? now()->subDays(2) : null,
                ]
            );

            $orders = Order::query()
                ->where('store_id', $store->id)
                ->orderBy('id')
                ->get();

            if ($orders->isEmpty()) {
                continue;
            }

            $paidOrders = $orders
                ->where('payment_status', 'paid')
                ->values();

            $cancelledOrders = $orders
                ->filter(fn (Order $order) => in_array(strtolower((string) $order->status), ['cancel', 'cancelled', 'canceled'], true))
                ->values();

            if ($paidOrders->count() >= 1) {
                $order = $paidOrders[0];
                $this->fillOrderInvoiceRequest($order, 'mobile_barcode');

                StoreInvoice::query()->updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'store_id' => $store->id,
                        'status' => StoreInvoice::STATUS_FAILED,
                        'invoice_number' => null,
                        'invoice_flow' => 'mobile_barcode',
                        'carrier_type' => 'mobile',
                        'carrier_code' => '/ABC1234',
                        'amount' => (int) $order->total,
                        'issue_attempts' => 3,
                        'upload_status' => 'failed',
                        'last_error' => 'Demo: provider timeout',
                        'legal_deadline_at' => now()->subHours(2),
                    ]
                );
            }

            if ($paidOrders->count() >= 2) {
                $order = $paidOrders[1];
                $this->fillOrderInvoiceRequest($order, 'member_carrier');

                StoreInvoice::query()->updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'store_id' => $store->id,
                        'status' => StoreInvoice::STATUS_ISSUED,
                        'invoice_number' => 'DF' . str_pad((string) ($setting->next_invoice_no + 10), 8, '0', STR_PAD_LEFT),
                        'random_number' => '4321',
                        'invoice_flow' => 'member_carrier',
                        'carrier_type' => 'member',
                        'carrier_code' => 'MEMBER-001',
                        'amount' => (int) $order->total,
                        'issue_attempts' => 1,
                        'upload_status' => 'pending',
                        'issued_at' => now()->subHours(1),
                        'legal_deadline_at' => now()->addHours(24),
                        'qr_code_url' => url('/invoice/qr/demo-1'),
                        'pdf_url' => url('/invoice/pdf/demo-1'),
                    ]
                );
            }

            if ($cancelledOrders->count() >= 1) {
                $order = $cancelledOrders[0];
                $this->fillOrderInvoiceRequest($order, 'donation_code');

                StoreInvoice::query()->updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'store_id' => $store->id,
                        'status' => StoreInvoice::STATUS_VOID_FAILED,
                        'invoice_number' => 'DF' . str_pad((string) ($setting->next_invoice_no + 20), 8, '0', STR_PAD_LEFT),
                        'random_number' => '1988',
                        'invoice_flow' => 'donation_code',
                        'carrier_type' => 'donation',
                        'donation_code' => '16888',
                        'amount' => (int) $order->total,
                        'issue_attempts' => 1,
                        'void_attempts' => 2,
                        'upload_status' => 'failed',
                        'issued_at' => now()->subDays(1),
                        'last_error' => 'Demo: void endpoint rejected',
                        'legal_deadline_at' => now()->subHours(6),
                    ]
                );
            }

            if ($paidOrders->count() >= 3) {
                $order = $paidOrders[2];
                $this->fillOrderInvoiceRequest($order, 'company_tax_id');

                $invoice = StoreInvoice::query()->updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'store_id' => $store->id,
                        'status' => StoreInvoice::STATUS_ALLOWANCE_FAILED,
                        'invoice_number' => 'DF' . str_pad((string) ($setting->next_invoice_no + 30), 8, '0', STR_PAD_LEFT),
                        'random_number' => '5678',
                        'invoice_flow' => 'company_tax_id',
                        'carrier_type' => 'company',
                        'company_tax_id' => '24536806',
                        'amount' => (int) $order->total,
                        'issue_attempts' => 1,
                        'upload_status' => 'uploaded',
                        'issued_at' => now()->subHours(3),
                        'uploaded_at' => now()->subHours(3),
                        'last_error' => 'Demo: allowance transmission failed',
                        'legal_deadline_at' => now()->addHours(40),
                    ]
                );

                StoreInvoiceAllowance::query()->updateOrCreate(
                    [
                        'store_invoice_id' => $invoice->id,
                        'amount' => max((int) floor((int) $order->total * 0.3), 1),
                    ],
                    [
                        'store_id' => $store->id,
                        'order_id' => $order->id,
                        'status' => 'failed',
                        'allowance_number' => null,
                        'reason' => 'Demo partial refund',
                        'attempts' => 2,
                        'upload_status' => 'failed',
                        'last_error' => 'Demo: allowance API unavailable',
                        'legal_deadline_at' => now()->subHour(),
                    ]
                );
            }
        }
    }

    private function fillOrderInvoiceRequest(Order $order, string $flow): void
    {
        $payload = match ($flow) {
            'mobile_barcode' => [
                'invoice_flow' => 'mobile_barcode',
                'invoice_mobile_barcode' => '/ABC1234',
                'invoice_member_carrier_code' => null,
                'invoice_donation_code' => null,
                'invoice_company_tax_id' => null,
                'invoice_company_name' => null,
            ],
            'member_carrier' => [
                'invoice_flow' => 'member_carrier',
                'invoice_mobile_barcode' => null,
                'invoice_member_carrier_code' => 'MEMBER-001',
                'invoice_donation_code' => null,
                'invoice_company_tax_id' => null,
                'invoice_company_name' => null,
            ],
            'donation_code' => [
                'invoice_flow' => 'donation_code',
                'invoice_mobile_barcode' => null,
                'invoice_member_carrier_code' => null,
                'invoice_donation_code' => '16888',
                'invoice_company_tax_id' => null,
                'invoice_company_name' => null,
            ],
            default => [
                'invoice_flow' => 'company_tax_id',
                'invoice_mobile_barcode' => null,
                'invoice_member_carrier_code' => null,
                'invoice_donation_code' => null,
                'invoice_company_tax_id' => '24536806',
                'invoice_company_name' => 'DineFlow Demo Co.',
            ],
        };

        $order->fill($payload + [
            'invoice_requested_at' => now()->subHours(4),
        ])->save();
    }
}
