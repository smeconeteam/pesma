<x-guest-layout>
    <div class="min-h-screen bg-gradient-to-b from-green-50/40 via-white to-white">
        <main class="mx-auto max-w-3xl px-4 py-8 sm:py-12">
            {{-- Header --}}
            <header class="mb-6 sm:mb-8">
                <button
                    type="button"
                    id="btnBackToRegister"
                    class="inline-flex items-center gap-2 rounded-xl border border-green-200 bg-white px-3 py-2 text-sm font-semibold text-green-700 shadow-sm hover:bg-green-50 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    <span aria-hidden="true">←</span>
                    Kembali
                </button>

                <div class="mt-5">
                    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                        {{ $policy->title }}
                    </h1>

                    @if(!empty($policy->updated_at))
                        <p class="mt-2 text-sm text-gray-600">
                            Terakhir diperbarui:
                            <span class="font-semibold text-gray-700">
                                {{ $policy->updated_at instanceof \Carbon\Carbon
                                    ? $policy->updated_at->translatedFormat('d F Y')
                                    : $policy->updated_at }}
                            </span>
                        </p>
                    @endif
                </div>
            </header>

            {{-- Content Card --}}
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="p-5 sm:p-8">
                    <article
                        class="policy-content prose prose-sm sm:prose lg:prose-lg max-w-none
                               prose-headings:scroll-mt-24
                               prose-headings:text-gray-900 prose-headings:font-semibold
                               prose-p:text-gray-700
                               prose-a:text-green-700 hover:prose-a:text-green-800
                               prose-strong:text-gray-900
                               prose-li:marker:text-green-600
                               prose-blockquote:border-green-500 prose-blockquote:text-gray-700
                               prose-code:text-green-800 prose-code:bg-green-50 prose-code:px-1 prose-code:py-0.5 prose-code:rounded
                               prose-pre:bg-gray-900 prose-pre:text-gray-100"
                    >
                        {!! $policy->content !!}
                    </article>
                </div>
            </section>

            <footer class="mt-8 text-center text-xs text-gray-500">
                <span class="inline-flex items-center gap-2">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    Dokumen ini dapat berubah sewaktu-waktu.
                </span>
            </footer>
        </main>
    </div>

    <style>
        /* Hardening untuk output rich editor (table/img) */
        .policy-content table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
        }
        .policy-content th,
        .policy-content td {
            border-bottom: 1px solid #e5e7eb;
            padding: .75rem .875rem;
            vertical-align: top;
        }
        .policy-content th {
            background: #f9fafb;
            font-weight: 700;
            color: #111827;
            text-align: left;
        }
        .policy-content img {
            max-width: 100%;
            height: auto;
            border-radius: .75rem;
        }
    </style>

    <script>
        (function () {
            const btn = document.getElementById('btnBackToRegister');
            if (!btn) return;

            const registerUrl = @json(route('public.registration.create'));

            btn.addEventListener('click', function () {
                // Coba tutup tab (hanya berhasil jika tab dibuka via window.open)
                window.close();

                // Fallback: tetap arahkan ke halaman pendaftaran
                // (kalau window.close diblok, user tetap masuk register)
                setTimeout(() => {
                    window.location.href = registerUrl;
                }, 50);
            });
        })();
    </script>
</x-guest-layout>
