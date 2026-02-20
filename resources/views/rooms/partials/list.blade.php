@if ($rooms->count() > 0)
    <div class="grid w-full grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($rooms as $room)
            <x-room-card :room="$room" />
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-8 w-full">
        {{ $rooms->links('pagination::default') }}
    </div>
@else
    <div class="empty-state">
        <div class="empty-state-icon">🏠</div>
        <h2 class="empty-state-title">{{ __('public.no_rooms_found') }}</h2>
        <p class="empty-state-text">
            @if (request()->hasAny(['search', 'dorm_id', 'room_type_id', 'resident_category_id']))
                {{ __('public.try_different_filters') }}
            @else
                {{ __('public.no_rooms_currently') }}
            @endif
        </p>
        @if (request()->hasAny(['search', 'dorm_id', 'room_type_id', 'resident_category_id']))
            <a href="{{ localizedRoute('rooms.available') }}" class="btn-primary">{{ __('public.reset_filter') }}</a>
        @else
            <a href="{{ localizedRoute('home') }}" class="btn-primary">{{ __('public.back_to_home') }}</a>
        @endif
    </div>
@endif
