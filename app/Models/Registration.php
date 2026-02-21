<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'status',
        'rejection_reason',
        'email',
        'name',
        'password',
        'resident_category_id',
        'citizenship_status',
        'country_id',
        'national_id',
        'student_id',
        'full_name',
        'gender',
        'birth_place',
        'birth_date',
        'university_school',
        'phone_number',
        'guardian_name',
        'guardian_phone_number',
        'address',
        'photo_path',
        'preferred_dorm_id',
        'preferred_room_type_id',
        'preferred_room_id',
        'planned_check_in_date',
        'approved_by',
        'approved_at',
        'user_id',
        'created_at', // ✅ Tambahkan ini agar bisa diisi manual
        'updated_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'planned_check_in_date' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    // Relations
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function residentCategory(): BelongsTo
    {
        return $this->belongsTo(ResidentCategory::class, 'resident_category_id');
    }

    public function preferredDorm(): BelongsTo
    {
        return $this->belongsTo(Dorm::class, 'preferred_dorm_id');
    }

    public function preferredRoomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'preferred_room_type_id');
    }

    public function preferredRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'preferred_room_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function hasRegistrationBill(): bool
    {
        return $this->bills()
            ->whereHas('billingType', function ($q) {
                $q->where('name', 'Biaya Pendaftaran');
            })
            ->exists();
    }

    public function getRegistrationBill()
    {
        return $this->bills()
            ->whereHas('billingType', function ($q) {
                $q->where('name', 'Biaya Pendaftaran');
            })
            ->first();
    }
}
