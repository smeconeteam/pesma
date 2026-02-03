<x-public-layout>
    <section class="hero">
        <div class="hero-container">
            <h1>Temukan Kamar Impian Anda</h1>
            <p>{{ $institution->dormitory_name ?? 'Asrama Modern' }} menyediakan berbagai pilihan kamar dengan fasilitas lengkap dan harga terjangkau</p>
            <div class="hero-buttons">
                <a href="#rooms" class="btn-primary" style="font-size: 18px; padding: 14px 32px;">Lihat Kamar</a>
                <a href="{{ route('public.registration.create') }}" class="btn-outline" style="font-size: 18px; padding: 14px 32px; margin-left: 16px;">Daftar Sekarang</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <div class="container" style="margin-top: -20px;">
        <div class="stats">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>{{ $totalRooms-1 }}+</h3>
                    <p>Kamar Tersedia</p>
                </div>
                <div class="stat-item">
                    <h3>100%</h3>
                    <p>Fasilitas Lengkap</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>Keamanan Terjaga</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rooms Section -->
    <div class="container" id="rooms">
        <div class="section-header">
            <div>
                <h2 class="section-title">Kamar yang Tersedia</h2>
                <p class="section-subtitle">Pilih kamar terbaik untuk kenyamanan Anda</p>
            </div>
            @if($totalRooms > 6)
                <a href="{{ route('rooms.available') }}" class="btn-outline">
                    Lihat Semua ({{ $totalRooms }})
                </a>
            @endif
        </div>

        @if($rooms->count() > 0)
            <div class="rooms-grid">
                @foreach($rooms as $room)
                    <a href="{{ route('rooms.show', $room->id) }}" class="room-card">
                        <div class="room-image" @if($room->thumbnail) style="background-image: url('{{ asset('storage/' . $room->thumbnail) }}'); background-size: cover; background-position: center;" @endif>
                            @if(!$room->thumbnail)
                                {{ $room->number }}
                            @endif
                            @if($room->is_active)
                                <div class="room-badge">{{ $room->residentCategory->name ?? 'Asrama' }}</div>
                            @endif
                        </div>
                        <div class="room-content">
                            <div class="room-title">{{ $room->block->dorm->name }} Nomor {{ $room->number }} Tipe {{ $room->roomType->name }}</div>
                            <div class="room-type">{{ $room->roomType->name }}</div>
                            <div class="room-location">📍 Komplek {{ $room->block->name }}, Cabang {{ $room->block->dorm->name }}, {{ \Illuminate\Support\Str::words($room->block->dorm->address, 7, '...') }}</div>
                            <div class="room-info">
                                <div class="room-price">
                                    Rp {{ number_format($room->monthly_rate ?? $room->roomType->default_monthly_rate, 0, ',', '.') }}
                                    <small>/bulan</small>
                                </div>
                                <div class="room-capacity">
                                    👥 {{ $room->capacity ?? $room->roomType->default_capacity }} orang
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($totalRooms > 6)
                <div class="view-all-wrapper">
                    <a href="{{ route('rooms.available') }}" class="btn-primary" style="font-size: 18px; padding: 14px 40px;">
                        Lihat Semua Kamar ({{ $totalRooms }})
                    </a>
                </div>
            @endif
        @else
            <div style="text-align: center; padding: 80px 20px;">
                <p style="font-size: 20px; color: #64748b;">Belum ada kamar tersedia saat ini</p>
            </div>
        @endif
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h4>{{ $institution->dormitory_name ?? 'Asrama' }}</h4>
                    <p>{{ $institution->institution_name ?? 'Institusi Pendidikan' }}</p>
                    @if($institution)
                        <p>{{ $institution->address }}</p>
                        <p>{{ $institution->phone }}</p>
                        <p>{{ $institution->email }}</p>
                    @endif
                </div>
                <div class="footer-section">
                    <h4>Tautan Cepat</h4>
                    <a href="{{ route('rooms.available') }}">Kamar Tersedia</a>
                    <a href="{{ route('public.registration.create') }}">Pendaftaran</a>
                    <a href="{{ route('login') }}">Login</a>
                </div>
                <div class="footer-section">
                    <h4>Informasi</h4>
                    <a href="https://ppmelfira.com/" target="_blank">Tentang Kami</a>
                    <a href="/kebijakan" target="_blank">Syarat & Ketentuan</a>
                    <a href="#">Kontak</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ $institution->dormitory_name ?? 'Asrama' }}. All rights reserved.</p>
            </div>
        </div>
    </footer>
</x-public-layout>
