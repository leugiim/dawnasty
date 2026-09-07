<?php

declare(strict_types=1);

namespace App\Building\Domain;

/**
 * Entrada del catálogo para un tipo de edificio: si cuenta como productor
 * de comida (game-design.md, sección 4.2 — exime del multiplicador de
 * escasez) y su tabla de niveles completa.
 */
final readonly class BuildingDefinition
{
    /**
     * @param list<BuildingLevel> $levels ordenados por nivel ascendente
     */
    public function __construct(
        public BuildingType $type,
        public bool $producesFood,
        public array $levels,
    ) {
    }

    public function level(int $level): ?BuildingLevel
    {
        foreach ($this->levels as $definedLevel) {
            if ($definedLevel->level === $level) {
                return $definedLevel;
            }
        }

        return null;
    }
}
