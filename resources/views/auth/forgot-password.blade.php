<x-public-layout>
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg sm:p-8 dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">{{ __('auth.forgot_password_title') }}</h1>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">{{ __('auth.forgot_password_subtitle') }}</p>
            </div>

            <div class="mb-8 flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-lg font-bold text-gray-900 sm:text-xl dark:text-white">{{ __('auth.forgot_password_title') }}</h2>
                </div>
            </div>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
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
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="flex flex-col gap-4 pt-2">
                    <button type="submit" class="w-full rounded-xl bg-green-600 py-3 text-center font-bold text-white shadow-md transition-all hover:bg-green-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-offset-gray-900">
                        {{ __('auth.send_reset_link') }}
                    </button>
                    
                    <a href="{{ route('login') }}" class="text-center text-sm font-bold text-green-700 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                        {{ __('auth.back_to_login') }}
                    </a>
                </div>
            </form>
        </section>
    </div>
</x-public-layout>


