<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop pivot table dulu
        Schema::dropIfExists('discount_dorm');

        // Kemudian drop table utama
        Schema::dropIfExists('discounts');
    }

    public function down(): void
    {
        // Recreate jika rollback (opsional, sesuai kebutuhan)
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->decimal('percent', 5, 2)->nullable();
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('voucher_code', 50)->nullable()->unique();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('applies_to_all')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('discount_dorm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('dorm_id')->constrained('dorms')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['discount_id', 'dorm_id']);
        });
    }
};
