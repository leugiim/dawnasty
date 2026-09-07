<?php

declare(strict_types=1);

namespace App\Building\Domain;

/**
 * Tipos de edificio que existen en el juego (game-design.md, sección 4.3).
 *
 * Vive en el módulo Building (el catálogo), no en Village, aunque sea
 * Village quien guarda qué edificios tiene construidos y a qué nivel — es
 * el mismo tipo de dependencia de vocabulario compartido que ResourceType
 * en sentido contrario (docs/architecture.md, sección 1.1: un módulo puede
 * depender del Domain/ de otro).
 *
 * Placeholder inicial (docs/tasks.md, módulo Building): solo estos 3, los
 * mínimos para tener el loop completo funcionando. El resto de edificios
 * propuestos en el diseño (Aserradero, Cantera, Mina, Herrería...) se
 * añaden como casos nuevos de este enum, con su entrada correspondiente en
 * BuildingCatalog, cuando toque ampliarlo.
 */
enum BuildingType: string
{
    case MainBuilding = 'main_building';
    case House = 'house';
    case Farm = 'farm';
}
