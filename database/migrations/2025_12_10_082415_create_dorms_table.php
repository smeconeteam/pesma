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
        Schema::create('dorms', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto increment (pk)

            $table->string('name');        // nama cabang asrama
            $table->text('address');       // alamat lengkap asrama cabang
            $table->text('description')    // deskripsi, catatan internal, dll.
                ->nullable();

            $table->timestamps();          // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dorms');
    }
};
