<?php

declare(strict_types=1);

namespace App\Player\Domain\Event;

use App\Player\Domain\PlayerId;
use App\_Shared\Domain\Event\DomainEvent;

final readonly class PlayerWasCreated implements DomainEvent
{
    public function __construct(
        public PlayerId $playerId,
    ) {
    }
}
