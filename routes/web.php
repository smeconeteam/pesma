<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\Resident\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified', 'resident.only'])->group(function () {

    // ✅ Dashboard resident pakai controller
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

Route::get('/no-access', function () {
    return view('no-access');
})->name('no-access');

require __DIR__ . '/auth.php';

// =====================
// Public Registration
// =====================
Route::get('/pendaftaran', [PublicRegistrationController::class, 'create'])
    ->name('public.registration.create');

Route::post('/pendaftaran', [PublicRegistrationController::class, 'store'])
    ->name('public.registration.store');

Route::get('/pendaftaran/berhasil', [PublicRegistrationController::class, 'success'])
    ->name('public.registration.success');
