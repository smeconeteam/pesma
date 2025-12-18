<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Dorm extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'description',
        'is_active',
    ];

    // RELASI
    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

    public function rooms()
    {
        return $this->hasManyThrough(Room::class, Block::class);
    }

    public function adminScopes()
    {
        return $this->hasMany(AdminScope::class);
    }

    public function canBeDeleted()
    {
        return ! $this->blocks()->exists();
    }
}
