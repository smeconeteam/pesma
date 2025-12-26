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
        $indoId = Country::query()->where('iso2', 'ID')->value('id');

        return view('public.registration.create', [
            'residentCategories' => ResidentCategory::query()->orderBy('name')->get(['id', 'name']),
            'dorms'              => Dorm::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roomTypes'          => RoomType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'countries'          => Country::query()->orderBy('name')->get(['id', 'name', 'iso2']),
            'indoCountryId'      => $indoId,
        ]);
    }

    public function store(StoreRegistrationRequest $request)
    {
        $data = $request->validated();

        $data['citizenship_status'] = $data['citizenship_status'] ?? 'WNI';

        if ($data['citizenship_status'] === 'WNI') {
            $indoId = Country::query()->where('iso2', 'ID')->value('id');
            if ($indoId) {
                $data['country_id'] = $indoId;
            }
        }

        // Password: kosong => 123456789
        $plain = $data['password'] ?? null;
        $data['password'] = filled($plain) ? bcrypt($plain) : bcrypt('123456789');

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('registrations', 'public');
        }

        $data['status'] = 'pending';

        Registration::create($data);

        return redirect()->route('public.registration.success');
    }

    public function success()
    {
        return view('public.registration.success');
    }
}
