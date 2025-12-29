<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $fillable = [
        'bill_id',
        'billing_type_id',
        'description',
        'amount',
        'quantity',
    ];

    protected $casts = [
        'amount' => 'integer',
        'quantity' => 'integer',
    ];

    // Relations
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function billingType(): BelongsTo
    {
        return $this->belongsTo(BillingType::class);
    }

    // Helpers
    public function getTotalAttribute(): int
    {
        return $this->amount * $this->quantity;
    }
}