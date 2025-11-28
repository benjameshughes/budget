<?php

namespace Database\Factories;

use App\Enums\BillCadence;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bill>
 */
class BillFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Rent', 'Electricity', 'Internet', 'Phone', 'Insurance', 'Subscription']),
            'amount' => fake()->randomFloat(2, 10, 500),
            'category_id' => null,
            'cadence' => fake()->randomElement(BillCadence::cases()),
            'day_of_month' => fake()->numberBetween(1, 28),
            'weekday' => fake()->numberBetween(0, 6),
            'interval_every' => 1,
            'start_date' => $startDate,
            'end_date' => null,
            'next_due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'autopay' => fake()->boolean(),
            'active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'cadence' => BillCadence::Weekly,
        ]);
    }

    public function biweekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'cadence' => BillCadence::Biweekly,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'cadence' => BillCadence::Monthly,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'cadence' => BillCadence::Yearly,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function autopay(): static
    {
        return $this->state(fn (array $attributes) => [
            'autopay' => true,
        ]);
    }

    public function withCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => Category::factory(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function dueOn(\DateTimeInterface|string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'next_due_date' => $date,
        ]);
    }
}
