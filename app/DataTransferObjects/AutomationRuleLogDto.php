<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\AutomationRuleLog;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class AutomationRuleLogDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $status,
        public ?array $triggerData,
        public ?array $actionsExecuted,
        public ?string $ranAt,
    ) {}

    public static function fromModel(AutomationRuleLog $log): self
    {
        return new self(
            id: $log->id,
            status: $log->status,
            triggerData: $log->trigger_data,
            actionsExecuted: $log->actions_executed,
            ranAt: $log->ran_at?->toIso8601String(),
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (AutomationRuleLog $model) => self::fromModel($model))->all();
    }
}
