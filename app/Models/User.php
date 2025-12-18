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
        // block_admin: ambil komplek yang dia pegang
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
        // Hanya role admin yang boleh login ke /admin
        // Penghuni (resident) TIDAK boleh WOIIII
        return $this->is_active && $this->hasAnyRole([
            'super_admin',
            'main_admin',
            'branch_admin',
            'block_admin',
        ]);
    }

    public function residentProfile(): HasOne
    {
        return $this->hasOne(ResidentProfile::class);
    }

    public function roomResidents(): HasMany
    {
        return $this->hasMany(RoomResident::class);
    }

    public function activeRoomResident(): HasOne
    {
        return $this->hasOne(\App\Models\RoomResident::class)
            ->whereNull('check_out_date')
            ->latestOfMany('check_in_date');
    }
}
