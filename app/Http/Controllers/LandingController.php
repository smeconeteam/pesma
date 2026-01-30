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
        // Get institution data
        $institution = Institution::first();
        
        // Get available rooms (latest 6 for landing page)
        $rooms = Room::with(['roomType', 'block.dorm'])
            ->where('is_active', true)
            ->latest()
            ->limit(6)
            ->get();
        
        // Count total available rooms
        $totalRooms = Room::where('is_active', true)->count();
        
        return view('landing', compact('institution', 'rooms', 'totalRooms'));
    }

    public function allRooms()
    {
        // Get institution data
        $institution = Institution::first();
        
        // Get all available rooms with pagination
        $rooms = Room::with(['roomType', 'block.dorm'])
            ->where('is_active', true)
            ->latest()
            ->paginate(12);
        
        return view('rooms.index', compact('institution', 'rooms'));
    }
}
