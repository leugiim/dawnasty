<?php

declare(strict_types=1);

namespace App\Domain\Player;

interface PlayerRepositoryInterface
{
    public function save(Player $player): void;

    public function byId(PlayerId $id): ?Player;
}
