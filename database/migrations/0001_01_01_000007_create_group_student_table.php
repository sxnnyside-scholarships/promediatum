<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // No unique constraint — a student MAY belong to multiple groups
            $table->index(['student_id', 'period_id']);
            $table->index(['group_id', 'period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_student');
    }
};
