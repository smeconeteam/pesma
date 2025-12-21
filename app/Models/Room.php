<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

        $slug = function (string $value): string {
            return Str::of($value)
                ->lower()
                ->replace('asrama', '')      // opsional: buang kata "asrama"
                ->replace(['_', '.'], ' ')
                ->squish()
                ->replace(' ', '-')          // spasi -> dash
                ->toString();
        };

        $dormPart = $slug($dormName);

        $blockTwoWords = Str::of($blockName)
            ->trim()
            ->squish()
            ->explode(' ')
            ->take(2)
            ->implode(' ');

        $blockPart = $slug($blockTwoWords);

        $typeFirstWord = Str::of($roomTypeName)->before(' ')->toString();
        $typePart = $slug($typeFirstWord);

        $numberPart = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

        return "{$dormPart}-{$blockPart}-{$typePart}-{$numberPart}";
    }

    public function roomResidents(): HasMany
    {
        return $this->hasMany(RoomResident::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RoomHistory::class, 'room_id');
    }

    public function hasActiveResidents(): bool
    {
        return $this->roomResidents()
            ->whereNull('check_out_date')
            ->exists();
    }

    public function canBeDeleted(): bool
    {
        return ! $this->hasActiveResidents();
    }

    protected static function booted(): void
    {
        static::saving(function ($room) {
            if ($room->code) {
                $room->code = strtolower($room->code);
            }
        });
    }
}
