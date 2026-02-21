<x-public-layout>
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg sm:p-8 dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">{{ __('auth.login_title') }}</h1>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">{{ __('auth.login_subtitle') }}</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-6">

                @csrf

                <!-- Alamat Email -->
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.email') }}</label>
                    <input
                        id="email"
                        class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Kata Sandi -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.password_label') }}</label>
                        @if (Route::has('password.request'))
                            <a class="text-xs font-semibold text-green-600 hover:text-green-700 focus:outline-none dark:text-green-400 dark:hover:text-green-300" href="{{ route('password.request') }}">
                                {{ __('auth.forgot_password') }}
                            </a>
                        @endif
                    </div>

                    <div class="relative">
                        <input
                            id="password"
                            class="w-full rounded-lg border-gray-300 pr-10 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                        />
                        
                        <!-- Tombol Toggle Password -->
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg id="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Ingat Saya -->
                <div class="block">
                    <label for="remember_me" class="inline-flex cursor-pointer items-center">
                        <input
                            id="remember_me"
                            type="checkbox"
                            class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500 dark:border-gray-600 dark:bg-gray-800"
                            name="remember"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('auth.remember_me') }}
                        </span>
                    </label>
                </div>

                <div class="flex flex-col gap-4 pt-2">
                    <button type="submit" class="w-full rounded-xl bg-green-600 py-3 text-center font-bold text-white shadow-md transition-all hover:bg-green-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-offset-gray-900">
                        {{ __('auth.login_button') }}
                    </button>
                    
                    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                        {{ __('auth.dont_have_account') }}
                        <a
                            href="{{ url('/pendaftaran') }}"
                            class="font-bold text-green-700 underline decoration-2 underline-offset-2 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                        >
                            {{ __('auth.register_here') }}
                        </a>
                    </p>
                </div>
            </form>

        </section>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeSlashIcon = document.getElementById('eye-slash-icon');
            
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
    </script>
</x-public-layout>