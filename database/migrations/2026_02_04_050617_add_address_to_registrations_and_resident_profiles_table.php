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
        // Tambahkan kolom address ke tabel registrations
        Schema::table('registrations', function (Blueprint $table) {
            $table->text('address')->nullable()->after('guardian_phone_number');
        });

        // Tambahkan kolom address ke tabel resident_profiles
        Schema::table('resident_profiles', function (Blueprint $table) {
            $table->text('address')->nullable()->after('guardian_phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('address');
        });

        Schema::table('resident_profiles', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
