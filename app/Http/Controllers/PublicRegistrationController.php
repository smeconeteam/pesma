<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Country;
use App\Models\Dorm;
use App\Models\Policy;
use App\Models\Registration;
use App\Models\ResidentCategory;
use App\Models\RoomType;
use Illuminate\Support\Facades\DB;

class PublicRegistrationController extends Controller
{
    public function create()
    {
        $indoId = Country::query()->where('iso2', 'ID')->value('id');

        // Ambil kebijakan aktif
        $policy = Policy::where('is_active', true)->first();

        // Hitung availability untuk setiap kombinasi dorm, room_type, dan resident_category
        $roomAvailability = DB::table('rooms')
            ->join('blocks', 'rooms.block_id', '=', 'blocks.id')
            ->join('dorms', 'blocks.dorm_id', '=', 'dorms.id')
            ->leftJoin('room_residents', function($join) {
                $join->on('rooms.id', '=', 'room_residents.room_id')
                    ->whereNull('room_residents.check_out_date');
            })
            ->where('rooms.is_active', true)
            ->whereNull('rooms.deleted_at')
            ->where('dorms.is_active', true)
            ->whereNull('dorms.deleted_at')
            ->where('blocks.is_active', true)
            ->whereNull('blocks.deleted_at')
            ->select(
                'dorms.id as dorm_id',
                'rooms.room_type_id',
                'rooms.resident_category_id',
                DB::raw('SUM(rooms.capacity) as total_capacity'),
                DB::raw('COUNT(room_residents.id) as occupied_count')
            )
            ->groupBy('dorms.id', 'rooms.room_type_id', 'rooms.resident_category_id')
            ->get()
            ->map(function($item) {
                $item->available_capacity = $item->total_capacity - $item->occupied_count;
                return $item;
            })
            ->filter(function($item) {
                return $item->available_capacity > 0;
            })
            ->values();

        return view('public.registration.create', [
            'residentCategories' => ResidentCategory::query()->orderBy('name')->get(['id', 'name']),
            'dorms'              => Dorm::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roomTypes'          => RoomType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'countries'          => Country::query()->orderBy('name')->get(['id', 'name', 'iso2']),
            'indoCountryId'      => $indoId,
            'policy'             => $policy,
            'roomAvailability'   => $roomAvailability,
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

        // Hapus agreed_to_policy dari data sebelum disimpan (tidak ada kolom ini di database)
        unset($data['agreed_to_policy']);

        Registration::create($data);

        return redirect()->route('public.registration.success');
    }

    public function success()
    {
        return view('public.registration.success');
    }

    public function policy()
    {
        $policy = Policy::where('is_active', true)->first();

        if (!$policy) {
            abort(404, 'Kebijakan tidak ditemukan');
        }

        return view('public.policy', [
            'policy' => $policy,
        ]);
    }
}