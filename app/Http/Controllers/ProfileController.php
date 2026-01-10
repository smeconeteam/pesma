<?php

namespace App\Http\Controllers;

use App\Models\RoomResident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Load data penghuni (kalau relasi ada)
        $user->load([
            'residentProfile.residentCategory',
            'residentProfile.country',
        ]);

        // Penempatan kamar aktif (belum checkout)
        $assignment = RoomResident::query()
            ->where('user_id', $user->id)
            ->whereNull('check_out_date')
            ->with([
                'room.block.dorm',
                'room.roomType', // kalau relasi kamu beda nama, sesuaikan
            ])
            ->latest('check_in_date')
            ->first();

        // PIC kamar aktif
        $picAssignment = null;

        if ($assignment?->room_id) {
            $picAssignment = RoomResident::query()
                ->where('room_id', $assignment->room_id)
                ->whereNull('check_out_date')
                ->where('is_pic', true)
                ->with('user.residentProfile')
                ->first();
        }

        return view('profile.edit', [
            'user'          => $user,
            'assignment'    => $assignment,
            'picAssignment' => $picAssignment,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // ✅ Ini default Breeze (ubah nama & email)
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        // kalau email berubah, reset verifikasi
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        // ✅ Default Breeze (kalau kamu masih mau fitur hapus akun)
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
