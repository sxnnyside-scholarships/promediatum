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

    public function test_students_index_calculates_batched_metrics_with_grades_and_alerts(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-B',
            'slug' => '2026-b',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $group = Group::create([
            'period_id' => $period->id,
            'name' => 'Historia Universal',
            'subject' => 'Historia',
            'slug' => 'historia-universal',
            'grade_level' => 'Secundaria',
            'is_archived' => false,
        ]);

        $category = \App\Models\GradeCategory::create([
            'group_id' => $group->id,
            'name' => 'Exámenes',
            'weight' => 100,
        ]);

        $student = Student::create([
            'first_name' => 'Lucía',
            'last_name' => 'Gómez',
            'slug' => Student::generateSlug('Lucía', 'Gómez'),
            'email' => 'lucia@estudiante.test',
        ]);

        $student->groups()->attach($group->id, ['period_id' => $period->id]);

        \App\Models\Grade::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'period_id' => $period->id,
            'category_id' => $category->id,
            'title' => 'Parcial 1',
            'score' => 50,
            'max_score' => 100,
            'date' => '2026-03-01',
        ]);

        // 3 consecutive absences
        for ($i = 1; $i <= 3; $i++) {
            \App\Models\Attendance::create([
                'student_id' => $student->id,
                'group_id' => $group->id,
                'period_id' => $period->id,
                'date' => "2026-03-0{$i}",
                'status' => 'absent',
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get('/students');

        $response->assertOk();
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Students/Index')
            ->has('metrics.'.$student->id, fn (\Inertia\Testing\AssertableInertia $metric) => $metric
                ->where('average', 50)
                ->where('at_risk', true)
                ->where('has_absence_alert', true)
                ->where('groups_count', 1)
            )
        );
    }
}
