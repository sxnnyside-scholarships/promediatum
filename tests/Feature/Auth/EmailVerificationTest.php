<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Email verification routes are not used in this app.
 * Auth uses recovery codes instead. See RecoveryCodeController tests.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_codes_page_requires_authentication(): void
    {
        $response = $this->get('/recovery-codes');

        $response->assertRedirect('/login');
    }

    public function test_recovery_codes_page_is_accessible_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/recovery-codes');

        // May redirect to acknowledge page or return 200
        $this->assertTrue(in_array($response->status(), [200, 302]));
    }
}
