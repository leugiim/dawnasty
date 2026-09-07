<?php

declare(strict_types=1);

namespace App\Village\Domain;

/**
 * Etapa de una Village (game-design.md, sección 4.4). Es literalmente un
 * rango de nivel del Edificio Principal, no un estado independiente que se
 * trackee aparte.
 *
 * Los cortes de nivel son números de balance pendientes (game-design.md,
 * sección 6, "Preguntas abiertas"): se usan aquí los mismos que el propio
 * documento de diseño usa como ejemplo ilustrativo en esa sección
 * (1-9/10-24/25-49/50+), a falta de una pasada de balance real. El número
 * final de etapas tampoco está cerrado — estas 4 son la lista de trabajo
 * provisional del diseño.
 */
enum Stage: string
{
    case Aldea = 'aldea';
    case Villa = 'villa';
    case Ciudad = 'ciudad';
    case Reino = 'reino';

    public static function fromMainBuildingLevel(int $level): self
    {
        return match (true) {
            $level >= 50 => self::Reino,
            $level >= 25 => self::Ciudad,
            $level >= 10 => self::Villa,
            default => self::Aldea,
        };
    }

    /**
     * Nombre que se le muestra al jugador para el Edificio Principal en
     * esta etapa (game-design.md, sección 4.3 — cerrado). Mismo edificio,
     * mismo modelo de datos; solo cambia el texto de UI según la etapa.
     */
    public function mainBuildingName(): string
    {
        return match ($this) {
            self::Aldea => 'Choza del Fundador',
            self::Villa => 'Salón del Clan',
            self::Ciudad => 'Casa Solariega',
            self::Reino => 'Trono de la Dinastía',
        };
    }
}
