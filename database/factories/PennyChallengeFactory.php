<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PennyChallenge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PennyChallenge>
 */
class PennyChallengeFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->numberBetween(2024, 2027);

        return [
            'user_id' => User::factory(),
            'name' => "{$year} 1p Challenge",
            'start_date' => Carbon::create($year, 1, 1),
            'end_date' => Carbon::create($year, 12, 31),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function for2026(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '2026 1p Challenge',
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 12, 31),
        ]);
    }

    /**
     * Create challenge with all days pre-generated.
     */
    public function withDays(): static
    {
        return $this->afterCreating(function (PennyChallenge $challenge) {
            $totalDays = $challenge->totalDays();

            for ($day = 1; $day <= $totalDays; $day++) {
                $challenge->days()->create([
                    'day_number' => $day,
                    'deposited_at' => null,
                    'transaction_id' => null,
                ]);
            }
        });
    }
}
