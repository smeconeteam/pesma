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
            return Room::with(['roomType:id,name,default_monthly_rate,default_capacity', 
                            'block:id,name,dorm_id', 
                            'block.dorm:id,name,address'])
                ->select('id', 'number', 'code', 'room_type_id', 'block_id', 'capacity', 'monthly_rate', 'is_active', 'thumbnail')
                ->where('is_active', true)
                ->latest('id')
                ->limit(6)
                ->get();
        });
        
        // Cache total count
        $totalRooms = cache()->remember('total_active_rooms', 300, function () {
            return Room::where('is_active', true)->count();
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
        
        // Build query with filters
        $query = Room::with(['roomType:id,name,default_monthly_rate,default_capacity', 
                        'block:id,name,dorm_id', 
                        'block.dorm:id,name,address'])
            ->select('id', 'number', 'code', 'room_type_id', 'block_id', 'capacity', 'monthly_rate', 'is_active', 'thumbnail')
            ->where('is_active', true);
        
        // Filter by search keyword
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('block', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('block.dorm', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                  })
                  ->orWhereHas('roomType', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
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
        
        $rooms = $query->latest('id')->paginate(12)->withQueryString();
        
        return view('rooms.index', compact('institution', 'rooms', 'dorms', 'roomTypes'));
    }

    /**
     * Show room detail page
     */
    public function showRoom($id)
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
            'facilitiesParkir',
            'facilitiesUmum',
            'facilitiesKamarMandi',
            'facilitiesKamar',
            'roomRules',
        ])->findOrFail($id);
        
        // Get similar rooms (same type or same dorm)
        $similarRooms = Room::with(['roomType:id,name,default_monthly_rate,default_capacity', 
                            'block:id,name,dorm_id', 
                            'block.dorm:id,name,address'])
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
}
