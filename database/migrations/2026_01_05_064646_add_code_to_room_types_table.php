<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Tambahkan kolom code tanpa unique constraint dulu
        Schema::table('room_types', function (Blueprint $table) {
            $table->string('code')->after('name')->nullable();
        });

        // Step 2: Isi data code dari name yang sudah ada
        $roomTypes = DB::table('room_types')->get();
        foreach ($roomTypes as $roomType) {
            $code = Str::slug($roomType->name, '-');

            // Jika kode sudah ada, tambahkan suffix angka
            $baseCode = $code;
            $counter = 1;
            while (DB::table('room_types')->where('code', $code)->where('id', '!=', $roomType->id)->exists()) {
                $code = $baseCode . '-' . $counter;
                $counter++;
            }

            DB::table('room_types')
                ->where('id', $roomType->id)
                ->update(['code' => $code]);
        }

        // Step 3: Setelah semua data terisi, buat kolom menjadi required dan unique
        Schema::table('room_types', function (Blueprint $table) {
            $table->string('code')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
