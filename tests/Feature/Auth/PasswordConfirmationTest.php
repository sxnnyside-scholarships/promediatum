<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Password confirmation route is not used in this app.
 * The app uses recovery codes for password reset instead.
 */
class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_requires_verification_first(): void
    {
        // GET /reset-password without session should redirect to forgot-password
        $response = $this->get('/reset-password');

        $response->assertRedirect(route('password.request'));
    }
}
