<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

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

    // Helpers
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
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
}
