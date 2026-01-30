<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Room;
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
                            'block.dorm:id,name'])
                ->select('id', 'number', 'code', 'room_type_id', 'block_id', 'capacity', 'monthly_rate', 'is_active')
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

    public function allRooms()
    {
        // Cache institution data
        $institution = cache()->remember('institution_data', 3600, function () {
            return Institution::first();
        });
        
        // Get all available rooms with pagination and optimized loading (Cached per page)
        $page = request()->get('page', 1);
        $rooms = cache()->remember('rooms_page_' . $page, 600, function () {
            return Room::with(['roomType:id,name,default_monthly_rate,default_capacity', 
                            'block:id,name,dorm_id', 
                            'block.dorm:id,name'])
                ->select('id', 'number', 'code', 'room_type_id', 'block_id', 'capacity', 'monthly_rate', 'is_active')
                ->where('is_active', true)
                ->latest('id')
                ->paginate(12);
        });
        
        return view('rooms.index', compact('institution', 'rooms'));
    }
}
