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

        .nav-links a:hover {
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
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
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
            color: #10b981;
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
    <nav class="navbar">
        <div class="navbar-container">
            <a href="{{ route('home') }}" class="logo">
                @if($institution && $institution->logo_url)
                    <img src="{{ $institution->logo_url }}" alt="{{ $institution->dormitory_name }}" class="logo-img">
                @endif
                <span>{{ $institution->dormitory_name ?? 'Asrama' }}</span>
            </a>
            <div class="nav-links">
                <a href="{{ route('home') }}">Beranda</a>
                <a href="{{ route('rooms.available') }}">Kamar Tersedia</a>
                <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
                    <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 18C8.68629 18 6 15.3137 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 15.3137 15.3137 18 12 18ZM12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16ZM11 1H13V4H11V1ZM11 20H13V23H11V20ZM3.51472 4.92893L4.92893 3.51472L7.05025 5.63604L5.63604 7.05025L3.51472 4.92893ZM16.9497 18.364L18.364 16.9497L20.4853 19.0711L19.0711 20.4853L16.9497 18.364ZM19.0711 3.51472L20.4853 4.92893L18.364 7.05025L16.9497 5.63604L19.0711 3.51472ZM5.63604 16.9497L7.05025 18.364L4.92893 20.4853L3.51472 19.0711L5.63604 16.9497ZM23 11V13H20V11H23ZM4 11V13H1V11H4Z"/>
                    </svg>
                    <svg class="moon-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M10 7C10 10.866 13.134 14 17 14C18.9584 14 20.729 13.1957 21.9995 11.8995C22 11.933 22 11.9665 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C12.0335 2 12.067 2 12.1005 2.00049C10.8043 3.27098 10 5.04157 10 7ZM4 12C4 16.4183 7.58172 20 12 20C15.0583 20 17.7158 18.2839 19.062 15.7621C18.3945 15.9187 17.7035 16 17 16C12.0294 16 8 11.9706 8 7C8 6.29648 8.08133 5.60547 8.2379 4.938C5.71611 6.28423 4 8.9417 4 12Z"/>
                    </svg>
                </button>
                <a href="{{ route('public.registration.create') }}" class="btn-secondary">Daftar Sekarang</a>
                <a href="{{ route('login') }}" class="btn-primary">Masuk</a>
            </div>
        </div>
    </nav>

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

        <!-- Rooms Grid -->
        @if($rooms->count() > 0)
            <div class="rooms-grid">
                @foreach($rooms as $room)
                    <a href="{{ route('public.registration.create') }}" class="room-card">
                        <div class="room-image" @if($room->thumbnail) style="background-image: url('{{ url('storage/' . $room->thumbnail) }}'); background-size: cover; background-position: center;" @endif>
                            @if(!$room->thumbnail)
                                {{ $room->number }}
                            @endif
                            @if($room->is_active)
                                <div class="room-badge">Tersedia</div>
                            @endif
                        </div>
                        <div class="room-content">
                            <div class="room-title">Cabang {{ $room->block->dorm->name }} Nomor {{ $room->number }} Tipe {{ $room->roomType->name }}</div>
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
                <h2 class="empty-state-title">Belum Ada Kamar Tersedia</h2>
                <p class="empty-state-text">Saat ini belum ada kamar yang tersedia untuk ditempati</p>
                <a href="{{ route('home') }}" class="btn-primary">Kembali ke Beranda</a>
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

    <script>
        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const sunIcon = document.querySelector('.sun-icon');
        const moonIcon = document.querySelector('.moon-icon');
        const html = document.documentElement;

        // Initialize icons based on current theme
        const currentTheme = document.documentElement.getAttribute('data-theme');
        if (currentTheme === 'dark') {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
        } else {
            sunIcon.style.display = 'block';
            moonIcon.style.display = 'none';
        }

        // Toggle dark mode
        darkModeToggle.addEventListener('click', function() {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Toggle icons
            if (newTheme === 'dark') {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            } else {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            }
        });
    </script>
</body>
</html>
