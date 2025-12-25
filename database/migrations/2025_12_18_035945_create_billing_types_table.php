<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_types', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            // Nominal (Rupiah). Pakai integer besar biar aman.
            $table->unsignedBigInteger('amount')->default(0);

            // true = berlaku untuk semua cabang
            $table->boolean('applies_to_all')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes(); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_types');
    }
};
