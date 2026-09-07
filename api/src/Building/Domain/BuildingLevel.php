<?php

declare(strict_types=1);

namespace App\Building\Domain;

use App\Village\Domain\ResourceType;

/**
 * Un nivel de la tabla de niveles de un edificio: coste para alcanzarlo y
 * su efecto a ese nivel (game-design.md, sección 4.3: "todo edificio... se
 * define por una tabla de niveles: para cada nivel, el coste en recursos
 * para alcanzarlo y el efecto que tiene a ese nivel").
 *
 * El "efecto" es una bolsa genérica de campos en vez de un tipo por
 * edificio: cada tipo de edificio solo rellena los que le aplican
 * (Casa -> $housingCapacity, Granja -> $producedResource/
 * $producedResourcePerHour, Edificio Principal ->
 * $villagerProductionPerHour). Placeholder mientras el catálogo es
 * pequeño (3 tipos); si el modelo de efectos crece mucho, candidato a
 * partirse en una clase de efecto por tipo.
 */
final readonly class BuildingLevel
{
    /**
     * @param array<string, int> $cost tipo de recurso (ResourceType::value) => cantidad
     */
    public function __construct(
        public int $level,
        public array $cost,
        public int $housingCapacity = 0,
        public ?ResourceType $producedResource = null,
        public int $producedResourcePerHour = 0,
        public int $villagerProductionPerHour = 0,
    ) {
    }
}
