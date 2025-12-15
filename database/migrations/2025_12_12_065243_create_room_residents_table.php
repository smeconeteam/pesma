<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_residents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('check_in_date');
            $table->date('check_out_date')->nullable();
            $table->boolean('is_pic')->default(false);

            $table->timestamps();

            // mencegah duplikasi entry yang sama
            $table->unique(['room_id', 'user_id', 'check_in_date']);

            $table->index(['room_id', 'check_out_date']);
            $table->index(['user_id', 'check_out_date']);
            $table->index(['room_id', 'is_pic', 'check_out_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_residents');
    }
};
