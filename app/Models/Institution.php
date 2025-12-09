<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_number',
        'institution_name',
        'dormitory_name',
        'address',
        'phone',
        'email',
        'website',
        'logo_path',
    ];
}
