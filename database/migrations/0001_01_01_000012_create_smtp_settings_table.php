<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted via model cast
            $table->string('encryption')->default('tls'); // tls | ssl | none
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();

            $table->unique('user_id'); // one config per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smtp_settings');
    }
};
