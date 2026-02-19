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
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('landing_headline')->nullable()->after('about_content');
            $table->text('landing_description')->nullable()->after('landing_headline');
            $table->json('landing_stats')->nullable()->after('landing_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['landing_headline', 'landing_description', 'landing_stats']);
        });
    }
};
