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

// Localized Public Routes

// Contact
Route::get('/kontak', [LandingController::class, 'contact'])->name('contact.id');
Route::get('/contact', [LandingController::class, 'contact'])->name('contact.en');

// Available Rooms
Route::get('/kamar', [LandingController::class, 'allRooms'])->name('rooms.available.id');
Route::get('/rooms', [LandingController::class, 'allRooms'])->name('rooms.available.en');

// Room Detail
Route::get('/kamar/{code}', [LandingController::class, 'showRoom'])->name('rooms.show.id');
Route::get('/room/{code}', [LandingController::class, 'showRoom'])->name('rooms.show.en');

// About
Route::get('/tentang', [LandingController::class, 'about'])->name('about.id');
Route::get('/about', [LandingController::class, 'about'])->name('about.en');

// Resident Routes
Route::middleware(['auth', 'verified', 'resident.only'])->group(function () {

    // Indonesian Routes
    Route::prefix('santri')->group(function () {
        Route::get('/', function () {
            return redirect()->route('dashboard.id');
        });
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.id');

        // Resident Menu
        Route::get('/kamar-saya', MyRoomController::class)->name('resident.my-room.id');
        Route::get('/riwayat-kamar', [RoomHistoryController::class, 'index'])->name('resident.room-history.id');
        Route::get('/tagihan', [BillsController::class, 'index'])->name('resident.bills.id');
        Route::get('/riwayat-pembayaran', [\App\Http\Controllers\Resident\PaymentHistoryController::class, 'index'])->name('resident.payment-history.id');

        // Profile
        Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit.id');
        Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update.id');
        Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy.id');
    });

    // English Routes
    Route::prefix('student')->group(function () {
        Route::get('/', function () {
            return redirect()->route('dashboard.en');
        });
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.en');

        // Resident Menu
        Route::get('/my-room', MyRoomController::class)->name('resident.my-room.en');
        Route::get('/room-history', [RoomHistoryController::class, 'index'])->name('resident.room-history.en');
        Route::get('/bills', [BillsController::class, 'index'])->name('resident.bills.en');
        Route::get('/payment-history', [\App\Http\Controllers\Resident\PaymentHistoryController::class, 'index'])->name('resident.payment-history.en');

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit.en');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update.en');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy.en');
    });

    // Legacy Redirects
    Route::get('/dashboard', function () {
        return redirect(localizedRoute('dashboard'));
    })->name('dashboard');

    Route::get('/profile', function () {
        return redirect(localizedRoute('profile.edit'));
    })->name('profile.edit');
});

// Public Routes
Route::get('/akses-ditolak', function () {
    return view('no-access');
})->name('no-access.id');
Route::get('/no-access', function () {
    return view('no-access');
})->name('no-access.en');

// Public Registration
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
