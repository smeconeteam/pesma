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
    /**
     * Generate tagihan untuk single user
     */
    public function generateIndividualBill(array $data): Bill
    {
        DB::beginTransaction();

        try {
            $bill = Bill::create([
                'user_id' => $data['user_id'],
                'billing_type_id' => $data['billing_type_id'],
                'room_id' => $data['room_id'] ?? null,
                'base_amount' => $data['base_amount'],
                'discount_percent' => $data['discount_percent'] ?? 0,
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Jika ada detail items
            if (isset($data['details']) && is_array($data['details'])) {
                foreach ($data['details'] as $detail) {
                    BillDetail::create([
                        'bill_id' => $bill->id,
                        'month' => $detail['month'] ?? null,
                        'description' => $detail['description'],
                        'base_amount' => $detail['base_amount'],
                        'discount_amount' => $detail['discount_amount'] ?? 0,
                        'amount' => $detail['amount'],
                    ]);
                }
            }

            DB::commit();
            return $bill;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate tagihan kamar untuk periode tertentu (6 bulan)
     */
    public function generateRoomBills(array $data): Collection
    {
        DB::beginTransaction();

        try {
            $bills = collect();

            // Data yang dibutuhkan
            $roomIds = $data['room_ids']; // array of room IDs
            $billingTypeId = $data['billing_type_id'];
            $periodStart = Carbon::parse($data['period_start']);
            $periodEnd = Carbon::parse($data['period_end']);
            $monthlyRate = $data['monthly_rate']; // harga per bulan per kamar
            $discountPercent = $data['discount_percent'] ?? 0;
            $dueDate = Carbon::parse($data['due_date']);

            // Hitung jumlah bulan
            $totalMonths = $periodStart->diffInMonths($periodEnd) + 1;

            foreach ($roomIds as $roomId) {
                $room = Room::with('activeResidents.user')->findOrFail($roomId);

                // Skip jika tidak ada penghuni aktif
                if ($room->activeResidents->isEmpty()) {
                    continue;
                }

                $totalResidents = $room->activeResidents->count();

                // Hitung per penghuni
                $totalForPeriod = $monthlyRate * $totalMonths;
                $discountAmount = ($totalForPeriod * $discountPercent) / 100;
                $totalAfterDiscount = $totalForPeriod - $discountAmount;
                $perResident = $totalAfterDiscount / $totalResidents;
                $monthlyPerResident = $perResident / $totalMonths;

                // Generate bill untuk setiap penghuni
                foreach ($room->activeResidents as $resident) {
                    $bill = Bill::create([
                        'user_id' => $resident->user_id,
                        'billing_type_id' => $billingTypeId,
                        'room_id' => $roomId,
                        'base_amount' => ($monthlyRate * $totalMonths) / $totalResidents,
                        'discount_percent' => $discountPercent,
                        'discount_amount' => $discountAmount / $totalResidents,
                        'total_amount' => $perResident,
                        'paid_amount' => 0,
                        'remaining_amount' => $perResident,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'due_date' => $dueDate,
                        'notes' => $data['notes'] ?? null,
                        'status' => 'draft',
                    ]);

                    // Generate detail per bulan
                    $currentMonth = $periodStart->copy();
                    for ($i = 1; $i <= $totalMonths; $i++) {
                        $monthlyBase = $monthlyRate / $totalResidents;
                        $monthlyDiscount = ($monthlyBase * $discountPercent) / 100;

                        BillDetail::create([
                            'bill_id' => $bill->id,
                            'month' => $i,
                            'description' => $room->code . ' - ' . $currentMonth->format('F Y'),
                            'base_amount' => $monthlyBase,
                            'discount_amount' => $monthlyDiscount,
                            'amount' => $monthlyBase - $monthlyDiscount,
                        ]);

                        $currentMonth->addMonth();
                    }

                    $bills->push($bill);
                }
            }

            DB::commit();
            return $bills;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate tagihan pendaftaran saat approve registration
     */
    public function generateRegistrationBill(User $user, array $data): Bill
    {
        return $this->generateIndividualBill([
            'user_id' => $user->id,
            'billing_type_id' => $data['billing_type_id'],
            'base_amount' => $data['amount'],
            'discount_percent' => $data['discount_percent'] ?? 0,
            'due_date' => $data['due_date'],
            'notes' => 'Biaya pendaftaran - ' . $user->name,
        ]);
    }

    /**
     * Generate tagihan pengembangan
     */
    public function generateDevelopmentBill(User $user, array $data): Bill
    {
        return $this->generateIndividualBill([
            'user_id' => $user->id,
            'billing_type_id' => $data['billing_type_id'],
            'base_amount' => $data['amount'],
            'discount_percent' => $data['discount_percent'] ?? 0,
            'due_date' => $data['due_date'],
            'notes' => $data['notes'] ?? 'Biaya pengembangan asrama',
        ]);
    }

    /**
     * Issue tagihan (ubah status dari draft ke issued)
     */
    public function issueBill(Bill $bill, User $admin): void
    {
        $bill->markAsIssued($admin);
    }

    /**
     * Issue multiple bills sekaligus
     */
    public function issueBills(array $billIds, User $admin): int
    {
        $updated = 0;

        foreach ($billIds as $billId) {
            $bill = Bill::find($billId);
            if ($bill && $bill->status === 'draft') {
                $this->issueBill($bill, $admin);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Check dan update status overdue untuk semua tagihan
     */
    public function updateOverdueStatus(): int
    {
        return Bill::whereIn('status', ['issued', 'partial'])
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }

    /**
     * Get statistik tagihan untuk user
     */
    public function getUserBillStats(User $user): array
    {
        return [
            'total_bills' => $user->bills()->count(),
            'unpaid_bills' => $user->bills()->unpaid()->count(),
            'total_amount' => $user->bills()->sum('total_amount'),
            'paid_amount' => $user->bills()->sum('paid_amount'),
            'remaining_amount' => $user->bills()->unpaid()->sum('remaining_amount'),
            'overdue_count' => $user->bills()->overdue()->count(),
        ];
    }

    /**
     * Get statistik tagihan untuk room
     */
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
