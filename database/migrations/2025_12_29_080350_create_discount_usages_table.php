<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->bigInteger('amount'); // Nominal diskon yang dipakai
            $table->dateTime('used_at');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'discount_id']);
            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_usages');
    }
};