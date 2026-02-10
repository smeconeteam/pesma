<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'kind',
        'instructions',
        'qr_image_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(PaymentMethodBankAccount::class);
    }

    public function billPayments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    /**
     * Get the formatted name of the payment method
     */
    public function getNameAttribute(): string
    {
        return match ($this->kind) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            'cash' => 'Cash',
            default => ucfirst($this->kind ?? '-'),
        };
    }
}
