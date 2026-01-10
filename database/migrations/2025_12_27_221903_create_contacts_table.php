<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('phone');
            $table->text('auto_message')->nullable();

            // NULL = Semua Cabang
            $table->foreignId('dorm_id')
                ->nullable()
                ->constrained('dorms')
                ->nullOnDelete();

            $table->string('display_name');

            // status aktif / nonaktif
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};