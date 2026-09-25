<?php

declare(strict_types=1);

namespace App\Building\Domain\Catalog;

use App\Building\Domain\BuildingDefinition;
use App\Building\Domain\BuildingDefinitionFactory;
use App\Building\Domain\BuildingLevel;
use App\Building\Domain\BuildingType;
use App\Village\Domain\ResourceType;

/**
 * Productor de comida (game-design.md, 4.2): exime del multiplicador de
 * escasez de comida, a diferencia del resto de edificios.
 */
final class Farm implements BuildingDefinitionFactory
{
    public function define(): BuildingDefinition
    {
        return new BuildingDefinition(
            type: BuildingType::Farm,
            producesFood: true,
            levels: [
                new BuildingLevel(
                    level: 1,
                    cost: [ResourceType::Wood->value => 15],
                    producedResource: ResourceType::Food,
                    producedResourcePerHour: 10,
                ),
                new BuildingLevel(
                    level: 2,
                    cost: [ResourceType::Wood->value => 30, ResourceType::Stone->value => 5],
                    producedResource: ResourceType::Food,
                    producedResourcePerHour: 20,
                ),
            ],
        );
    }
}
