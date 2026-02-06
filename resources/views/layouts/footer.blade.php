<footer class="w-full bg-gray-100 px-4 pt-12 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-8 md:flex-row md:justify-between md:gap-0 lg:gap-16 lg:px-12 lg:py-8 lg:pb-12 lg:pt-0">
            <div class="md:max-w-[50%]">
                <h4 class="mb-4 text-xl font-semibold">{{ $institution->dormitory_name ?? __('footer.dormitory') }}</h4>
                <p>{{ $institution->institution_name ?? __('footer.institution') }}</p>
                @if ($institution ?? false)
                    <p>{{ $institution->address }}</p>
                    <p>{{ $institution->phone }}</p>
                    <p>{{ $institution->email }}</p>
                @endif
            </div>
            <div class="footer-section">
                <h4 class="mb-4 text-xl font-semibold">{{ __('footer.quick_links') }}</h4>
                <ul class="flex flex-col">
                    <a href="{{ route('rooms.available') }}" class="transition-all duration-100 hover:text-green-600 hover:underline">{{ __('footer.rooms_available') }}</a>
                    <a href="{{ route('public.registration.create') }}" class="transition-all duration-100 hover:text-green-600 hover:underline">{{ __('footer.registration') }}</a>
                    <a href="{{ route('login') }}" class="transition-all duration-100 hover:text-green-600 hover:underline">{{ __('footer.login') }}</a>
                </ul>
            </div>
            <div class="footer-section">
                <h4 class="mb-4 text-xl font-semibold">{{ __('footer.information') }}</h4>
                <ul class="flex flex-col">
                    <a href="{{ env('APP_URL') }}" target="_blank" class="transition-all duration-100 hover:text-green-600 hover:underline">{{ __('footer.about_us') }}</a>

                    <a href="/kebijakan" target="_blank" class="transition-all duration-100 hover:text-green-600 hover:underline">{{ __('footer.terms_conditions') }}</a>
                    <a href="#" class="transition-all duration-100 hover:text-green-600 hover:underline">{{ __('footer.contact') }}</a>
                </ul>
            </div>
        </div>

        <div class="mt-6 border-t border-gray-300 pb-2 pt-4 text-center">
            <p>&copy; {{ date('Y') }} {{ $institution->dormitory_name ?? __('footer.dormitory') }}. {{ __('footer.rights_reserved') }}.</p>
        </div>
    </div>
</footer>
