<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PaymentMethodBankAccount extends Model
{
    protected $fillable = [
        'payment_method_id',
        'bank_name',
        'account_number',
        'account_name',
        'account_holder',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the resident categories that use this bank account
     */
    public function residentCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            ResidentCategory::class,
            'resident_category_bank_account',
            'payment_method_bank_account_id',
            'resident_category_id'
        )->withTimestamps();
    }

    /**
     * Get display name for the bank account
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [$this->bank_name, $this->account_number];
        
        if ($this->account_holder) {
            $parts[] = "({$this->account_holder})";
        } else {
            $parts[] = "({$this->account_name})";
        }
        
        return implode(' - ', $parts);
    }
}