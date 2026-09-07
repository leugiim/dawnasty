<?php

declare(strict_types=1);

namespace App\Village\Application\Command\FoundVillage;

final readonly class FoundVillageCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
