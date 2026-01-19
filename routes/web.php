<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\Resident\MyRoomController;
use App\Http\Controllers\Resident\RoomHistoryController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('login');
});

// =====================
// Resident Routes
// =====================
Route::middleware(['auth', 'verified', 'resident.only'])->group(function () {

    // Dashboard resident
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Halaman Kamar Saya (read-only)
    Route::get('/kamar-saya', MyRoomController::class)
        ->name('resident.my-room');

    // Riwayat Kamar
    Route::get('/riwayat-kamar', [RoomHistoryController::class, 'index'])
        ->name('resident.room-history');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

// =====================
// Public Routes
// =====================
Route::get('/no-access', function () {
    return view('no-access');
})->name('no-access');

// =====================
// Public Registration
// =====================
Route::get('/pendaftaran', [PublicRegistrationController::class, 'create'])
    ->name('public.registration.create');

Route::post('/pendaftaran', [PublicRegistrationController::class, 'store'])
    ->name('public.registration.store');

Route::get('/pendaftaran/berhasil', [PublicRegistrationController::class, 'success'])
    ->name('public.registration.success');

Route::get('/kebijakan', [PublicRegistrationController::class, 'policy'])
    ->name('public.policy');

Route::delete('/profile/hapus-foto', [ProfileController::class, 'deletePhoto'])
    ->name('profile.delete-photo')
    ->middleware('auth');

// =====================
// Auth Routes
// =====================
require __DIR__ . '/auth.php';
