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
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('bill_number')->unique();
            $table->enum('bill_type', ['registration', 'monthly_room', 'custom'])->default('custom');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->bigInteger('total_amount')->default(0); // Sebelum diskon
            $table->bigInteger('discount_amount')->default(0); // Potongan diskon
            $table->bigInteger('final_amount')->default(0); // Setelah diskon
            $table->bigInteger('paid_amount')->default(0); // Yang sudah dibayar
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->date('due_date')->nullable(); // null = unlimited
            $table->enum('paid_by', ['self', 'pic'])->default('self');
            $table->boolean('is_split_bill')->default(false);
            $table->integer('split_count')->nullable(); // Jumlah pembagi
            $table->text('notes')->nullable();
            $table->dateTime('issued_at');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['bill_type', 'status']);
            $table->index('due_date');
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};