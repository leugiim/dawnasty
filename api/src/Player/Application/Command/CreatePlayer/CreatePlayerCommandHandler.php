<?php

declare(strict_types=1);

namespace App\Player\Application\Command\CreatePlayer;

use App\Player\Domain\Player;
use App\Player\Domain\PlayerId;
use App\Player\Domain\PlayerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreatePlayerCommandHandler
{
    public function __construct(
        private PlayerRepositoryInterface $players,
        private MessageBusInterface $eventBus, // named: 'event.bus'
    ) {
    }

    public function __invoke(CreatePlayerCommand $command): void
    {
        $player = Player::create(
            PlayerId::fromString($command->id), // id viene del cliente, nunca se genera aquí
            $command->name,
        );

        $this->players->save($player);

        foreach ($player->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
