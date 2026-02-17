<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for common query patterns.
 *
 * Covers:
 *  - students: slug lookups, name ordering
 *  - grades: composite lookups by student+group+period+category, date ordering
 *  - attendances: student+group+period composite, status filtering, date ordering
 *  - observations: student+period, status, created_at ordering
 *  - export_histories: type+period composite
 *  - grade_categories: group_id lookup (implicit from FK, explicit for composite)
 *  - group_student: composite for pivot queries
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Students ──
        // slug already has unique index from create table
        Schema::table('students', function (Blueprint $table) {
            $table->index(['last_name', 'first_name'], 'students_name_index');
        });

        // ── Grades ──
        // Composite index already exists: student_id, group_id, period_id
        Schema::table('grades', function (Blueprint $table) {
            $table->index(['student_id', 'group_id', 'period_id', 'category_id'], 'grades_full_context_index');
            $table->index(['group_id', 'period_id', 'date'], 'grades_group_period_date_index');
        });

        // ── Attendances ──
        // Existing: unique(student_id, group_id, date), index(group_id, date)
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['student_id', 'group_id', 'period_id', 'status'], 'attendances_full_context_status_index');
            $table->index(['student_id', 'group_id', 'period_id', 'date'], 'attendances_context_date_index');
        });

        // ── Observations ──
        // Existing: index(student_id, group_id, period_id), index(group_id, status)
        Schema::table('observations', function (Blueprint $table) {
            $table->index(['student_id', 'period_id', 'status'], 'observations_student_period_status_index');
            $table->index(['status', 'created_at'], 'observations_status_created_index');
        });

        // ── Export Histories ──
        // Existing: index(user_id, created_at)
        Schema::table('export_histories', function (Blueprint $table) {
            $table->index(['type', 'period_id'], 'export_histories_type_period_index');
        });

        // ── Grade Categories ──
        Schema::table('grade_categories', function (Blueprint $table) {
            $table->index(['group_id'], 'grade_categories_group_index');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_name_index');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex('grades_full_context_index');
            $table->dropIndex('grades_group_period_date_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_full_context_status_index');
            $table->dropIndex('attendances_context_date_index');
        });

        Schema::table('observations', function (Blueprint $table) {
            $table->dropIndex('observations_student_period_status_index');
            $table->dropIndex('observations_status_created_index');
        });

        Schema::table('export_histories', function (Blueprint $table) {
            $table->dropIndex('export_histories_type_period_index');
        });

        Schema::table('grade_categories', function (Blueprint $table) {
            $table->dropIndex('grade_categories_group_index');
        });
    }
};
