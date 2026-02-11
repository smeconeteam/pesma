<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Tambahkan kolom contact_person_user_id untuk referensi ke admin cabang
            $table->foreignId('contact_person_user_id')
                ->nullable()
                ->after('contact_person_number')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['contact_person_user_id']);
            $table->dropColumn('contact_person_user_id');
        });
    }
};
