<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'amount',          // ✅ WAJIB
        'applies_to_all',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'integer',      // ✅ biar tegas
        'applies_to_all' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function dorms(): BelongsToMany
    {
        return $this->belongsToMany(Dorm::class, 'billing_type_dorm')->withTimestamps();
    }
}
