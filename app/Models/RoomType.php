<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_capacity',
        'default_monthly_rate',
        'is_active',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'preferred_room_type_id');
    }

    public function canBeDeleted(): bool
    {
        return ! $this->rooms()->exists();
    }
}
