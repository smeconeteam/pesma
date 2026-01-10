<div class="flex items-center gap-3">
    @if (isset($logoUrl))
        <img src="{{ $logoUrl }}" alt="Logo" class="h-10 w-auto object-contain">
    @endif

    <span class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
        {{ $brandName }}
    </span>
</div>
