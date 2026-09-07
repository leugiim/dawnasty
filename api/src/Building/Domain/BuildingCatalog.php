<?php

declare(strict_types=1);

namespace App\Building\Domain;

use App\Village\Domain\ResourceType;

/**
 * Catálogo de edificios: qué tipos existen y su tabla de niveles completa.
 *
 * Placeholder inicial (docs/tasks.md, módulo Building): solo los 3 tipos de
 * BuildingType, con solo 2 niveles cada uno y números de coste/efecto
 * inventados — "no bloquea la implementación, solo el pulido final"
 * (docs/tasks.md, cabecera). Sin persistencia: es contenido fijo del
 * propio código, no datos de usuario, así que no hace falta Doctrine ni
 * Infrastructure/ en este módulo todavía.
 */
final class BuildingCatalog
{
    /** @var array<string, BuildingDefinition> */
    private readonly array $definitions;

    public function __construct()
    {
        $this->definitions = [
            BuildingType::MainBuilding->value => new BuildingDefinition(
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
            ),
            BuildingType::House->value => new BuildingDefinition(
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
            ),
            BuildingType::Farm->value => new BuildingDefinition(
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
            ),
        ];

        // Guarda contra olvidarse de dar de alta un BuildingType nuevo aquí.
        foreach (BuildingType::cases() as $type) {
            if (!isset($this->definitions[$type->value])) {
                throw new \LogicException(sprintf('Missing catalog definition for building type "%s".', $type->value));
            }
        }
    }

    /**
     * @return list<BuildingDefinition>
     */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    public function definitionFor(BuildingType $type): BuildingDefinition
    {
        return $this->definitions[$type->value];
    }
}
