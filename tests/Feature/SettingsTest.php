<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_redirects_guests_to_login(): void
    {
        $response = $this->get('/settings');

        $response->assertRedirect('/login');
    }

    public function test_settings_index_renders_with_user_preferences(): void
    {
        $user = User::factory()->create([
            'settings' => [
                'sidebar_position' => 'leading',
                'text_weight' => '500',
                'fab_enabled' => true,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->where('settings.sidebar_position', 'leading')
            ->where('settings.text_weight', '500')
            ->where('settings.fab_enabled', true)
            ->has('backups')
        );
    }

    public function test_backups_url_redirects_to_settings_backups(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/backups');

        $response->assertRedirect(route('settings.index').'#backups');
    }

    public function test_settings_can_be_updated(): void
    {
        $user = User::factory()->create([
            'settings' => [
                'sidebar_position' => 'leading',
            ],
        ]);

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'sidebar_position' => 'trailing',
            'text_weight' => '600',
            'fab_enabled' => false,
            'icons_enabled' => true,
        ]);

        $response->assertRedirect();
        $freshUser = $user->fresh();
        $this->assertSame('trailing', $freshUser->settings['sidebar_position']);
        $this->assertSame('600', $freshUser->settings['text_weight']);
        $this->assertFalse($freshUser->settings['fab_enabled']);
        $this->assertTrue($freshUser->settings['icons_enabled']);
    }
}
