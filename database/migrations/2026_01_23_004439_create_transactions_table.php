<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense']); // pemasukan/pengeluaran
            $table->string('name'); // nama transaksi
            $table->bigInteger('amount'); // jumlah uang
            $table->enum('payment_method', ['cash', 'credit']); // tunai/kredit
            $table->date('transaction_date'); // tanggal transaksi
            $table->text('notes')->nullable(); // catatan
            
            // Relasi
            $table->foreignId('dorm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bill_payment_id')->nullable()->constrained()->nullOnDelete(); // dari pembayaran billing
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['type', 'transaction_date']);
            $table->index(['dorm_id', 'transaction_date']);
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};