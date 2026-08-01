<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Concerns\HasJsonOutput;
use App\Models\Category;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class CategoryDto implements JsonSerializable
{
    use HasJsonOutput;

    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            description: $category->description,
        );
    }

    public static function collect(Collection $models): array
    {
        return $models->map(fn (Category $model) => self::fromModel($model))->all();
    }
}
