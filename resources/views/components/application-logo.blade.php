@php
    use App\Models\Institution;
    use Illuminate\Support\Facades\Storage;

    $institution = Institution::first();
    $logoPath = $institution?->logo_path;
@endphp

@if ($logoPath)
    <img src="{{ Storage::url($logoPath) }}" {{ $attributes->merge([
        'alt' => $institution->dormitory_name ?? config('app.name'),
        'class' => 'h-10 w-auto',
    ]) }} />
@endif
