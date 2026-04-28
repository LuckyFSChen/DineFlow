<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_merchant_cannot_register_with_email_used_by_another_login_account(): void
    {
        User::factory()->create([
            'role' => 'admin',
            'email' => 'shared@example.com',
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Merchant User',
            'email' => 'shared@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'account_type' => 'merchant',
            'merchant_region' => 'tw',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
