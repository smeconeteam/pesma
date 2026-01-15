<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillService
{
    /**
     * Generate tagihan untuk multiple penghuni (Individual)
     */
    public function generateIndividualBills(array $data): Collection
    {
        DB::beginTransaction();

        try {
            $bills = collect();
            $residents = $data['residents'];

            foreach ($residents as $resident) {
                // Untuk individual & room, semua otomatis selected
                // Untuk category, cek selected
                if (isset($resident['selected']) && !$resident['selected']) {
                    continue;
                }

                $baseAmount = $resident['amount'];
                $discountPercent = $resident['discount_percent'] ?? 0;
                $discountAmount = ($baseAmount * $discountPercent) / 100;
                $totalAmount = $baseAmount - $discountAmount;

                $bill = Bill::create([
                    'user_id' => $resident['user_id'],
                    'billing_type_id' => $data['billing_type_id'],
                    'room_id' => null,
                    'base_amount' => $baseAmount,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'remaining_amount' => $totalAmount,
                    'period_start' => $data['period_start'] ?? null,
                    'period_end' => $data['period_end'] ?? null,
                    'due_date' => null,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'issued',
                    'issued_by' => auth()->id(),
                    'issued_at' => now(),
                ]);

                $bills->push($bill);
            }

            DB::commit();
            return $bills;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate tagihan kamar (multi-bulan) dengan detail per bulan
     * Max 60 bulan (5 tahun)
     */
    public function generateRoomBills(array $data): Collection
    {
        DB::beginTransaction();

        try {
            // Validasi max 60 bulan
            $totalMonths = $data['total_months'];
            if ($totalMonths > 60) {
                throw new \Exception('Maksimal periode tagihan kamar adalah 60 bulan (5 tahun)');
            }

            $bills = collect();
            $roomId = $data['room_id'];
            $residents = $data['residents'];
            $periodStart = Carbon::parse($data['period_start']);
            $periodEnd = Carbon::parse($data['period_end']);
            $monthlyRate = $data['monthly_rate'];

            foreach ($residents as $resident) {
                if (!($resident['selected'] ?? false)) {
                    continue;
                }

                $discountPercent = $resident['discount_percent'] ?? 0;

                // Total untuk periode
                $totalForPeriod = $monthlyRate * $totalMonths;
                $discountAmount = ($totalForPeriod * $discountPercent) / 100;
                $totalAfterDiscount = $totalForPeriod - $discountAmount;

                // Buat bill
                $bill = Bill::create([
                    'user_id' => $resident['user_id'],
                    'billing_type_id' => $data['billing_type_id'],
                    'room_id' => $roomId,
                    'base_amount' => $totalForPeriod,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAfterDiscount,
                    'paid_amount' => 0,
                    'remaining_amount' => $totalAfterDiscount,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'due_date' => null, // Tagihan kamar tidak ada jatuh tempo
                    'notes' => $data['notes'] ?? null,
                    'status' => 'issued',
                    'issued_by' => auth()->id(),
                    'issued_at' => now(),
                ]);

                // Generate detail per bulan
                $currentMonth = $periodStart->copy();
                $monthlyAfterDiscount = round($totalAfterDiscount / $totalMonths);
                $discountPerMonth = round($discountAmount / $totalMonths);

                for ($i = 1; $i <= $totalMonths; $i++) {
                    // Untuk bulan terakhir, hitung sisa agar total pas
                    if ($i === $totalMonths) {
                        $sumPreviousMonths = BillDetail::where('bill_id', $bill->id)->sum('amount');
                        $monthlyAfterDiscount = $totalAfterDiscount - $sumPreviousMonths;

                        $sumPreviousDiscounts = BillDetail::where('bill_id', $bill->id)->sum('discount_amount');
                        $discountPerMonth = $discountAmount - $sumPreviousDiscounts;
                    }

                    BillDetail::create([
                        'bill_id' => $bill->id,
                        'month' => $i,
                        'description' => 'Bulan ' . $currentMonth->format('F Y'),
                        'base_amount' => $monthlyRate,
                        'discount_amount' => $discountPerMonth,
                        'amount' => $monthlyAfterDiscount,
                    ]);

                    $currentMonth->addMonth();
                }

                $bills->push($bill);
            }

            DB::commit();
            return $bills;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate tagihan berdasarkan kategori penghuni
     */
    public function generateCategoryBills(array $data): Collection
    {
        DB::beginTransaction();

        try {
            $bills = collect();
            $residents = $data['residents'];

            foreach ($residents as $resident) {
                if (!($resident['selected'] ?? false)) {
                    continue;
                }

                $baseAmount = $resident['amount'];
                $discountPercent = $resident['discount_percent'] ?? 0;
                $discountAmount = ($baseAmount * $discountPercent) / 100;
                $totalAmount = $baseAmount - $discountAmount;

                $bill = Bill::create([
                    'user_id' => $resident['user_id'],
                    'billing_type_id' => $data['billing_type_id'],
                    'room_id' => null,
                    'base_amount' => $baseAmount,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'remaining_amount' => $totalAmount,
                    'period_start' => $data['period_start'] ?? null,
                    'period_end' => $data['period_end'] ?? null,
                    'due_date' => null,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'issued',
                    'issued_by' => auth()->id(),
                    'issued_at' => now(),
                ]);

                $bills->push($bill);
            }

            DB::commit();
            return $bills;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate tagihan pendaftaran (auto saat approve registration)
     */
    public function generateRegistrationBill(User $user, array $data): Bill
    {
        return Bill::create([
            'user_id' => $user->id,
            'billing_type_id' => $data['billing_type_id'],
            'base_amount' => $data['amount'],
            'discount_percent' => $data['discount_percent'] ?? 0,
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
            'due_date' => null,
            'notes' => 'Biaya pendaftaran - ' . $user->name,
            'status' => 'issued',
            'issued_by' => auth()->id(),
            'issued_at' => now(),
        ]);
    }

    /**
     * Update status tagihan yang sudah lewat periode selesai (jatuh tempo)
     * Hanya untuk tagihan yang memiliki period_end
     */
    public function updateOverdueStatus(): int
    {
        return Bill::whereIn('status', ['issued', 'partial'])
            ->whereNotNull('period_end')
            ->where('period_end', '<', now())
            ->update(['status' => 'overdue']);
    }

    public function hasUnpaidBills(User $user): bool
    {
        return $user->bills()
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->exists();
    }

    public function getUnpaidBills(User $user): Collection
    {
        return $user->bills()
            ->with(['billingType', 'room'])
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->get();
    }

    public function getUserBillStats(User $user): array
    {
        $bills = $user->bills;
        $unpaidBills = $user->bills()->unpaid()->get();

        return [
            'total_bills' => $bills->count(),
            'unpaid_bills' => $unpaidBills->count(),
            'total_amount' => $bills->sum('total_amount'),
            'paid_amount' => $bills->sum('paid_amount'),
            'remaining_amount' => $unpaidBills->sum('remaining_amount'),
            'overdue_count' => $user->bills()->overdue()->count(),
        ];
    }

    public function getRoomBillStats(Room $room): array
    {
        $activeResidents = $room->activeResidents()->with('user.bills')->get();

        $totalAmount = 0;
        $paidAmount = 0;
        $remainingAmount = 0;

        foreach ($activeResidents as $resident) {
            $userBills = $resident->user->bills()
                ->where('room_id', $room->id)
                ->get();

            $totalAmount += $userBills->sum('total_amount');
            $paidAmount += $userBills->sum('paid_amount');
            $remainingAmount += $userBills->sum('remaining_amount');
        }

        return [
            'total_residents' => $activeResidents->count(),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
        ];
    }
}
