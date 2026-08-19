<?php

declare(strict_types=1);

namespace App\Player\Application\EventHandler\PlayerWasCreated;

use App\Player\Domain\Event\PlayerWasCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler de ejemplo: demuestra que el flujo de eventos de dominio funciona
 * end-to-end (Command -> Player::create() -> event.bus -> este handler).
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class LogPlayerWasCreatedHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PlayerWasCreated $event): void
    {
        $this->logger->info('Player was created.', [
            'playerId' => (string) $event->playerId,
        ]);
    }
}
