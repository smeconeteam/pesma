@props(['short' => false, 'selectClass' => ''])

<div x-data="localeSwitcher()" {{ $attributes->except(['short', 'selectClass']) }} class="relative">
    <x-dropdown align="right" width="32" contentClasses="py-1 bg-white dark:bg-gray-800">
        <x-slot name="trigger">
            <button class="inline-flex w-full items-center justify-between rounded-md ring-2 ring-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <span class="flex items-center gap-2">
                    <span x-text="currentLocale === 'id' ? '🇮🇩' : '🇬🇧'"></span>
                    <span x-text="currentLocale === 'id' ? ({{ $short ? "'ID'" : "'Bahasa Indonesia'" }}) : ({{ $short ? "'EN'" : "'English'" }})"></span>
                </span>
                <svg class="-mr-1 ml-2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </x-slot>

        <x-slot name="content">
            <button @click="switchLocale('id')" class="cursor-pointer flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                <span>🇮🇩</span>
                <span>{{ $short ? 'ID' : 'Bahasa Indonesia' }}</span>
                <span x-show="currentLocale === 'id'" class="ml-auto text-green-600">✓</span>
            </button>
            <button @click="switchLocale('en')" class="cursor-pointer flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                <span>🇬🇧</span>
                <span>{{ $short ? 'EN' : 'English' }}</span>
                <span x-show="currentLocale === 'en'" class="ml-auto text-green-600">✓</span>
            </button>
        </x-slot>
    </x-dropdown>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('localeSwitcher', () => ({
                    currentLocale: '{{ app()->getLocale() }}',

                    init() {
                        const savedLocale = localStorage.getItem('locale');
                        if (savedLocale && savedLocale !== this.currentLocale) {
                            this.currentLocale = savedLocale;
                            this.setCookie('locale', savedLocale, 365);
                        } else if (!savedLocale) {
                            localStorage.setItem('locale', this.currentLocale);
                            this.setCookie('locale', this.currentLocale, 365);
                        }
                    },

                    async switchLocale(locale) {
                        if (locale === this.currentLocale && localStorage.getItem('locale') === locale) {
                            return;
                        }

                        try {
                            localStorage.setItem('locale', locale);

                            const csrfToken = document.querySelector('meta[name="csrf-token"]');
                            if (!csrfToken) {
                                console.error('CSRF token not found');
                                this.setCookie('locale', locale, 365);
                                window.location.reload();
                                return;
                            }

                            const response = await fetch('{{ route('locale.switch') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken.content,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    locale: locale
                                })
                            });

                            if (response.ok) {
                                const data = await response.json();
                                if (data.url) {
                                    window.location.href = data.url;
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                this.setCookie('locale', locale, 365);
                                window.location.reload();
                            }
                        } catch (error) {
                            console.error('Error switching locale:', error);
                            this.setCookie('locale', locale, 365);
                            window.location.reload();
                        }
                    },

                    setCookie(name, value, days) {
                        const expires = new Date();
                        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
                        document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
                    }
                }));
            });
        </script>
    @endpush
@endonce
