<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_method_bank_accounts', function (Blueprint $table) {
            // Tambahkan kolom account_holder
            $table->string('account_holder', 150)->nullable()->after('account_name');
        });

        // Tambahkan unique constraints setelah kolom dibuat
        Schema::table('payment_method_bank_accounts', function (Blueprint $table) {
            // Unique constraint untuk kombinasi bank_name, account_number, account_name
            $table->unique(
                ['bank_name', 'account_number', 'account_name'],
                'unique_bank_account_combination'
            );
            
            // Unique constraint untuk account_holder
            $table->unique('account_holder', 'unique_account_holder');
        });
    }

    public function down(): void
    {
        Schema::table('payment_method_bank_accounts', function (Blueprint $table) {
            // Drop unique constraints terlebih dahulu
            $table->dropUnique('unique_bank_account_combination');
            $table->dropUnique('unique_account_holder');
            
            // Kemudian drop kolom
            $table->dropColumn('account_holder');
        });
    }
};