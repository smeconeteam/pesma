<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnDelete();

            // Referensi ke room_resident_id (opsional, untuk tracking)
            $table->foreignId('room_resident_id')
                ->nullable()
                ->constrained('room_residents')
                ->cascadeOnDelete();

            $table->date('check_in_date');
            $table->date('check_out_date')->nullable();

            $table->boolean('is_pic')->default(false);

            // Tipe perpindahan: new (baru masuk), transfer (pindah kamar), checkout (keluar)
            $table->enum('movement_type', ['new', 'transfer', 'checkout'])
                ->default('new');

            // Catatan (opsional, misal: alasan pindah)
            $table->text('notes')->nullable();

            // Yang mencatat perpindahan
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'check_in_date']);
            $table->index(['room_id', 'check_in_date']);
            $table->index(['movement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_histories');
    }
};
