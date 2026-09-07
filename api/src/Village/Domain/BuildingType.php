<?php

declare(strict_types=1);

namespace App\Village\Domain;

/**
 * Tipos de edificio que una Village puede tener construidos.
 *
 * Placeholder inicial (docs/tasks.md, módulo Village / "Orden sugerido"
 * paso 1): de momento solo existe MainBuilding, el único edificio presente
 * desde la fundación (game-design.md, sección 4.3). El resto de edificios
 * propuestos en el diseño (Casa, Granja, Aserradero...) se añaden como
 * casos nuevos de este enum cuando llegue el módulo Building / el comando
 * UpgradeBuilding (docs/tasks.md, "Orden sugerido" paso 5) — no antes,
 * para no modelar tipos que todavía no se pueden construir ni consultar.
 */
enum BuildingType: string
{
    case MainBuilding = 'main_building';
}
