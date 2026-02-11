<x-public-layout>
    <!-- Main Content -->
    <!-- Main Content -->
    <div class="mx-auto max-w-7xl py-12">
        <!-- Breadcrumb -->
        <div class="mb-6 flex gap-2 px-4 text-sm text-gray-600 sm:px-6 lg:px-8 dark:text-gray-400">
            <a href="{{ route('home') }}" class="text-green-600 hover:underline dark:text-green-400">{{ __('public.home') }}</a>
            <span>/</span>
            <span>{{ __('public.rooms_available') }}</span>
        </div>

        <!-- Page Header -->
        <div class="mb-10 px-4 sm:px-6 lg:px-8">
            <h1 class="mb-2 text-4xl font-bold text-gray-900 dark:text-white">{{ __('public.rooms_available') }}</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400">{{ __('public.find_dream_room', ['count' => $rooms->total()]) }}</p>
        </div>

        <!-- Search and Filter Section -->
        <div class="relative mx-4 rounded-2xl bg-white p-6 shadow-sm sm:mx-6 lg:mx-8 dark:bg-gray-800 dark:shadow-gray-900/10">
            <form method="GET" action="{{ route('rooms.available') }}" class="flex flex-wrap items-center gap-4">
                <!-- Search Input -->
                <div class="relative min-w-[280px] flex-1">
                    <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 transform text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="search" class="pl-12! w-full rounded-xl border border-gray-200 py-4 pr-4 transition-colors focus:border-green-500 focus:ring-green-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder-gray-500" placeholder="{{ __('public.search_placeholder') }}" value="{{ request('search') }}" oninput="debounceSearch()">
                </div>

                <!-- Filter Dropdown (FilamentPHP Style) -->
                <div x-data="{ open: false }" @click.away="open = false" class="relative">
                    <!-- Filter Button -->
                    <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border-2 border-gray-100 px-3 py-2.5 transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('public.filters') }}</span>
                        @if (request()->hasAny(['dorm_id', 'room_type_id', 'resident_category_id']))
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-green-500 text-xs font-bold text-white">
                                {{ collect([request('dorm_id'), request('room_type_id'), request('resident_category_id')])->filter()->count() }}
                            </span>
                        @endif
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform dark:text-gray-500" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Panel -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 z-50 mt-2 w-80 rounded-xl border border-gray-200 bg-white shadow-xl dark:bg-gray-800 dark:border-gray-700" style="display: none;">
                        <div class="p-4">
                            <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('public.filters') }}</h3>
                                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-4">
                                <!-- Branch Filter -->
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('public.branch') }}</label>
                                    <select name="dorm_id" class="w-full cursor-pointer rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors hover:border-gray-300 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 dark:focus:ring-green-500/40">
                                        <option value="">{{ __('public.all_branches') }}</option>
                                        @foreach ($dorms as $dorm)
                                            <option value="{{ $dorm->id }}" {{ request('dorm_id') == $dorm->id ? 'selected' : '' }}>
                                                {{ $dorm->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Room Type Filter -->
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('public.room_type') }}</label>
                                    <select name="room_type_id" class="w-full cursor-pointer rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors hover:border-gray-300 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 dark:focus:ring-green-500/40">
                                        <option value="">{{ __('public.all_room_types') }}</option>
                                        @foreach ($roomTypes as $type)
                                            <option value="{{ $type->id }}" {{ request('room_type_id') == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Category Filter -->
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('public.type') }}</label>
                                    <select name="resident_category_id" class="w-full cursor-pointer rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors hover:border-gray-300 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 dark:focus:ring-green-500/40">
                                        <option value="">{{ __('public.all_categories') }}</option>
                                        @foreach ($residentCategories as $category)
                                            <option value="{{ $category->id }}" {{ request('resident_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Apply Button -->
                            <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">
                                <button type="submit" @click="open = false" class="flex-1 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700">
                                    {{ __('public.apply_filters') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="flex gap-3">
                    @if (request()->hasAny(['search', 'dorm_id', 'room_type_id', 'resident_category_id']))
                        <a href="{{ route('rooms.available') }}" class="inline-flex gap-1 items-center rounded-md bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            {{ __('public.reset') }}
                        </a>
                    @endif
                </div>
            </form>

            <!-- Active Filters Display -->
            @if (request()->hasAny(['search', 'dorm_id', 'room_type_id', 'resident_category_id']))
                <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-gray-600">{{ __('public.active_filters') }}</span>
                    @if (request('search'))
                        <span class="rounded-2xl bg-green-600 hover:bg-green-700 transition-all duration-100 px-2 text-white">
                            "{{ request('search') }}"
                        </span>
                    @endif
                    @if (request('dorm_id'))
                        @php $selectedDorm = $dorms->firstWhere('id', request('dorm_id')); @endphp
                        @if ($selectedDorm)
                            <span class="rounded-2xl bg-green-600 hover:bg-green-700 transition-all duration-100 px-2 text-white">
                                {{ $selectedDorm->name }}
                            </span>
                        @endif
                    @endif
                    @if (request('room_type_id'))
                        @php $selectedType = $roomTypes->firstWhere('id', request('room_type_id')); @endphp
                        @if ($selectedType)
                            <span class="rounded-2xl bg-green-600 hover:bg-green-700 transition-all duration-100 px-2 text-white">
                                {{ $selectedType->name }}
                            </span>
                        @endif
                    @endif
                    @if (request('resident_category_id'))
                        @php $selectedCategory = $residentCategories->firstWhere('id', request('resident_category_id')); @endphp
                        @if ($selectedCategory)
                            <span class="rounded-2xl bg-green-600 hover:bg-green-700 transition-all duration-100 px-2 text-white">
                                {{ $selectedCategory->name }}
                            </span>
                        @endif
                    @endif
                </div>
            @endif
        </div>

        <!-- Rooms Container -->
        <div id="rooms-container" class="mx-auto mt-4 flex flex-col items-center justify-between gap-6 rounded-t-2xl bg-white p-4 sm:p-6 lg:p-8 dark:bg-gray-800 dark:shadow-gray-900/10">
            @include('rooms.partials.list')
        </div>
    </div>

    @push('scripts')
        <script>
            let timeout = null;

            function debounceSearch() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    performSearch();
                }, 500);
            }

            function performSearch() {
                const form = document.querySelector('.filter-form');
                const url = new URL(form.action);
                const params = new URLSearchParams(new FormData(form));

                // Update URL parameters
                url.search = params.toString();

                // Show loading state (optional, can add spinner overlay)
                document.getElementById('rooms-container').style.opacity = '0.5';

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('rooms-container').innerHTML = html;
                        document.getElementById('rooms-container').style.opacity = '1';

                        // Update URL without reloading
                        window.history.pushState({}, '', url);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('rooms-container').style.opacity = '1';
                    });
            }

            // Auto-submit on select change
            document.querySelectorAll('.filter-select').forEach(select => {
                select.addEventListener('change', () => {
                    performSearch();
                });
            });

            // Handle browser back/forward buttons
            window.addEventListener('popstate', function() {
                window.location.reload();
            });
        </script>
    @endpush
</x-public-layout>
