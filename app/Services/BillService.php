<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\Room;
use App\Models\User;
use App\Models\BillingType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillService
{
    // GENERATE TAGIHAN PER PENGHUNI
    public function generateMultipleResidentBills(array $data): Collection
    {
        DB::beginTransaction();

        try {
            $bills = collect();
            $userIds = $data['user_ids'];

            foreach ($userIds as $userId) {
                $bill = Bill::create([
                    'user_id' => $userId,
                    'billing_type_id' => $data['billing_type_id'],
                    'room_id' => $data['room_id'] ?? null,
                    'base_amount' => $data['base_amount'],
                    'discount_percent' => $data['discount_percent'] ?? 0,
                    'period_start' => $data['period_start'] ?? null,
                    'period_end' => $data['period_end'] ?? null,
                    'due_date' => $data['due_date'],
                    'notes' => $data['notes'] ?? null,
                    // status 'issued', issued_by, issued_at akan auto-set di boot method
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

    // GENERATE TAGIHAN PER KAMAR
    public function generateRoomBills(array $data): Collection
    {
        DB::beginTransaction();

        try {
            $bills = collect();
            $roomId = $data['room_id'];
            $residents = $data['residents']; // array of [user_id, amount, discount_percent]

            foreach ($residents as $resident) {
                $bill = Bill::create([
                    'user_id' => $resident['user_id'],
                    'billing_type_id' => $data['billing_type_id'],
                    'room_id' => $roomId,
                    'base_amount' => $resident['amount'],
                    'discount_percent' => $resident['discount_percent'] ?? 0,
                    'period_start' => $data['period_start'] ?? null,
                    'period_end' => $data['period_end'] ?? null,
                    'due_date' => $data['due_date'],
                    'notes' => $data['notes'] ?? null,
                    // status 'issued', issued_by, issued_at akan auto-set di boot method
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


    // AUTO-GENERATE TAGIHAN PENDAFTARAN
    public function generateRegistrationBill(User $user, array $data): Bill
    {
        return Bill::create([
            'user_id' => $user->id,
            'billing_type_id' => $data['billing_type_id'],
            'base_amount' => $data['amount'],
            'discount_percent' => $data['discount_percent'] ?? 0,
            'due_date' => $data['due_date'],
            'notes' => 'Biaya pendaftaran - ' . $user->name,
        ]);
    }

    // UPDATE STATUS TAGIHAN YANG OVERDUE
    public function updateOverdueStatus(): int
    {
        return Bill::whereIn('status', ['issued', 'partial'])
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }

    // CHECK UNPAID BILLS
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

    // GET STATISTIK
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

    // GENERATE TAGIHAN KAMAR DENGAN PERIODE MULTI-BULAN
    public function generateMultiMonthRoomBills(array $data): Collection
    {
        DB::beginTransaction();

        try {
            $bills = collect();

            $roomId = $data['room_id'];
            $residents = $data['residents'];
            $periodStart = Carbon::parse($data['period_start']);
            $periodEnd = Carbon::parse($data['period_end']);
            $monthlyRate = $data['monthly_rate'];
            $discountPercent = $data['discount_percent'] ?? 0;

            // Hitung jumlah bulan
            $totalMonths = $periodStart->diffInMonths($periodEnd) + 1;

            foreach ($residents as $resident) {
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
                    'due_date' => $data['due_date'],
                    'notes' => $data['notes'] ?? null,
                ]);

                // Generate detail per bulan
                $currentMonth = $periodStart->copy();
                $monthlyAfterDiscount = $totalAfterDiscount / $totalMonths;

                for ($i = 1; $i <= $totalMonths; $i++) {
                    BillDetail::create([
                        'bill_id' => $bill->id,
                        'month' => $i,
                        'description' => 'Bulan ' . $currentMonth->format('F Y'),
                        'base_amount' => $monthlyRate,
                        'discount_amount' => ($monthlyRate * $discountPercent) / 100,
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
}
