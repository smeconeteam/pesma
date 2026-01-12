<x-guest-layout>
    <!-- Header - Sticky untuk mobile -->
    <div class="sticky top-0 z-10 bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 line-clamp-2 flex-1">
                    {{ $policy->title }}
                </h1>
                <button 
                    onclick="window.close()" 
                    class="flex-shrink-0 text-sm text-green-600 hover:text-green-700 font-medium px-3 py-1.5 border border-green-600 rounded-lg hover:bg-green-50 transition-colors"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 py-6 sm:py-8">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-6 md:p-8">
                <div class="prose prose-sm sm:prose max-w-none prose-headings:text-green-600 prose-headings:font-semibold prose-a:text-green-600 hover:prose-a:text-green-700 prose-strong:text-gray-900 prose-p:text-gray-700 prose-p:text-justify">
                    {!! $policy->content !!}
                </div>
            </div>
        </div>

        <!-- Footer Button - Fixed di bottom untuk mobile -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg p-4 sm:relative sm:mt-6 sm:bg-transparent sm:border-0 sm:shadow-none sm:p-0">
            <div class="max-w-4xl mx-auto">
                <button 
                    onclick="window.close()" 
                    class="w-full sm:w-auto sm:mx-auto sm:block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium text-sm sm:text-base shadow-lg sm:shadow-none focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    Kembali ke Formulir
                </button>
            </div>
        </div>

        <!-- Spacer untuk fixed button di mobile -->
        <div class="h-20 sm:hidden"></div>
    </div>

    <style>
        /* Custom prose styles untuk mobile optimization */
        @media (max-width: 640px) {
            .prose h1 { 
                font-size: 1.25rem;
                margin-top: 1.25em;
            }
            .prose h2 { 
                font-size: 1.125rem;
                margin-top: 1.25em;
            }
            .prose h3, .prose h4 { 
                font-size: 1rem;
                margin-top: 1em;
            }
            .prose {
                font-size: 0.9375rem;
            }
            .prose ul, .prose ol {
                padding-left: 1.25em;
            }
        }

        /* Line clamp utility */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Ensure smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
    </style>
</x-guest-layout>