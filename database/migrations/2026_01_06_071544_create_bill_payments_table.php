<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bill_id')
                ->constrained('bills')
                ->cascadeOnDelete();

            // Nomor pembayaran unik
            $table->string('payment_number', 50)->unique();

            $table->unsignedBigInteger('amount'); // jumlah dibayar
            $table->date('payment_date');

            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->cascadeOnDelete();

            // Info PIC yang membayar
            $table->foreignId('paid_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('paid_by_name'); // nama PIC saat bayar (untuk history)
            $table->boolean('is_pic_payment')->default(true); // apakah dibayar PIC

            // Bukti & Verifikasi
            $table->string('proof_path')->nullable(); // foto bukti
            $table->enum('status', ['pending', 'verified', 'rejected'])
                ->default('pending');

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['bill_id', 'status']);
            $table->index('payment_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
