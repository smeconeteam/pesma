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

    // Helper methods
    public function getDurationAttribute()
    {
        if (!$this->check_out_date) {
            return 'Masih menempati';
        }

        $days = $this->check_in_date->diffInDays($this->check_out_date);
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
}
