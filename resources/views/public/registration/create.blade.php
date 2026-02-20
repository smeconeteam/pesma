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
                        <select id="resident_category_id" name="resident_category_id" ... required>
                            <option value="">{{ __('registration.select_option') }}</option>
                            @foreach ($residentCategories as $cat)
                            {{-- ✅ Prioritas: old() dulu, baru prefill dari kamar --}}
                            <option value="{{ $cat->id }}"
                                @selected(old('resident_category_id', $prefill['resident_category_id'])==$cat->id)>
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

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.branch') }}</label>
                        <select id="preferred_dorm_id" name="preferred_dorm_id" ...>
                            <option value="">{{ __('registration.select_branch') }}</option>
                            @foreach ($dorms as $d)
                                <option value="{{ $d->id }}"
                                    @selected(old('preferred_dorm_id', $prefill['preferred_dorm_id']) == $d->id)>
                                    {{ $d->name }}
                                </option>
                            @endforeach
                        </select>
                        <p id="dorm-info" class="mt-2 text-xs font-medium hidden"></p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('registration.room_type') }}</label>
                        <select id="preferred_room_type_id" name="preferred_room_type_id" ...>
                            <option value="">{{ __('registration.select_room_type') }}</option>
                            @foreach ($roomTypes as $rt)
                                <option value="{{ $rt->id }}"
                                    @selected(old('preferred_room_type_id', $prefill['preferred_room_type_id']) == $rt->id)>
                                    {{ $rt->name }}
                                </option>
                            @endforeach
                        </select>
                        <p id="room-type-info" class="mt-2 text-xs font-medium hidden"></p>
                    </div>

                    <div>
                        {{-- Hidden field untuk simpan room spesifik --}}
                        <input type="hidden" name="preferred_room_id" value="{{ old('preferred_room_id', $prefill['room_id']) }}">

                        {{-- Tampilkan info kamar jika datang dari halaman detail kamar --}}
                        @if($prefillRoom)
                        <div class="md:col-span-2">
                            <div class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                                <svg class="h-5 w-5 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-green-800 dark:text-green-200">
                                    <span class="font-semibold">Kamar dipilih:</span>
                                    {{ $prefillRoom->block->dorm->name }} — Nomor {{ $prefillRoom->number }}
                                    ({{ $prefillRoom->roomType->name }})
                                </div>
                                {{-- Tombol batalkan pilihan kamar spesifik --}}
                                <a href="{{ route('public.registration.create', array_filter([
                                    'preferred_dorm_id'      => $prefill['preferred_dorm_id'],
                                    'preferred_room_type_id' => $prefill['preferred_room_type_id'],
                                    'resident_category_id'   => $prefill['resident_category_id'],
                                ])) }}" class="ml-auto text-xs text-green-700 underline hover:text-green-900">
                                    Ubah
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>

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

        // Existing form logic
        (function() {
            const citizenship = document.getElementById('citizenship_status');
            const country = document.getElementById('country_id');
            const residentCategory = document.getElementById('resident_category_id');
            const dormSelect = document.getElementById('preferred_dorm_id');
            const roomTypeSelect = document.getElementById('preferred_room_type_id');
            const dormInfo = document.getElementById('dorm-info');
            const roomTypeInfo = document.getElementById('room-type-info');
            const indoId = @json($indoCountryId);

            // Translations
            const trans = {
                no_branch_available: "{{ __('registration.no_branch_available') }}",
                branch_available: "{{ __('registration.branch_available') }}",
                no_room_type_available: "{{ __('registration.no_room_type_available') }}",
                no_room_type_available_cat: "{{ __('registration.no_room_type_available_cat') }}",
                room_type_available: "{{ __('registration.room_type_available') }}",
            };

            // Data availability dari backend
            const roomAvailability = @json($roomAvailability ?? []);

            function syncCountry() {
                const isWni = citizenship.value === 'WNI';
                if (isWni && indoId && !country.value) {
                    country.value = String(indoId);
                }
            }

            function filterDorms() {
                const categoryId = residentCategory.value;
                const selectedDormId = dormSelect.value;

                // Reset dan disable jika belum pilih kategori
                if (!categoryId) {
                    dormSelect.value = '';
                    roomTypeSelect.value = '';
                    Array.from(dormSelect.options).forEach((opt, idx) => {
                        if (idx > 0) opt.disabled = true;
                    });
                    Array.from(roomTypeSelect.options).forEach((opt, idx) => {
                        if (idx > 0) opt.disabled = true;
                    });
                    dormInfo.classList.add('hidden');
                    roomTypeInfo.classList.add('hidden');
                    return;
                }

                // Filter dorms
                let availableDormsCount = 0;
                Array.from(dormSelect.options).forEach((option, idx) => {
                    if (idx === 0) return; // skip placeholder

                    const dormId = parseInt(option.value);
                    const hasAvailability = roomAvailability.some(r =>
                        r.dorm_id === dormId &&
                        (r.resident_category_id === parseInt(categoryId) || r.resident_category_id === null) &&
                        r.available_capacity > 0
                    );

                    option.disabled = !hasAvailability;
                    if (hasAvailability) availableDormsCount++;
                });

                // Show info jika tidak ada cabang tersedia
                if (availableDormsCount === 0) {
                    dormInfo.textContent = trans.no_branch_available;
                    dormInfo.classList.remove('hidden');
                    dormInfo.classList.remove('text-gray-500', 'dark:text-gray-400');
                    dormInfo.classList.add('text-amber-600', 'dark:text-amber-400');
                } else {
                    dormInfo.textContent = `${availableDormsCount} ${trans.branch_available}`;
                    dormInfo.classList.remove('hidden');
                    dormInfo.classList.remove('text-amber-600', 'dark:text-amber-400');
                    dormInfo.classList.add('text-gray-500', 'dark:text-gray-400');
                }

                // Reset dorm jika tidak tersedia lagi
                if (selectedDormId && dormSelect.querySelector(`option[value="${selectedDormId}"]`)?.disabled) {
                    dormSelect.value = '';
                }

                filterRoomTypes();
            }

            function filterRoomTypes() {
                const categoryId = residentCategory.value;
                const dormId = dormSelect.value;
                const selectedRoomTypeId = roomTypeSelect.value;

                // Reset jika belum pilih kategori
                if (!categoryId) {
                    roomTypeSelect.value = '';
                    Array.from(roomTypeSelect.options).forEach((opt, idx) => {
                        if (idx > 0) opt.disabled = true;
                    });
                    roomTypeInfo.classList.add('hidden');
                    return;
                }

                // Filter room types
                let availableRoomTypesCount = 0;
                Array.from(roomTypeSelect.options).forEach((option, idx) => {
                    if (idx === 0) return; // skip placeholder

                    const roomTypeId = parseInt(option.value);

                    // Jika ada dorm dipilih, filter berdasarkan dorm dan kategori
                    // Jika tidak ada dorm, filter berdasarkan kategori saja (ada di semua dorm)
                    const hasAvailability = roomAvailability.some(r => {
                        const matchCategory = r.resident_category_id === parseInt(categoryId) || r.resident_category_id === null;
                        const matchRoomType = r.room_type_id === roomTypeId;
                        const matchDorm = dormId ? r.dorm_id === parseInt(dormId) : true;
                        const hasCapacity = r.available_capacity > 0;

                        return matchCategory && matchRoomType && matchDorm && hasCapacity;
                    });

                    option.disabled = !hasAvailability;
                    if (hasAvailability) availableRoomTypesCount++;
                });

                // Show info
                if (availableRoomTypesCount === 0) {
                    if (dormId) {
                        roomTypeInfo.textContent = trans.no_room_type_available;
                    } else {
                        roomTypeInfo.textContent = trans.no_room_type_available_cat;
                    }
                    roomTypeInfo.classList.remove('hidden');
                    roomTypeInfo.classList.remove('text-gray-500', 'dark:text-gray-400');
                    roomTypeInfo.classList.add('text-amber-600', 'dark:text-amber-400');
                } else {
                    roomTypeInfo.textContent = `${availableRoomTypesCount} ${trans.room_type_available}`;
                    roomTypeInfo.classList.remove('hidden');
                    roomTypeInfo.classList.remove('text-amber-600', 'dark:text-amber-400');
                    roomTypeInfo.classList.add('text-gray-500', 'dark:text-gray-400');
                }

                // Reset room type jika tidak tersedia lagi
                if (selectedRoomTypeId && roomTypeSelect.querySelector(`option[value="${selectedRoomTypeId}"]`)?.disabled) {
                    roomTypeSelect.value = '';
                }
            }

            // Event listeners
            citizenship.addEventListener('change', syncCountry);
            residentCategory.addEventListener('change', filterDorms);
            dormSelect.addEventListener('change', filterRoomTypes);

            // Initial sync
            syncCountry();
            filterDorms();
            filterRoomTypes();
        })();
    </script>
    @endpush
</x-public-layout>