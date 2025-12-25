<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('resident_category_id')
                ->nullable()
                ->after('room_type_id')
                ->constrained('resident_categories')
                ->nullOnDelete(); // jika kategori dihapus, set null

            $table->index(['resident_category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resident_category_id');
        });
    }
};
