<?php

declare(strict_types=1);

namespace App\Village\Domain\Event;

use App\Village\Domain\VillageId;
use App\_Shared\Domain\Event\DomainEvent;

final readonly class VillageFounded implements DomainEvent
{
    public function __construct(
        public VillageId $villageId,
    ) {
    }
}
