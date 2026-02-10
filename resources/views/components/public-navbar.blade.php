<nav class="navbar">
    <div class="navbar-container">
        <a href="{{ route('home') }}" class="logo">
            @if($institution && $institution->logo_url)
                <img src="{{ $institution->logo_url }}" alt="{{ $institution->dormitory_name }}" class="logo-img">
            @endif
            <span>{{ $institution->dormitory_name ?? 'Asrama' }}</span>
        </a>
        <div class="nav-links">
            <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a>
            <a href="{{ route('rooms.available') }}" class="nav-link {{ request()->routeIs('rooms.available') || request()->routeIs('rooms.show') ? 'active' : '' }}">Kamar Tersedia</a>
            <a href="{{ route('about') }}" class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}">Tentang Kami</a>
            @auth
                @php
                    $isAdmin = auth()->user()->hasAnyRole(['super_admin', 'main_admin', 'branch_admin', 'block_admin']);
                    $dashboardUrl = $isAdmin ? '/admin' : route('dashboard');
                @endphp
                <a href="{{ $dashboardUrl }}" class="btn-primary">Dashboard</a>
            @else
                <a href="{{ route('public.registration.create') }}" class="btn-secondary">Daftar Sekarang</a>
                <a href="{{ route('login') }}" class="btn-primary">Masuk</a>
            @endauth
            <button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 18C8.68629 18 6 15.3137 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 15.3137 15.3137 18 12 18ZM12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16ZM11 1H13V4H11V1ZM11 20H13V23H11V20ZM3.51472 4.92893L4.92893 3.51472L7.05025 5.63604L5.63604 7.05025L3.51472 4.92893ZM16.9497 18.364L18.364 16.9497L20.4853 19.0711L19.0711 20.4853L16.9497 18.364ZM19.0711 3.51472L20.4853 4.92893L18.364 7.05025L16.9497 5.63604L19.0711 3.51472ZM5.63604 16.9497L7.05025 18.364L4.92893 20.4853L3.51472 19.0711L5.63604 16.9497ZM23 11V13H20V11H23ZM4 11V13H1V11H4Z"/>
                </svg>
                <svg class="moon-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M10 7C10 10.866 13.134 14 17 14C18.9584 14 20.729 13.1957 21.9995 11.8995C22 11.933 22 11.9665 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C12.0335 2 12.067 2 12.1005 2.00049C10.8043 3.27098 10 5.04157 10 7ZM4 12C4 16.4183 7.58172 20 12 20C15.0583 20 17.7158 18.2839 19.062 15.7621C18.3945 15.9187 17.7035 16 17 16C12.0294 16 8 11.9706 8 7C8 6.29648 8.08133 5.60547 8.2379 4.938C5.71611 6.28423 4 8.9417 4 12Z"/>
                </svg>
            </button>
        </div>
    </div>
</nav>

<style>
    /* Navbar styles */
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

    .nav-link.active {
        color: #10b981 !important;
        font-weight: 600;
        position: relative;
    }

    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 2px;
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

    /* Buttons (Shared Style) */
    .btn-primary {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white !important; /* Force white text */
        padding: 10px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
        border: none;
        cursor: pointer;
        display: inline-block;
    }

    .btn-primary:hover {
        background: #059669; /* Emerald-600 */
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    [data-theme="dark"] .btn-primary {
        background: #34d399;
        color: #022c22 !important;
        font-weight: 700;
    }
    
    [data-theme="dark"] .btn-primary:hover {
        background: #10b981;
        box-shadow: 0 4px 15px rgba(52, 211, 153, 0.4);
    }

    .btn-secondary {
        background: var(--bg-secondary);
        color: #1f2937; /* gray-800 - Black text in light mode */
        padding: 10px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        border: 2px solid #10b981;
        transition: all 0.2s;
        display: inline-block;
    }

    .btn-secondary:hover {
        background: #10b981;
        color: white;
    }

    /* Dark Mode Secondary Button */
    [data-theme="dark"] .btn-secondary {
        color: #34d399;
        border-color: #34d399;
        background: transparent;
    }

    [data-theme="dark"] .btn-secondary:hover {
        background: #34d399;
        color: #022c22;
        box-shadow: 0 4px 15px rgba(52, 211, 153, 0.3);
    }

    .btn-outline {
        background: transparent;
        color: #10b981;
        padding: 12px 32px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        border: 2px solid #10b981;
        transition: all 0.2s;
        display: inline-block;
        font-size: 16px;
    }

    .btn-outline:hover {
        background: #10b981;
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    [data-theme="dark"] .btn-outline {
        color: #34d399; 
        border-color: #34d399;
    }

    [data-theme="dark"] .btn-outline:hover {
        background: #34d399;
        color: #022c22;
        box-shadow: 0 4px 15px rgba(52, 211, 153, 0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-links {
            display: none;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dark Mode Toggle Logic
        const darkModeToggle = document.getElementById('darkModeToggle');
        const sunIcon = document.querySelector('.sun-icon');
        const moonIcon = document.querySelector('.moon-icon');
        const html = document.documentElement;

        // Initialize icons based on current theme
        const currentTheme = html.getAttribute('data-theme');
        if (currentTheme === 'dark') {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
        } else {
            sunIcon.style.display = 'block';
            moonIcon.style.display = 'none';
        }

        // Toggle dark mode
        // Note: We use a check to avoid duplicate listeners if component is re-rendered or multiple instances
        if(darkModeToggle) {
            darkModeToggle.onclick = function() { // use onclick property to replace any existing handler
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
            };
        }
    });
</script>
