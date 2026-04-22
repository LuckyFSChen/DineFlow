<?php

namespace Tests\Feature;

use App\Mail\MerchantInquiryNotificationMail;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicProductIntroInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_product_intro_page_shows_active_subscription_plans(): void
    {
        SubscriptionPlan::query()->create([
            'name' => 'Starter Monthly',
            'slug' => 'basic-starter-monthly',
            'category' => 'basic',
            'price_twd' => 1200,
            'discount_twd' => 300,
            'duration_days' => 30,
            'max_stores' => 1,
            'features' => ['QR Ordering', 'Menu Management'],
            'description' => 'Great for a single-store launch.',
            'is_active' => true,
        ]);

        SubscriptionPlan::query()->create([
            'name' => 'Hidden Legacy Plan',
            'slug' => 'legacy-hidden',
            'category' => 'basic',
            'price_twd' => 9999,
            'discount_twd' => 0,
            'duration_days' => 30,
            'max_stores' => 1,
            'features' => ['Legacy'],
            'description' => 'Should not be shown.',
            'is_active' => false,
        ]);

        $response = $this->get(route('product.intro'));

        $response->assertOk();
        $response->assertSee('Starter Monthly');
        $response->assertSee('QR Ordering');
        $response->assertSee('Menu Management');
        $response->assertDontSee('Hidden Legacy Plan');
    }

    public function test_public_pricing_contact_page_is_available_from_guest_navigation(): void
    {
        $response = $this->get(route('product.pricing-contact'));

        $response->assertOk();
        $response->assertSee(route('product.pricing-contact'), false);
        $response->assertSee(__('nav.pricing_contact'));
        $response->assertSee(__('merchant_inquiry.section_title'));
    }

    public function test_public_product_intro_inquiry_sends_notification_email(): void
    {
        Mail::fake();

        config([
            'mail.merchant_registration_notify_to' => 'notify@example.com',
        ]);

        $response = $this->post(route('product.intro.inquiry.submit'), [
            'name' => 'Lowy',
            'phone' => '0912-345-678',
            'email' => 'merchant@example.com',
            'restaurant_name' => 'Demo Bistro',
            'status' => 'open',
            'country' => 'tw',
            'address' => 'Taipei City, Taiwan',
            'contact_time' => 'Weekdays afternoon',
            'message' => 'Need a walkthrough for two stores.',
            'return_to' => route('product.pricing-contact'),
        ]);

        $response->assertRedirect(route('product.pricing-contact').'#pricing-contact');
        $response->assertSessionHas('merchantInquirySuccess');

        Mail::assertSent(MerchantInquiryNotificationMail::class, function (MerchantInquiryNotificationMail $mail): bool {
            return $mail->hasTo('notify@example.com')
                && ($mail->inquiry['restaurant_name'] ?? null) === 'Demo Bistro'
                && ($mail->inquiry['country'] ?? null) === 'tw'
                && ($mail->inquiry['status'] ?? null) === 'open';
        });
    }
}
