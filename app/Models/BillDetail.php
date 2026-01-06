<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillDetail extends Model
{
    protected $fillable = [
        'bill_id',
        'month',
        'description',
        'base_amount',
        'discount_amount',
        'amount',
    ];

    protected $casts = [
        'month' => 'integer',
        'base_amount' => 'integer',
        'discount_amount' => 'integer',
        'amount' => 'integer',
    ];

    // Relations
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    // Helper Methods
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getFormattedBaseAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->base_amount, 0, ',', '.');
    }

    public function getFormattedDiscountAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->discount_amount, 0, ',', '.');
    }
}
