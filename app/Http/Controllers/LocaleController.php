<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function switch(Request $request)
    {
        $locale = $request->input('locale');
        
        if (!in_array($locale, ['id', 'en'])) {
            return back();
        }
        
        // Set cookie
        Cookie::queue('locale', $locale, 525600);
        
        // Juga simpan ke localStorage via session flash
        session()->flash('locale_changed', $locale);
        
        return back();
    }
}