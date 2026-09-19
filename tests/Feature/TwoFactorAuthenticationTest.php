<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RecoveryCodeService;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_displays_two_factor_and_recovery_codes_status(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Profile/Index')
            ->where('user.two_factor_enabled', false)
            ->where('user.two_factor_confirmed_at', null)
            ->has('user.unused_recovery_codes_count')
        );
    }

    public function test_two_factor_setup_generates_secret_and_qr_svg(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/profile/two-factor/setup');

        $response->assertOk();
        $response->assertJsonStructure(['secret', 'qr_svg']);
        $this->assertNotEmpty($response->json('secret'));
        $this->assertStringContainsString('<svg', $response->json('qr_svg'));
        $this->assertSame($response->json('secret'), session('two_factor_setup_secret'));
    }

    public function test_two_factor_confirm_activates_2fa_with_valid_code(): void
    {
        $user = User::factory()->create();

        $secret = 'JBSWY3DPEHPK3PXP';
        session(['two_factor_setup_secret' => $secret]);

        // Mock TwoFactorAuthenticationService to return true for code 123456
        $service = Mockery::mock(TwoFactorAuthenticationService::class);
        $service->shouldReceive('verify')
            ->with($secret, '123456')
            ->andReturn(true);
        $this->app->instance(TwoFactorAuthenticationService::class, $service);

        $response = $this->actingAs($user)->postJson('/profile/two-factor/confirm', [
            'code' => '123456',
        ]);

        $response->assertOk();
        $user->refresh();

        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
        $this->assertSame($secret, $user->two_factor_secret);
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertNull(session('two_factor_setup_secret'));
    }

    public function test_two_factor_confirm_fails_with_invalid_code(): void
    {
        $user = User::factory()->create();

        $secret = 'JBSWY3DPEHPK3PXP';
        session(['two_factor_setup_secret' => $secret]);

        $service = Mockery::mock(TwoFactorAuthenticationService::class);
        $service->shouldReceive('verify')
            ->with($secret, '000000')
            ->andReturn(false);
        $this->app->instance(TwoFactorAuthenticationService::class, $service);

        $response = $this->actingAs($user)->postJson('/profile/two-factor/confirm', [
            'code' => '000000',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('code');

        $user->refresh();
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_two_factor_can_be_disabled_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPassword123'),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());

        $response = $this->actingAs($user)->deleteJson('/profile/two-factor/disable', [
            'password' => 'CorrectPassword123',
        ]);

        $response->assertOk();
        $user->refresh();

        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_two_factor_cannot_be_disabled_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPassword123'),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user)->deleteJson('/profile/two-factor/disable', [
            'password' => 'WrongPassword',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');

        $user->refresh();
        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_login_redirects_to_two_factor_challenge_when_2fa_is_enabled(): void
    {
        $user = User::factory()->create([
            'email' => 'docente@promediatum.test',
            'password' => Hash::make('DocentePassword123'),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'docente@promediatum.test',
            'password' => 'DocentePassword123',
        ]);

        $response->assertRedirect('/two-factor-challenge');
        $this->assertSame($user->id, session('login.id'));
        $this->assertGuest();
    }

    public function test_two_factor_challenge_authenticates_with_valid_totp_code(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);

        session(['login.id' => $user->id]);

        $service = Mockery::mock(TwoFactorAuthenticationService::class);
        $service->shouldReceive('verify')
            ->with('JBSWY3DPEHPK3PXP', '123456')
            ->andReturn(true);
        $this->app->instance(TwoFactorAuthenticationService::class, $service);

        $response = $this->post('/two-factor-challenge', [
            'code' => '123456',
        ]);

        $response->assertRedirect('/workspace');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('login.id'));
    }

    public function test_two_factor_challenge_authenticates_and_consumes_recovery_code(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);

        /** @var RecoveryCodeService $recoveryService */
        $recoveryService = app(RecoveryCodeService::class);
        $plainCodes = $recoveryService->generate($user);
        $firstCode = $plainCodes[0];

        session(['login.id' => $user->id]);

        $response = $this->post('/two-factor-challenge', [
            'recovery_code' => $firstCode,
        ]);

        $response->assertRedirect('/workspace');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('login.id'));

        // The used recovery code cannot be reused
        $this->assertFalse($recoveryService->validate($user, $firstCode));
        $this->assertSame(5, $recoveryService->remainingCount($user));
    }

    public function test_recovery_codes_can_be_regenerated_with_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('DocentePassword123'),
        ]);

        $response = $this->actingAs($user)->postJson('/recovery-codes/regenerate', [
            'password' => 'DocentePassword123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['codes', 'download_content', 'remaining_count', 'message']);
        $this->assertCount(6, $response->json('codes'));
        $this->assertSame(6, $response->json('remaining_count'));
        $this->assertSame(6, $user->unusedRecoveryCodes()->count());
    }
}
