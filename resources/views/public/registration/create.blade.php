<x-public-layout>
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">{{ __('registration.title') }}</h1>
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">{{ __('registration.subtitle') }}</p>
        </div>

        @if ($errors->any())
        <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-6 text-red-700 dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400">
            <div class="mb-2 flex items-center gap-2 font-bold">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                {{ __('registration.error_title') }}
            </div>
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('public.registration.store') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            {{-- AKUN --}}
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg sm:p-8 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-bold text-gray-900 sm:text-xl dark:text-white">{{ __('registration.account') }}</h2>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.email') }} <span class="text-red-500">*</span></label>
                        <input name="email" type="email" value="{{ old('email') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.nickname') }} <span class="text-red-500">*</span></label>
                        <input name="name" type="text" value="{{ old('name') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.password') }}</label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                value="123456789"
                                class="w-full rounded-lg border-gray-300 pr-10 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">

                            <button
                                type="button"
                                onclick="togglePasswordField()"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg id="eye-icon-reg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg id="eye-slash-icon-reg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('registration.password_default') }}</p>
                    </div>
                </div>
            </section>

            {{-- PROFIL --}}
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg sm:p-8 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0c0 .883.393 1.627 1.008 2.126C8.596 9.984 8 11.416 8 13v.5m4-5.5a3.504 3.504 0 013.628 3.03A2.992 2.992 0 0113 13v.5m-5 .5v2.5m5-2.5v2.5" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-bold text-gray-900 sm:text-xl dark:text-white">{{ __('registration.profile') }}</h2>
                    </div>
                </div>


                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.resident_category') }} <span class="text-red-500">*</span></label>
                        <select id="resident_category_id" name="resident_category_id" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                            <option value="">{{ __('registration.select_option') }}</option>
                            @foreach ($residentCategories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('resident_category_id')==$cat->id)>
                                {{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.full_name') }} <span class="text-red-500">*</span></label>
                        <input name="full_name" type="text" value="{{ old('full_name') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.gender') }} <span class="text-red-500">*</span></label>
                        <select name="gender" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                            <option value="">{{ __('registration.select_option') }}</option>
                            <option value="M" @selected(old('gender')==='M' )>{{ __('registration.male') }}</option>
                            <option value="F" @selected(old('gender')==='F' )>{{ __('registration.female') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.nik') }} <span class="text-red-500">*</span></label>
                        <input name="national_id" inputmode="numeric" pattern="[0-9]*" value="{{ old('national_id') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.birth_place') }} <span class="text-red-500">*</span></label>
                        <input name="birth_place" type="text" value="{{ old('birth_place') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.birth_date') }} <span class="text-red-500">*</span></label>
                        <input name="birth_date" type="date" value="{{ old('birth_date') }}" max="{{ now()->subYears(6)->format('Y-m-d') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('registration.min_age') }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.student_id') }} <span class="text-red-500">*</span></label>
                        <input name="student_id" type="text" value="{{ old('student_id') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.university_school') }} <span class="text-red-500">*</span></label>
                        <input name="university_school" type="text" value="{{ old('university_school') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.photo') }}</label>
                        <input name="photo" type="file" accept="image/*" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300">
                    </div>
                </div>
            </section>

            {{-- KEWARGANEGARAAN --}}
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg sm:p-8 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-bold text-gray-900 sm:text-xl dark:text-white">{{ __('registration.citizenship_contact') }}</h2>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.citizenship') }} <span class="text-red-500">*</span></label>
                        <select id="citizenship_status" name="citizenship_status" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                            <option value="WNI" @selected(old('citizenship_status', 'WNI' )==='WNI' )>WNI</option>
                            <option value="WNA" @selected(old('citizenship_status')==='WNA' )>WNA</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.country') }} <span class="text-red-500">*</span></label>
                        <select id="country_id" name="country_id" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                            <option value="">{{ __('registration.select_option') }}</option>
                            @foreach ($countries as $c)
                            <option value="{{ $c->id }}" @selected(old('country_id', $indoCountryId)==$c->id)>
                                {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.phone') }} <span class="text-red-500">*</span></label>
                        <input name="phone_number" inputmode="numeric" pattern="[0-9]*" value="{{ old('phone_number') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.guardian_name') }}</label>
                        <input name="guardian_name" type="text" value="{{ old('guardian_name') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.guardian_phone') }}</label>
                        <input name="guardian_phone_number" inputmode="numeric" pattern="[0-9]*" value="{{ old('guardian_phone_number') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.address') }}</label>
                        <textarea name="address" rows="3" maxlength="500" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" placeholder="{{ __('registration.address_placeholder') }}">{{ old('address') }}</textarea>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('registration.address_hint') }}</p>
                    </div>
                </div>
            </section>

            {{-- PREFERENSI --}}
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg sm:p-8 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
                        <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-bold text-gray-900 sm:text-xl dark:text-white">{{ __('registration.room_preference') }}</h2>
                    </div>
                </div>

                @php $disabledClass = 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-700'; @endphp

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    {{-- Cabang --}}
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.branch') }}</label>
                        <select id="preferred_dorm_id" name="preferred_dorm_id"
                            class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white {{ $fromRoom ? $disabledClass : '' }}"
                            {{ $fromRoom ? 'disabled' : '' }}>
                            <option value="">{{ __('registration.select_branch') }}</option>
                            @foreach ($dorms as $d)
                            <option value="{{ $d->id }}"
                                @selected(old('preferred_dorm_id', $prefill['preferred_dorm_id'])==$d->id)>
                                {{ $d->name }}
                            </option>
                            @endforeach
                        </select>
                        @if($fromRoom)
                        <input type="hidden" name="preferred_dorm_id" value="{{ $prefill['preferred_dorm_id'] }}">
                        @endif
                        <p id="dorm-info" class="mt-2 text-xs font-medium hidden"></p>
                    </div>

                    {{-- Tipe Kamar --}}
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.room_type') }}</label>
                        <select id="preferred_room_type_id" name="preferred_room_type_id"
                            class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white {{ $fromRoom ? $disabledClass : '' }}"
                            {{ $fromRoom ? 'disabled' : '' }}>
                            <option value="">{{ __('registration.select_room_type') }}</option>
                            @foreach ($roomTypes as $rt)
                            <option value="{{ $rt->id }}"
                                @selected(old('preferred_room_type_id', $prefill['preferred_room_type_id'])==$rt->id)>
                                {{ $rt->name }}
                            </option>
                            @endforeach
                        </select>
                        @if($fromRoom)
                        <input type="hidden" name="preferred_room_type_id" value="{{ $prefill['preferred_room_type_id'] }}">
                        @endif
                        <p id="room-type-info" class="mt-2 text-xs font-medium hidden"></p>
                    </div>

                    {{-- Nomor Kamar --}}
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nomor Kamar <span class="text-gray-400 font-normal">(opsional)</span>
                        </label>

                        @if($fromRoom)
                        {{-- Dari halaman detail kamar: tampil read-only --}}
                        <div class="flex items-center gap-3 w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2.5 dark:border-gray-600 dark:bg-gray-700 {{ $disabledClass }}">
                            <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $prefillRoom?->block->dorm->name }} —
                                No. {{ $prefillRoom?->number }}
                                ({{ $prefillRoom?->roomType->name }}, {{ $prefillRoom?->residentCategory->name }},
                                sisa {{ $prefillRoom?->available_capacity }} tempat)
                            </span>
                        </div>
                        <input type="hidden" name="preferred_room_id" value="{{ $prefill['room_id'] }}">
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            Kamar dipilih dari halaman detail kamar.
                            <a href="{{ route('public.registration.create') }}" class="text-green-600 underline hover:text-green-700">Daftar tanpa pilih kamar</a>
                        </p>
                        @else
                        {{-- Daftar langsung: bisa pilih bebas, atau kosongkan --}}
                        <select id="preferred_room_id" name="preferred_room_id"
                            class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">— Pilih nomor kamar (opsional) —</option>
                            @foreach($availableRooms->groupBy(fn($r) => $r->block->dorm->name) as $dormName => $rooms)
                            <optgroup label="{{ $dormName }}">
                                @foreach($rooms->sortBy('number') as $room)
                                <option
                                    value="{{ $room->id }}"
                                    data-dorm="{{ $room->block->dorm_id }}"
                                    data-room-type="{{ $room->room_type_id }}"
                                    data-category="{{ $room->resident_category_id }}"
                                    @selected(old('preferred_room_id')==$room->id)>
                                    No. {{ $room->number }}
                                    — {{ $room->roomType->name }}
                                    — {{ $room->residentCategory->name }}
                                    (sisa {{ $room->available_capacity }} tempat)
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            Opsional. Pilih jika sudah tahu nomor kamar yang diinginkan. Pilihan akan menyesuaikan cabang dan tipe kamar secara otomatis.
                        </p>
                        @endif
                    </div>

                    {{-- Tanggal Rencana Masuk --}}
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.check_in_date') }}</label>
                        <input name="planned_check_in_date" type="date" value="{{ old('planned_check_in_date', now()->addDays(7)->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('registration.min_today') }}</p>
                    </div>

                </div>
            </section>

            {{-- CHECKBOX KEBIJAKAN --}}
            @if($policy)
            <div class="flex items-start space-x-3 rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-900/40 dark:bg-green-900/20">
                <input
                    type="checkbox"
                    name="agreed_to_policy"
                    id="agreed_to_policy"
                    value="1"
                    class="mt-1 h-5 w-5 flex-shrink-0 cursor-pointer rounded border-gray-300 text-green-600 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-800 dark:ring-offset-gray-900"
                    {{ old('agreed_to_policy') ? 'checked' : '' }}
                    required>
                <label for="agreed_to_policy" class="cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                    {{ __('registration.policy_agree') }}
                    <a
                        href="{{ route('public.policy') }}"
                        target="_blank"
                        rel="opener"
                        class="font-semibold text-green-700 underline decoration-2 underline-offset-2 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                        {{ __('registration.policy_link') }}
                    </a>
                    <span class="text-red-500 font-bold ml-1">*</span>
                </label>
            </div>

            @error('agreed_to_policy')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400 pl-8">{{ $message }}</p>
            @enderror
            @else
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5 dark:border-yellow-900/40 dark:bg-yellow-900/20">
                <p class="flex items-center gap-2 text-sm font-medium text-yellow-800 dark:text-yellow-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    {{ __('registration.policy_unavailable') }}
                </p>
            </div>
            @endif

            {{-- TOMBOL SUBMIT --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <a
                    href="{{ url('/') }}"
                    class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-center font-medium text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:focus:ring-offset-gray-900">
                    {{ __('registration.cancel') }}
                </a>
                <button
                    id="submitBtn"
                    type="submit"
                    class="rounded-lg bg-green-600 px-6 py-2.5 text-center font-medium text-white shadow-md transition-all hover:bg-green-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-offset-gray-900"
                    @if(!$policy) disabled title="{{ __('registration.policy_unavailable') }}" @endif>
                    {{ __('registration.submit') }}
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        // Toggle Password Function
        function togglePasswordField() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon-reg');
            const eyeSlashIcon = document.getElementById('eye-slash-icon-reg');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeSlashIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeSlashIcon.classList.add('hidden');
            }
        }

        // Form logic
        (function() {
            const fromRoom = @json($fromRoom);
            const citizenship = document.getElementById('citizenship_status');
            const country = document.getElementById('country_id');
            const residentCategory = document.getElementById('resident_category_id');
            const dormSelect = document.getElementById('preferred_dorm_id');
            const roomTypeSelect = document.getElementById('preferred_room_type_id');
            const roomSelect = document.getElementById('preferred_room_id'); // null jika fromRoom
            const dormInfo = document.getElementById('dorm-info');
            const roomTypeInfo = document.getElementById('room-type-info');
            const indoId = @json($indoCountryId);

            const trans = {
                no_branch_available: "{{ __('registration.no_branch_available') }}",
                branch_available: "{{ __('registration.branch_available') }}",
                no_room_type_available: "{{ __('registration.no_room_type_available') }}",
                no_room_type_available_cat: "{{ __('registration.no_room_type_available_cat') }}",
                room_type_available: "{{ __('registration.room_type_available') }}",
            };

            const roomAvailability = @json($roomAvailability ?? []);

            // ─── Sync negara ────────────────────────────────────────────────
            function syncCountry() {
                if (citizenship.value === 'WNI' && indoId && !country.value) {
                    country.value = String(indoId);
                }
            }

            // ─── Filter dropdown dorm ────────────────────────────────────────
            function filterDorms() {
                const categoryId = residentCategory?.value;
                const selectedDormId = dormSelect?.value;

                if (!categoryId) {
                    if (dormSelect) dormSelect.value = '';
                    if (roomTypeSelect) roomTypeSelect.value = '';
                    Array.from(dormSelect?.options ?? []).forEach((opt, i) => {
                        if (i > 0) opt.disabled = true;
                    });
                    Array.from(roomTypeSelect?.options ?? []).forEach((opt, i) => {
                        if (i > 0) opt.disabled = true;
                    });
                    dormInfo?.classList.add('hidden');
                    roomTypeInfo?.classList.add('hidden');
                    return;
                }

                let count = 0;
                Array.from(dormSelect?.options ?? []).forEach((opt, i) => {
                    if (i === 0) return;
                    const has = roomAvailability.some(r =>
                        r.dorm_id === parseInt(opt.value) &&
                        (r.resident_category_id === parseInt(categoryId) || r.resident_category_id === null) &&
                        r.available_capacity > 0
                    );
                    opt.disabled = !has;
                    if (has) count++;
                });

                if (dormInfo) {
                    if (count === 0) {
                        dormInfo.textContent = trans.no_branch_available;
                        dormInfo.classList.replace('text-gray-500', 'text-amber-600');
                    } else {
                        dormInfo.textContent = `${count} ${trans.branch_available}`;
                        dormInfo.classList.replace('text-amber-600', 'text-gray-500');
                    }
                    dormInfo.classList.remove('hidden');
                }

                if (selectedDormId && dormSelect?.querySelector(`option[value="${selectedDormId}"]`)?.disabled) {
                    dormSelect.value = '';
                }

                filterRoomTypes();
            }

            // ─── Filter dropdown tipe kamar ──────────────────────────────────
            function filterRoomTypes() {
                const categoryId = residentCategory?.value;
                const dormId = dormSelect?.value;
                const selectedRoomTypeId = roomTypeSelect?.value;

                if (!categoryId) {
                    if (roomTypeSelect) roomTypeSelect.value = '';
                    Array.from(roomTypeSelect?.options ?? []).forEach((opt, i) => {
                        if (i > 0) opt.disabled = true;
                    });
                    roomTypeInfo?.classList.add('hidden');
                    return;
                }

                let count = 0;
                Array.from(roomTypeSelect?.options ?? []).forEach((opt, i) => {
                    if (i === 0) return;
                    const has = roomAvailability.some(r => {
                        const matchCategory = r.resident_category_id === parseInt(categoryId) || r.resident_category_id === null;
                        const matchType = r.room_type_id === parseInt(opt.value);
                        const matchDorm = dormId ? r.dorm_id === parseInt(dormId) : true;
                        return matchCategory && matchType && matchDorm && r.available_capacity > 0;
                    });
                    opt.disabled = !has;
                    if (has) count++;
                });

                if (roomTypeInfo) {
                    if (count === 0) {
                        roomTypeInfo.textContent = dormId ? trans.no_room_type_available : trans.no_room_type_available_cat;
                        roomTypeInfo.classList.replace('text-gray-500', 'text-amber-600');
                    } else {
                        roomTypeInfo.textContent = `${count} ${trans.room_type_available}`;
                        roomTypeInfo.classList.replace('text-amber-600', 'text-gray-500');
                    }
                    roomTypeInfo.classList.remove('hidden');
                }

                if (selectedRoomTypeId && roomTypeSelect?.querySelector(`option[value="${selectedRoomTypeId}"]`)?.disabled) {
                    roomTypeSelect.value = '';
                }

                // Ikut filter nomor kamar jika dropdown tersedia
                if (roomSelect) filterRoomOptions();
            }

            // ─── Filter dropdown nomor kamar ─────────────────────────────────
            function filterRoomOptions() {
                if (!roomSelect) return;
                const dormId = dormSelect?.value ? parseInt(dormSelect.value) : null;
                const roomTypeId = roomTypeSelect?.value ? parseInt(roomTypeSelect.value) : null;
                const categoryId = residentCategory?.value ? parseInt(residentCategory.value) : null;

                let anyVisible = false;
                Array.from(roomSelect.options).forEach((opt, i) => {
                    if (i === 0) return; // skip placeholder
                    const matchDorm = !dormId || parseInt(opt.dataset.dorm) === dormId;
                    const matchType = !roomTypeId || parseInt(opt.dataset.roomType) === roomTypeId;
                    const matchCategory = !categoryId || parseInt(opt.dataset.category) === categoryId;
                    const visible = matchDorm && matchType && matchCategory;
                    opt.hidden = !visible;
                    opt.disabled = !visible;
                    if (visible) anyVisible = true;
                });

                // Reset jika kamar yang dipilih sudah tersembunyi
                if (roomSelect.value) {
                    const sel = roomSelect.options[roomSelect.selectedIndex];
                    if (sel?.hidden) roomSelect.value = '';
                }
            }

            // ─── Pilih nomor kamar → auto-isi dorm, tipe kamar, & kategori ──
            function onRoomChange() {
                if (!roomSelect) return;
                const opt = roomSelect.options[roomSelect.selectedIndex];
                if (!opt?.value) return;

                // Set kategori dulu agar filterDorms tidak reset dorm
                if (residentCategory && opt.dataset.category) {
                    residentCategory.value = opt.dataset.category;
                }
                if (dormSelect && opt.dataset.dorm) {
                    dormSelect.value = opt.dataset.dorm;
                }
                filterDorms(); // filter dulu sebelum set tipe kamar

                if (roomTypeSelect && opt.dataset.roomType) {
                    roomTypeSelect.value = opt.dataset.roomType;
                }
            }

            // ─── Validasi form untuk button submit ──────────────────────────────
            const submitBtn = document.getElementById('submitBtn');
            const form = document.querySelector('form');
            const policyAvailable = @json($policy);

            function validateForm() {
                // Field-field yang required (dengan tanda *)
                const requiredFields = [
                    'email',
                    'name',
                    'resident_category_id',
                    'full_name',
                    'gender',
                    'national_id',
                    'birth_place',
                    'birth_date',
                    'student_id',
                    'university_school',
                    'citizenship_status',
                    'country_id',
                    'phone_number'
                ];

                // Cek apakah policy tersedia
                if (!policyAvailable) {
                    submitBtn.disabled = true;
                    submitBtn.title = "{{ __('registration.policy_unavailable') }}";
                    return false;
                }

                // Cek apakah semua field required terisi
                let allFilled = true;
                for (let fieldName of requiredFields) {
                    const field = form.querySelector(`[name="${fieldName}"]`);
                    if (field && !field.value) {
                        allFilled = false;
                        break;
                    }
                }

                // Cek policy checkbox jika policy tersedia
                if (policyAvailable) {
                    const policyCheckbox = form.querySelector('input[name="agreed_to_policy"]');
                    if (policyCheckbox && !policyCheckbox.checked) {
                        allFilled = false;
                    }
                }

                // Update button state
                submitBtn.disabled = !allFilled;
                if (!allFilled) {
                    submitBtn.title = "{{ __('Silakan isi semua field yang ditandai dengan *') }}";
                } else {
                    submitBtn.title = '';
                }

                return allFilled;
            }

            // ─── Event listeners ─────────────────────────────────────────────
            citizenship.addEventListener('change', syncCountry);

            // Tambahkan event listener untuk validasi form
            form?.addEventListener('change', validateForm);
            form?.addEventListener('input', validateForm);

            if (!fromRoom) {
                // Hanya aktifkan filter interaktif jika BUKAN dari halaman detail kamar
                residentCategory?.addEventListener('change', () => {
                    filterDorms();
                    filterRoomOptions();
                });
                dormSelect?.addEventListener('change', () => {
                    filterRoomTypes();
                    filterRoomOptions();
                });
                roomTypeSelect?.addEventListener('change', filterRoomOptions);
                roomSelect?.addEventListener('change', onRoomChange);
            }

            // ─── Initial run ─────────────────────────────────────────────────
            syncCountry();
            if (!fromRoom) {
                filterDorms();
                filterRoomOptions();
            }
            validateForm(); // Validasi initial state
        })();
    </script>
    @endpush
</x-public-layout>