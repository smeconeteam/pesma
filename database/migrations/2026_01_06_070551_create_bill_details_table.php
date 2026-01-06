<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bill_id')
                ->constrained('bills')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('month')->nullable(); // bulan ke-1, 2, 3, dst
            $table->string('description'); // deskripsi item
            $table->unsignedBigInteger('base_amount'); // sebelum diskon
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('amount'); // setelah diskon

            $table->timestamps();

            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_details');
    }
};
