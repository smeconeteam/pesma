<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Room;
use App\Models\Dorm;
use App\Models\Institution;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        // Cache institution data (rarely changes)
        $institution = cache()->remember('institution_data', 3600, function () {
            return Institution::first();
        });

        // Get available rooms with optimized eager loading and caching (10 mins)
        $rooms = cache()->remember('landing_rooms', 600, function () {
            return Room::with([
                'roomType:id,name,default_monthly_rate,default_capacity',
                'block:id,name,dorm_id',
                'block.dorm:id,name,address',
                'residentCategory:id,name'
            ])
                ->select('id', 'number', 'code', 'room_type_id', 'block_id', 'resident_category_id', 'capacity', 'monthly_rate', 'is_active', 'thumbnail')
                ->where('is_active', true)
                ->whereRaw('(SELECT COUNT(*) FROM room_residents WHERE room_residents.room_id = rooms.id AND room_residents.check_out_date IS NULL) < COALESCE(rooms.capacity, (SELECT default_capacity FROM room_types WHERE room_types.id = rooms.room_type_id))')
                ->latest('id')
                ->limit(6)
                ->get();
        });

        // Cache total count
        $totalRooms = cache()->remember('total_available_rooms_real', 300, function () {
            return Room::where('is_active', true)
                ->whereRaw('(SELECT COUNT(*) FROM room_residents WHERE room_residents.room_id = rooms.id AND room_residents.check_out_date IS NULL) < COALESCE(rooms.capacity, (SELECT default_capacity FROM room_types WHERE room_types.id = rooms.room_type_id))')
                ->count();
        });

        return view('landing', compact('institution', 'rooms', 'totalRooms'));
    }

    public function allRooms(Request $request)
    {
        // Cache institution data
        $institution = cache()->remember('institution_data', 3600, function () {
            return Institution::first();
        });

        // Get filters for dropdown
        $dorms = cache()->remember('active_dorms', 3600, function () {
            return Dorm::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        });

        $roomTypes = cache()->remember('room_types', 3600, function () {
            return RoomType::orderBy('name')->get(['id', 'name']);
        });

        $residentCategories = cache()->remember('resident_categories_available', 3600, function () {
            return \App\Models\ResidentCategory::whereHas('rooms', function ($q) {
                $q->where('is_active', true)
                    ->whereRaw('(SELECT COUNT(*) FROM room_residents WHERE room_residents.room_id = rooms.id AND room_residents.check_out_date IS NULL) < COALESCE(rooms.capacity, (SELECT default_capacity FROM room_types WHERE room_types.id = rooms.room_type_id))');
            })->orderBy('name')->get(['id', 'name']);
        });

        // Build query with filters
        $query = Room::with([
            'roomType:id,name,default_monthly_rate,default_capacity',
            'block:id,name,dorm_id',
            'block.dorm:id,name,address',
            'residentCategory:id,name'
        ]) // Added residentCategory for badge
            ->select('id', 'number', 'code', 'room_type_id', 'block_id', 'resident_category_id', 'capacity', 'monthly_rate', 'is_active', 'thumbnail')
            ->where('is_active', true)
            ->whereRaw('(SELECT COUNT(*) FROM room_residents WHERE room_residents.room_id = rooms.id AND room_residents.check_out_date IS NULL) < COALESCE(rooms.capacity, (SELECT default_capacity FROM room_types WHERE room_types.id = rooms.room_type_id))');

        // Filter by search keyword
        if ($request->filled('search')) {
            $search = trim($request->search);

            // Try to parse format: "{dorm} Nomor {number} Tipe {type}"
            if (preg_match('/^(.+?)\s+Nomor\s+(\d+)\s+Tipe\s+(.+)$/i', $search, $matches)) {
                $dormName = trim($matches[1]);
                $roomNumber = trim($matches[2]);
                $roomType = trim($matches[3]);

                $query->where(function ($q) use ($dormName, $roomNumber, $roomType) {
                    $q->whereHas('block.dorm', function ($q) use ($dormName) {
                        $q->where('name', 'like', "%{$dormName}%");
                    })
                        ->where('number', $roomNumber)
                        ->whereHas('roomType', function ($q) use ($roomType) {
                            $q->where('name', 'like', "%{$roomType}%");
                        });
                });
            }
            // Try to parse partial format: "{dorm} Nomor {number}"
            elseif (preg_match('/^(.+?)\s+Nomor\s+(\d+)$/i', $search, $matches)) {
                $dormName = trim($matches[1]);
                $roomNumber = trim($matches[2]);

                $query->where(function ($q) use ($dormName, $roomNumber) {
                    $q->whereHas('block.dorm', function ($q) use ($dormName) {
                        $q->where('name', 'like', "%{$dormName}%");
                    })
                        ->where('number', $roomNumber);
                });
            }
            // Fallback to original logic
            else {
                // Clean search term
                $cleanSearch = trim(preg_replace('/(cabang|komplek|tipe|kamar|nomor|kategori)\s+/i', '', $search));

                if (!empty($cleanSearch)) {
                    $query->where(function ($q) use ($cleanSearch) {
                        $q->where('number', 'like', "%{$cleanSearch}%")
                            ->orWhere('code', 'like', "%{$cleanSearch}%")
                            ->orWhereHas('block', function ($q) use ($cleanSearch) {
                                $q->where('name', 'like', "%{$cleanSearch}%");
                            })
                            ->orWhereHas('block.dorm', function ($q) use ($cleanSearch) {
                                $q->where('name', 'like', "%{$cleanSearch}%")
                                    ->orWhere('address', 'like', "%{$cleanSearch}%");
                            })
                            ->orWhereHas('roomType', function ($q) use ($cleanSearch) {
                                $q->where('name', 'like', "%{$cleanSearch}%");
                            })
                            ->orWhereHas('residentCategory', function ($q) use ($cleanSearch) {
                                $q->where('name', 'like', "%{$cleanSearch}%");
                            });
                    });
                }
            }
        }

        // Filter by dorm (cabang)
        if ($request->filled('dorm_id')) {
            $query->whereHas('block', function ($q) use ($request) {
                $q->where('dorm_id', $request->dorm_id);
            });
        }

        // Filter by room type
        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        // Filter by resident category
        if ($request->filled('resident_category_id')) {
            $query->where('resident_category_id', $request->resident_category_id);
        }

        $rooms = $query->latest('id')->paginate(12)->withQueryString();

        if ($request->ajax()) {
            return view('rooms.partials.list', compact('rooms'));
        }

        return view('rooms.index', compact('institution', 'rooms', 'dorms', 'roomTypes', 'residentCategories'));
    }

    /**
     * Show room detail page
     */
    public function showRoom($code)
    {
        // Cache institution data
        $institution = cache()->remember('institution_data', 3600, function () {
            return Institution::first();
        });

        // Get room with all relations
        $room = Room::with([
            'roomType',
            'block.dorm',
            'residentCategory',
            'facilities',
            'parkingFacilities',
            'generalFacilities',
            'bathroomFacilities',
            'roomFacilities',
            'roomRules',
        ])->where('code', $code)->firstOrFail();

        // Get similar rooms (same type or same dorm)
        $similarRooms = Room::with([
            'roomType:id,name,default_monthly_rate,default_capacity',
            'block:id,name,dorm_id',
            'block.dorm:id,name,address'
        ])
            ->where('is_active', true)
            ->where('id', '!=', $room->id)
            ->where(function ($q) use ($room) {
                $q->where('room_type_id', $room->room_type_id)
                    ->orWhereHas('block', function ($q) use ($room) {
                        $q->where('dorm_id', $room->block->dorm_id);
                    });
            })
            ->limit(4)
            ->get();

        return view('rooms.show', compact('institution', 'room', 'similarRooms'));
    }

    /**
     * Show about page
     */
    public function about()
    {
        // Cache institution data
        $institution = cache()->remember('institution_data', 3600, function () {
            return Institution::first();
        });
        
        return view('about', compact('institution'));
    }
}
