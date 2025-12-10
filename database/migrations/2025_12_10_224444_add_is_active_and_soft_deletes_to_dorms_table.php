<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dorms', function (Blueprint $table) {
            // sesuaikan posisi "after" dengan kolom yang sudah ada
            $table->boolean('is_active')
                ->default(true)
                ->after('id');

            $table->softDeletes(); // menambahkan kolom deleted_at
        });
    }

    public function down(): void
    {
        Schema::table('dorms', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropSoftDeletes();
        });
    }
};
