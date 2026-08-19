<?php

declare(strict_types=1);

namespace App\Player\Application\Query\GetPlayer;

use App\Player\Domain\PlayerId;
use App\Player\Domain\PlayerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetPlayerQueryHandler
{
    public function __construct(
        private PlayerRepositoryInterface $players,
    ) {
    }

    public function __invoke(GetPlayerQuery $query): ?PlayerView
    {
        $player = $this->players->byId(PlayerId::fromString($query->id));

        if ($player === null) {
            return null;
        }

        return PlayerView::fromPlayer($player);
    }
}
