<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Password reset in this app uses recovery codes, not email tokens.
 * These tests verify the custom PasswordRecoveryController flow.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_forgot_password_requires_email_and_recovery_code(): void
    {
        $response = $this->post('/forgot-password', []);

        $response->assertSessionHasErrors(['email', 'recovery_code']);
    }

    public function test_forgot_password_rejects_invalid_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
            'recovery_code' => 'ABCD-1234',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_screen_redirects_without_session(): void
    {
        $response = $this->get('/reset-password');

        $response->assertRedirect(route('password.request'));
    }
}
