<footer class="w-full bg-white px-4 pt-12 sm:px-6 lg:px-8 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-8 md:flex-row md:justify-between md:gap-0 lg:gap-16 lg:px-12 lg:py-8 lg:pb-12 lg:pt-0">
            <div class="md:max-w-[50%]">
                <h4 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{{ $institution->dormitory_name ?? __('footer.dormitory') }}</h4>
                <p>{{ $institution->institution_name ?? __('footer.institution') }}</p>
                @if ($institution ?? false)
                    <p class="my-2">{{ $institution->address }}</p>
                    <p class="flex gap-1 my-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        {{ $institution->phone }}
                    </p>
                    <p class="flex gap-1 my-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        {{ $institution->email }}
                    </p>
                @endif
            </div>
            <div class="footer-section">
                <h4 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{{ __('footer.quick_links') }}</h4>
                <ul class="flex flex-col">
                    <a href="{{ localizedRoute('rooms.available') }}" class="transition-all duration-100 hover:text-green-600 hover:underline dark:hover:text-green-400">{{ __('footer.rooms_available') }}</a>
                    <a href="{{ localizedRoute('public.registration.create') }}" class="transition-all duration-100 hover:text-green-600 hover:underline dark:hover:text-green-400">{{ __('footer.registration') }}</a>
                    <a href="{{ route('login') }}" class="transition-all duration-100 hover:text-green-600 hover:underline dark:hover:text-green-400">{{ __('footer.login') }}</a>
                </ul>
            </div>
            <div class="footer-section">
                <h4 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{{ __('footer.information') }}</h4>
                <ul class="flex flex-col">
                    <a href="{{ localizedRoute('about') }}" class="transition-all duration-100 hover:text-green-600 hover:underline dark:hover:text-green-400">{{ __('footer.about_us') }}</a>

                    <a href="{{ localizedRoute('public.policy') }}" class="transition-all duration-100 hover:text-green-600 hover:underline">{{ __('footer.terms_conditions') }}</a>
                    <a href="{{ localizedRoute('contact') }}" class="transition-all duration-100 hover:text-green-600 hover:underline">{{ __('footer.contact') }}</a>
                </ul>
            </div>
        </div>

        <div class="mt-6 border-t border-gray-300 pb-2 pt-4 text-center dark:border-gray-700">
            <p>&copy; {{ date('Y') }} {{ $institution->dormitory_name ?? __('footer.dormitory') }}. {{ __('footer.rights_reserved') }}.</p>
        </div>
    </div>
</footer>
