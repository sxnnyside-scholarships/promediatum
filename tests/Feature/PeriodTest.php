<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_periods_redirects_guests_to_login(): void
    {
        $response = $this->get('/periods');

        $response->assertRedirect('/login');
    }

    public function test_periods_index_renders_with_periods_list(): void
    {
        $user = User::factory()->create();

        Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/periods');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Periods/Index')
            ->has('periods', 1)
        );
    }

    public function test_period_create_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/periods/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Periods/Create'));
    }

    public function test_period_can_be_created_and_auto_activates_if_first(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/periods', [
            'name' => '2026-A',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
        ]);

        $period = Period::where('name', '2026-A')->first();
        $this->assertNotNull($period);
        $this->assertTrue($period->is_active);
        $response->assertRedirect(route('periods.show', $period->slug));
    }

    public function test_period_show_is_rendered(): void
    {
        $user = User::factory()->create();

        $period = Period::create([
            'name' => '2026-B',
            'slug' => '2026-b',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('periods.show', $period->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Periods/Show')
            ->where('period.name', '2026-B')
        );
    }

    public function test_period_active_state_can_be_toggled(): void
    {
        $user = User::factory()->create();

        $period = Period::create([
            'name' => '2026-C',
            'slug' => '2026-c',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('periods.toggle-active', $period->slug));
        $response->assertRedirect();
        $this->assertFalse($period->fresh()->is_active);

        $response = $this->actingAs($user)->post(route('periods.toggle-active', $period->slug));
        $response->assertRedirect();
        $this->assertTrue($period->fresh()->is_active);
    }
}
