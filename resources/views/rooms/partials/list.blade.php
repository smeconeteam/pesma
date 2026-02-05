@if($rooms->count() > 0)
    <div class="rooms-grid">
        @foreach($rooms as $room)
            <a href="{{ route('rooms.show', $room->id) }}" class="room-card">
                <div class="room-image" @if($room->thumbnail) style="background-image: url('{{ url('storage/' . $room->thumbnail) }}'); background-size: cover; background-position: center;" @endif>
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

    <!-- Pagination -->
    <div class="pagination-wrapper">
        {{ $rooms->links('pagination::default') }}
    </div>
@else
    <div class="empty-state">
        <div class="empty-state-icon">🏠</div>
        <h2 class="empty-state-title">Tidak Ada Kamar Ditemukan</h2>
        <p class="empty-state-text">
            @if(request()->hasAny(['search', 'dorm_id', 'room_type_id', 'resident_category_id']))
                Coba ubah filter pencarian Anda atau reset filter untuk melihat semua kamar.
            @else
                Saat ini belum ada kamar yang tersedia untuk ditempati.
            @endif
        </p>
        @if(request()->hasAny(['search', 'dorm_id', 'room_type_id', 'resident_category_id']))
            <a href="{{ route('rooms.available') }}" class="btn-primary">Reset Filter</a>
        @else
            <a href="{{ route('home') }}" class="btn-primary">Kembali ke Beranda</a>
        @endif
    </div>
@endif
