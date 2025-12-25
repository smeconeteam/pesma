<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['iso2', 'iso3', 'name', 'calling_code'];

    public function residentsByNationality(): HasMany
    {
        return $this->hasMany(ResidentProfile::class, 'country_id');
    }

    public function registrationsByNationality(): HasMany
    {
        return $this->hasMany(Registration::class, 'country_id');
    }
}
