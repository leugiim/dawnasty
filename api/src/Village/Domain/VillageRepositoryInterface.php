<?php

declare(strict_types=1);

namespace App\Village\Domain;

interface VillageRepositoryInterface
{
    public function save(Village $village): void;

    public function byId(VillageId $id): ?Village;
}
