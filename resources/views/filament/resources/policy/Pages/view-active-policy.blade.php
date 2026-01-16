<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-6">
            {{-- Judul --}}
            <div>
                <h2 class="text-xl font-semibold">
                    {{ data_get($this->data, 'title', '-') }}
                </h2>
            </div>

            {{-- Meta info: Tanggal & Status --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ data_get($this->data, 'is_active') ? 'Aktif' : 'Tidak Aktif' }}
                    </p>
                </div>
            </div>

            {{-- Isi Rich Text (satu card yang sama) --}}
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Isi Kebijakan & Ketentuan</p>

                <div class="rounded-xl border border-gray-200 bg-white p-4 text-gray-900 shadow-sm
                            dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100">
                    <div class="prose max-w-none dark:prose-invert">
                        {!! data_get($this->data, 'content_html', '<p>-</p>') !!}
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
