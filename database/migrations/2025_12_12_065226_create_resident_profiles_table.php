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

            $table->foreignId('resident_category_id')
                ->nullable()
                ->constrained('resident_categories')
                ->nullOnDelete();

            $table->boolean('is_international')->default(false);

            $table->string('national_id')->nullable();          // NIK
            $table->string('student_id')->nullable();           // NIM
            $table->string('full_name');
            $table->string('gender', 1)->nullable();            // M/F
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('university_school')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone_number')->nullable();
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->string('photo_path')->nullable();

            $table->timestamps();

            $table->unique('user_id'); // 1 user = 1 resident_profile
            $table->index(['student_id']);
            $table->index(['national_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_profiles');
    }
};
