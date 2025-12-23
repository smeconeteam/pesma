@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto px-4 py-16 text-center">
    <div class="rounded-2xl border bg-white p-8">
        <h1 class="text-2xl font-semibold">Pendaftaran Terkirim ✅</h1>
        <p class="text-gray-600 mt-3">
            Terima kasih. Pendaftaran kamu sudah masuk dan sedang menunggu persetujuan admin.
            Setelah disetujui, kamu baru bisa login.
        </p>

        <div class="mt-6">
            <a href="{{ url('/') }}"
               class="inline-flex items-center justify-center rounded-lg bg-green-600 px-5 py-2.5 text-white font-medium hover:bg-green-700">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</div>
@endsection
