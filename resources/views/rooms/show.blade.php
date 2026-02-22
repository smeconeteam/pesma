<x-public-layout>
    <!-- Main Content -->
    <div class="mx-auto max-w-7xl py-8">
        <!-- Breadcrumb -->
        <div class="mb-6 flex flex-wrap items-center gap-2 px-4 text-sm text-gray-500 sm:px-6 lg:px-8 dark:text-gray-400">
            <a href="{{ localizedRoute('home') }}" class="text-green-600 transition-colors hover:text-green-700 hover:underline">{{ __('public.home') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ localizedRoute('rooms.available') }}" class="text-green-600 transition-colors hover:text-green-700 hover:underline">{{ __('public.rooms_available') }}</a>
            <span class="text-gray-400">/</span>
            <span class="font-medium">{{ $room->code }}</span>
        </div>

        <!-- Image Gallery -->
        <div class="relative mb-8 grid gap-2 overflow-hidden sm:grid-cols-1 md:grid-cols-[2fr_1fr] lg:mx-8">
            <!-- Main Image -->
            <div class="group h-[300px] w-full overflow-hidden bg-gray-100 md:h-[450px] md:rounded-xl">
                @if ($room->thumbnail)
                    <img src="{{ url('storage/' . $room->thumbnail) }}" alt="Foto Utama Kamar {{ $room->number }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                @else
                    <img src="{{ url('https://placehold.net/600x400.png') }}" alt="Foto Utama Kamar {{ $room->number }}" class="h-full w-full object-cover">
                @endif
            </div>

            <!-- Side Images -->
            <div class="hidden max-h-[450px] grid-rows-2 gap-2 overflow-hidden md:grid">
                @php
                    $images = $room->images ?? [];
                    $displayImages = array_slice($images, 0, 2);
                    $remainingCount = count($images) - 2;
                @endphp

                <!-- First Side Image -->
                <div class="group relative h-full w-full cursor-pointer overflow-hidden rounded-xl bg-gray-100">
                    @if (count($displayImages) > 0)
                        <img src="{{ url('storage/' . $displayImages[0]) }}" alt="Foto Kamar" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                    @else
                        <img src="{{ url('https://placehold.net/600x400.png') }}" alt="Foto Kamar {{ $room->number }}" class="h-full w-full object-cover">
                    @endif
                </div>

                <!-- Second Side Image (with overlay if more) -->
                <div class="group relative h-full w-full cursor-pointer overflow-hidden rounded-xl bg-gray-100">
                    @if (count($displayImages) > 1)
                        <img src="{{ url('storage/' . $displayImages[1]) }}" alt="Foto Kamar" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                    @else
                        <img src="{{ url('https://placehold.net/600x400.png') }}" alt="Foto Kamar {{ $room->number }}" class="h-full w-full object-cover">
                    @endif
                </div>
            </div>

            <button class="absolute bottom-2 right-1 inline-flex cursor-pointer items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-black" onclick="openGallery()">{{ __('public.view_all') }}</button>
        </div>

        <!-- Main Layout -->
        <div class="relative flex flex-col px-4 sm:px-6 md:flex-row lg:px-8">
            <div class="md:flex-2/3 flex flex-col gap-4 md:pr-20">
                <div class="flex items-center gap-2">
                    <div class="inline-flex rounded-md bg-gray-100 px-3 py-1 text-center text-sm font-medium text-blue-600 ring-1 ring-inset ring-blue-400">{{ $room->roomType->name }}</div>
                    <div class="inline-flex rounded-md bg-gray-100 px-3 py-1 text-center text-sm font-medium text-purple-600 ring-1 ring-inset ring-purple-400">{{ $room->residentCategory->name }}</div>
                </div>
                <h2 class="text-3xl font-semibold">{{ $room->block->dorm->name }} {{ __('public.complex') }} {{ $room->block->name }} {{ __('public.number') }} {{ $room->number }}</h2>

                <div class="flex flex-col gap-2">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                        </svg>

                        <p class="text-xl font-medium">
                            {{ $room->block->dorm->name }} - {{ $room->block->name }}
                        </p>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>

                        <p class="text-md wrap-anywhere text-wrap">
                            {{ $room->block->dorm->address }}
                        </p>
                    </div>
                </div>

                <div class="my-2 h-px bg-gray-200"></div>

                <div class="flex flex-col gap-6">
                    <div class="">
                        <h4 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{{ __('public.room_specifications') }}</h4>

                        <ul class="flex flex-col gap-2">
                            <li class="flex items-center gap-4 text-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                {{ $room->capacity ?? $room->roomType->default_capacity }} {{ __('public.resident') }}
                            </li>

                            <li class="flex items-center gap-4 text-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ruler-dimension-line-icon lucide-ruler-dimension-line">
                                    <path d="M10 15v-3" />
                                    <path d="M14 15v-3" />
                                    <path d="M18 15v-3" />
                                    <path d="M2 8V4" />
                                    <path d="M22 6H2" />
                                    <path d="M22 8V4" />
                                    <path d="M6 15v-3" />
                                    <rect x="2" y="12" width="20" height="8" rx="2" />
                                </svg>
                                {{ $room->width && $room->length ? $room->width . ' x ' . $room->length . ' m' : 'N/A' }}
                            </li>
                        </ul>
                    </div>

                    <div class="my-2 h-px bg-gray-200 dark:bg-gray-700"></div>

                    @if ($room->facilities->count() > 0)
                        @if ($room->roomFacilities->count() > 0)
                            <div class="">
                                <h4 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{{ __('public.room_facilities') }}</h4>

                                <ul class="grid grid-cols-2 gap-2">
                                    @foreach ($room->roomFacilities as $facility)
                                        <li class="flex items-center gap-4 text-lg">
                                            @if ($facility->icon)
                                                <x-dynamic-component :component="$facility->icon" class="h-6 w-6" />
                                            @else
                                                <div class="h-1 w-1 bg-gray-400 p-5"></div>
                                            @endif
                                            {{ $facility->name }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="my-2 h-px bg-gray-200 dark:bg-gray-700"></div>
                        @endif

                        @if ($room->generalFacilities->count() > 0)
                            <div class="">
                                <h4 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{{ __('public.public_facilities') }}</h4>

                                <ul class="grid grid-cols-2 gap-2">
                                    @foreach ($room->generalFacilities as $facility)
                                        <li class="flex items-center gap-4 text-lg">
                                            @if ($facility->icon)
                                                <x-dynamic-component :component="$facility->icon" class="h-6 w-6" />
                                            @else
                                                <div class="h-1 w-1 bg-gray-400 p-5"></div>
                                            @endif
                                            {{ $facility->name }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="my-2 h-px bg-gray-200 dark:bg-gray-700"></div>
                        @endif

                        @if ($room->bathroomFacilities->count() > 0)
                            <div class="">
                                <h4 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{{ __('public.bathroom_facilities') }}</h4>

                                <ul class="grid grid-cols-2 gap-2">
                                    @foreach ($room->bathroomFacilities as $facility)
                                        <li class="flex items-center gap-4 text-lg">
                                            @if ($facility->icon)
                                                <x-dynamic-component :component="$facility->icon" class="h-6 w-6" />
                                            @else
                                                <div class="h-1 w-1 bg-gray-400 p-5"></div>
                                            @endif
                                            {{ $facility->name }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="my-2 h-px bg-gray-200 dark:bg-gray-700"></div>
                        @endif

                        @if ($room->parkingFacilities->count() > 0)
                            <div class="">
                                <h4 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{{ __('public.parking_facilities') }}</h4>

                                <ul class="grid grid-cols-2 gap-2">
                                    @foreach ($room->parkingFacilities as $facility)
                                        <li class="flex items-center gap-4 text-lg">
                                            @if ($facility->icon)
                                                <x-dynamic-component :component="$facility->icon" class="h-6 w-6" />
                                            @else
                                                <div class="h-1 w-1 bg-gray-400 p-5"></div>
                                            @endif
                                            {{ $facility->name }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

                    <div class="my-2 h-px bg-gray-200 dark:bg-gray-700"></div>

                    @if ($room->roomRules->count() > 0)
                        <div class="">
                            <h4 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{{ __('public.room_rules') }}</h4>

                            <ul class="grid grid-cols-1 gap-2">
                                @foreach ($room->roomRules as $rules)
                                    <li class="flex items-center gap-4 text-lg">
                                        @if ($rules->icon)
                                            <x-dynamic-component :component="$rules->icon" class="h-6 w-6" />
                                        @else
                                            <div class="h-1 w-1 bg-gray-400 p-5"></div>
                                        @endif
                                        {{ $rules->name }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                    @endif
                </div>
            </div>

            <div class="shadow-t md:flex-1/3 md:top-18 fixed bottom-0 left-0 right-0 z-10 shrink-0 bg-white shadow-md md:sticky md:bottom-auto md:h-min md:w-auto md:rounded-xl dark:bg-gray-800 dark:shadow-gray-900/20">
                <div class="text-md {{ $room->available_capacity < 3 ? 'bg-red-600' : 'bg-green-600' }} w-full px-4 py-2 font-medium text-white md:rounded-t-xl">{{ __('public.slots_available', ['count' => $room->available_capacity]) }}</div>
                <h3 class="mb-2 px-4 py-2 text-2xl font-semibold text-gray-900 md:text-3xl dark:text-white">Rp{{ number_format($room->monthly_rate ?? $room->roomType->default_monthly_rate, 0, ',', '.') }}<span class="text-xl font-normal text-gray-600 dark:text-gray-400">{{ __('public.per_month') }}</span></h3>

                <div class="flex gap-4 px-4 pb-3 md:flex-col md:items-stretch md:pb-4">
                    <a href="{{ localizedRoute('public.registration.create', [
                        'room_id' => $room->id,
                        'preferred_dorm_id' => $room->block->dorm_id,
                        'preferred_room_type_id' => $room->room_type_id,
                        'resident_category_id' => $room->resident_category_id,
                    ]) }}" class="flex w-full items-center justify-center rounded-md bg-green-600 px-3 py-2 text-base font-bold text-white transition-all hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700">
                        {{ __('public.submit_booking') }}
                    </a>

                    @if ($room->contact_person_number)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $room->contact_person_number) }}" target="_blank" class="flex w-full items-center justify-center gap-2 rounded-md border-2 border-green-500 bg-white px-3 py-2 text-base font-bold text-green-600 transition-all hover:bg-green-50 dark:border-green-400 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                            </svg>
                            {{ __('public.whatsapp') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Similar Rooms -->
        @if ($similarRooms->count() > 0)
            <div class="mb-12 mt-16 px-4 sm:px-6 lg:px-8">
                <h2 class="mb-6 text-2xl font-extrabold text-gray-900 dark:text-white">{{ __('public.similar_rooms') }}</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($similarRooms as $similar)
                        <x-room-card :room="$similar" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="z-500 fixed inset-0 left-0 top-0 hidden overflow-y-auto bg-white" aria-modal="true" role="dialog">
        <!-- Close Button -->
        <button class="absolute right-2 top-2 flex h-12 w-12 cursor-pointer items-center justify-center text-gray-500" onclick="closeGallery()" aria-label="Close gallery">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="mx-auto mt-16 flex max-w-xl flex-col items-center gap-4 overflow-y-auto p-4">
            @if ($room->thumbnail)
                <img src="{{ url('storage/' . $room->thumbnail) }}" alt="Foto Kamar {{ $room->number }}">
            @else
                <img src="{{ url('https://placehold.net/600x400.png') }}" alt="Foto Kamar {{ $room->number }}">
            @endif

            @if ($room->images)
                @forelse ($room->images as $image)
                    <img src="{{ url('storage/' . $image) }}" alt="Foto Kamar {{ $room->number }}">
                @empty
                    <img src="{{ url('https://placehold.net/600x400.png') }}" alt="Foto Kamar {{ $room->number }}">
                @endforelse
            @else
                <img src="{{ url('https://placehold.net/600x400.png') }}" alt="Foto Kamar {{ $room->number }}">
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            const modal = document.getElementById('imageModal');

            function openGallery() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            function closeGallery() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }
        </script>
    @endpush
</x-public-layout>
