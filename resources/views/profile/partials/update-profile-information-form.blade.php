<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informasi Profil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Perbarui informasi profil, foto, dan alamat email akun Anda.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Photo Upload Section --}}
        <div>
            <x-input-label for="photo" :value="__('Foto Profil')" />
            
            <div class="mt-2 flex items-center gap-4">
                {{-- Current Photo Preview --}}
                <div class="shrink-0">
                    @php
                        $profile = $user->residentProfile ?? null;
                        $photoUrl = null;
                        if ($profile?->photo_path) {
                            $photoUrl = \Illuminate\Support\Facades\Storage::url($profile->photo_path);
                        }
                    @endphp
                    
                    @if ($photoUrl)
                        <img id="photo-preview" src="{{ $photoUrl }}" alt="Foto Profil"
                             class="h-20 w-20 rounded-full object-cover border-2 border-gray-300" />
                    @else
                        <div id="photo-preview" class="h-20 w-20 rounded-full bg-gray-100 border-2 border-gray-300 flex items-center justify-center">
                            <span class="text-2xl font-semibold text-gray-600">
                                {{ mb_substr($user->name ?? 'U', 0, 1) }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Upload and Delete Buttons --}}
                <div class="flex-1">
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <input 
                                type="file" 
                                id="photo" 
                                name="photo" 
                                accept="image/*"
                                class="hidden"
                                onchange="previewPhoto(event)"
                            />
                            <label 
                                for="photo"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition cursor-pointer w-full"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span id="file-label">Pilih Gambar</span>
                            </label>
                        </div>

                        {{-- Remove Photo Button (if photo exists) --}}
                        @if ($photoUrl)
                            <button 
                                type="button"
                                onclick="confirmDeletePhoto()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition active:scale-95 shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Hapus Foto
                            </button>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        Format: JPG, PNG, atau GIF. Maksimal 2MB.
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('photo')" />
                </div>
            </div>
        </div>

        {{-- Nickname Field --}}
        <div>
            <x-input-label for="name" :value="__('Nama Panggilan')" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', $user->name)"
                required
                autofocus
                autocomplete="name"
                placeholder="Nama yang digunakan untuk panggilan sehari-hari"
            />
            <p class="mt-1 text-xs text-gray-500">
                Nama ini akan digunakan untuk panggilan di sistem.
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        {{-- Email Field --}}
        <div>
            <x-input-label for="email" :value="__('Alamat Email')" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-1 block w-full"
                :value="old('email', $user->email)"
                required
                autocomplete="username"
            />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-gray-800">
                        {{ __('Alamat email Anda belum terverifikasi.') }}

                        <button
                            form="send-verification"
                            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            {{ __('Klik di sini untuk mengirim ulang email verifikasi.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-green-600">
                            {{ __('Tautan verifikasi baru telah dikirim ke alamat email Anda.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Phone Number --}}
        <div>
            <x-input-label for="phone_number" :value="__('No. HP')" />
            <x-text-input
                id="phone_number"
                name="phone_number"
                type="text"
                class="mt-1 block w-full"
                :value="old('phone_number', $profile?->phone_number)"
                placeholder="08xxxxxxxxxx"
            />
            <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >
                    {{ __('Tersimpan.') }}
                </p>
            @endif
        </div>
    </form>

    {{-- Hidden Form for Delete Photo --}}
    <form id="delete-photo-form" method="post" action="{{ route('profile.delete-photo') }}" class="hidden">
        @csrf
        @method('delete')
    </form>

    {{-- JavaScript for Photo Preview and Delete --}}
    <script>
        function previewPhoto(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('photo-preview');
            const fileLabel = document.getElementById('file-label');
            
            if (file) {
                // Check file size (2MB = 2097152 bytes)
                if (file.size > 2097152) {
                    alert('Ukuran file terlalu besar. Maksimal 2MB.');
                    event.target.value = '';
                    fileLabel.textContent = 'Pilih Gambar';
                    return;
                }

                // Check file type
                if (!file.type.match('image.*')) {
                    alert('File harus berupa gambar (JPG, PNG, atau GIF).');
                    event.target.value = '';
                    fileLabel.textContent = 'Pilih Gambar';
                    return;
                }

                // Update label with filename
                fileLabel.textContent = file.name.length > 20 ? file.name.substring(0, 20) + '...' : file.name;

                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="h-20 w-20 rounded-full object-cover border-2 border-gray-300" />`;
                }
                
                reader.readAsDataURL(file);
            } else {
                fileLabel.textContent = 'Pilih Gambar';
            }
        }

        function confirmDeletePhoto() {
            if (confirm('Apakah Anda yakin ingin menghapus foto profil? Tindakan ini tidak dapat dibatalkan.')) {
                document.getElementById('delete-photo-form').submit();
            }
        }
    </script>
</section>