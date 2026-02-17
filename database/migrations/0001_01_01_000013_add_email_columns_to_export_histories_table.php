<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_histories', function (Blueprint $table) {
            $table->boolean('sent_via_email')->default(false)->after('file_path');
            $table->string('recipient_email')->nullable()->after('sent_via_email');
        });
    }

    public function down(): void
    {
        Schema::table('export_histories', function (Blueprint $table) {
            $table->dropColumn(['sent_via_email', 'recipient_email']);
        });
    }
};
