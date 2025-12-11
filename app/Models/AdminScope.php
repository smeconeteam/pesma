<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminScope extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'dorm_id',
        'block_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dorm()
    {
        return $this->belongsTo(Dorm::class);
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
    }
}
