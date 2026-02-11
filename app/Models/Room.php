<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'block_id',
        'room_type_id',
        'resident_category_id',
        'code',
        'number',
        'capacity',
        'monthly_rate',
        'is_active',
        'thumbnail',
        'images',
        'width',
        'length',
        'contact_person_name',
        'contact_person_number',
        'contact_person_user_id',
    ];

    // Relasi ke Admin Cabang sebagai Penanggung Jawab
    public function contactPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contact_person_user_id');
    }

    protected $casts = [
        'capacity' => 'integer',
        'monthly_rate' => 'integer',
        'is_active' => 'boolean',
        'images' => 'array',
        'width' => 'decimal:2',
        'length' => 'decimal:2',
    ];

    // Relations
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function residentCategory(): BelongsTo
    {
        return $this->belongsTo(ResidentCategory::class, 'resident_category_id');
    }

    public function roomResidents(): HasMany
    {
        return $this->hasMany(RoomResident::class, 'room_id');
    }

    public function activeRoomResidents(): HasMany
    {
        return $this->roomResidents()->whereNull('check_out_date');
    }

    public function activeResidents(): HasMany
    {
        return $this->hasMany(RoomResident::class)
            ->whereNull('check_out_date');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function activeBills(): HasMany
    {
        return $this->bills()
            ->whereIn('status', ['issued', 'partial', 'overdue']);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class);
    }

    public function parkingFacilities(): BelongsToMany
    {
        return $this->facilities()->where('type', 'parkir');
    }

    public function generalFacilities(): BelongsToMany
    {
        return $this->facilities()->where('type', 'umum');
    }

    public function bathroomFacilities(): BelongsToMany
    {
        return $this->facilities()->where('type', 'kamar_mandi');
    }

    public function roomFacilities(): BelongsToMany
    {
        return $this->facilities()->where('type', 'kamar');
    }

    public function roomRules(): BelongsToMany
    {
        return $this->belongsToMany(RoomRule::class, 'room_room_rule');
    }

    // Helper methods
    public function isEmpty(): bool
    {
        return ! $this->activeRoomResidents()->exists();
    }

    public function getAvailableCapacityAttribute(): int
    {
        $activeCount = $this->activeResidents()->count();
        return max(0, ($this->capacity ?? 0) - $activeCount);
    }

    public function getActiveGenderAttribute(): ?string
    {
        return RoomResident::query()
            ->where('room_residents.room_id', $this->id)
            ->whereNull('room_residents.check_out_date')
            ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
            ->value('resident_profiles.gender');
    }

    public function canAcceptGender(string $gender): bool
    {
        $activeGender = $this->getActiveGenderAttribute();

        // Jika kosong, bisa terima gender apapun
        if (!$activeGender) return true;

        // Jika sudah ada penghuni, harus sama gendernya
        return $activeGender === $gender;
    }

    public function isFull(): bool
    {
        return $this->getAvailableCapacityAttribute() <= 0;
    }

    public static function generateCode(string $dormName, string $blockName, string $number): string
    {
        // Format: cabang-komplek-nomor
        // Contoh: coba-komplek-01
        $code = Str::slug($dormName) . '-' . Str::slug($blockName) . '-' . $number;

        return strtolower($code);
    }
}