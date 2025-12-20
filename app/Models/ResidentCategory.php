<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResidentCategory extends Model
{
    protected $fillable = ['name', 'description'];

    public function residentProfiles(): HasMany
    {
        return $this->hasMany(ResidentProfile::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'resident_category_id');
    }
}
