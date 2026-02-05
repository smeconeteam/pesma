@props(['active'])

@php
    $classes = $active ?? false 
        ? 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-green-500 dark:border-green-400 text-start text-base font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 focus:outline-none focus:text-green-800 dark:focus:text-green-300 focus:bg-green-100 dark:focus:bg-green-900/30 focus:border-green-700 dark:focus:border-green-500 transition duration-150 ease-in-out' 
        : 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:text-gray-800 dark:focus:text-gray-100 focus:bg-gray-50 dark:focus:bg-gray-800 focus:border-gray-300 dark:focus:border-gray-600 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
