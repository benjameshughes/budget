<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Enums\TriggerEvent;
use App\Models\AutomationRule;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class AutomationRuleDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $name,
        public TriggerEvent $triggerEvent,
        public ?array $triggerConditions,
        public ?array $actions,
        public bool $isActive,
        public int $runCount,
        public ?string $lastRunAt,
        public ?array $logs,
        public string $createdAt,
    ) {}

    public static function fromModel(AutomationRule $rule): self
    {
        return new self(
            id: $rule->id,
            name: $rule->name,
            triggerEvent: $rule->trigger_event,
            triggerConditions: $rule->trigger_conditions,
            actions: $rule->actions,
            isActive: (bool) $rule->is_active,
            runCount: $rule->run_count,
            lastRunAt: $rule->last_run_at?->toIso8601String(),
            logs: $rule->relationLoaded('logs')
                ? AutomationRuleLogDto::collect($rule->logs)
                : null,
            createdAt: $rule->created_at->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (AutomationRule $model) => self::fromModel($model))->all();
    }
}
