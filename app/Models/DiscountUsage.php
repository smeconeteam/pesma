<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountUsage extends Model
{
    protected $fillable = [
        'discount_id',
        'bill_id',
        'user_id',
        'amount',
        'used_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'used_at' => 'datetime',
    ];

    // Relations
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}