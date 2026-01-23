<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'amount',
        'payment_method',
        'transaction_date',
        'notes',
        'dorm_id',
        'block_id',
        'bill_payment_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'transaction_date' => 'date',
    ];

    // Relations
    public function dorm(): BelongsTo
    {
        return $this->belongsTo(Dorm::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function billPayment(): BelongsTo
    {
        return $this->belongsTo(BillPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeCash($query)
    {
        return $query->where('payment_method', 'cash');
    }

    public function scopeCredit($query)
    {
        return $query->where('payment_method', 'credit');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeForDorm($query, $dormId)
    {
        if ($dormId) {
            return $query->where('dorm_id', $dormId);
        }
        return $query;
    }

    public function scopeForBlock($query, $blockId)
    {
        if ($blockId) {
            return $query->where('block_id', $blockId);
        }
        return $query;
    }

    // Helper Methods
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            default => '-',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Tunai',
            'credit' => 'Kredit',
            default => '-',
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get running balance from start of transactions up to this one
     */
    public function getRunningBalance(): int
    {
        $query = self::where('transaction_date', '<=', $this->transaction_date)
            ->where(function($q) {
                $q->where('transaction_date', '<', $this->transaction_date)
                  ->orWhere(function($q2) {
                      $q2->where('transaction_date', '=', $this->transaction_date)
                         ->where('id', '<=', $this->id);
                  });
            });

        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');

        return $income - $expense;
    }

    /**
     * Static method to calculate balance for given filters
     */
    public static function calculateBalance($filters = [])
    {
        $query = self::query();

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('transaction_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['dorm_id'])) {
            $query->where('dorm_id', $filters['dorm_id']);
        }

        if (!empty($filters['block_id'])) {
            $query->where('block_id', $filters['block_id']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }
}