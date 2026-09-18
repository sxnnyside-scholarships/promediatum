<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Observation;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_redirects_guests_to_login(): void
    {
        $response = $this->get('/workspace');

        $response->assertRedirect('/login');
    }

    public function test_workspace_index_renders_with_cockpit_data(): void
    {
        $user = User::factory()->create([
            'first_name' => 'María',
            'last_name' => 'González',
            'institution' => 'Colegio Pedagógico Nacional',
        ]);

        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
            'is_active' => true,
        ]);

        $group = Group::create([
            'period_id' => $period->id,
            'name' => '1°A Biología',
            'subject' => 'Biología',
            'educational_level' => 'Secundaria',
            'slug' => '1a-biologia',
            'is_archived' => false,
        ]);

        $student = Student::create([
            'first_name' => 'Carlos',
            'last_name' => 'Hernández',
            'slug' => Student::generateSlug('Carlos', 'Hernández'),
            'email' => 'carlos@alumno.test',
        ]);

        $group->students()->attach($student->id, ['period_id' => $period->id]);

        Observation::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'period_id' => $period->id,
            'type' => 'performance',
            'content' => 'Comprensión sobresaliente en el laboratorio de células.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/workspace');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Workspace/Index')
            ->has('activePeriod')
            ->where('activePeriod.name', '2026-A')
            ->has('activePeriod.progress_percent')
            ->has('groups', 1)
            ->where('groups.0.name', '1°A Biología')
            ->where('groups.0.grade_level', 'Secundaria')
            ->has('pendingObservations', 1)
            ->where('pendingObservations.0.student.full_name', 'Carlos Hernández')
            ->where('pendingObservations.0.student.initials', 'CH')
            ->has('stats')
            ->where('stats.total_groups', 1)
            ->where('stats.total_students', 1)
            ->where('stats.pending_observations', 1)
        );
    }
}
