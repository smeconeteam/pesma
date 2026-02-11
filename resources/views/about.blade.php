<x-public-layout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold mb-2">Tentang Kami</h1>
            <p class="">Mengenal lebih dekat {{ $institution->dormitory_name ?? 'asrama kami' }}</p>
        </div>

        <!-- Main Content -->
        <div class="max-w-3xl mx-auto mt-4">
            @if ($institution && $institution->about_content)
                <div class="content-card">
                    <div class="rich-content">
                        {!! $institution->about_content !!}
                    </div>
                </div>
            @else
                <div class="content-card">
                    <div class="empty-state">
                        <svg class="w-16 h-16" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3>Konten Belum Tersedia</h3>
                        <p>Informasi tentang asrama sedang dalam proses penyusunan.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-public-layout>
