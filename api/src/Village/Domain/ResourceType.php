<?php

declare(strict_types=1);

namespace App\Village\Domain;

/**
 * Recursos crudos que puede acumular una Village (game-design.md, sección
 * 4.2). Lista "propuesta, sin cerrar" en el diseño: se amplía (y
 * probablemente se separen crudos/procesados) cuando se cierre esa
 * sección, sin tocar el resto del modelo de Village.
 */
enum ResourceType: string
{
    case Wood = 'wood';
    case Stone = 'stone';
    case Food = 'food';
    case Ore = 'ore';
}
