<?php

declare(strict_types=1);

namespace App\Village\Application\Query\GetVillage;

final readonly class GetVillageQuery
{
    public function __construct(
        public string $id,
    ) {
    }
}
