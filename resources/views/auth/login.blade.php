<x-guest-layout>
    <!-- Status Sesi -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Alamat Email -->
        <div>
            <x-input-label for="email" :value="__('Alamat Email')" />
            <x-text-input
                id="email"
                class="mt-1 block w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Kata Sandi -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Kata Sandi')" />

            <x-text-input
                id="password"
                class="mt-1 block w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Ingat Saya -->
        <div class="mt-4 block">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500"
                    name="remember"
                >
                <span class="ms-2 text-sm text-gray-600">
                    {{ __('Ingat saya') }}
                </span>
            </label>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <!-- Link ke Pendaftaran -->
            <div class="text-sm">
                <a
                    href="{{ url('/pendaftaran') }}"
                    class="text-green-600 underline hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 rounded-md"
                >
                    {{ __('Belum punya akun? Daftar di sini') }}
                </a>
            </div>

            <div class="flex items-center">
                @if (Route::has('password.request'))
                    <a
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        href="{{ route('password.request') }}"
                    >
                        {{ __('Lupa kata sandi?') }}
                    </a>
                @endif

                <x-primary-button class="ms-3">
                    {{ __('Masuk') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
