<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $institution = institution();
    @endphp

    <title>{{ $institution->dormitory_name ?? config('app.name', 'Laravel') }}</title>

    @if ($institution && $institution->logo_path)
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
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
        <div>
            <a href="/" class="flex justify-center">
                @if ($institution && $institution->logo_path)
                    <img
                        src="{{ Storage::url($institution->logo_path) }}"
                        alt="{{ $institution->dormitory_name }}"
                        class="h-20 w-20 object-contain"
                    >
                @else
                    <x-application-logo class="h-20 w-20 fill-current text-gray-500" />
                @endif
            </a>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900 text-center">
                {{ $institution->dormitory_name ?? config('app.name', 'Laravel') }}
            </h1>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
