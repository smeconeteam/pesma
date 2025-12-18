<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            // hanya jenisnya (qris/transfer/cash)
            $table->string('kind'); // qris | transfer | cash

            $table->text('instructions')->nullable();

            // khusus QRIS
            $table->string('qr_image_path')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
