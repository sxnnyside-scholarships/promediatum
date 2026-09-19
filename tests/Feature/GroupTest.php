<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_redirects_guests_to_login(): void
    {
        $response = $this->get('/groups');

        $response->assertRedirect('/login');
    }

    public function test_groups_index_is_displayed_with_periods(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        Group::create([
            'period_id' => $period->id,
            'name' => '1°A Biología',
            'subject' => 'Biología',
            'slug' => '1a-biologia',
            'grade_level' => 'Secundaria',
            'is_archived' => false,
        ]);

        $response = $this->actingAs($user)->get('/groups');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Groups/Index')
            ->has('groups', 1)
            ->has('periods', 1)
            ->where('activePeriodId', $period->id)
        );
    }

    public function test_group_create_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/groups/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Groups/Create'));
    }

    public function test_group_can_be_created(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/groups', [
            'name' => 'Química Orgánica',
            'subject' => 'Química',
            'educational_level' => 'Preparatoria',
            'period_id' => $period->id,
        ]);

        $group = Group::where('name', 'Química Orgánica')->first();
        $this->assertNotNull($group);
        $response->assertRedirect(route('groups.show', $group->slug));
    }

    public function test_group_show_renders_enrolled_and_available_students(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $group = Group::create([
            'period_id' => $period->id,
            'name' => 'Física Elemental',
            'subject' => 'Física',
            'slug' => 'fisica-elemental',
            'grade_level' => 'Secundaria',
            'is_archived' => false,
        ]);

        $enrolled = Student::create([
            'first_name' => 'Carlos',
            'last_name' => 'Hernández',
            'slug' => Student::generateSlug('Carlos', 'Hernández'),
            'email' => 'carlos@estudiante.test',
        ]);
        $group->students()->attach($enrolled->id, ['period_id' => $period->id]);

        $available = Student::create([
            'first_name' => 'Lucía',
            'last_name' => 'Gómez',
            'slug' => Student::generateSlug('Lucía', 'Gómez'),
            'email' => 'lucia@estudiante.test',
        ]);

        $response = $this->actingAs($user)->get(route('groups.show', $group->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Groups/Show')
            ->where('group.name', 'Física Elemental')
            ->has('students', 1)
            ->where('students.0.id', $enrolled->id)
            ->has('availableStudents', 1)
            ->where('availableStudents.0.id', $available->id)
        );
    }

    public function test_group_can_be_archived_and_unarchived(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $group = Group::create([
            'period_id' => $period->id,
            'name' => 'Álgebra Lineal',
            'subject' => 'Matemáticas',
            'slug' => 'algebra-lineal',
            'is_archived' => false,
        ]);

        $response = $this->actingAs($user)->post(route('groups.toggle-archive', $group->slug));
        $response->assertRedirect();
        $this->assertTrue($group->fresh()->is_archived);

        $response = $this->actingAs($user)->post(route('groups.toggle-archive', $group->slug));
        $response->assertRedirect();
        $this->assertFalse($group->fresh()->is_archived);
    }

    public function test_group_period_can_be_moved_and_syncs_student_pivots(): void
    {
        $user = User::factory()->create();
        $period1 = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);
        $period2 = Period::create([
            'name' => '2026-B',
            'slug' => '2026-b',
            'start_date' => now()->addMonths(2)->toDateString(),
            'end_date' => now()->addMonths(8)->toDateString(),
            'is_active' => false,
        ]);

        $group = Group::create([
            'period_id' => $period1->id,
            'name' => 'Taller de Lectura',
            'subject' => 'Español',
            'slug' => 'taller-de-lectura',
            'is_archived' => false,
        ]);

        $student = Student::create([
            'first_name' => 'María',
            'last_name' => 'López',
            'slug' => Student::generateSlug('María', 'López'),
            'email' => 'maria@estudiante.test',
        ]);
        $group->students()->attach($student->id, ['period_id' => $period1->id]);

        $response = $this->actingAs($user)->patch(route('groups.move-period', $group->slug), [
            'period_id' => $period2->id,
        ]);

        $response->assertRedirect();
        $this->assertSame($period2->id, $group->fresh()->period_id);
        $this->assertDatabaseHas('group_student', [
            'group_id' => $group->id,
            'student_id' => $student->id,
            'period_id' => $period2->id,
        ]);
    }

    public function test_students_can_be_enrolled_and_removed_from_group(): void
    {
        $user = User::factory()->create();
        $period = Period::create([
            'name' => '2026-A',
            'slug' => '2026-a',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $group = Group::create([
            'period_id' => $period->id,
            'name' => 'Geometría',
            'subject' => 'Matemáticas',
            'slug' => 'geometria',
            'is_archived' => false,
        ]);

        $student1 = Student::create([
            'first_name' => 'Sofía',
            'last_name' => 'Ramírez',
            'slug' => Student::generateSlug('Sofía', 'Ramírez'),
            'email' => 'sofia@estudiante.test',
        ]);
        $student2 = Student::create([
            'first_name' => 'Diego',
            'last_name' => 'Torres',
            'slug' => Student::generateSlug('Diego', 'Torres'),
            'email' => 'diego@estudiante.test',
        ]);

        // Single add
        $response = $this->actingAs($user)->post(route('groups.add-student', $group->slug), [
            'student_id' => $student1->id,
        ]);
        $response->assertRedirect();
        $this->assertCount(1, $group->fresh()->students);

        // Bulk add
        $response = $this->actingAs($user)->post(route('groups.bulk-add-students', $group->slug), [
            'student_ids' => [$student1->id, $student2->id],
        ]);
        $response->assertRedirect();
        $this->assertCount(2, $group->fresh()->students);

        // Remove
        $response = $this->actingAs($user)->delete(route('groups.remove-student', [$group->slug, $student1->id]));
        $response->assertRedirect();
        $this->assertCount(1, $group->fresh()->students);
        $this->assertSame($student2->id, $group->fresh()->students->first()->id);
    }
}
