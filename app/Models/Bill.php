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
        'bill_number',
        'user_id',
        'billing_type_id',
        'room_id',
        'base_amount',
        'discount_percent',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'period_start',
        'period_end',
        'due_date',
        'status',
        'notes',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'base_amount' => 'integer',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'integer',
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'remaining_amount' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'issued_at' => 'datetime',
    ];

    // Boot method untuk auto-generate bill_number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bill) {
            if (empty($bill->bill_number)) {
                $bill->bill_number = self::generateBillNumber();
            }

            // Auto-calculate amounts jika belum diset
            if (empty($bill->discount_amount)) {
                $bill->discount_amount = ($bill->base_amount * $bill->discount_percent) / 100;
            }

            if (empty($bill->total_amount)) {
                $bill->total_amount = $bill->base_amount - $bill->discount_amount;
            }

            if (!isset($bill->remaining_amount)) {
                $bill->remaining_amount = $bill->total_amount - $bill->paid_amount;
            }
        });
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function billingType(): BelongsTo
    {
        return $this->belongsTo(BillingType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(BillDetail::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
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
        return $query->whereIn('status', ['issued', 'partial', 'overdue']);
    }

    // Helper Methods
    public static function generateBillNumber(): string
    {
        $date = now()->format('Ymd');
        $lastBill = self::where('bill_number', 'LIKE', "{$date}-%")
            ->orderBy('bill_number', 'desc')
            ->first();

        if ($lastBill) {
            $lastNumber = (int) substr($lastBill->bill_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$date}-{$newNumber}";
    }

    public function updatePaymentStatus(): void
    {
        $this->paid_amount = $this->payments()
            ->where('status', 'verified')
            ->sum('amount');

        $this->remaining_amount = $this->total_amount - $this->paid_amount;

        // Update status
        if ($this->paid_amount == 0) {
            $this->status = 'issued';
        } elseif ($this->paid_amount > 0 && $this->paid_amount < $this->total_amount) {
            $this->status = 'partial';
        } elseif ($this->paid_amount >= $this->total_amount) {
            $this->status = 'paid';
        }

        $this->save();
    }

    public function getPaymentPercentageAttribute(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }
        return round(($this->paid_amount / $this->total_amount) * 100, 2);
    }

    public function isOverdue(): bool
    {
        // Jika tidak ada period_end (tak terbatas), tidak bisa overdue
        if (!$this->period_end) {
            return false;
        }

        return now()->gt($this->period_end) && $this->status !== 'paid';
    }

    public function markAsIssued(User $admin): void
    {
        $this->status = 'issued';
        $this->issued_by = $admin->id;
        $this->issued_at = now();
        $this->save();
    }

    public function canBeDeleted(): bool
    {
        // Tidak bisa dihapus jika sudah ada pembayaran
        return !$this->payments()->exists();
    }

    // Format currency untuk display
    public function getFormattedBaseAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->base_amount, 0, ',', '.');
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedPaidAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->paid_amount, 0, ',', '.');
    }

    public function getFormattedRemainingAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->remaining_amount, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'issued' => 'Tertagih',
            'partial' => 'Dibayar Sebagian',
            'paid' => 'Lunas',
            'overdue' => 'Jatuh Tempo',
            default => '-',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'issued' => 'warning',
            'partial' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
            default => 'gray',
        };
    }

    public function getDueDateDisplayAttribute(): string
    {
        if (!$this->period_end) {
            return 'Tak Terbatas';
        }

        return $this->period_end->format('d M Y');
    }
}
