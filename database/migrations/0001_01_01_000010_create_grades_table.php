<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('grade_categories')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('score', 5, 2);
            $table->decimal('max_score', 5, 2)->default(100);
            $table->date('date');
            $table->timestamps();

            $table->index(['student_id', 'group_id', 'period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
