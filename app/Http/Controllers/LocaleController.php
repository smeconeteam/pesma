<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    /**
     * Switch bahasa aplikasi
     * Menyimpan preference ke cookie yang akan dibaca oleh localStorage
     */
    public function switch(Request $request)
    {
        $locale = $request->input('locale');
        
        // Validasi locale yang diizinkan
        if (!in_array($locale, ['id', 'en'])) {
            return response()->json(['error' => 'Invalid locale'], 400);
        }
        
        // Set cookie untuk 1 tahun (persistent)
        $cookie = Cookie::make('locale', $locale, 525600); // 525600 minutes = 1 year
        
        return response()
            ->json(['success' => true, 'locale' => $locale])
            ->cookie($cookie);
    }
}