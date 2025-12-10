<x-guest-layout class="flex flex-col gap-2">
    <h1 class="text-center text-2xl font-medium">Akses Ditolak!</h1>
    <p class="text-center mb-2">Akun Anda tidak memiliki akses ke halaman ini.</p>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
<<<<<<< HEAD
        <button type="submit">Keluar</button>
=======
        <x-primary-button>{{ __('Log out') }}</x-primary-button>
>>>>>>> 316556ee39ab6c669cf47a9b6df9fd1b5b68f6d3
    </form>
</x-guest-layout>
