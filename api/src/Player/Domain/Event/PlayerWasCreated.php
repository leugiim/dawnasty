<?php

declare(strict_types=1);

namespace App\Domain\Player\Event;

use App\Domain\Player\PlayerId;
use App\Domain\Shared\Event\DomainEvent;

final readonly class PlayerWasCreated implements DomainEvent
{
    public function __construct(
        public PlayerId $playerId,
    ) {
    }
}
