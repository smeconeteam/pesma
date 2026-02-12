<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->boolean('show_phone_on_landing')->default(false)->after('phone_number');
        });

        Schema::table('admin_scopes', function (Blueprint $table) {
            $table->boolean('show_phone_on_landing')->default(false)->after('block_id');
        });
    }

    public function down(): void
    {
        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->dropColumn('show_phone_on_landing');
        });

        Schema::table('admin_scopes', function (Blueprint $table) {
            $table->dropColumn('show_phone_on_landing');
        });
    }
};
