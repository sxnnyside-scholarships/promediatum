<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed_with_enriched_educator_data(): void
    {
        $user = User::factory()->create([
            'first_name' => 'María',
            'last_name' => 'González',
            'email' => 'maria@docente.test',
            'institution' => 'Colegio Pedagógico Nacional',
            'pronoun' => 'ella',
            'educational_area' => 'Ciencias Exactas',
            'educational_level' => 'Secundaria',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Profile/Index')
            ->has('user')
            ->where('user.first_name', 'María')
            ->where('user.last_name', 'González')
            ->where('user.full_name', 'María González')
            ->where('user.initials', 'MG')
            ->where('user.institution', 'Colegio Pedagógico Nacional')
            ->where('user.pronoun', 'ella')
            ->where('user.educational_area', 'Ciencias Exactas')
            ->where('user.educational_level', 'Secundaria')
            ->has('user.unused_recovery_codes_count')
        );
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'institution' => 'Instituto de Prueba',
                'pronoun' => 'él',
                'educational_area' => 'Matemáticas',
                'educational_level' => 'Preparatoria',
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->last_name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('Instituto de Prueba', $user->institution);
        $this->assertSame('él', $user->pronoun);
        $this->assertSame('Matemáticas', $user->educational_area);
        $this->assertSame('Preparatoria', $user->educational_level);
    }

    public function test_password_can_be_updated_from_profile(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentPassword123'),
        ]);

        $response = $this
            ->actingAs($user)
            ->put('/profile/password', [
                'current_password' => 'CurrentPassword123',
                'password' => 'NewSecurePassword456',
                'password_confirmation' => 'NewSecurePassword456',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewSecurePassword456', $user->refresh()->password));
    }
}
