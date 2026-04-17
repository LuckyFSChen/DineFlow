<?php

namespace Tests\Feature\Auth;

use App\Models\Store;
use App\Models\User;
use App\Support\TakeoutCartSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TakeoutCartInheritanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_takeout_cart_is_inherited_after_customer_login(): void
    {
        $store = Store::create([
            'name' => 'Test Store',
        ]);

        $user = User::factory()->create([
            'phone' => '0912345678',
            'role' => 'customer',
        ]);

        $guestToken = 'guest-token';
        $guestCartKey = TakeoutCartSession::cartSessionKey($store->id, $guestToken);

        $this->withSession([
            TakeoutCartSession::tokenSessionKey($store->id) => $guestToken,
            $guestCartKey => [
                'line-1' => [
                    'line_key' => 'line-1',
                    'product_id' => 1,
                    'product_name' => 'Black Tea',
                    'price' => 50,
                    'qty' => 2,
                    'subtotal' => 100,
                ],
            ],
        ])->get('/login');

        $captchaAnswer = app('session.store')->get('auth_login_captcha_answer');

        $response = $this->post('/login', [
            'phone' => '0912345678',
            'password' => 'password',
            'captcha_answer' => $captchaAnswer,
        ]);

        $userToken = TakeoutCartSession::userToken($user);
        $userCartKey = TakeoutCartSession::cartSessionKey($store->id, $userToken);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame($userToken, app('session.store')->get(TakeoutCartSession::tokenSessionKey($store->id)));
        $this->assertSame(2, app('session.store')->get($userCartKey . '.line-1.qty'));
        $this->assertSame(100, app('session.store')->get($userCartKey . '.line-1.subtotal'));
        $this->assertFalse(app('session.store')->has($guestCartKey));
    }
}
