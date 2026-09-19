<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_dashboard_is_displayed_with_groups_and_kpis(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
        ]);

        Group::create([
            'period_id' => $period->id,
            'name' => 'Química I',
            'subject' => 'Química',
            'slug' => 'quimica-i',
            'is_archived' => false,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Attendance/Dashboard')
            ->has('groups', 1)
            ->where('groups.0.name', 'Química I')
            ->has('kpis')
            ->where('kpis.total_groups', 1)
        );
    }

    public function test_attendance_index_renders_enrolled_students_with_status_and_matrix(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
        ]);

        $group = Group::create([
            'period_id' => $period->id,
            'name' => 'Biología I',
            'subject' => 'Biología',
            'slug' => 'biologia-i',
            'is_archived' => false,
        ]);

        $student = Student::create([
            'first_name' => 'Ana',
            'last_name' => 'Torres',
            'slug' => Student::generateSlug('Ana', 'Torres'),
            'email' => 'ana@estudiante.test',
        ]);
        $group->students()->attach($student->id, ['period_id' => $period->id]);

        $date = now()->toDateString();
        Attendance::create([
            'student_id' => $student->id,
            'group_id' => $group->id,
            'period_id' => $period->id,
            'date' => $date,
            'status' => 'present',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index', ['group' => $group->slug, 'date' => $date]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Attendance/Index')
            ->where('date', $date)
            ->has('students', 1)
            ->where('students.0.id', $student->id)
            ->where('students.0.status', 'present')
            ->has('sessionDates')
            ->has('otherGroups')
            ->has('stats')
        );
    }

    public function test_bulk_attendance_can_be_stored(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
        ]);

        $group = Group::create([
            'period_id' => $period->id,
            'name' => 'Historia I',
            'subject' => 'Historia',
            'slug' => 'historia-i',
            'is_archived' => false,
        ]);

        $student1 = Student::create([
            'first_name' => 'Carlos',
            'last_name' => 'Gómez',
            'slug' => Student::generateSlug('Carlos', 'Gómez'),
            'email' => 'carlos@estudiante.test',
        ]);
        $student2 = Student::create([
            'first_name' => 'Elena',
            'last_name' => 'Vargas',
            'slug' => Student::generateSlug('Elena', 'Vargas'),
            'email' => 'elena@estudiante.test',
        ]);
        $group->students()->attach([$student1->id, $student2->id], ['period_id' => $period->id]);

        $date = now()->toDateString();

        $response = $this->actingAs($user)->post(route('attendance.store', $group->slug), [
            'date' => $date,
            'records' => [
                ['student_id' => $student1->id, 'status' => 'present'],
                ['student_id' => $student2->id, 'status' => 'absent'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'group_id' => $group->id,
            'student_id' => $student1->id,
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('attendances', [
            'group_id' => $group->id,
            'student_id' => $student2->id,
            'status' => 'absent',
        ]);
    }
}
