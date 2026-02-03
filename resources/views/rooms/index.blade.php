<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kamar Tersedia - {{ $institution->dormitory_name ?? 'Asrama' }}</title>
    
    <!-- Performance Optimization -->
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    
    <!-- Fonts with font-display swap for performance -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    
    <script>
        // Check for saved theme preference or default to light mode immediately to prevent flash
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
    </script>
    <style>
        :root {
            /* Light Mode - Matching Tailwind Gray-50/White */
            --bg-primary: #f9fafb; /* gray-50 */
            --bg-secondary: #ffffff;
            --text-primary: #111827; /* gray-900 */
            --text-secondary: #6b7280; /* gray-500 */
            --text-tertiary: #9ca3af; /* gray-400 */
            --border-color: #e5e7eb; /* gray-200 */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --footer-bg: #111827; /* gray-900 */
        }

        [data-theme="dark"] {
            /* Dark Mode - Matching Tailwind Gray-950/Gray-900 */
            --bg-primary: #030712; /* gray-950 */
            --bg-secondary: #111827; /* gray-900 */
            --text-primary: #f3f4f6; /* gray-100 */
            --text-secondary: #9ca3af; /* gray-400 */
            --text-tertiary: #6b7280; /* gray-500 */
            --border-color: #1f2937; /* gray-800 */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            --footer-bg: #030712; /* gray-950 */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
        }

        /* Navbar */
        .navbar {
            background: var(--bg-secondary);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 700;
            color: #10b981;
            text-decoration: none;
        }

        .logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:not(.btn-primary):not(.btn-secondary):hover {
            color: #10b981;
        }

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dark-mode-toggle:hover {
            transform: scale(1.1);
            border-color: #10b981;
        }

        .dark-mode-toggle svg {
            width: 20px;
            height: 20px;
            fill: var(--text-primary);
            transition: transform 0.3s ease;
            display: block;
        }

        .dark-mode-toggle:hover svg {
            transform: rotate(20deg);
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #059669; /* Emerald-600 */
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        /* Dark Mode Button Consistency */
        [data-theme="dark"] .btn-primary {
            background: #34d399; /* Emerald-400 */
            color: #022c22;
            font-weight: 700;
        }

        [data-theme="dark"] .btn-primary:hover {
            background: #10b981;
            box-shadow: 0 4px 15px rgba(52, 211, 153, 0.4);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: #1f2937; /* gray-800 */
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: 2px solid #10b981;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: #10b981;
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        /* Fix for Dark Mode - Make Secondary Button BRIGHTER (Outline style) */
        [data-theme="dark"] .btn-secondary {
            color: #34d399; /* Emerald-400 (Brighter Green) */
            border-color: #34d399;
            background: transparent; /* Ensure transparent bg to show border */
        }

        [data-theme="dark"] .btn-secondary:hover {
            background: #34d399;
            color: #022c22; /* Dark text on hover */
            box-shadow: 0 4px 15px rgba(52, 211, 153, 0.3);
        }

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .page-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .breadcrumb a {
            color: #10b981;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Rooms Grid */
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .room-card {
            background: var(--bg-secondary);
            border-radius: 16px; /* sm:rounded-2xl */
            overflow: hidden;
            border: 1px solid var(--border-color); /* Added border like dashboard */
            box-shadow: var(--shadow-lg); /* shadow-lg */
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .room-card:hover {
            transform: translateY(-2px);
            border-color: #10b981; /* hover:border-green-500 approximation */
        }

        .room-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 700;
            position: relative;
        }

        .room-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255,255,255,0.95);
            color: #10b981;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .room-content {
            padding: 20px;
        }

        .room-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .room-type {
            font-size: 14px;
            color: #10b981;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .room-location {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }

        .room-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }

        .room-price {
            font-size: 18px;
            font-weight: 700;
            color: #10b981;
        }

        .room-price small {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .room-capacity {
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* Pagination */
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }

        .pagination {
            display: flex;
            gap: 8px;
            list-style: none;
            align-items: center;
        }

        .pagination li {
            display: inline-block;
        }

        .pagination a,
        .pagination span {
            display: block;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .pagination a {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
        }

        .pagination a:hover {
            border-color: #10b981;
            color: #10b981;
        }

        .pagination .active span {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: 2px solid #10b981;
        }

        .pagination .disabled span {
            color: #cbd5e1;
            border-color: var(--border-color);
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .empty-state-text {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        /* Filter Section */
        .filter-section {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
        }

        .search-wrapper {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: var(--text-tertiary);
        }

        .search-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            font-family: inherit;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .search-input::placeholder {
            color: var(--text-tertiary);
        }

        .filter-dropdowns {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 14px 40px 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            background: var(--bg-primary);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 18px;
            min-width: 160px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 12px;
        }

        .btn-filter {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-filter:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-reset {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 24px;
            background: var(--bg-primary);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-reset:hover {
            border-color: #ef4444;
            color: #ef4444;
        }

        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
            align-items: center;
        }

        .active-filters-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        [data-theme="dark"] .btn-filter {
            background: #34d399;
            color: #022c22;
        }

        [data-theme="dark"] .btn-filter:hover {
            background: #10b981;
            box-shadow: 0 4px 15px rgba(52, 211, 153, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .page-title {
                font-size: 28px;
            }

            .rooms-grid {
                grid-template-columns: 1fr;
            }

            .pagination a,
            .pagination span {
                padding: 8px 12px;
                font-size: 14px;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-wrapper {
                min-width: 100%;
            }

            .filter-dropdowns {
                flex-direction: column;
                width: 100%;
            }

            .filter-select {
                width: 100%;
                min-width: 100%;
            }

            .filter-buttons {
                width: 100%;
            }

            .btn-filter,
            .btn-reset {
                flex: 1;
                justify-content: center;
            }
        }

        /* Footer */
        .footer {
            background: var(--footer-bg);
            color: white;
            padding: 48px 24px 24px;
            margin-top: 80px;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .footer-section p,
        .footer-section a {
            color: #94a3b8;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            transition: color 0.2s;
        }

        .footer-section a:hover {
            color: #10b981;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #334155;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <x-public-navbar :institution="$institution" />

    <!-- Main Content -->
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Beranda</a>
            <span>/</span>
            <span>Kamar Tersedia</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Kamar Tersedia</h1>
            <p class="page-subtitle">Temukan kamar impian Anda dari {{ $rooms->total() }} kamar yang tersedia</p>
        </div>

        <!-- Search and Filter Section -->
        <div class="filter-section">
            <form method="GET" action="{{ route('rooms.available') }}" class="filter-form">
                <!-- Search Input -->
                <div class="search-wrapper">
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="search" class="search-input" placeholder="Cari kamar, nomor, cabang, tipe..." value="{{ request('search') }}">
                </div>

                <!-- Filter Dropdowns -->
                <div class="filter-dropdowns">
                    <select name="dorm_id" class="filter-select">
                        <option value="">Semua Cabang</option>
                        @foreach($dorms as $dorm)
                            <option value="{{ $dorm->id }}" {{ request('dorm_id') == $dorm->id ? 'selected' : '' }}>
                                {{ $dorm->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="room_type_id" class="filter-select">
                        <option value="">Semua Tipe Kamar</option>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type->id }}" {{ request('room_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Buttons -->
                <div class="filter-buttons">
                    <button type="submit" class="btn-filter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        Cari
                    </button>
                    @if(request()->hasAny(['search', 'dorm_id', 'room_type_id']))
                        <a href="{{ route('rooms.available') }}" class="btn-reset">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <!-- Active Filters Display -->
            @if(request()->hasAny(['search', 'dorm_id', 'room_type_id']))
                <div class="active-filters">
                    <span class="active-filters-label">Filter aktif:</span>
                    @if(request('search'))
                        <span class="filter-tag">
                            🔍 "{{ request('search') }}"
                        </span>
                    @endif
                    @if(request('dorm_id'))
                        @php $selectedDorm = $dorms->firstWhere('id', request('dorm_id')); @endphp
                        @if($selectedDorm)
                            <span class="filter-tag">
                                🏢 {{ $selectedDorm->name }}
                            </span>
                        @endif
                    @endif
                    @if(request('room_type_id'))
                        @php $selectedType = $roomTypes->firstWhere('id', request('room_type_id')); @endphp
                        @if($selectedType)
                            <span class="filter-tag">
                                🏠 {{ $selectedType->name }}
                            </span>
                        @endif
                    @endif
                </div>
            @endif
        </div>

        <!-- Rooms Grid -->
        @if($rooms->count() > 0)
            <div class="rooms-grid">
                @foreach($rooms as $room)
                    <a href="{{ route('rooms.show', $room->id) }}" class="room-card">
                        <div class="room-image" @if($room->thumbnail) style="background-image: url('{{ url('storage/' . $room->thumbnail) }}'); background-size: cover; background-position: center;" @endif>
                            @if(!$room->thumbnail)
                                {{ $room->number }}
                            @endif
                            @if($room->is_active)
                                <div class="room-badge">Tersedia</div>
                            @endif
                        </div>
                        <div class="room-content">
                            <div class="room-title">{{ $room->block->dorm->name }} Nomor {{ $room->number }} Tipe {{ $room->roomType->name }}</div>
                            <div class="room-type">{{ $room->roomType->name }}</div>
                            <div class="room-location">📍 Komplek {{ $room->block->name }}, Cabang {{ $room->block->dorm->name }}, {{ $room->block->dorm->address }}</div>
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
                    @if(request()->hasAny(['search', 'dorm_id', 'room_type_id']))
                        Coba ubah filter pencarian Anda atau reset filter untuk melihat semua kamar.
                    @else
                        Saat ini belum ada kamar yang tersedia untuk ditempati.
                    @endif
                </p>
                @if(request()->hasAny(['search', 'dorm_id', 'room_type_id']))
                    <a href="{{ route('rooms.available') }}" class="btn-primary">Reset Filter</a>
                @else
                    <a href="{{ route('home') }}" class="btn-primary">Kembali ke Beranda</a>
                @endif
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
                    <a href="{{ route('home') }}">Beranda</a>
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
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ $institution->dormitory_name ?? 'Asrama' }}. All rights reserved.</p>
            </div>
            </div>
        </div>
    </footer>

</body>
</html>
