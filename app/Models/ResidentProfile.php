<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'resident_category_id',
        'is_international',
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
        'check_in_date',
        'check_out_date',
        'photo_path',
    ];

    protected $casts = [
        'birth_date'    => 'date',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'is_international' => 'boolean',
    ];

    public function residentCategory(): BelongsTo
    {
        return $this->belongsTo(ResidentCategory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
