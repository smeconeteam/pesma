<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'block_id',
        'room_type_id',
        'code',
        'number',
        'capacity',
        'monthly_rate',
        'is_active',
    ];

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function dorm()
    {
        return $this->hasOneThrough(
            Dorm::class,
            Block::class,
            'id',      // FK di blocks (blocks.id)
            'id',      // FK di dorms  (dorms.id)
            'block_id', // FK lokal rooms.block_id
            'dorm_id'  // FK di blocks.dorm_id
        );
    }
}
