<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $room->roomType->name }} - Kamar {{ $room->number }} | {{ $institution->dormitory_name ?? 'Asrama' }}</title>
    <meta name="description" content="Kamar {{ $room->number }} tipe {{ $room->roomType->name }} di Cabang {{ $room->block->dorm->name }}. Harga Rp {{ number_format($room->monthly_rate ?? $room->roomType->default_monthly_rate, 0, ',', '.') }}/bulan.">
    
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

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--text-secondary);
            flex-wrap: wrap;
        }

        .breadcrumb a {
            color: #10b981;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Image Gallery */
        .gallery-section {
            margin-bottom: 32px;
        }

        .gallery-main {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 8px;
            border-radius: 16px;
            overflow: hidden;
        }

        .gallery-main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 64px;
            font-weight: 800;
        }

        .gallery-main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-side {
            display: grid;
            grid-template-rows: repeat(2, 1fr);
            gap: 8px;
        }

        .gallery-side-image {
            width: 100%;
            height: 196px;
            object-fit: cover;
            background: rgba(16, 185, 129, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #10b981;
            font-size: 24px;
            font-weight: 600;
        }

        .gallery-side-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-more {
            position: relative;
            cursor: pointer;
        }

        .gallery-more-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 600;
        }

        /* Main Content Layout */
        .detail-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
            align-items: start;
        }

        /* Room Info Section */
        .room-detail-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .room-detail-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .room-badge-section {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .room-badge {
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

        .room-badge.type {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .room-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .room-location {
            color: var(--text-secondary);
            font-size: 16px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .room-location svg {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .room-detail-body {
            padding: 24px;
        }

        /* Section Title */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Room Specs */
        .room-specs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: var(--bg-primary);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .spec-icon {
            width: 40px;
            height: 40px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .spec-info {
            flex: 1;
        }

        .spec-label {
            font-size: 12px;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .spec-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Facilities */
        .facilities-section {
            margin-bottom: 24px;
        }

        .facilities-category {
            margin-bottom: 20px;
        }

        .facilities-category-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .facilities-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .facility-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text-primary);
            transition: all 0.2s;
        }

        .facility-item:hover {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.05);
        }

        .facility-icon {
            width: 24px;
            height: 24px;
            object-fit: contain;
        }

        /* Room Rules */
        .rules-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .rule-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            font-size: 14px;
            color: #ef4444;
        }

        .rule-icon {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }

        /* Description */
        .description-content {
            color: var(--text-secondary);
            font-size: 15px;
            line-height: 1.8;
            white-space: pre-line;
        }

        /* Sidebar - Price Card */
        .price-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 24px;
            position: sticky;
            top: 100px;
            box-shadow: var(--shadow-lg);
        }

        .price-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .price-main {
            font-size: 32px;
            font-weight: 800;
            color: #10b981;
            margin-bottom: 4px;
        }

        .price-period {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .price-divider {
            height: 1px;
            background: var(--border-color);
            margin: 20px 0;
        }

        .price-details {
            margin-bottom: 24px;
        }

        .price-detail-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
        }

        .price-detail-item:not(:last-child) {
            border-bottom: 1px solid var(--border-color);
        }

        .price-detail-label {
            color: var(--text-secondary);
        }

        .price-detail-value {
            color: var(--text-primary);
            font-weight: 600;
        }

        .btn-primary-large {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: all 0.2s;
        }

        .btn-primary-large:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        [data-theme="dark"] .btn-primary-large {
            background: #34d399;
            color: #022c22;
        }

        [data-theme="dark"] .btn-primary-large:hover {
            background: #10b981;
            box-shadow: 0 4px 15px rgba(52, 211, 153, 0.4);
        }

        .btn-outline-large {
            width: 100%;
            padding: 14px 24px;
            background: transparent;
            color: #10b981;
            border: 2px solid #10b981;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 12px;
            transition: all 0.2s;
        }

        .btn-outline-large:hover {
            background: #10b981;
            color: white;
        }

        [data-theme="dark"] .btn-outline-large {
            color: #34d399;
            border-color: #34d399;
        }

        [data-theme="dark"] .btn-outline-large:hover {
            background: #34d399;
            color: #022c22;
        }

        .contact-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .contact-label {
            font-size: 12px;
            color: var(--text-tertiary);
            margin-bottom: 8px;
        }

        .contact-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .contact-phone {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Similar Rooms */
        .similar-section {
            margin-top: 48px;
            margin-bottom: 48px;
        }

        .similar-title {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .room-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .room-card:hover {
            transform: translateY(-2px);
            border-color: #10b981;
        }

        .room-card-image {
            width: 100%;
            height: 160px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 700;
        }

        .room-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .room-card-content {
            padding: 16px;
        }

        .room-card-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--text-primary);
        }

        .room-card-location {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }

        .room-card-price {
            font-size: 18px;
            font-weight: 700;
            color: #10b981;
        }

        .room-card-price small {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
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

        /* Responsive */
        @media (max-width: 1024px) {
            .detail-layout {
                grid-template-columns: 1fr;
            }

            .price-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .gallery-main {
                grid-template-columns: 1fr;
            }

            .gallery-main-image {
                height: 280px;
            }

            .gallery-side {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: auto;
            }

            .gallery-side-image {
                height: 140px;
            }

            .room-title {
                font-size: 22px;
            }

            .room-specs {
                grid-template-columns: repeat(2, 1fr);
            }

            .similar-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Image Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-close {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
        }

        .modal-content img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 8px;
        }

        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .modal-nav:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-nav.prev {
            left: 24px;
        }

        .modal-nav.next {
            right: 24px;
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
            <a href="{{ route('rooms.available') }}">Kamar Tersedia</a>
            <span>/</span>
            <span>{{ $room->roomType->name }} - Kamar {{ $room->number }}</span>
        </div>

        <!-- Image Gallery -->
        <div class="gallery-section">
            <div class="gallery-main">
                <div class="gallery-main-image" onclick="openGallery(0)">
                    @if($room->thumbnail)
                        <img src="{{ url('storage/' . $room->thumbnail) }}" alt="Foto Utama Kamar {{ $room->number }}">
                    @else
                        {{ $room->number }}
                    @endif
                </div>
                <div class="gallery-side">
                    @php
                        $images = $room->images ?? [];
                        $displayImages = array_slice($images, 0, 2);
                        $remainingCount = count($images) - 2;
                    @endphp
                    @if(count($displayImages) > 0)
                        <div class="gallery-side-image" onclick="openGallery(1)">
                            <img src="{{ url('storage/' . $displayImages[0]) }}" alt="Foto Kamar">
                        </div>
                    @else
                        <div class="gallery-side-image">
                            <span>📷</span>
                        </div>
                    @endif
                    @if(count($displayImages) > 1)
                        <div class="gallery-side-image gallery-more" onclick="openGallery(2)">
                            <img src="{{ url('storage/' . $displayImages[1]) }}" alt="Foto Kamar">
                            @if($remainingCount > 0)
                                <div class="gallery-more-overlay">
                                    +{{ $remainingCount }} Foto
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="gallery-side-image">
                            <span>📷</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Layout -->
        <div class="detail-layout">
            <!-- Left Content -->
            <div class="detail-main">
                <!-- Room Info Card -->
                <div class="room-detail-card">
                    <div class="room-detail-header">
                        <div class="room-badge-section">
                            <span class="room-badge">✅ Tersedia</span>
                            <span class="room-badge type">{{ $room->roomType->name }}</span>
                            @if($room->residentCategory)
                                <span class="room-badge type">{{ $room->residentCategory->name }}</span>
                            @endif
                        </div>
                        <h1 class="room-title">{{ $room->block->dorm->name }} Nomor {{ $room->number }} Tipe {{ $room->roomType->name }}</h1>
                        <div class="room-location">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>Komplek {{ $room->block->name }}, Cabang {{ $room->block->dorm->name }}, {{ $room->block->dorm->address }}</span>
                        </div>
                    </div>
                    <div class="room-detail-body">
                        <!-- Room Specs -->
                        <h3 class="section-title">📐 Spesifikasi Kamar</h3>
                        <div class="room-specs">
                            <div class="spec-item">
                                <div class="spec-icon">👥</div>
                                <div class="spec-info">
                                    <div class="spec-label">Kapasitas</div>
                                    <div class="spec-value">{{ $room->capacity ?? $room->roomType->default_capacity }} Orang</div>
                                </div>
                            </div>
                            @if($room->width && $room->length)
                                <div class="spec-item">
                                    <div class="spec-icon">📏</div>
                                    <div class="spec-info">
                                        <div class="spec-label">Ukuran</div>
                                        <div class="spec-value">{{ $room->width }} x {{ $room->length }} m</div>
                                    </div>
                                </div>
                            @endif
                            <div class="spec-item">
                                <div class="spec-icon">🏠</div>
                                <div class="spec-info">
                                    <div class="spec-label">Tipe</div>
                                    <div class="spec-value">{{ $room->roomType->name }}</div>
                                </div>
                            </div>
                            <div class="spec-item">
                                <div class="spec-icon">🏢</div>
                                <div class="spec-info">
                                    <div class="spec-label">Komplek</div>
                                    <div class="spec-value">{{ $room->block->name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Facilities Card -->
                @if($room->facilities->count() > 0)
                <div class="room-detail-card">
                    <div class="room-detail-body">
                        <h3 class="section-title">🏠 Fasilitas Kamar</h3>
                        <div class="facilities-section">
                            @if($room->facilitiesKamar->count() > 0)
                                <div class="facilities-category">
                                    <div class="facilities-category-title">🛏️ Fasilitas Kamar</div>
                                    <div class="facilities-grid">
                                        @foreach($room->facilitiesKamar as $facility)
                                            <div class="facility-item">
                                                @if($facility->icon)
                                                    <x-dynamic-component :component="$facility->icon" class="facility-icon" style="width: 20px; height: 20px; color: #10b981;" />
                                                @endif
                                                {{ $facility->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($room->facilitiesKamarMandi->count() > 0)
                                <div class="facilities-category">
                                    <div class="facilities-category-title">🚿 Fasilitas Kamar Mandi</div>
                                    <div class="facilities-grid">
                                        @foreach($room->facilitiesKamarMandi as $facility)
                                            <div class="facility-item">
                                                @if($facility->icon)
                                                    <x-dynamic-component :component="$facility->icon" class="facility-icon" style="width: 20px; height: 20px; color: #10b981;" />
                                                @endif
                                                {{ $facility->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($room->facilitiesUmum->count() > 0)
                                <div class="facilities-category">
                                    <div class="facilities-category-title">🏢 Fasilitas Umum</div>
                                    <div class="facilities-grid">
                                        @foreach($room->facilitiesUmum as $facility)
                                            <div class="facility-item">
                                                @if($facility->icon)
                                                    <x-dynamic-component :component="$facility->icon" class="facility-icon" style="width: 20px; height: 20px; color: #10b981;" />
                                                @endif
                                                {{ $facility->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($room->facilitiesParkir->count() > 0)
                                <div class="facilities-category">
                                    <div class="facilities-category-title">🚗 Fasilitas Parkir</div>
                                    <div class="facilities-grid">
                                        @foreach($room->facilitiesParkir as $facility)
                                            <div class="facility-item">
                                                @if($facility->icon)
                                                    <x-dynamic-component :component="$facility->icon" class="facility-icon" style="width: 20px; height: 20px; color: #10b981;" />
                                                @endif
                                                {{ $facility->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Room Rules Card -->
                @if($room->roomRules->count() > 0)
                <div class="room-detail-card">
                    <div class="room-detail-body">
                        <h3 class="section-title">📋 Peraturan Kamar</h3>
                        <div class="rules-list">
                            @foreach($room->roomRules as $rule)
                                <div class="rule-item">
                                    @if($rule->icon)
                                        <x-dynamic-component :component="$rule->icon" class="rule-icon" style="width: 18px; height: 18px;" />
                                    @endif
                                    {{ $rule->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Description Card -->
                @if($room->roomType->description)
                <div class="room-detail-card">
                    <div class="room-detail-body">
                        <h3 class="section-title">📝 Deskripsi</h3>
                        <div class="description-content">{{ $room->roomType->description }}</div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar - Price Card -->
            <div class="detail-sidebar">
                <div class="price-card">
                    <div class="price-label">Harga Sewa</div>
                    <div class="price-main">Rp {{ number_format($room->monthly_rate ?? $room->roomType->default_monthly_rate, 0, ',', '.') }}</div>
                    <div class="price-period">per bulan</div>

                    <div class="price-divider"></div>

                    <div class="price-details">
                        <div class="price-detail-item">
                            <span class="price-detail-label">Tipe Kamar</span>
                            <span class="price-detail-value">{{ $room->roomType->name }}</span>
                        </div>
                        <div class="price-detail-item">
                            <span class="price-detail-label">Kapasitas</span>
                            <span class="price-detail-value">{{ $room->capacity ?? $room->roomType->default_capacity }} Orang</span>
                        </div>
                        <div class="price-detail-item">
                            <span class="price-detail-label">Cabang</span>
                            <span class="price-detail-value">{{ $room->block->dorm->name }}</span>
                        </div>
                        <div class="price-detail-item">
                            <span class="price-detail-label">Komplek</span>
                            <span class="price-detail-value">{{ $room->block->name }}</span>
                        </div>
                    </div>

                    <a href="{{ route('public.registration.create') }}" class="btn-primary-large">
                        Ajukan Pemesanan
                    </a>

                    @if($room->contact_person_name || $room->contact_person_number)
                        <div class="contact-info">
                            <div class="contact-label">Hubungi Pengelola</div>
                            @if($room->contact_person_name)
                                <div class="contact-name">{{ $room->contact_person_name }}</div>
                            @endif
                            @if($room->contact_person_number)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $room->contact_person_number) }}" target="_blank" class="btn-outline-large">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                    WhatsApp
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Similar Rooms -->
        @if($similarRooms->count() > 0)
        <div class="similar-section">
            <h2 class="similar-title">Kamar Serupa</h2>
            <div class="similar-grid">
                @foreach($similarRooms as $similar)
                    <a href="{{ route('rooms.show', $similar->id) }}" class="room-card">
                        <div class="room-card-image">
                            @if($similar->thumbnail)
                                <img src="{{ url('storage/' . $similar->thumbnail) }}" alt="Kamar {{ $similar->number }}">
                            @else
                                {{ $similar->number }}
                            @endif
                        </div>
                        <div class="room-card-content">
                            <div class="room-card-title">{{ $similar->block->dorm->name }} Nomor {{ $similar->number }}</div>
                            <div class="room-card-location">📍 {{ $similar->block->name }}, {{ $similar->roomType->name }}</div>
                            <div class="room-card-price">
                                Rp {{ number_format($similar->monthly_rate ?? $similar->roomType->default_monthly_rate, 0, ',', '.') }}
                                <small>/bulan</small>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
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
                <p>&copy; {{ date('Y') }} {{ $institution->dormitory_name ?? 'Asrama' }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Image Modal -->
    <div class="modal-overlay" id="imageModal">
        <button class="modal-close" onclick="closeGallery()">✕</button>
        <button class="modal-nav prev" onclick="prevImage()">‹</button>
        <div class="modal-content">
            <img src="" alt="Gallery Image" id="modalImage">
        </div>
        <button class="modal-nav next" onclick="nextImage()">›</button>
    </div>

    <script>
        // Gallery functionality
        const allImages = [
            @if($room->thumbnail)
                '{{ url('storage/' . $room->thumbnail) }}',
            @endif
            @if($room->images)
                @foreach($room->images as $image)
                    '{{ url('storage/' . $image) }}',
                @endforeach
            @endif
        ];
        
        let currentImageIndex = 0;

        function openGallery(index) {
            if (allImages.length === 0) return;
            currentImageIndex = index;
            document.getElementById('modalImage').src = allImages[currentImageIndex];
            document.getElementById('imageModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeGallery() {
            document.getElementById('imageModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function prevImage() {
            if (allImages.length === 0) return;
            currentImageIndex = (currentImageIndex - 1 + allImages.length) % allImages.length;
            document.getElementById('modalImage').src = allImages[currentImageIndex];
        }

        function nextImage() {
            if (allImages.length === 0) return;
            currentImageIndex = (currentImageIndex + 1) % allImages.length;
            document.getElementById('modalImage').src = allImages[currentImageIndex];
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeGallery();
            if (e.key === 'ArrowLeft') prevImage();
            if (e.key === 'ArrowRight') nextImage();
        });

        // Close modal on overlay click
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) closeGallery();
        });
    </script>
</body>
</html>
