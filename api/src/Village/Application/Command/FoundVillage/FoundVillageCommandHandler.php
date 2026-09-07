<?php

declare(strict_types=1);

namespace App\Village\Application\Command\FoundVillage;

use App\Village\Domain\Village;
use App\Village\Domain\VillageId;
use App\Village\Domain\VillageRepositoryInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class FoundVillageCommandHandler
{
    public function __construct(
        private VillageRepositoryInterface $villages,
        private MessageBusInterface $eventBus, // named: 'event.bus'
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(FoundVillageCommand $command): void
    {
        $village = Village::found(
            VillageId::fromString($command->id), // id viene del cliente, nunca se genera aquí
            $this->clock->now(),
        );

        $this->villages->save($village);

        foreach ($village->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
