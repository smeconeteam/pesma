<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_types', function (Blueprint $table) {
            $table->foreignId('resident_category_id')
                ->nullable()
                ->after('applies_to_all')
                ->constrained('resident_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('billing_types', function (Blueprint $table) {
            $table->dropForeign(['resident_category_id']);
            $table->dropColumn('resident_category_id');
        });
    }
};