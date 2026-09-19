<?php

namespace Tests\Feature;

use App\Models\ExportHistory;
use App\Models\Group;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_history_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);
        $group = Group::create([
            'period_id' => $period->id,
            'name' => 'Biología 1A',
            'subject' => 'Biología',
            'slug' => 'biologia-1a',
            'grade_level' => '1ro',
            'is_archived' => false,
        ]);
        $student = Student::create([
            'first_name' => 'Mariana',
            'last_name' => 'Torres',
            'slug' => Student::generateSlug('Mariana', 'Torres'),
            'email' => 'mariana@estudiante.test',
        ]);
        $group->students()->attach($student->id, ['period_id' => $period->id]);

        $response = $this->actingAs($user)->get(route('exports.history'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Exports/History')
            ->has('periods', 1)
            ->has('groups', 1)
            ->has('students', 1)
            ->has('prefill')
            ->where('prefill.type', null)
        );
    }

    public function test_exports_history_receives_prefill_query_parameters(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-B',
            'slug' => '2026-b',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-15',
            'is_active' => true,
        ]);
        $group = Group::create([
            'period_id' => $period->id,
            'name' => 'Literatura 3A',
            'subject' => 'Literatura',
            'slug' => 'literatura-3a',
            'grade_level' => '3ro',
            'is_archived' => false,
        ]);
        $student = Student::create([
            'first_name' => 'Gabriel',
            'last_name' => 'Sánchez',
            'slug' => Student::generateSlug('Gabriel', 'Sánchez'),
            'email' => 'gabriel@estudiante.test',
        ]);
        $group->students()->attach($student->id, ['period_id' => $period->id]);

        // Arrive from Student View
        $response = $this->actingAs($user)->get(route('exports.history', [
            'type' => 'student',
            'student_id' => $student->id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Exports/History')
            ->where('prefill.type', 'student')
            ->where('prefill.student_id', (string) $student->id)
        );

        // Arrive from Group View
        $responseGroup = $this->actingAs($user)->get(route('exports.history', [
            'type' => 'group',
            'group_id' => $group->id,
        ]));

        $responseGroup->assertStatus(200);
        $responseGroup->assertInertia(fn ($page) => $page
            ->component('Exports/History')
            ->where('prefill.type', 'group')
            ->where('prefill.group_id', (string) $group->id)
        );
    }

    public function test_export_generation_creates_history_record(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);
        $group = Group::create([
            'period_id' => $period->id,
            'name' => 'Artes 2A',
            'subject' => 'Artes',
            'slug' => 'artes-2a',
            'grade_level' => '2do',
            'is_archived' => false,
        ]);
        $student = Student::create([
            'first_name' => 'Estudiante',
            'last_name' => 'Prueba',
            'slug' => Student::generateSlug('Estudiante', 'Prueba'),
        ]);
        $group->students()->attach($student->id, ['period_id' => $period->id]);

        $response = $this->actingAs($user)->post(route('exports.store'), [
            'type' => 'group',
            'format' => 'csv',
            'period_id' => $period->id,
            'group_id' => $group->id,
            'delivery_method' => 'download',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('export_histories', [
            'user_id' => $user->id,
            'type' => 'group',
            'format' => 'csv',
            'group_id' => $group->id,
            'period_id' => $period->id,
        ]);
    }

    public function test_export_download_forbidden_for_other_users(): void
    {
        Storage::fake('local');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $history = ExportHistory::create([
            'user_id' => $user1->id,
            'period_id' => $period->id,
            'type' => 'group',
            'format' => 'csv',
            'file_name' => 'test_export.csv',
            'file_path' => 'exports/test_export.csv',
        ]);

        // User 2 cannot download user 1's export
        $response = $this->actingAs($user2)->get(route('exports.download', $history));
        $response->assertStatus(403);
    }
}
