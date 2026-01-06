<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BillPayment extends Model
{
    protected $fillable = [
        'bill_id',
        'payment_number',
        'amount',
        'payment_date',
        'payment_method_id',
        'paid_by_user_id',
        'paid_by_name',
        'is_pic_payment',
        'proof_path',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payment_date' => 'date',
        'is_pic_payment' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
        });

        static::updated(function ($payment) {
            // Auto-update bill status ketika payment diverifikasi
            if ($payment->isDirty('status') && $payment->status === 'verified') {
                $payment->bill->updatePaymentStatus();
            }
        });

        static::deleting(function ($payment) {
            // Hapus bukti pembayaran dari storage
            if ($payment->proof_path && Storage::disk('public')->exists($payment->proof_path)) {
                Storage::disk('public')->delete($payment->proof_path);
            }
        });
    }

    // Relations
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
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

    // Helper Methods
    public static function generatePaymentNumber(): string
    {
        $date = now()->format('Ymd');
        $lastPayment = self::where('payment_number', 'LIKE', "PAY-{$date}-%")
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

    public function verify(User $admin): void
    {
        $this->status = 'verified';
        $this->verified_by = $admin->id;
        $this->verified_at = now();
        $this->save();

        // Update bill status
        $this->bill->updatePaymentStatus();
    }

    public function reject(User $admin, string $reason): void
    {
        $this->status = 'rejected';
        $this->verified_by = $admin->id;
        $this->verified_at = now();
        $this->rejection_reason = $reason;
        $this->save();
    }

    public function getProofUrlAttribute(): ?string
    {
        if ($this->proof_path) {
            return Storage::disk('public')->url($this->proof_path);
        }
        return null;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            default => '-',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'verified' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }
}
