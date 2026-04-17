<?php

namespace Tests\Feature\Console;

use App\Mail\LocalMailVerificationMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LocalMailVerificationCommandTest extends TestCase
{
    public function test_it_sends_local_mail_verification_to_the_given_address(): void
    {
        Mail::fake();

        $this->artisan('mail:verify-local test@example.com')
            ->assertExitCode(0)
            ->expectsOutputToContain('Verification email sent to test@example.com');

        Mail::assertSent(LocalMailVerificationMail::class, function (LocalMailVerificationMail $mail): bool {
            return $mail->hasTo('test@example.com');
        });
    }

    public function test_it_rejects_invalid_email_address(): void
    {
        Mail::fake();

        $this->artisan('mail:verify-local not-an-email')
            ->assertExitCode(1)
            ->expectsOutputToContain('Invalid email address');

        Mail::assertNothingSent();
    }
}
