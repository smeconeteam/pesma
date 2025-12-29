<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillingType;
use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BillGeneratorService
{
    /**
     * Generate tagihan pendaftaran untuk resident baru
     */
    public function generateRegistrationBill(
        User $user,
        ?int $dormId = null,
        ?string $voucherCode = null
    ): Bill {
        return DB::transaction(function () use ($user, $dormId, $voucherCode) {
            $profile = $user->residentProfile;

            if (!$profile) {
                throw new \Exception('User tidak memiliki profil resident');
            }

            // Ambil billing types yang berlaku
            $billingTypes = BillingType::query()
                ->where('is_active', true)
                ->where(function ($q) use ($dormId, $profile) {
                    // Applies to all
                    $q->where('applies_to_all', true)
                        // Atau yang spesifik
                        ->orWhere(function ($q2) use ($dormId, $profile) {
                            if ($dormId) {
                                $q2->whereHas('dorms', fn($dq) => $dq->where('dorms.id', $dormId));
                            }

                            if ($profile->resident_category_id) {
                                $q2->where(function ($q3) use ($profile) {
                                    $q3->where('resident_category_id', $profile->resident_category_id)
                                        ->orWhereNull('resident_category_id');
                                });
                            }
                        });
                })
                ->get();

            if ($billingTypes->isEmpty()) {
                throw new \Exception('Tidak ada billing type yang berlaku untuk resident ini');
            }

            // Hitung total
            $totalAmount = $billingTypes->sum('amount');
            $discountAmount = 0;
            $discount = null;

            // Cek voucher
            if ($voucherCode) {
                $discount = Discount::query()
                    ->where('voucher_code', $voucherCode)
                    ->where('is_active', true)
                    ->where(function ($q) use ($dormId) {
                        $q->where('applies_to_all', true)
                            ->orWhereHas('dorms', fn($dq) => $dq->where('dorms.id', $dormId));
                    })
                    ->whereDate(function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereNull('valid_from')
                                ->orWhereDate('valid_from', '<=', now());
                        })
                        ->where(function ($q3) {
                            $q3->whereNull('valid_until')
                                ->orWhereDate('valid_until', '>=', now());
                        });
                    })
                    ->first();

                if ($discount) {
                    if ($discount->type === 'percent') {
                        $discountAmount = intval($totalAmount * ($discount->percent / 100));
                    } else {
                        $discountAmount = min($discount->amount, $totalAmount);
                    }
                }
            }

            $finalAmount = $totalAmount - $discountAmount;

            // Buat Bill
            $bill = Bill::create([
                'user_id' => $user->id,
                'bill_number' => Bill::generateBillNumber('registration'),
                'bill_type' => 'registration',
                'room_id' => null,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'paid_amount' => 0,
                'status' => 'pending',
                'due_date' => null, // Unlimited
                'paid_by' => 'self',
                'is_split_bill' => false,
                'split_count' => null,
                'notes' => 'Tagihan pendaftaran penghuni baru',
                'issued_at' => now(),
            ]);

            // Buat Bill Items
            foreach ($billingTypes as $bt) {
                BillItem::create([
                    'bill_id' => $bill->id,
                    'billing_type_id' => $bt->id,
                    'description' => $bt->name,
                    'amount' => $bt->amount,
                    'quantity' => 1,
                ]);
            }

            // Catat penggunaan diskon
            if ($discount && $discountAmount > 0) {
                DiscountUsage::create([
                    'discount_id' => $discount->id,
                    'bill_id' => $bill->id,
                    'user_id' => $user->id,
                    'amount' => $discountAmount,
                    'used_at' => now(),
                ]);
            }

            return $bill;
        });
    }
}