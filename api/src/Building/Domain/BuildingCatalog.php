<?php

declare(strict_types=1);

namespace App\Building\Domain;

use App\Building\Domain\Catalog\Farm;
use App\Building\Domain\Catalog\House;
use App\Building\Domain\Catalog\MainBuilding;

/**
 * Catálogo de edificios: qué tipos existen y su tabla de niveles completa.
 * Cliente del Factory Method BuildingDefinitionFactory — cada tipo de
 * edificio vive en su propio archivo bajo Domain/Catalog/, responsable
 * solo de sus propios números, en vez de tenerlos todos mezclados aquí.
 *
 * Placeholder inicial (docs/tasks.md, módulo Building): solo los 3 tipos
 * de BuildingType, con solo 2 niveles cada uno y números de coste/efecto
 * inventados — "no bloquea la implementación, solo el pulido final"
 * (docs/tasks.md, cabecera). Sin persistencia: es contenido fijo del
 * propio código, no datos de usuario, así que no hace falta Doctrine ni
 * Infrastructure/ en este módulo todavía.
 */
final class BuildingCatalog
{
    /** @var array<string, BuildingDefinition> */
    private readonly array $definitions;

    /**
     * @param list<BuildingDefinitionFactory> $factories una por tipo de
     *        edificio (Domain/Catalog/); se añade un nuevo BuildingType
     *        registrando aquí su factory, sin tocar el resto de esta clase.
     */
    public function __construct(array $factories = [
        new MainBuilding(),
        new House(),
        new Farm(),
    ]) {
        $definitions = [];
        foreach ($factories as $factory) {
            $definition = $factory->define();
            $definitions[$definition->type->value] = $definition;
        }
        $this->definitions = $definitions;

        // Guarda contra olvidarse de registrar la factory de un BuildingType nuevo.
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
