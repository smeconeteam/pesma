<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_method_bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->cascadeOnDelete();

            $table->string('bank_name');          // BCA, BRI, Mandiri, dll
            $table->string('account_number');     // nomor rekening
            $table->string('account_name');       // atas nama
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['payment_method_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_method_bank_accounts');
    }
};
