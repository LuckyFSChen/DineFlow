<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_merchant_email_change_requires_verification_before_email_is_updated(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'merchant',
            'email' => 'merchant@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Merchant User',
                'email' => 'new-merchant@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'email-verification-link-sent');

        $user->refresh();

        $this->assertSame('Merchant User', $user->name);
        $this->assertSame('merchant@example.com', $user->email);
        $this->assertSame('new-merchant@example.com', $user->pending_email);
        $this->assertNotNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_merchant_email_change_rejects_email_used_by_another_login_account(): void
    {
        Notification::fake();

        User::factory()->create([
            'role' => 'admin',
            'email' => 'shared@example.com',
        ]);

        $user = User::factory()->create([
            'role' => 'merchant',
            'email' => 'merchant@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Merchant User',
                'email' => 'shared@example.com',
            ]);

        $response
            ->assertSessionHasErrors('email')
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('merchant@example.com', $user->email);
        $this->assertNull($user->pending_email);

        Notification::assertNothingSent();
    }

    public function test_merchant_email_change_rejects_email_pending_on_another_login_account(): void
    {
        Notification::fake();

        User::factory()->create([
            'role' => 'merchant',
            'email' => 'other@example.com',
            'pending_email' => 'shared@example.com',
        ]);

        $user = User::factory()->create([
            'role' => 'merchant',
            'email' => 'merchant@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Merchant User',
                'email' => 'shared@example.com',
            ]);

        $response
            ->assertSessionHasErrors('email')
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('merchant@example.com', $user->email);
        $this->assertNull($user->pending_email);

        Notification::assertNothingSent();
    }

    public function test_pending_merchant_email_is_applied_after_verification(): void
    {
        $user = User::factory()->create([
            'role' => 'merchant',
            'email' => 'merchant@example.com',
            'pending_email' => 'new-merchant@example.com',
            'email_verified_at' => now(),
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('new-merchant@example.com')]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $user->refresh();

        $this->assertSame('new-merchant@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_pending_merchant_email_is_not_applied_if_another_login_account_claimed_it(): void
    {
        $user = User::factory()->create([
            'role' => 'merchant',
            'email' => 'merchant@example.com',
            'pending_email' => 'shared@example.com',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'role' => 'admin',
            'email' => 'shared@example.com',
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('shared@example.com')]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response
            ->assertRedirect(route('profile.edit', absolute: false))
            ->assertSessionHasErrors('email');

        $user->refresh();

        $this->assertSame('merchant@example.com', $user->email);
        $this->assertSame('shared@example.com', $user->pending_email);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
