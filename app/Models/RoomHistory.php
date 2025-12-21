<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomHistory extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'room_resident_id',
        'check_in_date',
        'check_out_date',
        'is_pic',
        'movement_type',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'is_pic' => 'boolean',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function roomResident(): BelongsTo
    {
        return $this->belongsTo(RoomResident::class, 'room_resident_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Scopes untuk query optimization
    public function scopeWithRelations($query)
    {
        return $query->with([
            'room.block.dorm',
            'recordedBy',
        ]);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('check_in_date', 'desc');
    }

    // Helper methods
    public function getDurationAttribute()
    {
        if (!$this->check_in_date) {
            return '-';
        }

        if (!$this->check_out_date) {
            $days = now()->diffInDays($this->check_in_date);
        } else {
            $days = $this->check_in_date->diffInDays($this->check_out_date);
        }

        if ($days === 0) {
            return '< 1 hari';
        }

        $months = floor($days / 30);
        $remainingDays = $days % 30;

        if ($months > 0) {
            return $months . ' bulan ' . $remainingDays . ' hari';
        }

        return $days . ' hari';
    }

    public function getMovementTypeLabelAttribute()
    {
        return match ($this->movement_type) {
            'new' => 'Masuk Baru',
            'transfer' => 'Pindah Kamar',
            'checkout' => 'Keluar',
            default => '-',
        };
    }

    public function getRoomInfoAttribute()
    {
        if (!$this->relationLoaded('room')) {
            return $this->room->code ?? '-';
        }

        $room = $this->room;
        if (!$room) return '-';

        $block = $room->relationLoaded('block') ? $room->block : null;
        $dorm = $block && $block->relationLoaded('dorm') ? $block->dorm : null;

        if (!$dorm || !$block) return $room->code;

        return "{$dorm->name} - {$block->name} - {$room->code}";
    }
}
