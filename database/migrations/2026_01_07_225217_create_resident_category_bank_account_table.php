<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_category_bank_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_category_id')
                ->constrained('resident_categories')
                ->onDelete('cascade')
                ->name('rc_ba_resident_category_fk'); // ✅ Nama constraint pendek
            
            $table->foreignId('payment_method_bank_account_id')
                ->constrained('payment_method_bank_accounts')
                ->onDelete('cascade')
                ->name('rc_ba_bank_account_fk'); // ✅ Nama constraint pendek
            
            $table->timestamps();

            // Unique constraint agar tidak ada duplikasi
            $table->unique(
                ['resident_category_id', 'payment_method_bank_account_id'],
                'rc_ba_unique' // ✅ Nama constraint pendek
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_category_bank_account');
    }
};