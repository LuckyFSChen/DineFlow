<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCreateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_admin_user_from_email(): void
    {
        $this->artisan('admin:create', [
            'email' => 'new.admin@example.com',
        ])
            ->expectsOutputToContain('Admin user created successfully.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'new.admin@example.com')->first();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('New Admin', $user->name);
        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('password', (string) $user->password));
        $this->assertTrue((bool) $user->must_change_password);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_it_promotes_an_existing_user_to_admin_and_preserves_their_name(): void
    {
        $user = User::query()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => 'secret-123',
            'role' => 'customer',
        ]);

        $this->artisan('admin:create', [
            'email' => 'existing@example.com',
        ])
            ->expectsOutputToContain('Admin user updated successfully.')
            ->assertSuccessful();

        $user->refresh();

        $this->assertSame('Existing User', $user->name);
        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('password', (string) $user->password));
        $this->assertTrue((bool) $user->must_change_password);
        $this->assertNotNull($user->email_verified_at);
    }
}
