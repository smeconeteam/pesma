<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Block extends Model
{
    use HasFactory;

    protected $fillable = [
        'dorm_id',
        'name',
        'description',
        'is_active',
    ];

    public function dorm()
    {
        return $this->belongsTo(Dorm::class);
    }

    public function adminScopes()
    {
        return $this->hasMany(AdminScope::class);
    }
}
