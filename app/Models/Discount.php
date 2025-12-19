<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',            // percent|fixed
        'percent',
        'amount',
        'voucher_code',    // ✅ baru
        'valid_from',      // ✅ baru (date)
        'valid_until',     // ✅ baru (date)
        'applies_to_all',
        'is_active',
        'description',
    ];

    protected $casts = [
        // pakai float supaya tidak dipaksa selalu 2 desimal di DB-level casting
        'percent' => 'float',
        'amount' => 'integer',

        // ✅ date casts
        'valid_from' => 'date',
        'valid_until' => 'date',

        'applies_to_all' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function dorms(): BelongsToMany
    {
        return $this->belongsToMany(Dorm::class, 'discount_dorm')->withTimestamps();
    }
}
