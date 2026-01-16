<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('registration_id')
                ->nullable()
                ->after('room_id')
                ->constrained('registrations')
                ->nullOnDelete();

            $table->index('registration_id');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['registration_id']);
            $table->dropIndex(['registration_id']);
            $table->dropColumn('registration_id');
        });
    }
};
