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

    /**
     * Relasi ke PaymentMethod
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Relasi many-to-many ke ResidentCategory
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
     * Cek apakah bank account ini berlaku untuk kategori tertentu
     */
    public function isAvailableForCategory(?int $categoryId): bool
    {
        if (!$categoryId) {
            return true; // Jika tidak ada kategori, anggap available
        }

        return $this->residentCategories()
            ->where('resident_categories.id', $categoryId)
            ->exists();
    }

    /**
     * Get formatted bank info
     */
    public function getFormattedBankInfoAttribute(): string
    {
        return "{$this->bank_name} - {$this->account_number} ({$this->account_name})";
    }
}
