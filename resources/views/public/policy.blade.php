<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $policy->title ?? 'Kebijakan' }} - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 50%, #ecfdf5 100%);
            min-height: 100vh;
            color: #374151;
            line-height: 1.7;
        }

        /* Header */
        .policy-header {
            background: linear-gradient(135deg, #166534 0%, #15803d 50%, #22c55e 100%);
            padding: 2rem 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .policy-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .header-container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 50px;
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(-4px);
        }

        .back-button svg {
            width: 18px;
            height: 18px;
        }

        .header-content {
            margin-top: 2rem;
        }

        .policy-title {
            color: white;
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .policy-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .meta-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50px;
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .meta-item svg {
            width: 16px;
            height: 16px;
            opacity: 0.9;
        }

        /* Main Content */
        .policy-main {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }

        .policy-card {
            background: white;
            border-radius: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 
                        0 10px 15px -3px rgba(0, 0, 0, 0.05),
                        0 20px 25px -5px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin-top: -3rem;
            position: relative;
            z-index: 10;
        }

        .card-header {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .card-header-icon svg {
            width: 22px;
            height: 22px;
        }

        .card-header-text h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #166534;
            margin-bottom: 0.125rem;
        }

        .card-header-text p {
            font-size: 0.8125rem;
            color: #16a34a;
        }

        .policy-content {
            padding: 2rem 1.5rem;
        }

        /* Typography for policy content */
        .policy-content h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #111827;
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #dcfce7;
        }

        .policy-content h1:first-child {
            margin-top: 0;
        }

        .policy-content h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #166534;
            margin: 1.75rem 0 0.875rem;
        }

        .policy-content h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #374151;
            margin: 1.5rem 0 0.75rem;
        }

        .policy-content p {
            margin-bottom: 1rem;
            color: #4b5563;
        }

        .policy-content ul, 
        .policy-content ol {
            margin: 1rem 0 1.5rem 1.5rem;
            color: #4b5563;
        }

        .policy-content li {
            margin-bottom: 0.5rem;
            padding-left: 0.25rem;
        }

        .policy-content li::marker {
            color: #22c55e;
        }

        .policy-content a {
            color: #16a34a;
            text-decoration: underline;
            text-underline-offset: 2px;
            transition: color 0.2s;
        }

        .policy-content a:hover {
            color: #166534;
        }

        .policy-content blockquote {
            margin: 1.5rem 0;
            padding: 1rem 1.25rem;
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
            border-radius: 0 0.75rem 0.75rem 0;
            color: #166534;
            font-style: italic;
        }

        .policy-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .policy-content th,
        .policy-content td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .policy-content th {
            background: #f9fafb;
            font-weight: 700;
            color: #111827;
        }

        .policy-content tr:last-child td {
            border-bottom: none;
        }

        .policy-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.75rem;
            margin: 1rem 0;
        }

        .policy-content code {
            background: #f0fdf4;
            color: #166534;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.875em;
        }

        .policy-content pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 1.25rem;
            border-radius: 0.75rem;
            overflow-x: auto;
            margin: 1.5rem 0;
        }

        .policy-content pre code {
            background: none;
            color: inherit;
            padding: 0;
        }

        .policy-content hr {
            border: none;
            height: 2px;
            background: linear-gradient(90deg, transparent, #dcfce7, transparent);
            margin: 2rem 0;
        }

        /* Footer */
        .policy-footer {
            padding: 1.5rem;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .footer-dot {
            width: 8px;
            height: 8px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
        }

        .footer-text {
            font-size: 0.8125rem;
            color: #6b7280;
        }

        /* Responsive Design */
        @media (min-width: 768px) {
            .policy-header {
                padding: 3rem 2rem;
            }

            .policy-title {
                font-size: 2.25rem;
            }

            .policy-main {
                padding: 2.5rem 2rem 4rem;
            }

            .policy-content {
                padding: 2.5rem 2rem;
            }

            .policy-content h1 {
                font-size: 1.75rem;
            }

            .policy-content h2 {
                font-size: 1.375rem;
            }
        }

        @media (max-width: 480px) {
            .policy-header {
                padding: 1.5rem 1rem;
            }

            .header-content {
                margin-top: 1.5rem;
            }

            .policy-title {
                font-size: 1.5rem;
            }

            .policy-main {
                padding: 1.5rem 1rem 2.5rem;
            }

            .policy-card {
                border-radius: 1rem;
                margin-top: -2rem;
            }

            .card-header {
                padding: 1rem;
            }

            .policy-content {
                padding: 1.25rem 1rem;
            }

            .back-button {
                padding: 0.5rem 1rem;
                font-size: 0.8125rem;
            }

            .meta-item {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="policy-header">
        <div class="header-container">
            <button onclick="window.close()" class="back-button">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </button>

            <div class="header-content">
                <h1 class="policy-title">{{ $policy->title }}</h1>
                <div class="policy-meta">
                    @if($policy->updated_at)
                        <span class="meta-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Diperbarui: {{ $policy->updated_at instanceof \Carbon\Carbon ? $policy->updated_at->translatedFormat('d F Y') : $policy->updated_at }}
                        </span>
                    @endif
                    <span class="meta-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Kebijakan Aktif
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="policy-main">
        <article class="policy-card">
            <div class="card-header">
                <div class="card-header-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="card-header-text">
                    <h2>Syarat & Ketentuan</h2>
                    <p>Harap baca dengan seksama</p>
                </div>
            </div>

            <div class="policy-content">
                {!! $policy->content !!}
            </div>

            <footer class="policy-footer">
                <span class="footer-dot"></span>
                <span class="footer-text">Dokumen ini dapat berubah sewaktu-waktu tanpa pemberitahuan terlebih dahulu.</span>
            </footer>
        </article>
    </main>
</body>
</html>
