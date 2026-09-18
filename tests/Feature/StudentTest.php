<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/students');

        $response->assertOk();
    }

    public function test_student_can_be_created_with_contact_notes_and_groups(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $group1 = Group::create([
            'period_id' => $period->id,
            'name' => 'Matemáticas Avanzadas',
            'subject' => 'Matemáticas',
            'slug' => 'matematicas-avanzadas',
            'grade_level' => 'Secundaria',
            'is_archived' => false,
        ]);

        $group2 = Group::create([
            'period_id' => $period->id,
            'name' => 'Física Cuántica',
            'subject' => 'Física',
            'slug' => 'fisica-cuantica',
            'grade_level' => 'Secundaria',
            'is_archived' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/students', [
                'first_name' => 'Carlos',
                'last_name' => 'Hernández',
                'email' => 'carlos@estudiante.test',
                'phone' => '+52 55 1234 5678',
                'guardian_name' => 'María Hernández',
                'notes' => 'Excelente comprensión analítica, adaptación curricular de ritmo.',
                'group_ids' => [$group1->id, $group2->id],
            ]);

        $response->assertSessionHasNoErrors();

        $student = Student::where('email', 'carlos@estudiante.test')->first();
        $this->assertNotNull($student);
        $this->assertSame('Carlos', $student->first_name);
        $this->assertSame('Hernández', $student->last_name);
        $this->assertSame('Carlos Hernández', $student->full_name);
        $this->assertSame('CH', $student->initials);
        $this->assertSame('María Hernández', $student->guardian_name);
        $this->assertCount(2, $student->groups);
    }

    public function test_student_profile_is_displayed(): void
    {
        $user = User::factory()->create();
        $student = Student::create([
            'first_name' => 'Ana',
            'last_name' => 'Martínez',
            'slug' => Student::generateSlug('Ana', 'Martínez'),
            'email' => 'ana@estudiante.test',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('students.show', $student->slug));

        $response->assertOk();
    }

    public function test_student_profile_can_be_updated(): void
    {
        $user = User::factory()->create();
        $student = Student::create([
            'first_name' => 'Ana',
            'last_name' => 'Martínez',
            'slug' => Student::generateSlug('Ana', 'Martínez'),
            'email' => 'ana@estudiante.test',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('students.update', $student->slug), [
                'first_name' => 'Ana Sofía',
                'last_name' => 'Martínez López',
                'email' => 'anasofia@estudiante.test',
                'phone' => '555-9876',
                'guardian_name' => 'Roberto Martínez',
                'notes' => 'Nueva nota actualizada.',
            ]);

        $response->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('Ana Sofía', $student->first_name);
        $this->assertSame('Martínez López', $student->last_name);
        $this->assertSame('AM', $student->initials);
        $this->assertSame('anasofia@estudiante.test', $student->email);
        $this->assertSame('Roberto Martínez', $student->guardian_name);
    }
}
