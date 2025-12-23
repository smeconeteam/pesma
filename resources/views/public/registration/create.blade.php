<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 py-10">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Pendaftaran Penghuni</h1>
            <p class="text-gray-600 mt-1">Isi data berikut. Setelah dikirim, admin akan meninjau dan menyetujui pendaftaran.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                <div class="font-semibold mb-2">Ada kesalahan:</div>
                <ul class="list-disc pl-5 space-y-1">
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input name="email" type="email" value="{{ old('email') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama (Username)</label>
                        <input name="name" type="text" value="{{ old('name') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Password</label>
                        <input name="password" type="password"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                        <p class="text-xs text-gray-500 mt-1">Boleh dikosongkan, sistem akan set default 123456789.</p>
                    </div>
                </div>
            </section>

            {{-- PROFIL --}}
            <section class="rounded-xl border bg-white p-6">
                <h2 class="text-lg font-semibold">Profil Calon Penghuni</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                    <div>
                        <label class="block text-sm font-medium">Kategori Penghuni</label>
                        <select name="resident_category_id"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                            <option value="">-- pilih --</option>
                            @foreach($residentCategories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('resident_category_id') == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama Lengkap</label>
                        <input name="full_name" type="text" value="{{ old('full_name') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Jenis Kelamin</label>
                        <select name="gender"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                            <option value="">-- pilih --</option>
                            <option value="M" @selected(old('gender') === 'M')>Laki-laki</option>
                            <option value="F" @selected(old('gender') === 'F')>Perempuan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">NIK</label>
                        <input name="national_id" inputmode="numeric" pattern="[0-9]*" value="{{ old('national_id') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">NIM</label>
                        <input name="student_id" type="text" value="{{ old('student_id') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Tempat Lahir</label>
                        <input name="birth_place" type="text" value="{{ old('birth_place') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Tanggal Lahir</label>
                        <input name="birth_date" type="date" value="{{ old('birth_date') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Universitas/Sekolah</label>
                        <input name="university_school" type="text" value="{{ old('university_school') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Foto</label>
                        <input name="photo" type="file" accept="image/*"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>
            </section>

            {{-- KEWARGANEGARAAN --}}
            <section class="rounded-xl border bg-white p-6">
                <h2 class="text-lg font-semibold">Kewarganegaraan & Kontak</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                    <div>
                        <label class="block text-sm font-medium">Kewarganegaraan</label>
                        <select id="citizenship_status" name="citizenship_status"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                            <option value="WNI" @selected(old('citizenship_status','WNI') === 'WNI')>WNI</option>
                            <option value="WNA" @selected(old('citizenship_status') === 'WNA')>WNA</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Asal Negara</label>
                        <select id="country_id" name="country_id"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required>
                            <option value="">-- pilih --</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->id }}" @selected(old('country_id', $indoCountryId) == $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">No. HP</label>
                        <input name="phone_number" inputmode="numeric" pattern="[0-9]*" value="{{ old('phone_number') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama Wali</label>
                        <input name="guardian_name" type="text" value="{{ old('guardian_name') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">No. HP Wali</label>
                        <input name="guardian_phone_number" inputmode="numeric" pattern="[0-9]*" value="{{ old('guardian_phone_number') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>
            </section>

            {{-- PREFERENSI --}}
            <section class="rounded-xl border bg-white p-6">
                <h2 class="text-lg font-semibold">Preferensi Kamar</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                    <div>
                        <label class="block text-sm font-medium">Cabang</label>
                        <select name="preferred_dorm_id"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            <option value="">-- opsional --</option>
                            @foreach($dorms as $d)
                                <option value="{{ $d->id }}" @selected(old('preferred_dorm_id') == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Tipe Kamar</label>
                        <select name="preferred_room_type_id"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            <option value="">-- opsional --</option>
                            @foreach($roomTypes as $rt)
                                <option value="{{ $rt->id }}" @selected(old('preferred_room_type_id') == $rt->id)>{{ $rt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Rencana Tanggal Masuk</label>
                        <input name="planned_check_in_date" type="date" value="{{ old('planned_check_in_date') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>
            </section>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-5 py-2.5 text-white font-medium hover:bg-green-700">
                    Kirim Pendaftaran
                </button>
                <a href="{{ url('/') }}" class="text-sm text-gray-600 hover:underline">Kembali</a>
            </div>
        </form>
    </div>

    <script>
    (function () {
        const citizenship = document.getElementById('citizenship_status');
        const country     = document.getElementById('country_id');
        const indoId      = @json($indoCountryId);

        function syncCountry() {
            const isWni = citizenship.value === 'WNI';
            if (isWni && indoId) {
                country.value = String(indoId);
                country.setAttribute('disabled', 'disabled');
            } else {
                country.removeAttribute('disabled');
            }
        }

        citizenship.addEventListener('change', syncCountry);
        syncCountry();
    })();
    </script>
</x-app-layout>
