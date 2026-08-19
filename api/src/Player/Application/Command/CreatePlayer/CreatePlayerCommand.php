<?php

declare(strict_types=1);

namespace App\Application\Command\CreatePlayer;

final readonly class CreatePlayerCommand
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
