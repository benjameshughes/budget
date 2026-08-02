<?php

declare(strict_types=1);

namespace App\Actions\PennyChallenge;

use App\Models\PennyChallenge;

final readonly class DeletePennyChallengeAction
{
    public function handle(PennyChallenge $challenge): void
    {
        $challenge->days()->delete();
        $challenge->delete();
    }
}
