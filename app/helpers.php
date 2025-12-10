<?php

use App\Models\Institution;
use Illuminate\Support\Facades\Schema;

if (! function_exists('institution')) {
    function institution(): ?Institution
    {
        // Jangan query saat jalanin perintah di console (migrate, db:seed, dll)
        if (app()->runningInConsole()) {
            return null;
        }

        // Pastikan tabel institutions sudah ada
        if (! Schema::hasTable('institutions')) {
            return null;
        }

        // Ambil record pertama (asumsi cuma satu lembaga)
        return Institution::first();
    }
}
