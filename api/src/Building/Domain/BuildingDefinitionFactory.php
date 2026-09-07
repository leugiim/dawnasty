<?php

declare(strict_types=1);

namespace App\Building\Domain;

/**
 * Factory Method: una implementación por tipo de edificio (ver
 * Domain/Catalog/), cada una responsable solo de su propia tabla de
 * niveles. BuildingCatalog es el cliente que las usa sin saber de qué
 * tipo concreto son.
 */
interface BuildingDefinitionFactory
{
    public function define(): BuildingDefinition;
}
