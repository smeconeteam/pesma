<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = Block::with(['dorm'])->get();
        $roomTypes = RoomType::all();

        if ($roomTypes->isEmpty() || $blocks->isEmpty()) {
            return;
        }

        foreach ($blocks as $block) {
            $dorm = $block->dorm;
            if (!$dorm) continue;

            // Generate room code prefix
            $dormSlug = Str::slug($dorm->name);
            $blockSlug = Str::slug($block->name);

            // Buat 2-3 kamar per room type per block
            foreach ($roomTypes as $roomType) {
                $typeSlug = Str::slug($roomType->name);

                for ($i = 1; $i <= 2; $i++) {
                    $number = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $code = "{$dormSlug}-{$blockSlug}-{$typeSlug}-{$number}";

                    Room::firstOrCreate(
                        ['code' => $code],
                        [
                            'block_id' => $block->id,
                            'room_type_id' => $roomType->id,
                            'number' => $number,
                            'capacity' => $roomType->default_capacity,
                            'monthly_rate' => $roomType->default_monthly_rate,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
