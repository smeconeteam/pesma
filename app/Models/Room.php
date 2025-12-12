<?php

namespace App\Models;

use Illuminate\Support\Str;
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

    public static function generateCode(
        string $dormName,
        string $blockName,
        string $roomTypeName,
        string $number
    ): string {
        $dormPart = Str::of($dormName)
            ->slug('')
            ->lower()
            ->toString();

        $blockPart = Str::upper(
            Str::of($blockName)->before(' ')->toString()
        );

        $typePart = Str::upper(
            Str::of($roomTypeName)->before(' ')->toString()
        );

        $numberPart = str_pad($number, 2, '0', STR_PAD_LEFT);

        return "{$dormPart}-{$blockPart}-{$typePart}-{$numberPart}";
    }
}
