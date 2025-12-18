<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            // percent | fixed
            $table->string('type'); // simpan string supaya fleksibel
            $table->decimal('percent', 5, 2)->nullable();        // 0 - 100
            $table->unsignedBigInteger('amount')->nullable();    // rupiah untuk fixed

            $table->boolean('applies_to_all')->default(false);
            $table->boolean('is_active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
