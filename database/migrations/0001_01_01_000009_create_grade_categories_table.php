<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight', 5, 2); // e.g. 30.00 for 30%
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_categories');
    }
};
