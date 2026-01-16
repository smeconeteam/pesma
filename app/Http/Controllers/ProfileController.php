<?php

namespace App\Http\Controllers;

use App\Models\RoomResident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        
        // Get current room assignment (check_out_date is null = active assignment)
        $assignment = $user->roomResidents()
            ->whereNull('check_out_date')
            ->with(['room.block.dorm', 'room.roomType'])
            ->first();
        
        // Get PIC assignment for the same room
        $picAssignment = null;
        if ($assignment) {
            $picAssignment = $assignment->room->roomResidents()
                ->where('is_pic', true)
                ->whereNull('check_out_date')
                ->with('user.residentProfile')
                ->first();
        }
        
        return view('profile.edit', [
            'user' => $user,
            'assignment' => $assignment,
            'picAssignment' => $picAssignment,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Update basic user information
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Update resident profile if exists
        $profile = $user->residentProfile;
        
        if ($profile) {
            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($profile->photo_path) {
                    Storage::disk('public')->delete($profile->photo_path);
                }

                // Store new photo
                $photoPath = $request->file('photo')->store('profile-photos', 'public');
                $profile->photo_path = $photoPath;
            }

            // Handle photo removal
            if ($request->has('remove_photo') && $request->remove_photo == '1') {
                if ($profile->photo_path) {
                    Storage::disk('public')->delete($profile->photo_path);
                    $profile->photo_path = null;
                }
            }

            // Update phone number only
            if ($request->filled('phone_number')) {
                $profile->phone_number = $request->phone_number;
            }

            $profile->save();
        }

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

    public function deletePhoto(Request $request)
    {
        $user = $request->user();
        $profile = $user->residentProfile;
        
        if ($profile && $profile->photo_path) {
            // Hapus file foto dari storage
            Storage::delete($profile->photo_path);
            
            // Hapus path dari database
            $profile->photo_path = null;
            $profile->save();
            
            return redirect()->route('profile.edit')
                ->with('status', 'photo-deleted');
        }
        
        return redirect()->route('profile.edit');
    }
}