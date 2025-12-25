<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resident_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // kategori penghuni (pondok/wisma/asrama/kos)
            $table->foreignId('resident_category_id')
                ->nullable()
                ->constrained('resident_categories')
                ->nullOnDelete();

            // status kewarganegaraan + asal negara
            $table->enum('citizenship_status', ['WNI', 'WNA'])->default('WNI');
            $table->foreignId('country_id')
                ->nullable()
                ->constrained('countries')
                ->nullOnDelete();

            // identitas & biodata
            $table->string('national_id')->nullable();       // NIK (WNI)
            $table->string('student_id')->nullable();        // NIM
            $table->string('full_name');
            $table->string('gender', 1)->nullable();         // M/F
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('university_school')->nullable();

            // nomor telepon (simpan dengan format bebas: +62xxx atau 08xxx)
            $table->string('phone_number')->nullable();

            // wali
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone_number')->nullable();

            // status
            $table->enum('status', ['registered', 'active', 'inactive'])->default('registered');

            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();

            $table->string('photo_path')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // indexes
            $table->unique('user_id');
            $table->index(['citizenship_status']);
            $table->index(['resident_category_id']);
            $table->index(['country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_profiles');
    }
};
