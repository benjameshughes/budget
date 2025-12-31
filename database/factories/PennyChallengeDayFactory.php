<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PennyChallenge;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PennyChallengeDay>
 */
class PennyChallengeDayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'penny_challenge_id' => PennyChallenge::factory(),
            'day_number' => fake()->numberBetween(1, 365),
            'deposited_at' => null,
            'transaction_id' => null,
        ];
    }

    public function forChallenge(PennyChallenge $challenge): static
    {
        return $this->state(fn (array $attributes) => [
            'penny_challenge_id' => $challenge->id,
        ]);
    }

    public function dayNumber(int $dayNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'day_number' => $dayNumber,
        ]);
    }

    public function deposited(): static
    {
        return $this->state(fn (array $attributes) => [
            'deposited_at' => now(),
        ]);
    }

    public function withTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'deposited_at' => $transaction->payment_date,
            'transaction_id' => $transaction->id,
        ]);
    }
}
