<x-public-layout>
    <div class="mx-auto max-w-7xl py-12">
        <!-- Breadcrumb -->
        <div class="mb-6 flex gap-2 px-4 text-sm text-gray-600 sm:px-6 lg:px-8 dark:text-gray-400">
            <a href="{{ localizedRoute('home') }}" class="text-green-600 hover:underline dark:text-green-400">{{ __('public.home') }}</a>
            <span>/</span>
            <span>{{ __('navigation.about') }}</span>
        </div>

        <div class="mb-10 px-4 sm:px-6 lg:px-8">
            <h1 class="mb-2 text-4xl font-bold text-gray-900 dark:text-white">
                {{ __('about.title') }}
            </h1>
        </div>

        <!-- Main Content -->
        <div class="mx-auto mt-4 px-4 sm:px-6 lg:px-8">
            @if ($institution && $institution->about_content)
            <div class="content-card">
                <div class="rich-content">
                    {!! $institution->about_content !!}
                </div>
            </div>
            @else
            <div class="content-card">
                <div class="empty-state">
                    <svg class="h-16 w-16" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3>{{ __('about.content_unavailable_title') }}</h3>
                    <p>{{ __('about.content_unavailable_desc', ['name' => $institution->dormitory_name ?? config('app.name', 'Laravel')]) }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <style>
        /* =============================================
           Rich Content — styling untuk output RichEditor
           Tailwind me-reset semua elemen HTML secara
           default, sehingga list, heading, dsb. perlu
           di-restore secara eksplisit di sini.
        =============================================== */
        .rich-content {
            color: inherit;
            line-height: 1.75;
        }

        /* --- Headings --- */
        .rich-content h1 {
            font-size: 1.875rem;
            /* 30px */
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: inherit;
        }

        .rich-content h2 {
            font-size: 1.5rem;
            /* 24px */
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            color: inherit;
        }

        .rich-content h3 {
            font-size: 1.25rem;
            /* 20px */
            font-weight: 600;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            color: inherit;
        }

        /* --- Paragraf --- */
        .rich-content p {
            margin-bottom: 1rem;
        }

        /* --- Bulleted List (ul) --- */
        .rich-content ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .rich-content ul li {
            margin-bottom: 0.25rem;
        }

        /* --- Numbered List (ol) --- */
        .rich-content ol {
            list-style-type: decimal;
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .rich-content ol li {
            margin-bottom: 0.25rem;
        }

        /* --- Nested list --- */
        .rich-content ul ul,
        .rich-content ol ol,
        .rich-content ul ol,
        .rich-content ol ul {
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
        }

        /* --- Blockquote --- */
        .rich-content blockquote {
            border-left: 4px solid #16a34a;
            /* green-600 */
            padding-left: 1rem;
            margin: 1rem 0;
            font-style: italic;
            color: #6b7280;
            /* gray-500 */
        }

        /* --- Link --- */
        .rich-content a {
            color: #16a34a;
            /* green-600 */
            text-decoration: underline;
        }

        .rich-content a:hover {
            color: #15803d;
            /* green-700 */
        }

        /* --- Bold & Italic --- */
        .rich-content strong {
            font-weight: 700;
        }

        .rich-content em {
            font-style: italic;
        }

        .rich-content u {
            text-decoration: underline;
        }

        .rich-content s {
            text-decoration: line-through;
        }

        /* --- Code --- */
        .rich-content code {
            background-color: #f3f4f6;
            /* gray-100 */
            border-radius: 0.25rem;
            padding: 0.125rem 0.375rem;
            font-size: 0.875em;
            font-family: ui-monospace, monospace;
        }

        .rich-content pre {
            background-color: #1f2937;
            /* gray-800 */
            color: #f9fafb;
            /* gray-50 */
            border-radius: 0.5rem;
            padding: 1rem;
            overflow-x: auto;
            margin-bottom: 1rem;
        }

        .rich-content pre code {
            background: none;
            padding: 0;
            font-size: 0.875rem;
            color: inherit;
        }

        /* --- Gambar --- */
        .rich-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }

        /* --- Dark mode --- */
        @media (prefers-color-scheme: dark) {
            .dark .rich-content blockquote {
                color: #9ca3af;
                /* gray-400 */
            }

            .dark .rich-content code {
                background-color: #374151;
                /* gray-700 */
                color: #f9fafb;
            }

            .dark .rich-content a {
                color: #4ade80;
                /* green-400 */
            }

            .dark .rich-content a:hover {
                color: #86efac;
                /* green-300 */
            }
        }
    </style>
</x-public-layout>