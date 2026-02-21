<x-public-layout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="mb-6 flex gap-2 px-4 text-sm text-gray-600 sm:px-6 lg:px-8 dark:text-gray-400">
            <a href="{{ localizedRoute('home') }}" class="text-green-600 hover:underline dark:text-green-400">{{ __('public.home') }}</a>
            <span>/</span>
            <span>{{ __('navigation.contact') }}</span>
        </div>

        <!-- Page Header -->
        <div class="mb-10 px-4 sm:px-6 lg:px-8">
            <h1 class="mb-2 text-4xl font-bold text-gray-900 dark:text-white">
                {{ __('contact.title') }}
            </h1>
        </div>

        <!-- Main Admins Section -->
        @if ($mainAdmins->count() > 0)
            <div class="mb-20">
                <h2 class="mb-8 border-b border-gray-200 pb-4 text-center text-2xl font-bold text-gray-900 dark:border-gray-700 dark:text-gray-100">
                    {{ __('contact.main_admin') }}
                </h2>

                <div class="flex flex-wrap justify-center gap-8">
                    @foreach ($mainAdmins as $admin)
                        @php
                            $profile = $admin->adminProfile ?? $admin->residentProfile;
                            $name = $profile?->full_name ?? $admin->name;
                            $phone = $profile?->phone_number ?? '-';
                            $photo = $profile?->photo_path ?? null;

                            $waNumber = preg_replace('/[^0-9]/', '', $phone);
                            if (str_starts_with($waNumber, '0')) {
                                $waNumber = '62' . substr($waNumber, 1);
                            }
                        @endphp
                        <div class="flex h-full shrink-0 flex-col items-center overflow-hidden rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-lg transition-shadow duration-300 hover:shadow-xl dark:border-gray-700 dark:bg-gray-800">
                            <div class="mb-6 h-32 w-32 overflow-hidden rounded-full border-4 border-green-100 bg-gray-100 dark:border-green-900 dark:bg-gray-700">
                                @if ($photo)
                                    <img src="{{ Storage::url($photo) }}" alt="{{ $admin->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-green-600 text-4xl font-bold text-white">
                                        {{ substr($admin->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <h3 class="mb-2 text-xl font-bold text-gray-900 dark:text-white">{{ $name }}</h3>
                            <p class="mb-6 font-medium text-green-600 dark:text-green-400">{{ __('contact.main_admin') }}</p>

                            @if ($phone !== '-')
                                <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-6 py-2.5 font-semibold text-white transition-colors duration-200 hover:bg-green-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                    </svg>
                                    {{ __('contact.contact_via_whatsapp') }}
                                </a>
                            @else
                                <button disabled class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-gray-300 px-6 py-2.5 font-semibold text-gray-500 dark:bg-gray-700">
                                    {{ __('contact.contact_unavailable') }}
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Branch Admins Section -->
        <div>
            <h2 class="mb-8 border-b border-gray-200 pb-4 text-center text-2xl font-bold text-gray-900 dark:border-gray-700 dark:text-gray-100">
                {{ __('contact.dorm_admin_branch') }}
            </h2>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                @foreach ($dorms as $dorm)
                    <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-900">
                        <div class="mb-6 flex items-center gap-4">
                            <div class="rounded-xl bg-green-100 p-3 dark:bg-green-900/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $dorm->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $dorm->address }}</p>
                            </div>
                        </div>

                        <div class="flex-grow space-y-4">
                            @forelse($dorm->adminScopes as $scope)
                                @php
                                    $admin = $scope->user;
                                    $profile = $admin->residentProfile;
                                    $name = $profile?->full_name ?? $admin->name;
                                    $phone = $profile?->phone_number ?? '-';
                                    $photo = $profile?->photo_path ?? null;

                                    $waNumber = preg_replace('/[^0-9]/', '', $phone);
                                    if (str_starts_with($waNumber, '0')) {
                                        $waNumber = '62' . substr($waNumber, 1);
                                    }
                                @endphp
                                <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-center gap-4">
                                        <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-full border-2 border-gray-100 bg-gray-100 dark:border-gray-700 dark:bg-gray-700">
                                            @if ($photo)
                                                <img src="{{ Storage::url($photo) }}" alt="{{ $admin->name }}" class="h-full w-full object-cover">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center bg-green-600 font-bold text-white">
                                                    {{ substr($admin->name, 0, 1) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $name }}</p>
                                            <p class="text-xs font-medium text-green-600 dark:text-green-400">{{ __('contact.dorm_admin') }}</p>
                                        </div>
                                    </div>

                                    @if ($phone !== '-')
                                        <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="flex-shrink-0 rounded-full bg-green-100 p-2 text-green-600 transition-colors hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50" title="{{ __('contact.contact_via_whatsapp') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            @empty
                                <div class="py-4 text-center text-sm italic text-gray-500">
                                    {{ __('contact.no_admin_branch') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-public-layout>
