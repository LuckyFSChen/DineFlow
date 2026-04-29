<?php

namespace Tests\Feature\Auth;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'phone' => '0912345678',
            'role' => 'customer',
        ]);

        $this->get('/login');
        $captchaAnswer = app('session.store')->get('auth_login_captcha_answer');

        $response = $this->post('/login', [
            'phone' => '0912345678',
            'password' => 'password',
            'captcha_answer' => $captchaAnswer,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        User::factory()->create([
            'phone' => '0912345678',
            'role' => 'customer',
        ]);

        $this->get('/login');
        $originalQuestion = app('session.store')->get('auth_login_captcha_question');
        $captchaAnswer = app('session.store')->get('auth_login_captcha_answer');

        $this->post('/login', [
            'phone' => '0912345678',
            'password' => 'wrong-password',
            'captcha_answer' => $captchaAnswer,
        ]);

        $this->assertGuest();
        $this->assertNotSame($originalQuestion, app('session.store')->get('auth_login_captcha_question'));
    }

    public function test_login_validation_failure_refreshes_captcha(): void
    {
        $this->get('/login');
        $originalQuestion = app('session.store')->get('auth_login_captcha_question');

        $this->post('/login', [
            'phone' => '',
            'password' => '',
            'captcha_answer' => '',
        ]);

        $this->assertGuest();
        $this->assertNotSame($originalQuestion, app('session.store')->get('auth_login_captcha_question'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_users_required_to_change_password_can_still_view_order_success_page(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'must_change_password' => true,
            'phone' => '0912345678',
        ]);

        $store = Store::create([
            'name' => 'Success Store',
            'slug' => 'success-store',
            'is_active' => true,
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'order_type' => 'takeout',
            'order_no' => 'SUCCESS-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_phone' => '0912345678',
            'subtotal' => 100,
            'total' => 100,
        ]);

        $response = $this->actingAs($user)->get(route('customer.order.success', [
            'store' => $store->slug,
            'order' => $order->uuid,
        ]));

        $response->assertOk();
        $response->assertViewIs('customer.success');
    }
}
