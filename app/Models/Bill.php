<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Bill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'bill_number',
        'bill_type',
        'room_id',
        'total_amount',
        'discount_amount',
        'final_amount',
        'paid_amount',
        'status',
        'due_date',
        'paid_by',
        'is_split_bill',
        'split_count',
        'notes',
        'issued_at',
        'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'discount_amount' => 'integer',
        'final_amount' => 'integer',
        'paid_amount' => 'integer',
        'is_split_bill' => 'boolean',
        'split_count' => 'integer',
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function discountUsages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'partial', 'overdue']);
    }

    // Helpers
    public function getRemainingAmountAttribute(): int
    {
        return max(0, $this->final_amount - $this->paid_amount);
    }

    public function isFullyPaid(): bool
    {
        return $this->paid_amount >= $this->final_amount;
    }

    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->status !== 'paid' && now()->isAfter($this->due_date);
    }

    public function updatePaymentStatus(): void
    {
        if ($this->isFullyPaid()) {
            $this->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } elseif ($this->paid_amount > 0) {
            $this->update([
                'status' => $this->isOverdue() ? 'overdue' : 'partial',
            ]);
        } elseif ($this->isOverdue()) {
            $this->update([
                'status' => 'overdue',
            ]);
        }
    }

    public static function generateBillNumber(string $type = 'custom'): string
    {
        $prefix = match($type) {
            'registration' => 'REG',
            'monthly_room' => 'MR',
            default => 'BILL',
        };

        $date = now()->format('Ymd');
        $lastBill = static::where('bill_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('bill_number', 'desc')
            ->first();

        if ($lastBill) {
            $lastNumber = (int) substr($lastBill->bill_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$date}-{$newNumber}";
    }

    // Auto update status overdue
    protected static function booted()
    {
        static::saving(function (Bill $bill) {
            if ($bill->isDirty('paid_amount') || $bill->isDirty('final_amount')) {
                // Akan diupdate manual via updatePaymentStatus()
            }
        });
    }
}