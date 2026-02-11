<x-public-layout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="text-center mb-16">
            <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white sm:text-5xl">
                {{ __('contact.title') }}
            </h1>
            <p class="mt-4 text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                {{ __('contact.subtitle') }}
            </p>
        </div>

        <!-- Main Admins Section -->
        <div class="mb-20">
            <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-gray-100 mb-8 border-b border-gray-200 dark:border-gray-700 pb-4">
                {{ __('contact.main_admin') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 justify-center">
                @foreach($mainAdmins as $admin)
                    @php
                        $profile = $admin->adminProfile ?? $admin->residentProfile;
                        $name = $profile?->full_name ?? $admin->name;
                        $phone = $profile?->phone_number ?? '-';
                        $photo = $profile?->photo_path ?? null;
                        
                        $waNumber = preg_replace('/[^0-9]/', '', $phone);
                        if(str_starts_with($waNumber, '0')) {
                            $waNumber = '62' . substr($waNumber, 1);
                        }
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-100 dark:border-gray-700 flex flex-col items-center p-8 text-center h-full">
                        <div class="w-32 h-32 rounded-full overflow-hidden mb-6 border-4 border-green-100 dark:border-green-900 bg-gray-100 dark:bg-gray-700">
                            @if($photo)
                                <img src="{{ Storage::url($photo) }}" alt="{{ $admin->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-green-600 text-white text-4xl font-bold">
                                    {{ substr($admin->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $name }}</h3>
                        <p class="text-green-600 dark:text-green-400 font-medium mb-6">{{ __('contact.main_admin') }}</p>
                        
                        @if($phone !== '-')
                            <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors duration-200 w-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                </svg>
                                {{ __('contact.contact_via_whatsapp') }}
                            </a>
                        @else
                            <button disabled class="inline-flex items-center justify-center gap-2 bg-gray-300 dark:bg-gray-700 text-gray-500 font-semibold py-2.5 px-6 rounded-lg cursor-not-allowed w-full">
                                {{ __('contact.contact_unavailable') }}
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Branch Admins Section -->
        <div>
            <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-gray-100 mb-8 border-b border-gray-200 dark:border-gray-700 pb-4">
                {{ __('contact.dorm_admin_branch') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($dorms as $dorm)
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 h-full flex flex-col">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $dorm->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $dorm->address }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 flex-grow">
                            @forelse($dorm->adminScopes as $scope)
                                @php
                                    $admin = $scope->user;
                                    $profile = $admin->residentProfile;
                                    $name = $profile?->full_name ?? $admin->name;
                                    $phone = $profile?->phone_number ?? '-';
                                    $photo = $profile?->photo_path ?? null;
                                    
                                    $waNumber = preg_replace('/[^0-9]/', '', $phone);
                                    if(str_starts_with($waNumber, '0')) {
                                        $waNumber = '62' . substr($waNumber, 1);
                                    }
                                @endphp
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0 border-2 border-gray-100 dark:border-gray-700 bg-gray-100 dark:bg-gray-700">
                                            @if($photo)
                                                <img src="{{ Storage::url($photo) }}" alt="{{ $admin->name }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-green-600 text-white font-bold">
                                                    {{ substr($admin->name, 0, 1) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $name }}</p>
                                            <p class="text-xs text-green-600 dark:text-green-400 font-medium">{{ __('contact.dorm_admin') }}</p>
                                        </div>
                                    </div>

                                    @if($phone !== '-')
                                        <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="flex-shrink-0 p-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors" title="{{ __('contact.contact_via_whatsapp') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-4 text-gray-500 text-sm italic">
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
