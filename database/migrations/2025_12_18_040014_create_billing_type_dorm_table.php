<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_type_dorm', function (Blueprint $table) {
            $table->id();

            $table->foreignId('billing_type_id')->constrained('billing_types')->cascadeOnDelete();
            $table->foreignId('dorm_id')->constrained('dorms')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['billing_type_id', 'dorm_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_type_dorm');
    }
};
