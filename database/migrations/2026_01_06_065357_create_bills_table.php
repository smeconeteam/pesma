<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();

            // Nomor tagihan unik
            $table->string('bill_number', 50)->unique();

            // Relasi
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('billing_type_id')
                ->constrained('billing_types')
                ->cascadeOnDelete();

            $table->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();

            // Nominal
            $table->unsignedBigInteger('base_amount'); // nominal dasar
            $table->decimal('discount_percent', 5, 2)->default(0); // persentase diskon
            $table->unsignedBigInteger('discount_amount')->default(0); // nominal diskon
            $table->unsignedBigInteger('total_amount'); // base - discount
            $table->unsignedBigInteger('paid_amount')->default(0); // sudah dibayar
            $table->unsignedBigInteger('remaining_amount'); // sisa

            // Periode
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date')->nullable();

            // Status
            $table->enum('status', ['issued', 'partial', 'paid', 'overdue'])
                ->default('issued');

            // Metadata
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('issued_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['billing_type_id', 'status']);
            $table->index(['room_id', 'period_start']);
            $table->index('due_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
