    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $institution?->dormitory_name ?? config('app.name') }}</title>

        @if ($institution?->logo_path)
            @php
                $favicon = Storage::url($institution->logo_path);
            @endphp

            <!-- Favicon -->
            <link rel="icon" type="image/png" href="{{ $favicon }}">
            <link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon }}">
            <link rel="icon" type="image/png" sizes="16x16" href="{{ $favicon }}">
            <link rel="apple-touch-icon" href="{{ $favicon }}">
            <link rel="shortcut icon" href="{{ $favicon }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <script>
            // Inisialisasi dark mode SEBELUM halaman render
            (function() {
                const isDark = localStorage.getItem('darkMode') === 'true';
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-950">
            @include('layouts.public-navigation')

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <!-- Stack untuk scripts tambahan -->
            @stack('scripts')
        </div>
        @include('layouts.footer')
    </body>

    </html>
