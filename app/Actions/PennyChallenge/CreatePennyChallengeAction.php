<?php

declare(strict_types=1);

namespace App\Actions\PennyChallenge;

use App\DataTransferObjects\Actions\CreatePennyChallengeData;
use App\Models\PennyChallenge;
use Illuminate\Support\Facades\DB;

final readonly class CreatePennyChallengeAction
{
    public function handle(CreatePennyChallengeData $data): PennyChallenge
    {
        return DB::transaction(function () use ($data) {
            $challenge = PennyChallenge::create([
                'user_id' => $data->userId,
                'name' => $data->name,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
            ]);

            $this->generateDays($challenge);

            return $challenge;
        });
    }

    private function generateDays(PennyChallenge $challenge): void
    {
        $totalDays = $challenge->totalDays();
        $days = [];
        $now = now();

        for ($day = 1; $day <= $totalDays; $day++) {
            $days[] = [
                'penny_challenge_id' => $challenge->id,
                'day_number' => $day,
                'deposited_at' => null,
                'transaction_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Batch insert for better performance
        foreach (array_chunk($days, 100) as $chunk) {
            $challenge->days()->insert($chunk);
        }
    }
}
