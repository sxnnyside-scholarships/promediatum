<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Observation;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_observations_index_can_be_rendered(): void
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
            'name' => 'Matemáticas 1A',
            'subject' => 'Matemáticas',
            'slug' => 'matematicas-1a',
            'grade_level' => '1ro',
            'is_archived' => false,
        ]);
        $student = Student::create([
            'first_name' => 'Carlos',
            'last_name' => 'Hernández',
            'slug' => Student::generateSlug('Carlos', 'Hernández'),
            'email' => 'carlos@estudiante.test',
        ]);
        $group->students()->attach($student->id, ['period_id' => $period->id]);

        Observation::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'period_id' => $period->id,
            'type' => 'behavior',
            'content' => 'Excelente participación en clase.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('observations.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Observations/Index')
            ->has('observations', 1)
            ->has('groups', 1)
            ->has('students', 1)
        );
    }

    public function test_observation_can_be_created_via_store(): void
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
            'name' => 'Historia 2B',
            'subject' => 'Historia',
            'slug' => 'historia-2b',
            'grade_level' => '2do',
            'is_archived' => false,
        ]);
        $student = Student::create([
            'first_name' => 'Lucía',
            'last_name' => 'Morales',
            'slug' => Student::generateSlug('Lucía', 'Morales'),
            'email' => 'lucia@estudiante.test',
        ]);

        $response = $this->actingAs($user)
            ->from(route('observations.index'))
            ->post(route('observations.store'), [
                'student_id' => $student->id,
                'group_id' => $group->id,
                'type' => 'achievement',
                'content' => 'Ganó primer lugar en la feria de ciencias.',
            ]);

        $response->assertRedirect(route('observations.index'));
        $this->assertDatabaseHas('observations', [
            'student_id' => $student->id,
            'group_id' => $group->id,
            'type' => 'achievement',
            'content' => 'Ganó primer lugar en la feria de ciencias.',
            'status' => 'pending',
        ]);
    }

    public function test_observation_resolution_can_be_toggled(): void
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
            'name' => 'Física 3C',
            'subject' => 'Física',
            'slug' => 'fisica-3c',
            'grade_level' => '3ro',
            'is_archived' => false,
        ]);
        $student = Student::create([
            'first_name' => 'Mateo',
            'last_name' => 'Gómez',
            'slug' => Student::generateSlug('Mateo', 'Gómez'),
            'email' => 'mateo@estudiante.test',
        ]);

        $observation = Observation::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'period_id' => $period->id,
            'type' => 'followup',
            'content' => 'Reunión con tutor pendiente.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('observations.toggle-resolved', $observation));

        $response->assertRedirect();
        $this->assertDatabaseHas('observations', [
            'id' => $observation->id,
            'status' => 'resolved',
        ]);
    }

    public function test_observation_can_be_deleted(): void
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
            'name' => 'Química 4D',
            'subject' => 'Química',
            'slug' => 'quimica-4d',
            'grade_level' => '4to',
            'is_archived' => false,
        ]);
        $student = Student::create([
            'first_name' => 'Sofía',
            'last_name' => 'López',
            'slug' => Student::generateSlug('Sofía', 'López'),
            'email' => 'sofia@estudiante.test',
        ]);

        $observation = Observation::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'period_id' => $period->id,
            'type' => 'performance',
            'content' => 'Requiere apoyo en estequiometría.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->delete(route('observations.destroy', $observation));

        $response->assertRedirect();
        $this->assertDatabaseMissing('observations', [
            'id' => $observation->id,
        ]);
    }
}
