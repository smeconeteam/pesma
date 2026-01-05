<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResidentCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the rooms for this category
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'resident_category_id');
    }

    /**
     * Get the resident profiles for this category
     */
    public function residentProfiles(): HasMany
    {
        return $this->hasMany(ResidentProfile::class, 'resident_category_id');
    }

    /**
     * Get the registrations for this category
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'resident_category_id');
    }
}