<?php

declare(strict_types=1);

namespace App\Building\Domain\Catalog;

use App\Building\Domain\BuildingDefinition;
use App\Building\Domain\BuildingDefinitionFactory;
use App\Building\Domain\BuildingLevel;
use App\Building\Domain\BuildingType;
use App\Village\Domain\ResourceType;

/**
 * Da capacidad de vivienda adicional (game-design.md, 4.1) — encima de la
 * capacidad base placeholder que ya trae toda Village sin ninguna Casa
 * construida (Village::BASE_HOUSING_CAPACITY).
 */
final class House implements BuildingDefinitionFactory
{
    public function define(): BuildingDefinition
    {
        return new BuildingDefinition(
            type: BuildingType::House,
            producesFood: false,
            levels: [
                new BuildingLevel(level: 1, cost: [ResourceType::Wood->value => 20], housingCapacity: 5),
                new BuildingLevel(
                    level: 2,
                    cost: [ResourceType::Wood->value => 40, ResourceType::Stone->value => 10],
                    housingCapacity: 10,
                ),
            ],
        );
    }
}
