<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AutomationRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AutomationRuleLog>
 */
class AutomationRuleLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'automation_rule_id' => AutomationRule::factory(),
            'status' => 'success',
            'trigger_data' => ['trigger' => ['amount' => 50000]],
            'actions_executed' => [],
            'error_message' => null,
            'ran_at' => now(),
        ];
    }

    public function failed(string $error = 'Something went wrong'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
