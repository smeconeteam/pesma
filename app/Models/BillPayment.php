<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    // METHODS

    public function verify(int $verifiedById): void
    {
        DB::transaction(function () use ($verifiedById) {
            // Update status payment
            $this->update([
                'status' => 'verified',
                'verified_by' => $verifiedById,
                'verified_at' => now(),
            ]);

            // Update tagihan
            $bill = $this->bill;

            if ($this->is_pic_payment && $this->notes) {
                preg_match_all('/Rp ([\d,.]+) untuk (BILL-[\w-]+)/', $this->notes, $matches);

                if (!empty($matches[1]) && !empty($matches[2])) {
                    foreach ($matches[2] as $index => $billNumber) {
                        $allocatedAmount = (int) str_replace(['.', ','], '', $matches[1][$index]);

                        $targetBill = Bill::where('bill_number', $billNumber)->first();
                        if ($targetBill) {
                            $targetBill->paid_amount += $allocatedAmount;
                            $targetBill->remaining_amount = max(0, $targetBill->total_amount - $targetBill->paid_amount);

                            if ($targetBill->remaining_amount <= 0) {
                                $targetBill->status = 'paid';
                            } elseif ($targetBill->paid_amount > 0) {
                                $targetBill->status = 'partial';
                            }

                            $targetBill->save();
                        }
                    }
                }
            }
            // Individual payment
            else {
                $bill->paid_amount += $this->amount;
                $bill->remaining_amount = max(0, $bill->total_amount - $bill->paid_amount);

                if ($bill->remaining_amount <= 0) {
                    $bill->status = 'paid';
                } elseif ($bill->paid_amount > 0) {
                    $bill->status = 'partial';
                }

                $bill->save();
            }

            // ✅ AUTO CREATE TRANSACTION (Pemasukan dari Billing)
            $this->createTransaction($verifiedById);
        });
    }

    public function reject(string $reason, int $verifiedById): void
    {
        DB::transaction(function () use ($reason, $verifiedById) {
            // Update status payment
            $this->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'verified_by' => $verifiedById,
                'verified_at' => now(),
            ]);

            // Delete transaction if exists
            $this->transaction()->delete();
        });
    }

    /**
     * Create transaction entry for verified payment
     */
    protected function createTransaction(int $createdById): void
    {
        // Skip if transaction already exists
        if ($this->transaction()->exists()) {
            return;
        }

        $bill = $this->bill;
        $room = $bill->room;
        
        // Determine payment method
        $paymentMethodType = $this->paymentMethod?->kind === 'cash' ? 'cash' : 'credit';

        Transaction::create([
            'type' => 'income',
            'name' => "Pembayaran {$bill->billingType->name} - {$this->paid_by_name}",
            'amount' => $this->amount,
            'payment_method' => $paymentMethodType,
            'transaction_date' => $this->payment_date,
            'notes' => $this->is_pic_payment 
                ? "Pembayaran PIC (Gabungan)\n{$this->notes}" 
                : "Pembayaran Bill #{$bill->bill_number}",
            'dorm_id' => $room?->block?->dorm_id,
            'block_id' => $room?->block_id,
            'bill_payment_id' => $this->id,
            'created_by' => $createdById,
        ]);
    }

    /**
     * Cek apakah payment bisa diedit
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Cek apakah payment bisa diverifikasi
     */
    public function canBeVerified(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Format status untuk display
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            default => $this->status,
        };
    }

    /**
     * Get payment type label
     */
    public function getPaymentTypeLabelAttribute(): string
    {
        return $this->is_pic_payment ? 'PIC (Gabungan)' : 'Individual';
    }

    /**
     * Get proof URL
     */
    public function getProofUrlAttribute(): ?string
    {
        if (!$this->proof_path) {
            return null;
        }
        
        return \Illuminate\Support\Facades\Storage::url($this->proof_path);
    }
}