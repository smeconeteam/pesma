<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'applies_to_all',
        'is_active',
    ];

    protected $casts = [
        'applies_to_all' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi many-to-many dengan Dorm
     */
    public function dorms(): BelongsToMany
    {
        return $this->belongsToMany(Dorm::class, 'billing_type_dorm')
            ->withTimestamps();
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}