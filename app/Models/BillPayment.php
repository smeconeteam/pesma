<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPayment extends Model
{
    protected $fillable = [
        'bill_id',
        'payment_number',
        'amount',
        'payment_date',
        'payment_method_id',
        'bank_account_id',
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
        'payment_date' => 'date',
        'verified_at' => 'datetime',
        'is_pic_payment' => 'boolean',
    ];

    // RELATIONSHIPS
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentMethodBankAccount::class, 'bank_account_id');
    }

    // METHODS

    /**
     * VERIFY - Simple: 1 payment = 1 bill
     */
    public function verify(int $verifiedById): void
    {
        DB::transaction(function () use ($verifiedById) {
            // Update status payment
            $this->update([
                'status' => 'verified',
                'verified_by' => $verifiedById,
                'verified_at' => now(),
            ]);

            // Update bill yang terkait
            $bill = $this->bill;

            if ($bill) {
                $bill->paid_amount += $this->amount;
                $bill->remaining_amount = max(0, $bill->total_amount - $bill->paid_amount);

                if ($bill->remaining_amount <= 0) {
                    $bill->status = 'paid';
                } elseif ($bill->paid_amount > 0) {
                    $bill->status = 'partial';
                }

                $bill->save();
            }

            // Buat transaction untuk arus kas
            $this->createTransaction();
        });
    }

    public function reject(string $reason, int $verifiedById): void
    {
        DB::transaction(function () use ($reason, $verifiedById) {
            $this->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'verified_by' => $verifiedById,
                'verified_at' => now(),
            ]);
        });
    }

    public function canBeEdited(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeVerified(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => __('payment-history.status_pending'),
            'verified' => __('payment-history.status_verified'),
            'rejected' => __('payment-history.status_rejected'),
            default => $this->status,
        };
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return $this->is_pic_payment ? 'PIC (Gabungan)' : 'Individual';
    }

    /**
     * Get semua payment dalam group PIC yang sama
     */
    public function getPicGroupPayments()
    {
        if (!$this->is_pic_payment) {
            return collect([$this]);
        }

        return self::where('payment_number', $this->payment_number)
            ->with(['bill.billingType', 'bill.user.residentProfile'])
            ->get();
    }

    /**
     * Get total amount untuk group PIC
     */
    public function getPicGroupTotalAttribute(): int
    {
        if (!$this->is_pic_payment) {
            return $this->amount;
        }

        return self::where('payment_number', $this->payment_number)
            ->sum('amount');
    }

    /**
     * Get jumlah bills dalam group PIC
     */
    public function getPicGroupCountAttribute(): int
    {
        if (!$this->is_pic_payment) {
            return 1;
        }

        return self::where('payment_number', $this->payment_number)->count();
    }

    /**
     * Get payment details untuk tampilan view (khusus PIC payment)
     */
    public function getPaymentDetails(): array
    {
        if (!$this->is_pic_payment) {
            return [];
        }

        return self::where('payment_number', $this->payment_number)
            ->with(['bill.user.residentProfile', 'bill.billingType'])
            ->get()
            ->map(function ($payment) {
                return [
                    'resident_name' => $payment->bill->user->residentProfile->full_name ?? $payment->bill->user->name,
                    'bill_number' => $payment->bill->bill_number,
                    'billing_type' => $payment->bill->billingType->name,
                    'amount' => $payment->amount,
                ];
            })
            ->toArray();
    }

    /**
     * Buat transaction untuk arus kas saat payment verified
     */
    protected function createTransaction(): void
    {
        // Jika sudah ada transaction, skip
        if ($this->transaction()->exists()) {
            return;
        }

        $bill = $this->bill()->with(['room.block.dorm', 'billingType'])->first();
        if (!$bill) {
            return;
        }

        $transactionData = [
            'type' => 'income',
            'name' => 'Pembayaran ' . $bill->billingType->name,
            'amount' => $this->amount,
            'payment_method' => $this->paymentMethod->kind === 'cash' ? 'cash' : 'credit',
            'transaction_date' => $this->payment_date,
            'notes' => "Pembayaran dari: {$this->paid_by_name}\nNo. Tagihan: {$bill->bill_number}\nNo. Pembayaran: {$this->payment_number}",
            'bill_payment_id' => $this->id,
            'created_by' => $this->verified_by,
        ];

        // Set dorm_id dan block_id jika ada
        if ($bill->room) {
            $transactionData['dorm_id'] = $bill->room->block->dorm_id;
            $transactionData['block_id'] = $bill->room->block_id;
        }

        \App\Models\Transaction::create($transactionData);
    }

    /**
     * Hapus transaction terkait saat payment dihapus
     */
    public function deleteTransaction(): void
    {
        $this->transaction()->delete();
    }

    /**
     * Relation to transaction
     */
    public function transaction()
    {
        return $this->hasOne(\App\Models\Transaction::class, 'bill_payment_id');
    }
}
