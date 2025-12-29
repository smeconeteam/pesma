<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relations
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return $this->roles()
            ->whereIn('name', $roles)
            ->exists();
    }

    public function adminScopes()
    {
        return $this->hasMany(AdminScope::class);
    }

    public function branchDormIds()
    {
        if ($this->hasRole(['super_admin', 'main_admin'])) {
            return Dorm::pluck('id');
        }

        return $this->adminScopes()
            ->where('type', 'branch')
            ->whereNotNull('dorm_id')
            ->pluck('dorm_id');
    }

    public function blockIds()
    {
        return $this->adminScopes()
            ->where('type', 'block')
            ->whereNotNull('block_id')
            ->pluck('block_id');
    }

    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasAnyRole([
            'super_admin',
            'main_admin',
            'branch_admin',
            'block_admin',
        ]);
    }

    public function residentProfile(): HasOne
    {
        return $this->hasOne(ResidentProfile::class, 'user_id');
    }

    public function roomResidents(): HasMany
    {
        return $this->hasMany(RoomResident::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'user_id');
    }

    public function approvedRegistrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'approved_by');
    }

    public function roomHistories(): HasMany
    {
        return $this->hasMany(RoomHistory::class, 'user_id')
            ->orderBy('check_in_date', 'desc');
    }

    public function recordedRoomHistories(): HasMany
    {
        return $this->hasMany(RoomHistory::class, 'recorded_by');
    }

    public function activeRoomResident(): HasOne
    {
        return $this->hasOne(RoomResident::class)
            ->whereNull('check_out_date')
            ->latestOfMany('check_in_date');
    }

    // ✅ RELASI BARU: Billing
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }

    public function discountUsages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class);
    }

    // Helper untuk cek tagihan unpaid
    public function hasUnpaidBills(): bool
    {
        return $this->bills()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->exists();
    }

    // Helper untuk total tunggakan
    public function getTotalOutstandingAmount(): int
    {
        return $this->bills()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->get()
            ->sum(fn($bill) => $bill->final_amount - $bill->paid_amount);
    }
}
