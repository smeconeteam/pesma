<x-public-layout>
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">{{ __('auth.reset_password_title') }}</h1>
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">{{ __('auth.reset_password_subtitle') }}</p>
        </div>

        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg sm:p-8 dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-8 flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-lg font-bold text-gray-900 sm:text-xl dark:text-white">{{ __('auth.reset_password_title') }}</h2>
                </div>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
                @csrf

                <!-- Token Reset Kata Sandi -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Alamat Email -->
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.email') }}</label>
                    <input
                        id="email"
                        class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Kata Sandi Baru -->
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.new_password') }}</label>
                    <input
                        id="password"
                        class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                    />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Konfirmasi Kata Sandi Baru -->
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.confirm_password') }}</label>
                    <input
                        id="password_confirmation"
                        class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full rounded-xl bg-green-600 py-3 text-center font-bold text-white shadow-md transition-all hover:bg-green-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-offset-gray-900">
                        {{ __('auth.reset_password_button') }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-public-layout>


