<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'default_capacity',
        'default_monthly_rate',
        'is_active',
    ];

    public function rooms(){
        return $this->hasMany(Room::class);
    }
}
