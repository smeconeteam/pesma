<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Block extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dorm_id',
        'name',
        'description',
        'is_active',
    ];

    public function dorm()
    {
        return $this->belongsTo(Dorm::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function adminScopes()
    {
        return $this->hasMany(AdminScope::class);
    }
}
