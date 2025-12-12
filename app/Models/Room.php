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
        ?string $dormName,
        ?string $blockName,
        ?string $roomTypeName,
        ?string $number
    ): ?string {
        if (! $dormName || ! $blockName || ! $roomTypeName || ! $number) {
            return null;
        }

        // cabangjakarta
        $dormPart = Str::of($dormName)
            ->lower()
            ->replace(['asrama', ' '], '')
            ->toString();

        // KomplekA (BUKAN KomplekACimahi)
        $blockPart = Str::of($blockName)
            ->trim()
            ->explode(' ')
            ->take(2)
            ->implode('');

        // vip / reguler / vvip
        $typePart = Str::of($roomTypeName)
            ->before(' ')
            ->lower()
            ->toString();

        // 03
        $numberPart = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

        return "{$dormPart}-{$blockPart}-{$typePart}-{$numberPart}";
    }


}
