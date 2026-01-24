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

            <div class="mt-4 flex justify-center" x-data="{ open: false }">
                <div class="relative">
                    <button @click="open = !open" @click.outside="open = false" type="button" class="flex items-center justify-between w-28 px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                        <span class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-500 uppercase">{{ app()->getLocale() == 'id' ? 'ID' : 'GB' }}</span>
                            <span class="font-semibold text-gray-900 uppercase">{{ app()->getLocale() == 'id' ? 'ID' : 'EN' }}</span>
                        </span>
                        <svg class="w-4 h-4 ml-2 text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 z-10 w-full mt-1 overflow-hidden bg-white border border-gray-300 rounded-lg shadow-lg"
                         style="display: none;">
                        
                        <form action="{{ route('locale.switch') }}" method="POST">
                            @csrf
                            <input type="hidden" name="locale" value="id">
                            <button type="submit" class="flex items-center w-full px-3 py-2 text-left transition-colors {{ app()->getLocale() == 'id' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                                <span class="mr-2 text-xs font-bold uppercase {{ app()->getLocale() == 'id' ? 'text-blue-100' : 'text-gray-500' }}">ID</span>
                                <span class="font-semibold uppercase">ID</span>
                            </button>
                        </form>

                        <form action="{{ route('locale.switch') }}" method="POST">
                            @csrf
                            <input type="hidden" name="locale" value="en">
                            <button type="submit" class="flex items-center w-full px-3 py-2 text-left transition-colors {{ app()->getLocale() == 'en' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                                <span class="mr-2 text-xs font-bold uppercase {{ app()->getLocale() == 'en' ? 'text-blue-100' : 'text-gray-500' }}">GB</span>
                                <span class="font-semibold uppercase">EN</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div {{ $attributes->merge(['class' => 'mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md max-w-md rounded-lg md:max-w-5xl md:rounded-none']) }}>
            {{ $slot }}
        </div>
    </div>
</body>

</html>
