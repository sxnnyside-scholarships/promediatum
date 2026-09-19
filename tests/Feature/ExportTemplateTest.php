<?php

namespace Tests\Feature;

use App\Models\ExportTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_templates_index_renders_authenticated_user_templates(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ExportTemplate::create([
            'user_id' => $user->id,
            'name' => 'Reporte Oficial',
            'type' => 'student',
            'is_default' => true,
            'config' => ['colors' => ['primary' => '#1E293B']],
        ]);

        ExportTemplate::create([
            'user_id' => $otherUser->id,
            'name' => 'Plantilla Privada',
            'type' => 'student',
            'is_default' => false,
            'config' => ['colors' => ['primary' => '#000000']],
        ]);

        $response = $this->actingAs($user)->get(route('exports.templates.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Exports/Templates/Index')
            ->has('templates', 1)
            ->where('templates.0.name', 'Reporte Oficial')
        );
    }

    public function test_template_can_be_created_and_resets_previous_default(): void
    {
        $user = User::factory()->create();

        $first = ExportTemplate::create([
            'user_id' => $user->id,
            'name' => 'Plantilla 1',
            'type' => 'student',
            'is_default' => true,
            'config' => ExportTemplate::defaultConfig(),
        ]);

        $response = $this->actingAs($user)->post(route('exports.templates.store'), [
            'name' => 'Plantilla 2',
            'type' => 'student',
            'is_default' => true,
            'config' => ExportTemplate::defaultConfig(),
        ]);

        $response->assertRedirect(route('exports.templates.index'));
        $this->assertFalse($first->fresh()->is_default);
        $this->assertDatabaseHas('export_templates', [
            'user_id' => $user->id,
            'name' => 'Plantilla 2',
            'is_default' => true,
        ]);
    }

    public function test_user_cannot_edit_or_delete_another_users_template(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $template = ExportTemplate::create([
            'user_id' => $owner->id,
            'name' => 'Plantilla Confidencial',
            'type' => 'student',
            'is_default' => false,
            'config' => ExportTemplate::defaultConfig(),
        ]);

        $this->actingAs($attacker)
            ->get(route('exports.templates.edit', $template->id))
            ->assertForbidden();

        $this->actingAs($attacker)
            ->put(route('exports.templates.update', $template->id), [
                'name' => 'Hacked Name',
                'type' => 'student',
                'is_default' => false,
                'config' => ExportTemplate::defaultConfig(),
            ])
            ->assertForbidden();

        $this->actingAs($attacker)
            ->delete(route('exports.templates.destroy', $template->id))
            ->assertForbidden();

        $this->assertDatabaseHas('export_templates', ['id' => $template->id]);
    }

    public function test_template_can_be_updated_and_deleted(): void
    {
        $user = User::factory()->create();

        $template = ExportTemplate::create([
            'user_id' => $user->id,
            'name' => 'Plantilla Original',
            'type' => 'student',
            'is_default' => false,
            'config' => ExportTemplate::defaultConfig(),
        ]);

        $response = $this->actingAs($user)->put(route('exports.templates.update', $template->id), [
            'name' => 'Plantilla Modificada',
            'type' => 'student',
            'is_default' => true,
            'config' => ExportTemplate::defaultConfig(),
        ]);

        $response->assertRedirect(route('exports.templates.index'));
        $this->assertSame('Plantilla Modificada', $template->fresh()->name);
        $this->assertTrue($template->fresh()->is_default);

        $deleteResponse = $this->actingAs($user)->delete(route('exports.templates.destroy', $template->id));
        $deleteResponse->assertRedirect(route('exports.templates.index'));
        $this->assertDatabaseMissing('export_templates', ['id' => $template->id]);
    }
}
