<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - {{ $institution->dormitory_name ?? 'Asrama' }}</title>
    <meta name="description" content="Informasi lengkap tentang {{ $institution->dormitory_name ?? 'Asrama' }} dan layanan yang kami sediakan.">
    
    <!-- Favicon -->
    @if($institution && $institution->logo_url)
        <link rel="icon" type="image/png" href="{{ $institution->logo_url }}">
        <link rel="apple-touch-icon" href="{{ $institution->logo_url }}">
    @else
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
    @endif
    
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
            padding: 40px 24px;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 48px;
            padding: 60px 24px 40px;
        }

        .page-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            font-size: 20px;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Content Card */
        .content-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 48px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            max-width: 900px;
            margin: 0 auto;
        }

        /* Rich Content Styles */
        .rich-content {
            color: var(--text-primary);
            font-size: 18px;
            line-height: 1.8;
        }

        .rich-content h1 {
            font-size: 36px;
            font-weight: 800;
            margin-top: 40px;
            margin-bottom: 20px;
            color: var(--text-primary);
        }

        .rich-content h1:first-child {
            margin-top: 0;
        }

        .rich-content h2 {
            font-size: 30px;
            font-weight: 700;
            margin-top: 32px;
            margin-bottom: 16px;
            color: var(--text-primary);
        }

        .rich-content h3 {
            font-size: 24px;
            font-weight: 600;
            margin-top: 28px;
            margin-bottom: 14px;
            color: var(--text-primary);
        }

        .rich-content p {
            margin-bottom: 16px;
            color: var(--text-primary);
        }

        .rich-content ul,
        .rich-content ol {
            margin-bottom: 16px;
            padding-left: 32px;
            color: var(--text-primary);
        }

        .rich-content li {
            margin-bottom: 8px;
        }

        .rich-content a {
            color: #10b981;
            text-decoration: underline;
            transition: color 0.2s;
        }

        .rich-content a:hover {
            color: #059669;
        }

        .rich-content blockquote {
            border-left: 4px solid #10b981;
            padding-left: 24px;
            margin: 24px 0;
            font-style: italic;
            color: var(--text-secondary);
        }

        .rich-content code {
            background: var(--bg-primary);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 16px;
            font-family: 'Courier New', monospace;
            color: var(--text-primary);
        }

        .rich-content pre {
            background: var(--bg-primary);
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            margin-bottom: 16px;
        }

        .rich-content pre code {
            background: none;
            padding: 0;
        }

        .rich-content strong {
            font-weight: 700;
            color: var(--text-primary);
        }

        .rich-content em {
            font-style: italic;
        }

        .rich-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 24px 0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 24px;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .empty-state p {
            font-size: 16px;
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
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            transition: color 0.2s;
        }

        .footer-section a:hover {
            color: #10b981;
        }

        .footer-bottom {
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 32px;
            }

            .page-subtitle {
                font-size: 16px;
            }

            .content-card {
                padding: 24px;
            }

            .rich-content {
                font-size: 16px;
            }

            .rich-content h1 {
                font-size: 28px;
            }

            .rich-content h2 {
                font-size: 24px;
            }

            .rich-content h3 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar (Using Component for consistency) -->
    <x-public-navbar :institution="$institution" />

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Tentang Kami</h1>
        <p class="page-subtitle">Mengenal lebih dekat {{ $institution->dormitory_name ?? 'asrama kami' }}</p>
    </div>

    <!-- Main Content -->
    <div class="container">
        @if($institution && $institution->about_content)
            <div class="content-card">
                <div class="rich-content">
                    {!! $institution->about_content !!}
                </div>
            </div>
        @else
            <div class="content-card">
                <div class="empty-state">
                    <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3>Konten Belum Tersedia</h3>
                    <p>Informasi tentang asrama sedang dalam proses penyusunan.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h4>{{ $institution->institution_name ?? 'Asrama' }}</h4>
                    <p>{{ $institution->dormitory_name ?? 'Asrama Modern' }}</p>
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
                    <a href="{{ route('about') }}">Tentang Kami</a>
                    <a href="#">Syarat & Ketentuan</a>
                    <a href="#">Kontak</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ $institution->institution_name ?? 'Asrama' }}. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
