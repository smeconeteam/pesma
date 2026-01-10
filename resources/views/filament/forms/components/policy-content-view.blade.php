@php
    /** @var \Filament\Forms\Components\ViewField $field */
    $state = $getState();
@endphp

<div class="space-y-2">
    <div class="rounded-xl border border-gray-200 bg-white p-4 text-gray-900 shadow-sm
                dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100">
        <div class="prose max-w-none dark:prose-invert">
            {!! $state !!}
        </div>
    </div>
</div>
