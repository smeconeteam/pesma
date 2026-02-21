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

if (!function_exists('localizedRoute')) {
    /**
     * Generate a localized route URL based on the current locale.
     * 
     * For routes that have locale-specific versions (e.g., 'contact' has both
     * 'contact.id' and 'contact.en'), this function picks the correct one
     * based on the current app locale.
     *
     * @param string $name The base route name (e.g., 'contact', 'about')
     * @param mixed $parameters Route parameters
     * @param bool $absolute Whether to generate an absolute URL
     * @return string
     */
    function localizedRoute(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $locale = \Illuminate\Support\Facades\App::getLocale();
        $localizedName = $name . '.' . $locale;

        // If a locale-specific route exists, use it; otherwise fallback to base name
        if (app('router')->has($localizedName)) {
            return route($localizedName, $parameters, $absolute);
        }

        return route($name, $parameters, $absolute);
    }
}
