<?php

declare(strict_types=1);

namespace App\Player\Domain;

interface PlayerRepositoryInterface
{
    public function save(Player $player): void;

    public function byId(PlayerId $id): ?Player;
}
