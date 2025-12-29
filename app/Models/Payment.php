<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill_id',
        'payment_number',
        'amount',
        'payment_method_id',
        'payment_date',
        'proof_path',
        'notes',
        'verified_by',
        'verified_at',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
    ];

    // Relations
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Helpers
    public static function generatePaymentNumber(): string
    {
        $date = now()->format('Ymd');
        $lastPayment = static::where('payment_number', 'like', "PAY-{$date}-%")
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "PAY-{$date}-{$newNumber}";
    }

    // Auto update bill when payment verified
    protected static function booted()
    {
        static::updated(function (Payment $payment) {
            if ($payment->wasChanged('status') && $payment->status === 'verified') {
                $bill = $payment->bill;
                $verifiedTotal = $bill->payments()->verified()->sum('amount');
                
                $bill->update(['paid_amount' => $verifiedTotal]);
                $bill->updatePaymentStatus();
            }
        });
    }
}