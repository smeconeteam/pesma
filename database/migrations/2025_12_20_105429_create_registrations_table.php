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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();

            // Status pendaftaran
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            // Alasan penolakan (opsional)
            $table->text('rejection_reason')->nullable();

            // Data akun
            $table->string('email')->unique();
            $table->string('name');
            $table->string('password'); // akan di-hash

            // Data profil (sama seperti resident_profiles)
            $table->foreignId('resident_category_id')
                ->nullable()
                ->constrained('resident_categories')
                ->nullOnDelete();

            $table->enum('citizenship_status', ['WNI', 'WNA'])->default('WNI');
            $table->foreignId('country_id')
                ->nullable()
                ->constrained('countries')
                ->nullOnDelete();

            $table->string('national_id')->nullable();
            $table->string('student_id')->nullable();
            $table->string('full_name');
            $table->string('gender', 1)->nullable(); // M/F
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('university_school')->nullable();

            $table->string('phone_number')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone_number')->nullable();

            $table->string('photo_path')->nullable();

            // Preferensi kamar yang diinginkan
            $table->foreignId('preferred_dorm_id')
                ->nullable()
                ->constrained('dorms')
                ->nullOnDelete();

            $table->foreignId('preferred_room_type_id')
                ->nullable()
                ->constrained('room_types')
                ->nullOnDelete();

            // Tanggal rencana masuk
            $table->date('planned_check_in_date')->nullable();

            // Approved by & Approved at
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // User ID jika sudah diapprove (relasi ke users)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['email']);
            $table->index(['preferred_dorm_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
