<?php

declare(strict_types=1);

namespace App\Building\Domain\Catalog;

use App\Building\Domain\BuildingDefinition;
use App\Building\Domain\BuildingDefinitionFactory;
use App\Building\Domain\BuildingLevel;
use App\Building\Domain\BuildingType;
use App\Village\Domain\ResourceType;

/**
 * Único edificio presente desde la fundación de una Village (game-design.md,
 * 4.3). Su efecto es la producción pasiva de aldeanos (game-design.md, 4.1)
 * — todavía sin usar en ningún sitio, a falta de la lógica de llegada de
 * aldeanos (docs/tasks.md, "Orden sugerido" paso 4).
 */
final class MainBuilding implements BuildingDefinitionFactory
{
    public function define(): BuildingDefinition
    {
        return new BuildingDefinition(
            type: BuildingType::MainBuilding,
            producesFood: false,
            levels: [
                new BuildingLevel(level: 1, cost: [], villagerProductionPerHour: 1),
                new BuildingLevel(
                    level: 2,
                    cost: [ResourceType::Wood->value => 50, ResourceType::Stone->value => 20],
                    villagerProductionPerHour: 2,
                ),
            ],
        );
    }
}
