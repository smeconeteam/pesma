@php
    $institution = \App\Models\Institution::first();
    $faviconUrl = null;
    
    if ($institution && $institution->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($institution->logo_path)) {
        $faviconUrl = asset('storage/' . $institution->logo_path);
    } else {
        // Fallback ke favicon default
        $faviconUrl = asset('favicon.ico');
    }
@endphp

{{-- Favicon --}}
<link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="57x57" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="60x60" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="72x72" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="76x76" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="114x114" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="120x120" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="144x144" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="152x152" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $faviconUrl }}">