<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentProfile extends Model
{
    protected $fillable = [
        'user_id',
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
        'phone_country_id',
        'phone_number',
        'guardian_name',
        'guardian_phone_country_id',
        'guardian_phone_number',
        'check_in_date',
        'check_out_date',
        'photo_path',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function phoneCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'phone_country_id');
    }

    public function guardianPhoneCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'guardian_phone_country_id');
    }

    public function residentCategory(): BelongsTo
    {
        return $this->belongsTo(ResidentCategory::class, 'resident_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
