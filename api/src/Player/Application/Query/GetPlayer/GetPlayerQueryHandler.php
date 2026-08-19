<?php

declare(strict_types=1);

namespace App\Player\Application\Query\GetPlayer;

use App\Player\Domain\Exception\PlayerNotFoundException;
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

    public function __invoke(GetPlayerQuery $query): PlayerView
    {
        $id = PlayerId::fromString($query->id);

        $player = $this->players->byId($id);

        if ($player === null) {
            throw PlayerNotFoundException::withId($id);
        }

        return PlayerView::fromPlayer($player);
    }
}
