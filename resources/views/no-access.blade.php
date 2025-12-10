<x-guest-layout class="flex flex-col gap-2">
    <h1 class="text-center text-2xl font-medium">Akses Ditolak!</h1>
    <p class="text-center mb-2">Akun Anda tidak memiliki akses ke halaman ini.</p>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <x-primary-button>{{ __('Log out') }}</x-primary-button>
    </form>
</x-guest-layout>
