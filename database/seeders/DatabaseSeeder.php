<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\GradeCategory;
use App\Models\Group;
use App\Models\Observation;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use App\Services\RecoveryCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic development data.
     *
     * Creates:
     * - 1 teacher user
     * - 1 active period + 1 past period
     * - 3 groups with grade categories
     * - 12 students distributed across groups
     * - Attendance records (with consecutive absences for risk detection)
     * - Sample grades (some below 60% for risk flags)
     * - Observations (mix of types and statuses)
     */
    public function run(): void
    {
        // ── Teacher User ──
        $user = User::create([
            'first_name' => 'María',
            'last_name' => 'González',
            'email' => 'maria@promediatum.test',
            'password' => Hash::make('password1'),
            'institution' => 'Escuela Normal Superior',
            'pronoun' => 'ella',
            'educational_area' => 'Ciencias Exactas',
            'educational_level' => 'Secundaria',
            'settings' => [
                'sidebar_position' => 'leading',
                'text_weight' => '400',
                'fab_enabled' => true,
            ],
        ]);

        // Generate recovery codes
        $recoveryService = app(RecoveryCodeService::class);
        $recoveryService->generate($user);

        // ── Periods ──
        $pastPeriod = Period::create([
            'name' => 'Agosto–Diciembre 2024',
            'slug' => Period::generateSlug('Agosto–Diciembre 2024'),
            'start_date' => '2024-08-19',
            'end_date' => '2024-12-13',
            'is_active' => false,
        ]);

        $activePeriod = Period::create([
            'name' => 'Enero–Junio 2025',
            'slug' => Period::generateSlug('Enero–Junio 2025'),
            'start_date' => '2025-01-13',
            'end_date' => '2025-06-27',
            'is_active' => true,
        ]);

        // ── Students ──
        $studentsData = [
            ['first_name' => 'Carlos', 'last_name' => 'Hernández'],
            ['first_name' => 'Ana', 'last_name' => 'Martínez'],
            ['first_name' => 'Luis', 'last_name' => 'Ramírez'],
            ['first_name' => 'Sofía', 'last_name' => 'López'],
            ['first_name' => 'Diego', 'last_name' => 'Torres'],
            ['first_name' => 'Valentina', 'last_name' => 'García'],
            ['first_name' => 'Miguel', 'last_name' => 'Flores'],
            ['first_name' => 'Isabella', 'last_name' => 'Morales'],
            ['first_name' => 'Andrés', 'last_name' => 'Sánchez'],
            ['first_name' => 'Camila', 'last_name' => 'Ruiz'],
            ['first_name' => 'Javier', 'last_name' => 'Ortega'],
            ['first_name' => 'Daniela', 'last_name' => 'Vega'],
        ];

        $students = collect();
        foreach ($studentsData as $data) {
            $students->push(Student::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'slug' => Student::generateSlug($data['first_name'], $data['last_name']),
            ]));
        }

        // ── Groups ──
        $mathGroup = Group::create([
            'name' => 'Matemáticas 3-A',
            'slug' => Group::generateSlug('Matemáticas 3-A'),
            'subject' => 'Matemáticas',
            'educational_level' => 'Secundaria',
            'period_id' => $activePeriod->id,
        ]);

        $scienceGroup = Group::create([
            'name' => 'Ciencias 2-B',
            'slug' => Group::generateSlug('Ciencias 2-B'),
            'subject' => 'Ciencias Naturales',
            'educational_level' => 'Secundaria',
            'period_id' => $activePeriod->id,
        ]);

        $historyGroup = Group::create([
            'name' => 'Historia 1-C',
            'slug' => Group::generateSlug('Historia 1-C'),
            'subject' => 'Historia',
            'educational_level' => 'Secundaria',
            'period_id' => $activePeriod->id,
        ]);

        // ── Assign students to groups ──
        // Math: first 8 students
        foreach ($students->take(8) as $s) {
            $mathGroup->students()->attach($s->id, ['period_id' => $activePeriod->id]);
        }
        // Science: students 3–10
        foreach ($students->slice(2, 8) as $s) {
            $scienceGroup->students()->attach($s->id, ['period_id' => $activePeriod->id]);
        }
        // History: students 5–12
        foreach ($students->slice(4) as $s) {
            $historyGroup->students()->attach($s->id, ['period_id' => $activePeriod->id]);
        }

        // ── Grade Categories ──
        // Math: Exams 40%, Homework 30%, Participation 30%
        $mathExams = GradeCategory::create(['group_id' => $mathGroup->id, 'name' => 'Exámenes', 'weight' => 40]);
        $mathHw = GradeCategory::create(['group_id' => $mathGroup->id, 'name' => 'Tareas', 'weight' => 30]);
        $mathPart = GradeCategory::create(['group_id' => $mathGroup->id, 'name' => 'Participación', 'weight' => 30]);

        // Science: Projects 35%, Labs 35%, Quizzes 30%
        $sciProjects = GradeCategory::create(['group_id' => $scienceGroup->id, 'name' => 'Proyectos', 'weight' => 35]);
        $sciLabs = GradeCategory::create(['group_id' => $scienceGroup->id, 'name' => 'Laboratorio', 'weight' => 35]);
        $sciQuiz = GradeCategory::create(['group_id' => $scienceGroup->id, 'name' => 'Quizzes', 'weight' => 30]);

        // History: Essays 40%, Exams 35%, Participation 25%
        $hisEssays = GradeCategory::create(['group_id' => $historyGroup->id, 'name' => 'Ensayos', 'weight' => 40]);
        $hisExams = GradeCategory::create(['group_id' => $historyGroup->id, 'name' => 'Exámenes', 'weight' => 35]);
        $hisPart = GradeCategory::create(['group_id' => $historyGroup->id, 'name' => 'Participación', 'weight' => 25]);

        // ── Grades ──
        // Math group grades — some students perform poorly for risk detection
        $mathStudents = $students->take(8);
        foreach ($mathStudents as $idx => $student) {
            // Exam 1
            Grade::create([
                'student_id' => $student->id,
                'group_id' => $mathGroup->id,
                'period_id' => $activePeriod->id,
                'category_id' => $mathExams->id,
                'title' => 'Examen Parcial 1',
                'score' => $idx < 2 ? rand(30, 50) : rand(65, 95), // First 2 students at risk
                'max_score' => 100,
                'date' => '2025-02-14',
            ]);

            // Homework
            Grade::create([
                'student_id' => $student->id,
                'group_id' => $mathGroup->id,
                'period_id' => $activePeriod->id,
                'category_id' => $mathHw->id,
                'title' => 'Tarea: Ecuaciones',
                'score' => $idx === 0 ? 3 : rand(7, 10), // Carlos at risk
                'max_score' => 10,
                'date' => '2025-02-07',
            ]);

            // Participation
            Grade::create([
                'student_id' => $student->id,
                'group_id' => $mathGroup->id,
                'period_id' => $activePeriod->id,
                'category_id' => $mathPart->id,
                'title' => 'Participación Febrero',
                'score' => $idx < 2 ? rand(4, 6) : rand(7, 10),
                'max_score' => 10,
                'date' => '2025-02-28',
            ]);
        }

        // ── Attendance ──
        // Generate 10 days of attendance for math group
        $attendanceDates = collect([
            '2025-02-03', '2025-02-04', '2025-02-05', '2025-02-06', '2025-02-07',
            '2025-02-10', '2025-02-11', '2025-02-12', '2025-02-13', '2025-02-14',
        ]);

        foreach ($mathStudents as $idx => $student) {
            foreach ($attendanceDates as $dateIdx => $date) {
                $status = 'present';

                // Carlos (idx 0): 4 consecutive absences (days 5–8) — triggers risk
                if ($idx === 0 && $dateIdx >= 4 && $dateIdx <= 7) {
                    $status = 'absent';
                }
                // Ana (idx 1): 3 consecutive absences (days 7–9)
                elseif ($idx === 1 && $dateIdx >= 6 && $dateIdx <= 8) {
                    $status = 'absent';
                }
                // Random absences for others
                elseif (rand(1, 10) === 1) {
                    $status = 'absent';
                }

                Attendance::create([
                    'student_id' => $student->id,
                    'group_id' => $mathGroup->id,
                    'period_id' => $activePeriod->id,
                    'date' => $date,
                    'status' => $status,
                ]);
            }
        }

        // ── Observations ──
        // Carlos — performance concern (pending)
        Observation::create([
            'student_id' => $students[0]->id,
            'group_id' => $mathGroup->id,
            'period_id' => $activePeriod->id,
            'type' => 'performance',
            'content' => 'Calificaciones consistentemente bajas en exámenes y tareas. Requiere atención inmediata y plan de apoyo académico.',
            'status' => 'pending',
        ]);

        // Carlos — behavior followup (pending)
        Observation::create([
            'student_id' => $students[0]->id,
            'group_id' => $mathGroup->id,
            'period_id' => $activePeriod->id,
            'type' => 'followup',
            'content' => 'Se ha notado desinterés y distracción en clase. Contactar a padres de familia para reunión.',
            'status' => 'pending',
        ]);

        // Ana — attendance concern (pending)
        Observation::create([
            'student_id' => $students[1]->id,
            'group_id' => $mathGroup->id,
            'period_id' => $activePeriod->id,
            'type' => 'behavior',
            'content' => 'Tres ausencias consecutivas sin justificación. Verificar situación familiar.',
            'status' => 'pending',
        ]);

        // Sofía — achievement (resolved)
        Observation::create([
            'student_id' => $students[3]->id,
            'group_id' => $mathGroup->id,
            'period_id' => $activePeriod->id,
            'type' => 'achievement',
            'content' => 'Excelente desempeño en el examen parcial. Candidata para olimpiada de matemáticas.',
            'status' => 'resolved',
            'resolved_at' => now()->subDays(3),
        ]);

        // Diego — behavior (resolved)
        Observation::create([
            'student_id' => $students[4]->id,
            'group_id' => $scienceGroup->id,
            'period_id' => $activePeriod->id,
            'type' => 'behavior',
            'content' => 'Incidente de conducta en laboratorio. Se habló con el estudiante y se llegó a un acuerdo.',
            'status' => 'resolved',
            'resolved_at' => now()->subDays(7),
        ]);

        // Valentina — performance followup (pending)
        Observation::create([
            'student_id' => $students[5]->id,
            'group_id' => $scienceGroup->id,
            'period_id' => $activePeriod->id,
            'type' => 'performance',
            'content' => 'Dificultades con el proyecto de ciencias. Necesita tutoría adicional en metodología científica.',
            'status' => 'pending',
        ]);

        // Miguel — achievement (pending review)
        Observation::create([
            'student_id' => $students[6]->id,
            'group_id' => $historyGroup->id,
            'period_id' => $activePeriod->id,
            'type' => 'achievement',
            'content' => 'Ensayo destacado sobre la Revolución Mexicana. Considerar para concurso de ensayo escolar.',
            'status' => 'pending',
        ]);

        // Javier — followup (pending)
        Observation::create([
            'student_id' => $students[10]->id,
            'group_id' => $historyGroup->id,
            'period_id' => $activePeriod->id,
            'type' => 'followup',
            'content' => 'Seguimiento a reunión con padres. El estudiante muestra mejora gradual en participación.',
            'status' => 'pending',
        ]);
    }
}
