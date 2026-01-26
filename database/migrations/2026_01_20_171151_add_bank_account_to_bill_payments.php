<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_payments', function (Blueprint $table) {
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('payment_method_id')
                ->constrained('payment_method_bank_accounts')
                ->nullOnDelete();

            $table->index('bank_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('bill_payments', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropIndex(['bank_account_id']);
            $table->dropColumn('bank_account_id');
        });
    }
};
