<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RoomRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($roomRule) {
            $roomRule->slug = Str::slug($roomRule->name);
        });

        static::updating(function ($roomRule) {
            $roomRule->slug = Str::slug($roomRule->name);
        });
    }
}
