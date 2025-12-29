<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillingType;
use App\Models\Room;
use App\Models\RoomResident;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyBills extends Command
{
    protected $signature = 'bills:generate-monthly
                            {--month= : Month to generate (Y-m format, default: next month)}
                            {--dry-run : Show what would be generated without saving}';

    protected $description = 'Generate tagihan bulanan untuk semua kamar yang aktif';

    public function handle()
    {
        $month = $this->option('month') ?: now()->addMonth()->format('Y-m');
        $dryRun = $this->option('dry-run');

        $this->info("Generating monthly bills for: {$month}");

        if ($dryRun) {
            $this->warn("DRY RUN MODE - No data will be saved");
        }

        $startOfMonth = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Ambil semua kamar yang punya penghuni aktif
        $rooms = Room::query()
            ->where('is_active', true)
            ->whereHas('activeRoomResidents')
            ->with(['activeRoomResidents.user.residentProfile', 'block.dorm', 'roomType'])
            ->get();

        if ($rooms->isEmpty()) {
            $this->warn('Tidak ada kamar dengan penghuni aktif');
            return 0;
        }

        $this->info("Found {$rooms->count()} rooms with active residents");

        $generated = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($rooms as $room) {
                $activeResidents = $room->activeRoomResidents;
                $residentCount = $activeResidents->count();

                if ($residentCount === 0) {
                    continue;
                }

                // Ambil PIC
                $pic = $activeResidents->firstWhere('is_pic', true);

                if (!$pic) {
                    $this->warn("Room {$room->code} tidak punya PIC, skip");
                    $skipped++;
                    continue;
                }

                // Cek apakah sudah ada tagihan untuk bulan ini
                $exists = Bill::query()
                    ->where('user_id', $pic->user_id)
                    ->where('room_id', $room->id)
                    ->where('bill_type', 'monthly_room')
                    ->whereYear('issued_at', $startOfMonth->year)
                    ->whereMonth('issued_at', $startOfMonth->month)
                    ->exists();

                if ($exists) {
                    $this->warn("Room {$room->code} sudah punya tagihan bulan ini, skip");
                    $skipped++;
                    continue;
                }

                // Hitung tarif kamar (split bill)
                $monthlyRate = $room->monthly_rate ?? $room->roomType->default_monthly_rate ?? 0;
                $splitAmount = $residentCount > 1 ? intval($monthlyRate / $residentCount) : $monthlyRate;

                $totalAmount = $splitAmount;
                $items = [];

                // Item: Tarif Kamar
                $items[] = [
                    'billing_type_id' => null,
                    'description' => "Tarif Kamar {$room->code} (Split {$residentCount} orang)",
                    'amount' => $splitAmount,
                    'quantity' => 1,
                ];

                // Ambil billing types yang berlaku
                $billingTypes = BillingType::query()
                    ->where('is_active', true)
                    ->where(function ($q) use ($room, $pic) {
                        $q->where('applies_to_all', true)
                            ->orWhere(function ($q2) use ($room, $pic) {
                                $profile = $pic->user->residentProfile;
                                $dormId = $room->block->dorm_id;

                                if ($profile && $profile->resident_category_id) {
                                    $q2->where('resident_category_id', $profile->resident_category_id);
                                }

                                $q2->whereHas('dorms', fn($dq) => $dq->where('dorms.id', $dormId));
                            });
                    })
                    ->get();

                // Tambahkan billing types ke items
                foreach ($billingTypes as $bt) {
                    $items[] = [
                        'billing_type_id' => $bt->id,
                        'description' => $bt->name,
                        'amount' => $bt->amount,
                        'quantity' => 1,
                    ];

                    $totalAmount += $bt->amount;
                }

                if ($dryRun) {
                    $this->line("Would generate: Room {$room->code} | PIC: {$pic->user->name} | Total: Rp " . number_format($totalAmount));
                    $generated++;
                    continue;
                }

                // Buat Bill
                $bill = Bill::create([
                    'user_id' => $pic->user_id,
                    'bill_number' => Bill::generateBillNumber('monthly_room'),
                    'bill_type' => 'monthly_room',
                    'room_id' => $room->id,
                    'total_amount' => $totalAmount,
                    'discount_amount' => 0,
                    'final_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'due_date' => $endOfMonth->toDateString(),
                    'paid_by' => 'pic',
                    'is_split_bill' => $residentCount > 1,
                    'split_count' => $residentCount,
                    'notes' => "Tagihan bulanan untuk periode {$startOfMonth->format('F Y')}",
                    'issued_at' => now(),
                ]);

                // Buat Bill Items
                foreach ($items as $itemData) {
                    BillItem::create(array_merge(['bill_id' => $bill->id], $itemData));
                }

                $this->info("Generated: Room {$room->code} | PIC: {$pic->user->name} | Total: Rp " . number_format($totalAmount));
                $generated++;
            }

            if (!$dryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            $this->info("\nSummary:");
            $this->info("Generated: {$generated}");
            $this->info("Skipped: {$skipped}");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}