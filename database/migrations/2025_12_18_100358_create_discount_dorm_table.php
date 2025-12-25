<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discount_dorm', function (Blueprint $table) {
            $table->id();

            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('dorm_id')->constrained('dorms')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['discount_id', 'dorm_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_dorm');
    }
};
