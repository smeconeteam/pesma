<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dorm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class);
    }

    public function rooms()
    {
        return $this->hasManyThrough(Room::class, Block::class);
    }

    public function adminScopes(): HasMany
    {
        return $this->hasMany(AdminScope::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'preferred_dorm_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Helper methods
    public function canBeDeleted(): bool
    {
        return !$this->blocks()->exists();
    }
}