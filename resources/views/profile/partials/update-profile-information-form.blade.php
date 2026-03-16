<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('profile.profile_info') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('profile.profile_desc') }}
        </p>
    </header>

    <form method="post" action="{{ localizedRoute('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Photo Upload Section --}}
        <div>
            <x-input-label for="photo" :value="__('profile.photo')" />
            
            <div class="mt-2 flex flex-col sm:flex-row items-start sm:items-center gap-4">
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
                             class="h-20 w-20 rounded-full object-cover border-2 border-gray-300 dark:border-gray-600" />
                    @else
                        <div id="photo-preview" class="h-20 w-20 rounded-full bg-gray-100 dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center">
                            <span class="text-2xl font-semibold text-gray-600 dark:text-gray-400">
                                {{ mb_substr($user->name ?? 'U', 0, 1) }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Upload and Delete Area --}}
                <div class="flex-1 w-full sm:w-auto">
                    <div class="cloud-upload relative overflow-hidden h-40">
                        <input 
                            type="file" 
                            id="photo" 
                            name="photo" 
                            accept="image/*"
                            class="absolute inset-0 z-10 cursor-pointer opacity-0"
                            onchange="previewPhoto(event)"
                        />
                        <div class="cloud-upload-container h-full">
                            <div class="cloud-icon" id="cloud-icon-p"></div>
                            <div class="cloud-upload-text">
                                <span class="font-semibold text-blue-500" id="file-label-text">Klik untuk ubah</span> atau seret foto baru
                                <p class="mt-1 text-xs opacity-70">PNG, JPG atau WEBP (Maks. 2MB)</p>
                            </div>
                        </div>
                    </div>

                    {{-- Remove Photo Button (styled as a small link/secondary button) --}}
                    @if ($photoUrl)
                        <div class="mt-3">
                            <button 
                                type="button"
                                onclick="confirmDeletePhoto()"
                                class="text-xs font-semibold text-red-500 hover:text-red-700 flex items-center gap-1 transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ __('profile.delete_photo') }}
                            </button>
                        </div>
                    @endif
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('profile.photo_format') }}
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('photo')" />
                </div>
            </div>
        </div>

        {{-- Nickname Field --}}
        <div>
            <x-input-label for="name" :value="__('profile.nickname')" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', $user->name)"
                required
                autofocus
                autocomplete="name"
                placeholder="{{ __('profile.nickname_placeholder') }}"
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('profile.nickname_help') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        {{-- Email Field --}}
        <div>
            <x-input-label for="email" :value="__('profile.email_address')" />
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
                        {{ __('profile.email_unverified') }}

                        <button
                            form="send-verification"
                            class="rounded-md text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                        >
                            {{ __('profile.resend_verification') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-green-600">
                            {{ __('profile.verification_sent') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Phone Number --}}
        <div>
            <x-input-label for="phone_number" :value="__('profile.phone_number')" />
            <x-text-input
                id="phone_number"
                name="phone_number"
                type="text"
                class="mt-1 block w-full"
                :value="old('phone_number', $profile?->phone_number)"
                placeholder="{{ __('profile.phone_placeholder') }}"
            />
            <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
        </div>

        {{-- Address --}}
        <div>
            <x-input-label for="address" :value="__('profile.address')" />
            <x-text-input
                id="address"
                name="address"
                type="text"
                class="mt-1 block w-full"
                :value="old('address', $profile?->address)"
                placeholder="{{ __('profile.address_placeholder') }}"
            />
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        {{-- School --}}
        <div>
            <x-input-label for="university_school" :value="__('profile.school')" />
            <x-text-input
                id="university_school"
                name="university_school"
                type="text"
                class="mt-1 block w-full"
                :value="old('university_school', $profile?->university_school)"
                placeholder="{{ __('profile.school_placeholder') }}"
            />
            <x-input-error class="mt-2" :messages="$errors->get('university_school')" />
        </div>

        {{-- Student ID --}}
        <div>
            <x-input-label for="student_id" :value="__('profile.student_id_label')" />
            <x-text-input
                id="student_id"
                name="student_id"
                type="text"
                class="mt-1 block w-full"
                :value="old('student_id', $profile?->student_id)"
                placeholder="{{ __('profile.student_id_placeholder') }}"
            />
            <x-input-error class="mt-2" :messages="$errors->get('student_id')" />
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('profile.save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >
                    {{ __('profile.saved') }}
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
    <style>
        /* CSS injection for profile page */
        .cloud-upload {
            background-color: rgba(0, 0, 0, 0.02);
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
        }
        .dark .cloud-upload {
            background-color: rgba(255, 255, 255, 0.03);
            border: none;
        }
        .cloud-upload:hover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.04);
        }
        .cloud-upload-container {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .cloud-icon {
            width: 48px;
            height: 48px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 50%;
            margin-bottom: 0.75rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.8' stroke='%233b82f6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z' /%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 24px;
            transition: all 0.3s ease;
        }
        .cloud-upload:hover .cloud-icon {
            transform: scale(1.1);
            background-color: #3b82f6;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.8' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z' /%3E%3C/svg%3E");
        }
    </style>
    <script>
        function previewPhoto(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('photo-preview');
            const fileLabel = document.getElementById('file-label-text');
            const cloudIcon = document.getElementById('cloud-icon-p');
            
            if (file) {
                // Check file size (2MB = 2097152 bytes)
                if (file.size > 2097152) {
                    alert("{{ __('profile.file_too_large') }}");
                    event.target.value = '';
                    fileLabel.textContent = "Klik untuk ubah";
                    return;
                }

                // Check file type
                if (!file.type.match('image.*')) {
                    alert("{{ __('profile.invalid_file_type') }}");
                    event.target.value = '';
                    fileLabel.textContent = "Klik untuk ubah";
                    return;
                }

                // Update label with filename
                fileLabel.textContent = file.name.length > 20 ? file.name.substring(0, 20) + '...' : file.name;

                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="h-20 w-20 rounded-full object-cover border-2 border-blue-500 shadow-md" />`;
                }
                
                reader.readAsDataURL(file);
            } else {
                fileLabel.textContent = "Klik untuk ubah";
            }
        }

        function confirmDeletePhoto() {
            if (confirm("{{ __('profile.confirm_delete_photo') }}")) {
                document.getElementById('delete-photo-form').submit();
            }
        }
    </script>
</section>