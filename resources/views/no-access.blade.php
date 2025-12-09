<x-guest-layout>
    <h1>Akses Ditolak</h1>
    <p>Akun Anda tidak memiliki akses ke halaman ini.</p>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit">Logout</button>
    </form>
</x-guest-layout>
