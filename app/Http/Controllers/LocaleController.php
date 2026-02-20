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
            if ($request->wantsJson()) {
                return response()->json(['url' => url()->previous()]);
            }
            return back();
        }
        
        // Set cookie
        Cookie::queue('locale', $locale, 525600);
        
        // Juga simpan ke localStorage via session flash
        session()->flash('locale_changed', $locale);

        // Determine target URL
        $targetUrl = url()->previous();
        
        try {
            // Create a request for the previous URL to match it against routes
            $previousRequest = \Illuminate\Http\Request::create($targetUrl);
            $route = app('router')->getRoutes()->match($previousRequest);
            $routeName = $route->getName();
            
            if ($routeName) {
                // Check if route name matches localized pattern (ending in .id or .en)
                if (preg_match('/^(.*)\.(id|en)$/', $routeName, $matches)) {
                    $baseName = $matches[1];
                    $newRouteName = $baseName . '.' . $locale;
                    
                    if (\Illuminate\Support\Facades\Route::has($newRouteName)) {
                        // Merge path parameters and query parameters
                        $params = array_merge($previousRequest->query->all(), $route->parameters());
                        $targetUrl = route($newRouteName, $params);
                    }
                }
            }
        } catch (\Exception $e) {
            // If matching fails or any error, keep targetUrl as previous
        }
        
        if ($request->wantsJson()) {
            return response()->json(['url' => $targetUrl]);
        }
        
        return redirect($targetUrl);
    }
}