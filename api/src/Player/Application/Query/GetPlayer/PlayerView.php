<?php

declare(strict_types=1);

namespace App\Player\Application\Query\GetPlayer;

use App\Player\Domain\Player;

/**
 * Read model de salida para Player. Nunca se expone la entidad de dominio
 * fuera de Application/.
 */
final readonly class PlayerView
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }

    public static function fromPlayer(Player $player): self
    {
        return new self(
            id: (string) $player->id(),
            name: $player->name(),
        );
    }
}
