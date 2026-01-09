<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\RoomResident;
use Illuminate\Http\Request;

class MyRoomController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Penempatan kamar aktif penghuni
        $assignment = RoomResident::query()
            ->where('user_id', $user->id)
            ->whereNull('check_out_date')
            ->with([
                'room.block.dorm',
                'room.roomType',
            ])
            ->latest('check_in_date')
            ->first();

        $roommates = collect();
        $picAssignment = null;

        if ($assignment?->room_id) {
            // Teman sekamar (aktif)
            $roommates = RoomResident::query()
                ->where('room_id', $assignment->room_id)
                ->whereNull('check_out_date')
                ->with(['user.residentProfile'])
                ->orderByDesc('is_pic')
                ->orderBy('check_in_date')
                ->get();

            $picAssignment = $roommates->firstWhere('is_pic', true);
        }

        return view('resident.my-room', [
            'assignment'    => $assignment,
            'roommates'     => $roommates,
            'picAssignment' => $picAssignment,
        ]);
    }
}
