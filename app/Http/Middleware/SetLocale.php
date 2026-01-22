<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ambil locale dari cookie (yang di-set via JavaScript localStorage)
        $locale = $request->cookie('locale');
        
        // Validasi dan set locale
        if ($locale && in_array($locale, ['id', 'en'])) {
            App::setLocale($locale);
        } else {
            // Fallback ke config default
            App::setLocale(config('app.locale', 'id'));
        }
        
        return $next($request);
    }
}