<?php

declare(strict_types=1);

namespace App\Application\Query\GetPlayer;

final readonly class GetPlayerQuery
{
    public function __construct(
        public string $id,
    ) {
    }
}
