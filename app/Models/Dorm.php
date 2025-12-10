<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dorm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'description',
    ];

    // RELASI

    // Satu asrama cabang punya banyak blok
    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

    // Admin yang di-scope ke cabang ini (via admin_scopes)
    public function adminScopes()
    {
        return $this->hasMany(AdminScope::class);
    }

    // Kalau nanti kamu mau akses semua kamar melalui blok:
    public function rooms()
    {
        // relasi hasManyThrough: Dorm -> Blocks -> Rooms
        return $this->hasManyThrough(Room::class, Block::class);
    }
}
