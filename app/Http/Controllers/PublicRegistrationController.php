<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Country;
use App\Models\Dorm;
use App\Models\Registration;
use App\Models\ResidentCategory;
use App\Models\RoomType;
use Illuminate\Support\Carbon;

class PublicRegistrationController extends Controller
{
    public function create()
    {
        $indoId = Country::query()->where('iso2', 'ID')->value('id'); // dipakai untuk WNI auto Indonesia

        return view('public.registration.create', [
            'residentCategories' => ResidentCategory::query()->orderBy('name')->get(['id', 'name']),
            'dorms'              => Dorm::query()->orderBy('name')->get(['id', 'name']),
            'roomTypes'          => RoomType::query()->orderBy('name')->get(['id', 'name']),
            'countries'          => Country::query()->orderBy('name')->get(['id', 'name', 'iso2']),
            'indoCountryId'      => $indoId,
        ]);
    }

    public function store(StoreRegistrationRequest $request)
    {
        $data = $request->validated();

        // Default sesuai Filament: WNI dan planned check-in +7 hari (kalau user kosongin)
        $data['citizenship_status'] ??= 'WNI';
        $data['planned_check_in_date'] ??= Carbon::now()->addDays(7)->toDateString(); // sesuai default di resource:contentReference[oaicite:5]{index=5}

        // Kalau WNI => paksa country_id = Indonesia (mirip behaviour Filament):contentReference[oaicite:6]{index=6}
        if (($data['citizenship_status'] ?? 'WNI') === 'WNI') {
            $indoId = Country::query()->where('iso2', 'ID')->value('id');
            if ($indoId) {
                $data['country_id'] = $indoId;
            }
        }

        // Password: mengikuti resource (kosong => 123456789, disimpan bcrypt):contentReference[oaicite:7]{index=7}
        $plain = $data['password'] ?? null;
        $data['password'] = filled($plain) ? bcrypt($plain) : bcrypt('123456789');

        // Foto: simpan ke /storage/app/public/registrations (sejalan directory 'registrations'):contentReference[oaicite:8]{index=8}
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('registrations', 'public');
        }

        $data['status'] = 'pending'; // status menunggu:contentReference[oaicite:9]{index=9}

        Registration::create($data);

        return redirect()->route('public.registration.success');
    }

    public function success()
    {
        return view('public.registration.success');
    }
}
