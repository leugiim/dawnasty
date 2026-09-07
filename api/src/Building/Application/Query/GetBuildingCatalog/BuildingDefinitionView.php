<?php

declare(strict_types=1);

namespace App\Building\Application\Query\GetBuildingCatalog;

use App\Building\Domain\BuildingDefinition;
use App\_Shared\Domain\ApiResult;

final readonly class BuildingDefinitionView implements ApiResult
{
    /**
     * @param list<array{
     *     level: int,
     *     cost: array<string, int>,
     *     housingCapacity: int,
     *     producedResource: string|null,
     *     producedResourcePerHour: int,
     *     villagerProductionPerHour: int,
     * }> $levels
     */
    public function __construct(
        public string $type,
        public bool $producesFood,
        public array $levels,
    ) {
    }

    public static function fromDefinition(BuildingDefinition $definition): self
    {
        return new self(
            type: $definition->type->value,
            producesFood: $definition->producesFood,
            levels: array_map(
                static fn ($level): array => [
                    'level' => $level->level,
                    'cost' => $level->cost,
                    'housingCapacity' => $level->housingCapacity,
                    'producedResource' => $level->producedResource?->value,
                    'producedResourcePerHour' => $level->producedResourcePerHour,
                    'villagerProductionPerHour' => $level->villagerProductionPerHour,
                ],
                $definition->levels,
            ),
        );
    }

    public function toPrimitives(): array
    {
        return [
            'type' => $this->type,
            'producesFood' => $this->producesFood,
            'levels' => $this->levels,
        ];
    }
}
