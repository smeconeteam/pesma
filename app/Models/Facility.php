<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Facility extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
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

    // Update boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($facility) {
            $facility->slug = Str::slug($facility->type . '-' . $facility->name);
        });

        static::updating(function ($facility) {
            $facility->slug = Str::slug($facility->type . '-' . $facility->name);
        });
    }
}