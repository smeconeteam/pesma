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
        $roomTypes = RoomType::query()
            ->where('is_active', true)
            ->get();

        if ($roomTypes->isEmpty()) {
            $roomTypes = RoomType::all();
        }

        // ambil blocks + dorm
        $blocks = Block::with(['dorm'])
            ->get()
            ->filter(fn($block) => $block->dorm !== null);

        foreach ($blocks as $block) {
            $dorm = $block->dorm;

            $dormPart = Str::of($dorm->name)
                ->slug('')
                ->lower()
                ->toString();

            $blockPart = null;

            if (isset($block->code) && filled($block->code)) {
                $blockPart = Str::upper((string) $block->code);
            } else {
                $first = Str::of($block->name)->trim()->substr(0, 1)->toString();
                $blockPart = Str::upper($first ?: 'X');
            }

            foreach ($roomTypes as $roomType) {
                $typePart = Str::of($roomType->name)->before(' ')->upper()->toString();
                if ($typePart === '') {
                    $typePart = 'TYPE';
                }

                for ($i = 1; $i <= 3; $i++) {
                    $number = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $code = "{$dormPart}-{$blockPart}-{$typePart}-{$number}";

                    Room::updateOrCreate(
                        ['code' => $code],
                        [
                            'block_id'     => $block->id,
                            'room_type_id' => $roomType->id,
                            'number'       => $number,

                            'capacity'     => null,
                            'monthly_rate' => null,

                            'is_active'    => true,
                        ]
                    );
                }
            }
        }
    }
}
