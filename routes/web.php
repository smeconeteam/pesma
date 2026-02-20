<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\Resident\MyRoomController;
use App\Http\Controllers\Resident\RoomHistoryController;
use App\Http\Controllers\Resident\BillsController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;


// Landing Page for Public
Route::get('/', [LandingController::class, 'index'])->name('home');

// =====================
// Localized Public Routes — both ID and EN slugs are registered
// =====================

// Contact
Route::get('/kontak', [LandingController::class, 'contact'])->name('contact.id');
Route::get('/contact', [LandingController::class, 'contact'])->name('contact.en');

// Available Rooms
Route::get('/kamar-tersedia', [LandingController::class, 'allRooms'])->name('rooms.available.id');
Route::get('/rooms-available', [LandingController::class, 'allRooms'])->name('rooms.available.en');

// Room Detail
Route::get('/kamar/{code}', [LandingController::class, 'showRoom'])->name('rooms.show.id');
Route::get('/room/{code}', [LandingController::class, 'showRoom'])->name('rooms.show.en');

// About
Route::get('/tentang', [LandingController::class, 'about'])->name('about.id');
Route::get('/about', [LandingController::class, 'about'])->name('about.en');

// =====================
// Resident Routes (with localized slugs)
// =====================
Route::middleware(['auth', 'verified', 'resident.only'])->group(function () {

    // Dashboard resident (same in both languages)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // My Room
    Route::get('/kamar-saya', MyRoomController::class)
        ->name('resident.my-room.id');
    Route::get('/my-room', MyRoomController::class)
        ->name('resident.my-room.en');

    // Room History
    Route::get('/riwayat-kamar', [RoomHistoryController::class, 'index'])
        ->name('resident.room-history.id');
    Route::get('/room-history', [RoomHistoryController::class, 'index'])
        ->name('resident.room-history.en');

    // Bills
    Route::get('/tagihan', [BillsController::class, 'index'])
        ->name('resident.bills.id');
    Route::get('/bills', [BillsController::class, 'index'])
        ->name('resident.bills.en');

    // Payment History
    Route::get('/riwayat-pembayaran', [\App\Http\Controllers\Resident\PaymentHistoryController::class, 'index'])
        ->name('resident.payment-history.id');
    Route::get('/payment-history', [\App\Http\Controllers\Resident\PaymentHistoryController::class, 'index'])
        ->name('resident.payment-history.en');

    // Profile routes (same URL in both languages)
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
// Public Registration (with localized slugs)
// =====================
Route::get('/pendaftaran', [PublicRegistrationController::class, 'create'])
    ->name('public.registration.create.id');
Route::get('/registration', [PublicRegistrationController::class, 'create'])
    ->name('public.registration.create.en');

Route::post('/pendaftaran', [PublicRegistrationController::class, 'store'])
    ->name('public.registration.store.id');
Route::post('/registration', [PublicRegistrationController::class, 'store'])
    ->name('public.registration.store.en');

Route::get('/pendaftaran/berhasil', [PublicRegistrationController::class, 'success'])
    ->name('public.registration.success.id');
Route::get('/registration/success', [PublicRegistrationController::class, 'success'])
    ->name('public.registration.success.en');

Route::get('/kebijakan', [PublicRegistrationController::class, 'policy'])
    ->name('public.policy.id');
Route::get('/policy', [PublicRegistrationController::class, 'policy'])
    ->name('public.policy.en');

Route::delete('/profile/hapus-foto', [ProfileController::class, 'deletePhoto'])
    ->name('profile.delete-photo')
    ->middleware('auth');

// =====================
// Receipt Routes
// =====================
Route::middleware(['auth'])->group(function () {
    Route::get('/receipt/{payment}', [\App\Http\Controllers\ReceiptController::class, 'show'])
        ->name('receipt.show');
    Route::get('/receipt/{payment}/download', [\App\Http\Controllers\ReceiptController::class, 'download'])
        ->name('receipt.download');
});

// Route untuk switch bahasa
Route::post('/locale/switch', [LocaleController::class, 'switch'])->name('locale.switch');
// =====================
// Auth Routes
// =====================
require __DIR__ . '/auth.php';