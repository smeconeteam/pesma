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

                {{-- Upload and Delete Buttons --}}
                <div class="flex-1 w-full sm:w-auto">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="w-full sm:w-auto">
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
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition cursor-pointer w-full"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span id="file-label">{{ __('profile.choose_image') }}</span>
                            </label>
                        </div>

                        {{-- Remove Photo Button (if photo exists) --}}
                        @if ($photoUrl)
                            <button 
                                type="button"
                                onclick="confirmDeletePhoto()"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition active:scale-95 w-full sm:w-auto"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ __('profile.delete_photo') }}
                            </button>
                        @endif
                    </div>
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
    <script>
        function previewPhoto(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('photo-preview');
            const fileLabel = document.getElementById('file-label');
            
            if (file) {
                // Check file size (2MB = 2097152 bytes)
                if (file.size > 2097152) {
                    alert("{{ __('profile.file_too_large') }}");
                    event.target.value = '';
                    fileLabel.textContent = "{{ __('profile.choose_image') }}";
                    return;
                }

                // Check file type
                if (!file.type.match('image.*')) {
                    alert("{{ __('profile.invalid_file_type') }}");
                    event.target.value = '';
                    fileLabel.textContent = "{{ __('profile.choose_image') }}";
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
                fileLabel.textContent = "{{ __('profile.choose_image') }}";
            }
        }

        function confirmDeletePhoto() {
            if (confirm("{{ __('profile.confirm_delete_photo') }}")) {
                document.getElementById('delete-photo-form').submit();
            }
        }
    </script>
</section>