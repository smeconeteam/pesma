<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DynamicConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            // Cek apakah tabel institutions ada dan koneksi database berhasil
            if (Schema::hasTable('institutions')) {
                $institution = DB::table('institutions')->first();
                
                if ($institution) {
                    // Set app name dari database
                    Config::set('app.name', $institution->dormitory_name);
                }
            }
        } catch (\Exception $e) {
            // Jika terjadi error (misal: saat migration belum jalan), 
            // gunakan default dari .env
            // Log error jika perlu
        }
    }
}