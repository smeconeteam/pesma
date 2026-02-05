<x-guest-layout>
    <div class="mx-auto w-full px-4 py-10">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Pendaftaran Penghuni</h1>
            <p class="mt-1 text-gray-600">Isi data berikut. Setelah dikirim, admin akan meninjau dan menyetujui pendaftaran.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                <div class="mb-2 font-semibold">Ada kesalahan:</div>
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
            <section class="rounded-xl border bg-white p-6">
                <h2 class="text-lg font-semibold">Akun</h2>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Email <span class="text-red-500">*</span></label>
                        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama Panggilan <span class="text-red-500">*</span></label>
                        <input name="name" type="text" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Password</label>
                        <div class="relative">
                            <input 
                                id="password"
                                name="password" 
                                type="password" 
                                value="123456789"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 pr-10"
                            >
                            
                            <!-- Tombol Toggle Password -->
                            <button
                                type="button"
                                onclick="togglePasswordField()"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-600 hover:text-gray-800 focus:outline-none"
                            >
                                <svg id="eye-icon-reg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg id="eye-slash-icon-reg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Default password: 123456789 (dapat diubah setelah login)</p>
                    </div>
                </div>
            </section>

            {{-- PROFIL --}}
            <section class="rounded-xl border bg-white p-6">
                <h2 class="text-lg font-semibold">Profil Calon Penghuni</h2>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Kategori Penghuni <span class="text-red-500">*</span></label>
                        <select id="resident_category_id" name="resident_category_id" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                            <option value="">-- pilih --</option>
                            @foreach ($residentCategories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('resident_category_id') == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input name="full_name" type="text" value="{{ old('full_name') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Jenis Kelamin <span class="text-red-500">*</span></label>
                        <select name="gender" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                            <option value="">-- pilih --</option>
                            <option value="M" @selected(old('gender') === 'M')>Laki-laki</option>
                            <option value="F" @selected(old('gender') === 'F')>Perempuan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">NIK <span class="text-red-500">*</span></label>
                        <input name="national_id" inputmode="numeric" pattern="[0-9]*" value="{{ old('national_id') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>



                    <div>
                        <label class="block text-sm font-medium">Tempat Lahir <span class="text-red-500">*</span></label>
                        <input name="birth_place" type="text" value="{{ old('birth_place') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Tanggal Lahir <span class="text-red-500">*</span></label>
                        <input name="birth_date" type="date" value="{{ old('birth_date') }}" max="{{ now()->subYears(6)->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                        <p class="mt-1 text-xs text-gray-500">Minimal usia 6 tahun</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">NIM/NIS <span class="text-red-500">*</span></label>
                        <input name="student_id" type="text" value="{{ old('student_id') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Universitas/Sekolah <span class="text-red-500">*</span></label>
                        <input name="university_school" type="text" value="{{ old('university_school') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Foto (Opsional)</label>
                        <input name="photo" type="file" accept="image/*" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>
            </section>

            {{-- KEWARGANEGARAAN --}}
            <section class="rounded-xl border bg-white p-6">
                <h2 class="text-lg font-semibold">Kewarganegaraan & Kontak</h2>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Kewarganegaraan <span class="text-red-500">*</span></label>
                        <select id="citizenship_status" name="citizenship_status" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                            <option value="WNI" @selected(old('citizenship_status', 'WNI') === 'WNI')>WNI</option>
                            <option value="WNA" @selected(old('citizenship_status') === 'WNA')>WNA</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Asal Negara <span class="text-red-500">*</span></label>
                        <select id="country_id" name="country_id" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                            <option value="">-- pilih --</option>
                            @foreach ($countries as $c)
                                <option value="{{ $c->id }}" @selected(old('country_id', $indoCountryId) == $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">No. HP <span class="text-red-500">*</span></label>
                        <input name="phone_number" inputmode="numeric" pattern="[0-9]*" value="{{ old('phone_number') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama Wali (Opsional)</label>
                        <input name="guardian_name" type="text" value="{{ old('guardian_name') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">No. HP Wali (Opsional)</label>
                        <input name="guardian_phone_number" inputmode="numeric" pattern="[0-9]*" value="{{ old('guardian_phone_number') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Alamat (Opsional)</label>
                        <textarea name="address" rows="3" maxlength="500" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" placeholder="Masukkan alamat lengkap...">{{ old('address') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Alamat lengkap tempat tinggal</p>
                    </div>
                </div>
            </section>

            {{-- PREFERENSI --}}
            <section class="rounded-xl border bg-white p-6">
                <h2 class="text-lg font-semibold">Preferensi Kamar (Opsional)</h2>
                <p class="mt-1 text-sm text-gray-600">Anda dapat mengisi preferensi kamar atau membiarkan admin menentukan kamar yang sesuai untuk Anda.</p>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Cabang</label>
                        <select id="preferred_dorm_id" name="preferred_dorm_id" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            <option value="">-- pilih cabang --</option>
                            @foreach ($dorms as $d)
                                <option value="{{ $d->id }}" @selected(old('preferred_dorm_id') == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <p id="dorm-info" class="mt-1 text-xs text-gray-500 hidden"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Tipe Kamar</label>
                        <select id="preferred_room_type_id" name="preferred_room_type_id" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            <option value="">-- pilih tipe kamar --</option>
                            @foreach ($roomTypes as $rt)
                                <option value="{{ $rt->id }}" @selected(old('preferred_room_type_id') == $rt->id)>{{ $rt->name }}</option>
                            @endforeach
                        </select>
                        <p id="room-type-info" class="mt-1 text-xs text-gray-500 hidden"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Rencana Tanggal Masuk</label>
                        <input name="planned_check_in_date" type="date" value="{{ old('planned_check_in_date', now()->addDays(7)->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                        <p class="mt-1 text-xs text-gray-500">Minimal hari ini</p>
                    </div>
                </div>
            </section>

            {{-- CHECKBOX KEBIJAKAN --}}
            @if($policy)
                <div class="flex items-start space-x-3 bg-green-50 border border-green-200 rounded-lg p-4">
                    <input 
                        type="checkbox" 
                        name="agreed_to_policy" 
                        id="agreed_to_policy" 
                        value="1"
                        class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded flex-shrink-0"
                        {{ old('agreed_to_policy') ? 'checked' : '' }}
                        required
                    >
                    <label for="agreed_to_policy" class="text-sm text-gray-700">
                        Saya telah membaca dan menyetujui 
                        <a 
                            href="{{ route('public.policy') }}" 
                            target="_blank"
                            class="text-green-600 hover:text-green-700 font-semibold underline"
                        >
                            Kebijakan & Ketentuan
                        </a>
                        yang berlaku
                        <span class="text-red-600">*</span>
                    </label>
                </div>
                
                @error('agreed_to_policy')
                    <p class="text-sm text-red-600 -mt-4">{{ $message }}</p>
                @enderror
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-800">
                        ⚠️ Kebijakan belum tersedia. Silakan hubungi administrator.
                    </p>
                </div>
            @endif

            {{-- TOMBOL SUBMIT --}}
            <div class="flex items-center justify-end gap-3">
                <a 
                    href="{{ url('/') }}" 
                    class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium"
                >
                    Batal
                </a>
                <button 
                    type="submit" 
                    class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    @if(!$policy) disabled title="Kebijakan belum tersedia" @endif
                >
                    Kirim Pendaftaran
                </button>
            </div>
        </form>
    </div>

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
                    dormInfo.textContent = 'Tidak ada cabang yang tersedia untuk kategori ini. Silakan pilih kategori penghuni lain atau biarkan admin menentukan kamar yang sesuai.';
                    dormInfo.classList.remove('hidden');
                    dormInfo.classList.remove('text-gray-500');
                    dormInfo.classList.add('text-amber-600');
                } else {
                    dormInfo.textContent = `${availableDormsCount} cabang tersedia untuk kategori ini`;
                    dormInfo.classList.remove('hidden');
                    dormInfo.classList.remove('text-amber-600');
                    dormInfo.classList.add('text-gray-500');
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
                        roomTypeInfo.textContent = 'Tidak ada tipe kamar yang tersedia untuk kombinasi cabang dan kategori ini. Coba pilih cabang lain atau biarkan admin menentukan kamar yang sesuai.';
                    } else {
                        roomTypeInfo.textContent = 'Tidak ada tipe kamar yang tersedia untuk kategori ini. Silakan pilih kategori penghuni lain atau biarkan admin menentukan kamar yang sesuai.';
                    }
                    roomTypeInfo.classList.remove('hidden');
                    roomTypeInfo.classList.remove('text-gray-500');
                    roomTypeInfo.classList.add('text-amber-600');
                } else {
                    roomTypeInfo.textContent = `${availableRoomTypesCount} tipe kamar tersedia`;
                    roomTypeInfo.classList.remove('hidden');
                    roomTypeInfo.classList.remove('text-amber-600');
                    roomTypeInfo.classList.add('text-gray-500');
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
        })();
    </script>
</x-guest-layout>