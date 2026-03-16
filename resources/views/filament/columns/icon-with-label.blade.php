<div class="flex items-center gap-2">
    @if($getState())
        @php
            $svgHtml = '';
            try {
                $svgHtml = svg($getState(), 'h-5 w-5')->toHtml();
            } catch (\Exception $e) {
                $svgHtml = '';
            }
            $iconLabel = \App\Services\IconService::getAllIcons()[$getState()] ?? $getState();
        @endphp
        @if($svgHtml)
            {!! $svgHtml !!}
        @endif
        <span>{{ $iconLabel }}</span>
    @else
        <span>-</span>
    @endif
</div>
