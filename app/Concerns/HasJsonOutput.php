<?php

declare(strict_types=1);

namespace App\Concerns;

use BackedEnum;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * @mixin JsonSerializable
 */
trait HasJsonOutput
{
    public function toArray(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            $data[$key] = $this->resolveValue($value);
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function resolveValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof Carbon, $value instanceof DateTimeInterface => $value->format('Y-m-d\TH:i:sP'),
            is_object($value) && method_exists($value, 'toArray') => $value->toArray(),
            $value instanceof Collection => $value->map(fn ($item) => $this->resolveValue($item))->all(),
            is_array($value) => array_map(fn ($item) => $this->resolveValue($item), $value),
            default => $value,
        };
    }
}
